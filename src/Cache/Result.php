<?php

namespace duncan3dc\Sql\Cache;

use Psr\Log\LoggerInterface;

/**
 * Cache results on disk to make future queries faster.
 */
class Result extends AbstractResult
{
    /**
     * @var Sql $sql A reference to an sql class instance to execute the query over
     */
    private $sql;

    /**
     * @var string $query The query to be executed
     */
    private $query;

    /**
     * @var array $params The parameters to be used in the query
     */
    private $params;

    /**
     * @var LoggerInterface $logger The logging instance.
     */
    private $logger;

    /**
     * @var string $hash The hash key of the query
     */
    private $hash;

    /**
     * @var Filesystem $fs The cache storage filesystem.
     */
    private $fs;

    /**
     * @var int $totalRows The total number of rows
     */
    private $totalRows;

    /**
     * @var int $columnCount The number of columns in each row
     */
    private $columnCount;

    /**
     * @var int $rowLimit The maximum number of rows that we permit to cache
     */
    private $rowLimit;

    /**
     * @var int $timeout How long the data should be cached for
     */
    private $timeout;

    /**
     * @var array $indexMap Map the row index to it's position in the sorted array
     */
    private $indexMap;


    public function __construct(SqlInterface $sql, string $query, array $params, OptionsInterface $options)
    {
        $this->sql = $sql;

        $this->logger = $options->getLogger();

        # Store the query for other methods to use
        $this->query = $query;
        $this->params = $params;

        # Create the hash of the query to use as an identifier
        $this->hash = sha1($this->query . print_r($this->params, true));

        /**
         * Create the path to the cache directory
         * Adding the number of directories specified in the options
         * This is because most filesystems place a limit on how many links you can have within a directory,
         * so this reduces that problem by spliting the cache directories into subdirectories
         */
        $path = $options->getDirectory() . "/";
        $directories = [];
        $max = $options->getDirectories();
        for ($i = 0; $i < $max; $i++) {
            if (!$dir = substr($this->hash, $i, 1)) {
                break;
            }
            $path .= $dir . "/";
            $directories[] = $dir;
        }
        $path .= $this->hash;
        $directories[] = $this->hash;

        $this->fs = new Filesystem($path);

        $this->timeout = $options->getTime();
        $this->rowLimit = $options->getLimit();

        # If cache doesn't exist for this query then create it now
        if (!$this->isCached()) {
            $this->createCache();
        }

        $data = $this->fs->get(".data");

        $this->totalRows = $data["totalRows"];
        $this->columnCount = $data["columnCount"];
        $this->position = 0;

        $this->indexMap = false;
    }


    private function isCached()
    {
        $this->logger->debug("checking the cache: " . $this->fs->getPath());

        # If no status file exists for this cache then presume there isn't any valid data
        if (!$this->fs->has(".data")) {
            $this->logger->debug("no status file found");
            return false;
        }

        # If the status file is older than the specified timeout then force a refresh
        $time = $this->fs->date(".data");
        $date = date("Y-m-d H:i:s", $time);
        $this->logger->debug("cache found ({$date})");

        if ($time < (time() - $this->timeout)) {
            $this->logger->debug("cache is too old to use");
            return false;
        }

        return true;
    }


    private function createCache()
    {
        $this->result = $this->sql->query($this->query, $this->params);

        $umask = umask(0);

        $this->fs->put(".data", []);
        $this->fs->put(".sorted", []);

        $rowNum = 0;
        $columnCount = 0;
        $this->result->fetchStyle(Sql::FETCH_RAW);
        foreach ($this->result as $row) {
            if (!$rowNum) {
                $columnCount = count($row);
            }

            $this->fs->put("{$rowNum}.row", $row);

            $rowNum++;

            if ($this->rowLimit && $rowNum > $this->rowLimit) {
                break;
            }
        }

        umask($umask);

        $this->fs->put(".data", [
            "totalRows"     =>  $rowNum,
            "columnCount"   =>  $columnCount,
        ]);
    }


    /**
     * Internal method to fetch the next row from the result set
     *
     * @return array|null
     */
    private function getNextRow()
    {
        if ($this->position >= $this->totalRows) {
            return;
        }

        if ($this->indexMap) {
            $rowIndex = $this->indexMap[$this->position];
        } else {
            $rowIndex = $this->position;
        }

        $data = $this->fs->get("{$rowIndex}.row");

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
        $sorted = $this->fs->get(".sorted");
        $this->indexMap = $sorted->{$col};

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
            $this->fs->put(".sorted", $sorted);
        }

        if ($desc) {
            $this->indexMap = array_reverse($this->indexMap);
        }

        return true;
    }
}
