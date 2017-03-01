<?php

namespace duncan3dc\Sql;

use duncan3dc\Helpers\Helper;
use duncan3dc\Serial\Json;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Cache results on disk to make future queries faster.
 */
class CachedResult extends AbstractResult implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    const MINUTE = 60;
    const HOUR = 3600;
    const DAY = 86400;

    /**
     * @var Sql $sql A reference to an sql class instance to execute the query over
     */
    public $sql;

    /**
     * @var string $query The query to be executed
     */
    public $query;

    /**
     * @var array $params The parameters to be used in the query
     */
    public $params;

    /**
     * @var string $hash The hash key of the query
     */
    public $hash;

    /**
     * @var string $dir The location of the cache storage
     */
    public $dir;

    /**
     * @var int $totalRows The total number of rows
     */
    protected $totalRows;

    /**
     * @var int $columnCount The number of columns in each row
     */
    protected $columnCount;

    /**
     * @var int $rowLimit The maximum number of rows that we permit to cache
     */
    protected $rowLimit;

    /**
     * @var int $timeout How long the data should be cached for
     */
    public $timeout;

    /**
     * @var int $cacheTime The time that the data was cached at
     */
    public $cacheTime;

    /**
     * @var array $indexMap Map the row index to it's position in the sorted array
     */
    protected $indexMap;


    public function __construct(array $options, LoggerInterface $logger = null)
    {
        $options = Helper::getOptions($options, [
            "dir"           =>  "/tmp/query_cache",
            "sql"           =>  null,
            "query"         =>  null,
            "params"        =>  null,
            "timeout"       =>  self::DAY,
            "limit"         =>  10000,
            "directories"   =>  3,
            "permissions"   =>  0777,
        ]);

        $this->sql = $options["sql"];

        $this->logger = $logger ?: new NullLogger;

        # Store the query for other methods to use
        $this->query = $options["query"];
        $this->params = $options["params"];

        # Create the hash of the query to use as an identifier
        $this->hash = sha1($this->query . print_r($this->params, true));

        /**
         * Create the path to the cache directory
         * Adding the number of directories specified in the options
         * This is because most filesystems place a limit on how many links you can have within a directory,
         * so this reduces that problem by spliting the cache directories into subdirectories
         */
        $this->dir = $options["dir"] . "/";
        $directories = [];
        for ($i = 0; $i < $options["directories"]; $i++) {
            if (!$dir = substr($this->hash, $i, 1)) {
                break;
            }
            $this->dir .= $dir . "/";
            $directories[] = $dir;
        }
        $this->dir .= $this->hash;
        $directories[] = $this->hash;

        $this->timeout = $options["timeout"];
        $this->rowLimit = round($options["limit"]);

        # Ensure a cache directory exists for this query
        if (!is_dir($this->dir)) {

            # Ensure the base directory exists
            if (!is_dir($options["dir"])) {
                mkdir($options["dir"], 0777, true);
                chmod($options["dir"], $options["permissions"]);
            }

            /**
             * We can't use the recursive feature of mkdir(), as it's permissions handling is affected by umask().
             * So we create each directory individually and set the permissions using chmod().
             */
            $path = $options["dir"];
            foreach ($directories as $dir) {
                $path .= "/" . $dir;
                if (!is_dir($path)) {
                    mkdir($path);
                    chmod($path, $options["permissions"]);
                }
            }
        }

        # If cache doesn't exist for this query then create it now
        if (!$this->isCached()) {
            $this->createCache();
        }

        $data = Json::decodeFromFile($this->dir . "/.data");
        $this->totalRows = $data["totalRows"];
        $this->columnCount = $data["columnCount"];
        $this->position = 0;

        $this->indexMap = false;
    }


    protected function isCached()
    {
        $this->logger->debug("checking the cache ({$this->dir})");

        # If no status file exists for this cache then presume there isn't any valid data
        if (!file_exists($this->dir . "/.data")) {
            $this->logger->debug("no status file found");
            return false;
        }

        # If the status file is older than the specified timeout then force a refresh
        $this->cacheTime = filectime($this->dir . "/.data");
        $date = date("Y-m-d H:i:s", $this->cacheTime);
        $this->logger->debug("cache found ({$date})");

        if ($this->cacheTime < (time() - $this->timeout)) {
            $this->logger->debug("cache is too old to use");
            return false;
        }

        return true;
    }


    protected function createCache()
    {
        $this->result = $this->sql->query($this->query, $this->params);

        $this->cacheTime = time();

        $umask = umask(0);

        Json::encodeToFile($this->dir . "/.data", []);
        Json::encodeToFile($this->dir . "/.sorted", []);

        $rowNum = 0;
        $columnCount = 0;
        $this->result->fetchStyle(Sql::FETCH_RAW);
        foreach ($this->result as $row) {
            if (!$rowNum) {
                $columnCount = count($row);
            }

            Json::encodeToFile($this->dir . "/" . $rowNum . ".row", $row);

            $rowNum++;

            if ($this->rowLimit && $rowNum > $this->rowLimit) {
                break;
            }
        }

        umask($umask);

        Json::encodeToFile($this->dir . "/.data", [
            "totalRows"     =>  $rowNum,
            "columnCount"   =>  $columnCount,
        ]);
    }


    /**
     * Internal method to fetch the next row from the result set
     *
     * @return array|null
     */
    protected function getNextRow()
    {
        if ($this->position >= $this->totalRows) {
            return;
        }

        if ($this->indexMap) {
            $rowIndex = $this->indexMap[$this->position];
        } else {
            $rowIndex = $this->position;
        }

        $data = Json::decodeFromFile($this->dir . "/" . $rowIndex . ".row");

        $this->position++;

        return $data;
    }


    /**
     * Fetch an individual value from the result set
     *
     * @param int $row The index of the row to fetch (zero-based)
     * @param int $col The index of the column to fetch (zero-based)
     *
     * @return string
     */
    public function result($row, $col)
    {
        $this->seek($row);

        $row = $this->fetch(Sql::FETCH_ROW);

        $val = $row[$col];

        return $val;
    }


    /**
     * Seek to a specific record of the result set
     *
     * @param int $row The index of the row to position to (zero-based)
     *
     * @return void
     */
    public function seek($row)
    {
        $this->position = $row;
    }


    /**
     * Get the number of rows in the result set
     *
     * @return int
     */
    public function count()
    {
        return $this->totalRows;
    }


    /**
     * Get the number of columns in the result set
     *
     * @return int
     */
    public function columnCount()
    {
        return $this->columnCount;
    }


    public function orderBy($col, $desc = null)
    {
        # Check if the data has already been sorted by this column
        $sorted = Json::decodeFromFile($this->dir . "/.sorted");
        $this->indexMap = $sorted[$col];

        # If the data hasn't already been sorted then create an index map for it now
        if (!is_array($this->indexMap)) {

            $sort = [];
            $pos = 0;
            $this->seek(0);
            while ($row = $this->fetch()) {
                $sort[$pos] = $row[$col];
                $pos++;
            }
            $this->seek(0);

            asort($sort);
            $this->indexMap = array_keys($sort);

            $sorted[$col] = $this->indexMap;
            Json::encodeToFile($this->dir . "/.sorted", $sorted);
        }

        if ($desc) {
            $this->indexMap = array_reverse($this->indexMap);
        }

        return true;
    }
}