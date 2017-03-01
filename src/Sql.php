<?php

namespace duncan3dc\Sql;

use duncan3dc\Log\LoggerAwareTrait;
use duncan3dc\Sql\Driver\ResultInterface as DriverResultInterface;
use duncan3dc\Sql\Driver\ServerInterface;
use duncan3dc\Sql\Exceptions\ConnectionException;
use duncan3dc\Sql\Exceptions\QueryException;
use Psr\Log\LoggerAwareInterface;

/**
 * Main class that allows interaction with databases.
 */
class Sql implements SqlInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var ServerInterface $driver The instance of the driver class handling the abstraction.
     */
    private $driver;

    /**
     * @var string $name The name assigned to this server.
     */
    private $name;

    /**
     * @var bool $connected Flag to indicate whether we are connected to the server yet.
     */
    private $connected = false;

    /**
     * @var array $tables The tables that have been defined and which database they are in
     */
    public $tables = [];

    /**
     * @var boolean $allowNulls A flag to indicate whether nulls should be used or not
     */
    public $allowNulls = false;

    /**
     * @var boolean $transaction A flag to indicate whether we are currently in transaction mode or not
     */
    protected $transaction;

    /**
     * @var string $query The query we are currently attempting to run
     */
    protected $query;

    /**
     * @var array $params The params for the query we are currently attempting to run
     */
    protected $params;

    /**
     * @var string $preparedQuery The emulated prepared query we are currently attempting to run
     */
    protected $preparedQuery;


    /**
     * Create a new instance.
     *
     * @param ServerInterface $driver The instance of the driver class handling the abstraction
     */
    public function __construct(ServerInterface $driver, string $name = "")
    {
        $this->driver = $driver;
        $this->driver->setSql($this);

        $this->name = $name;
    }


    /**
     * Get the name assigned to this server.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }


    /**
     * Get the server instance of the driver in use.
     *
     * @return ServerInterface
     */
    public function getServer(): ServerInterface
    {
        return $this->driver;
    }


    /**
     * If we have not already connected then connect to the server now.
     *
     * @return $this
     */
    public function connect(): SqlInterface
    {
        if ($this->connected) {
            return $this;
        }

        # Set that we are connected here, because queries can be run as part of the below code, which would cause an infinite loop
        $this->connected = true;

        if (!$this->driver->connect()) {
            $this->connected = false;
            throw ConnectionException::fromSql($this);
        }

        return $this;
    }


    /**
     * Execute an sql query.
     *
     * @param string $query The query string to run
     * @param array $params The parameters to use in the query
     *
     * @return ResultInterface
     */
    public function query(string $query, array $params = []): ResultInterface
    {
        $this->connect();

        $this->query = $query;
        $this->params = null;
        $this->preparedQuery = false;

        if (is_array($params)) {
            $this->params = $params;
        }

        $this->quoteChars($query);
        $this->changeQuerySyntax($query);
        $this->tableNames($query);
        $this->namedParams($query, $params);
        $this->paramArrays($query, $params);
        $this->convertNulls($params);

        $preparedQuery = $this->prepareQuery($query, $params);
        $this->preparedQuery = $preparedQuery;

        $this->logger->debug($preparedQuery);

        $result = $this->driver->query($query, $params, $preparedQuery);

        if (!$result instanceof DriverResultInterface) {
            throw QueryException::fromSql($this);
        }

        return new Result($result);
    }


    /**
     * Define which database each table is located in
     */
    public function definitions($data)
    {
        # Either specified as an array of tables
        if (is_array($data)) {
            $tables = $data;

        # Or as an includable script with a $tables array defined in it
        } else {
            require $data;
        }

        $this->tables = array_merge($this->tables, $tables);
    }


    /**
     * Get the database that should be used for this table
     */
    protected function getTableDatabase($table)
    {
        if (!array_key_exists($table, $this->tables)) {
            return false;
        }

        $database = $this->tables[$table];

        return $database;
    }


    /**
     * Get the full table name, including the database.
     *
     * @param string $table The table name
     */
    protected function getTableName($table)
    {
        $database = $this->getTableDatabase($table);

        # If we found a database for this table then include it in the return value
        if ($database) {
            $database = $this->quoteField($database);

            if ($this->driver instanceof Driver\Mssql\Server) {
                $database .= ".dbo";
            }

            return $database . "." . $this->quoteField($table);
        }

        # If we didn't find a database, and this table already looks like it includes
        if (strpos($table, ".") !== false) {
            return $table;
        }


        return $this->quoteField($table);
    }


    /**
     * Allow a query to be modified without affecting quoted strings within it
     *
     * @param string $query The query to modify.
     * @param callable $callback The callback to use to modify each section of the query
     *
     * @return string
     */
    private function modifyQuery(string $query, callable $callback)
    {
        $regex = "/('[^']*')/";
        if (!preg_match($regex, $query)) {
            return $callback($query);
        }

        $parts = preg_split($regex, $query, null, PREG_SPLIT_DELIM_CAPTURE);

        $query = "";

        foreach ($parts as $part) {

            # If this part of the query isn't a string, then perform the replace on it
            if (substr($part, 0, 1) != "'") {
                $part = $callback($part);
            }

            # Append this part of the query onto the new query we are constructing
            $query .= $part;
        }

        return $query;
    }


    /**
     * Replace any quote characters used to the appropriate type for the current mode
     * This function attempts to ignore any instances that are surrounded by single quotes, as these should not be converted
     */
    protected function quoteChars(&$query)
    {
        $quoteChar = "`";

        $chars = $this->driver->getQuoteChars();
        if (is_array($chars)) {
            $start = $chars[0];
            $end = $chars[1];
        } else {
            $start = $chars;
            $end = $chars;
        }

        if ($start === $quoteChar && $end === $quoteChar) {
            return;
        }

        # Create part of the regex that will represent the quoted field we are trying to find
        $match = $quoteChar . "([^" . $quoteChar . "]*)" . $quoteChar;

        $this->modifyQuery($query, function($part) use($match, $start, $end) {
            return preg_replace("/" . $match . "/", $start . "$1" . $end, $part);
        });
    }


    /**
     * Replace any non-standard functions with the appropriate function for the current mode.
     */
    protected function changeQuerySyntax(&$query)
    {
        $query = $this->driver->changeQuerySyntax($query);
    }


    /**
     * Convert table references to full database/table names
     * This allows tables to be surrounded in braces, without specifying the database
     */
    protected function tableNames(&$query)
    {
        $this->modifyQuery($query, function($part) {
            return preg_replace_callback("/{([^}]+)}/", function($match) {
                return $this->getTableName($match[1]);
            }, $part);
        });
    }


    /**
     * If any of the parameters are arrays, then convert the single marker from the query to handle them
     */
    protected function paramArrays(&$query, &$params)
    {
        if (!is_array($params)) {
            return;
        }

        $newParams = [];

        $this->modifyQuery($query, function ($part) use (&$params, &$newParams) {
            $newPart = "";
            $tmpPart = $part;

            while (count($params) > 0) {
                $pos = strpos($tmpPart, "?");
                if ($pos === false) {
                    break;
                }

                $val = array_shift($params);

                $newPart .= substr($tmpPart, 0, $pos);
                $tmpPart = substr($tmpPart, $pos + 1);

                # If this is just a straight value then don't do anything to it
                if (!is_array($val)) {
                    $newPart .= "?";
                    $newParams[] = $val;
                    continue;
                }

                # If the array is only 1 element (or no elements) long then convert it to an = (or <> for NOT IN)
                if (count($val) < 2) {
                    $newPart = preg_replace("/\s*\bNOT\s+IN\s*$/i", "<>", $newPart);
                    $newPart = preg_replace("/\s*\bIN\s*$/i", "=", $newPart);
                    $newPart .= "?";
                    $newParams[] = reset($val);
                    continue;
                }

                # Convert each element of the array to a separate marker
                $markers = [];
                foreach ($val as $v) {
                    $markers[] = "?";
                    $newParams[] = $v;
                }
                $newPart .= "(" . implode(",", $markers) . ")";
            }

            $newPart .= $tmpPart;

            return $newPart;
        });

        $params = $newParams;
    }


    /**
     * If the params array uses named keys then convert them to the regular markers
     */
    protected function namedParams(&$query, &$params)
    {
        if (!is_array($params)) {
            return;
        }

        $pattern = "a-zA-Z0-9_";

        if (!preg_match("/\?([" . $pattern . "]+)/", $query)) {
            return;
        }

        $oldParams = $params;
        $params = [];

        reset($oldParams);
        $this->modifyQuery($query, function($part) use(&$params, &$oldParams, $pattern) {
            return preg_replace_callback("/\?([" . $pattern . "]*)([^" . $pattern . "]|$)/", function($match) use(&$params, &$oldParams) {
                if ($key = $match[1]) {
                    $params[] = $oldParams[$key];
                } else {
                    $params[] = current($oldParams);
                    next($oldParams);
                }
                return "?" . $match[2];
            }, $part);
        });
    }


    protected function convertNulls(&$params)
    {
        if ($this->allowNulls) {
            return;
        }

        if (!is_array($params)) {
            return;
        }

        foreach ($params as &$val) {
            if (gettype($val) == "NULL") {
                $val = "";
            }
        }
    }


    /**
     * Convert a parameterised query to a standard query.
     *
     * @param string $query The query with parameter markers
     * @param array $params The parameters to use for the markers
     *
     * @return string
     */
    private function prepareQuery(string $query, array $params)
    {
        reset($params);

        return $this->modifyQuery($query, function ($part) use (&$params) {
            $newPart = "";
            while ($pos = strpos($part, "?")) {
                $newPart .= substr($part, 0, $pos);
                $part = substr($part, $pos + 1);

                $value = current($params);
                next($params);

                switch (gettype($value)) {

                    case "boolean":
                        $value = (int) $value;
                        break;

                    case "integer":
                    case "double":
                        break;

                    case "NULL":
                        $value = "NULL";
                        break;

                    default:
                        $value = $this->driver->quoteValue($value);
                }

                $newPart .= $value;
            }

            return $newPart . $part;
        });

        return $query;
    }


    public function error()
    {
        $this->logger->error($this->getErrorMessage(), [
            "code"      =>  $this->getErrorCode(),
            "query"     =>  $this->query,
            "params"    =>  $this->params,
            "prepared"  =>  $this->preparedQuery,
            "backtrace" =>  debug_backtrace(),
        ]);

        throw new QueryException($this->getErrorMessage(), $this->getErrorCode());
    }


    /**
     * Get the last error code from the driver.
     *
     * @return mixed
     */
    public function getErrorCode()
    {
        return $this->driver->getErrorCode();
    }


    /**
     * Get the last error message from the driver.
     *
     * @return mixed
     */
    public function getErrorMessage()
    {
        return $this->driver->getErrorMessage();
    }


    public function table($table)
    {
        $tableName = $this->getTableName($table);
        return new Table($tableName, $this);
    }


    public function update($table, array $set, $where)
    {
        return $this->table($table)->update($set, $where);
    }


    public function insert($table, array $params, $extra = null)
    {
        return $this->table($table)->insert($params, $extra);
    }


    public function bulkInsert($table, array $params, $extra = null)
    {
        return $this->table($table)->bulkInsert($params, $extra);
    }


    public function getId(ResultInterface $result)
    {
        if (!$id = $this->driver->getId($result)) {
            throw new \Exception("Failed to retrieve the last inserted row id");
        }
        return $id;
    }


    /**
     * Convert an array of parameters into a valid where clause
     */
    public function where($where, &$params)
    {
        if (!is_array($where)) {
            throw new \Exception("Invalid where clause specified, must be an array");
        }

        if (!is_array($params)) {
            $params = [];
        }

        $query = "";

        $andFlag = false;

        foreach ($where as $field => $value) {

            # Add the and flag if this isn't the first field
            if ($andFlag) {
                $query .= "AND ";
            } else {
                $andFlag = true;
            }

            # Add the field name to the query
            $query .= $this->quoteField($field);

            # Convert arrays to use the in helper
            if (is_array($value)) {
                $value = static::in($value);
            }

            # Any parameters not using a helper should use the standard equals helper
            if (!is_object($value)) {
                $value = static::equals($value);
            }

            $query .= " " . $value->getClause() . " ";
            foreach ($value->getValues() as $val) {
                $params[] = $val;
            }
        }

        return $query;
    }


    /**
     * Convert an array/string of fields into a valid select clause
     */
    public function selectFields($fields)
    {
        # By default just select an empty string
        $select = "''";

        # If an array of fields have been passed
        if (is_array($fields)) {

            # If we have some fields, then add them to the query, ensuring they are quoted appropriately
            if (count($fields) > 0) {
                $select = "";

                foreach ($fields as $field) {
                    if ($select) {
                        $select .= ", ";
                    }
                    $select .= $this->quoteField($field);
                }
            }

        # if the fields isn't an array
        } elseif (!is_bool($fields)) {
            # Otherwise assume it is a string of fields to select and add them to the query
            if (strlen($fields) > 0) {
                $select = $fields;
            }
        }

        return $select;
    }


    public function delete($table, $where)
    {
        return $this->table($table)->delete($where);
    }

    /**
     * Grab the first row from a table using the standard select statement
     * This is a convience method for a fieldSelect() where all fields are required
     */
    public function select($table, $where, $orderBy = null)
    {
        return $this->table($table)->select($where, $orderBy);
    }


    /**
     * Grab specific fields from the first row from a table using the standard select statement
     */
    public function fieldSelect($table, $fields, $where, $orderBy = null)
    {
        return $this->table($table)->fieldSelect($fields, $where, $orderBy);
    }


    /**
     * Create a standard select statement and return the result
     * This is a convience method for a fieldSelectAll() where all fields are required
     */
    public function selectAll($table, $where, $orderBy = null)
    {
        return $this->table($table)->selectAll($where, $orderBy);
    }


    /**
     * Create a standard select statement and return the result
     */
    public function fieldSelectAll($table, $fields, $where, $orderBy = null)
    {
        return $this->table($table)->fieldSelectAll($fields, $where, $orderBy);
    }


    /**
     * Check if a record exists without fetching any data from it.
     *
     * @param string $table The table name to fetch from
     * @param array|int $where The where clause to use, or the NO_WHERE_CLAUSE constant
     *
     * @return boolean Whether a matching row exists in the table or not
     */
    public function exists($table, $where)
    {
        return $this->table($table)->exists($where);
    }


    /**
     * Insert a new record into a table, unless it already exists in which case update it
     */
    public function insertOrUpdate($table, array $set, array $where)
    {
        return $this->table($table)->insertOrUpdate($set, $where);
    }


    /**
     * Synonym for insertOrUpdate()
     */
    public function updateOrInsert($table, array $set, array $where)
    {
        return $this->table($table)->insertOrUpdate($set, $where);
    }


    /**
     * Create an order by clause from a string of fields or an array of fields
     */
    public function orderBy($fields)
    {
        if (!is_array($fields)) {
            $fields = explode(",", $fields);
        }

        $orderBy = "";

        foreach ($fields as $field) {
            if (!$field = trim($field)) {
                continue;
            }
            if (!$orderBy) {
                $orderBy = "ORDER BY ";
            } else {
                $orderBy .= ", ";
            }

            if (strpos($field, " ")) {
                $orderBy .= $field;
            } else {
                $orderBy .= $this->quoteField($field);
            }
        }

        return $orderBy;
    }


    /**
     * Quote a field with the appropriate characters for this mode
     */
    protected function quoteField($field)
    {
        $field = trim($field);

        return $this->driver->quoteField($field);
    }


    /**
     * Quote a table with the appropriate characters for this mode
     */
    protected function quoteTable($table)
    {
        $table = trim($table);

        return $this->driver->quoteTable($table);
    }


    /**
     * This method allows easy appending of search criteria to queries
     * It takes existing query/params to be edited as the first 2 parameters
     * The third parameter is the string that is being searched for
     * The fourth parameter is an array of fields that should be searched for in the sql
     */
    public function search(&$query, array &$params, $search, array $fields)
    {
        $query .= "( ";

        $search = str_replace('"', '', $search);

        $words = explode(" ", $search);

        foreach ($words as $key => $word) {

            if ($key) {
                $query .= "AND ";
            }

            $query .= "( ";
                foreach ($fields as $key => $field) {
                    if ($key) {
                        $query .= "OR ";
                    }
                    $query .= "LOWER(" . $field . ") LIKE ? ";
                    $params[] = "%" . strtolower(trim($word)) . "%";
                }
            $query .= ") ";
        }

        $query .= ") ";
    }


    /**
     * Start a transaction by turning autocommit off
     */
    public function startTransaction()
    {
        # Ensure we have a connection to start the transaction on
        $this->connect();

        if (!$result = $this->driver->startTransaction()) {
            $this->error();
        }

        $this->transaction = true;

        return true;
    }


    /**
     * Check if the object is currently in transaction mode.
     *
     * @return bool
     */
    public function isTransaction()
    {
        return $this->transaction;
    }


    /**
     * End a transaction by either committing changes made, or reverting them
     */
    public function endTransaction($commit)
    {
        if ($commit) {
            $result = $this->commit();
        } else {
            $result = $this->rollback();
        }

        if (!$result = $this->driver->endTransaction()) {
            $this->error();
        }

        $this->transaction = false;

        return true;
    }


    /**
     * Commit queries without ending the transaction
     */
    public function commit()
    {
        if (!$result = $this->driver->commit()) {
            $this->error();
        }

        return true;
    }


    /**
     * Rollback queries without ending the transaction
     */
    public function rollback()
    {
        if (!$result = $this->driver->rollback()) {
            $this->error();
        }

        return true;
    }


    /**
     * Lock some tables for exlusive write access
     * But allow read access to other processes
     */
    public function lockTables(string ...$tables)
    {
        /**
         * Unlock any previously locked tables
         * This is done to provide consistency across different modes, as mysql only allows one single lock over multiple tables
         * Also the odbc only allows all locks to be released, not individual tables. So it makes sense to force the batching of lock/unlock operations
         */
        $this->unlockTables();

        foreach ($tables as &$table) {
            $table = $this->getTableName($table);
        }
        unset($table);

        return $this->driver->lockTables($tables);
    }


    /**
     * Unlock all tables previously locked
     */
    public function unlockTables()
    {
        return $this->driver->unlockTables();
    }


    public function getDatabases()
    {
        return $this->driver->getDatabases();
    }


    public function getTables($database)
    {
        return $this->driver->getTables();
    }


    public function getViews($database)
    {
        return $this->driver->getViews();
    }


    /**
     * Close the sql connection.
     *
     * @return $this
     */
    public function disconnect(): SqlInterface
    {
        if (!$this->connected) {
            return $this;
        }

        $result = $this->driver->disconnect();

        # If the disconnect was successful, set class property to reflect it
        if ($result) {
            $this->connected = false;
        }

        return $this;
    }


    /**
     * Automatically disconnect from the server when we're done.
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}
