<?php

namespace duncan3dc\Sql;

use duncan3dc\Sql\Driver\ServerInterface;
use Psr\Log\LoggerInterface;

interface SqlInterface
{
    /**
     * Get the server instance of the driver in use.
     *
     * @return ServerInterface
     */
    public function getServer(): ServerInterface;


    /**
     * Get the name assigned to this server.
     *
     * @return string
     */
    public function getName(): string;


    /**
     * Allow queries to be created without a where cluase
     */
    const NO_WHERE_CLAUSE = 101;

    /**
     * Set the database timezone to be the same as the php one
     */
    const USE_PHP_TIMEZONE = 102;

    /**
     * Mysql extension to replace any existing records with a unique key match
     */
    const INSERT_REPLACE = 103;

    /**
     * Mysql extension to ignore any existing records with a unique key match
     */
    const INSERT_IGNORE = 104;

    /**
     * Return rows as an enumerated array (using column numbers)
     */
    const FETCH_ROW = 108;

    /**
     * Return rows as an associative array (using field names)
     */
    const FETCH_ASSOC = 109;

    /**
     * Return a generator of the first 1 or 2 columns
     */
    const FETCH_GENERATOR = 110;

    /**
     * Return the raw row from the database without performing cleanup
     */
    const FETCH_RAW = 111;


    /**
     * If we have not already connected then connect to the database now.
     *
     * @return SqlInterface
     */
    public function connect(): SqlInterface;


    /**
     * Execute an sql query.
     *
     * @param string $query The query string to run
     * @param array $params The parameters to use in the query
     *
     * @return ResultInterface
     */
    public function query(string $query, array $params = []): ResultInterface;


    /**
     * Get the last error code from the driver.
     *
     * @return mixed
     */
    public function getErrorCode();


    /**
     * Get the last error message from the driver.
     *
     * @return mixed
     */
    public function getErrorMessage();


    /**
     * Close the sql connection.
     *
     * @return SqlInterface
     */
    public function disconnect(): SqlInterface;


    /**
     * Get the driver instance.
     *
     * @return ServerInterface
     */
    public function getDriver();


    /**
     * Set the logger to use.
     *
     * @param LoggerInterface $logger Which logger to use
     *
     * @return static
     */
    public function setLogger(LoggerInterface $logger);


    /**
     * Get the logger in use.
     *
     * @return LoggerInterface
     */
    public function getLogger();


    /**
     * Define which database each table is located in.
     */
    public function definitions($data);


    /**
     * Convenience method to create a cached query instance.
     */
    public function cache($query, array $params = null, Time $time = null);


    public function error();


    public function table($table);


    public function update($table, array $set, $where);


    public function insert($table, array $params, $extra = null);


    public function bulkInsert($table, array $params, $extra = null);


    public function getId(ResultInterface $result);


    /**
     * Convert an array of parameters into a valid where clause
     */
    public function where($where, &$params);


    /**
     * Convert an array/string of fields into a valid select clause
     */
    public function selectFields($fields);


    public function delete($table, $where);


    /**
     * Grab the first row from a table using the standard select statement
     * This is a convience method for a fieldSelect() where all fields are required
     */
    public function select($table, $where, $orderBy = null);


    /**
     * Grab specific fields from the first row from a table using the standard select statement
     */
    public function fieldSelect($table, $fields, $where, $orderBy = null);


    /**
     * Create a standard select statement and return the result
     * This is a convience method for a fieldSelectAll() where all fields are required
     */
    public function selectAll($table, $where, $orderBy = null);


    /**
     * Create a standard select statement and return the result
     */
    public function fieldSelectAll($table, $fields, $where, $orderBy = null);


    /**
     * Check if a record exists without fetching any data from it.
     *
     * @param string $table The table name to fetch from
     * @param array|int $where The where clause to use, or the NO_WHERE_CLAUSE constant
     *
     * @return boolean Whether a matching row exists in the table or not
     */
    public function exists($table, $where);


    /**
     * Insert a new record into a table, unless it already exists in which case update it
     */
    public function insertOrUpdate($table, array $set, array $where);


    /**
     * Synonym for insertOrUpdate()
     */
    public function updateOrInsert($table, array $set, array $where);


    /**
     * Create an order by clause from a string of fields or an array of fields
     */
    public function orderBy($fields);


    /**
     * This method allows easy appending of search criteria to queries
     * It takes existing query/params to be edited as the first 2 parameters
     * The third parameter is the string that is being searched for
     * The fourth parameter is an array of fields that should be searched for in the sql
     */
    public function search(&$query, array &$params, $search, array $fields);


    /**
     * Start a transaction by turning autocommit off.
     */
    public function startTransaction();


    /**
     * Check if the object is currently in transaction mode.
     *
     * @return bool
     */
    public function isTransaction();


    /**
     * End a transaction by either committing changes made, or reverting them.
     */
    public function endTransaction($commit);


    /**
     * Commit queries without ending the transaction.
     */
    public function commit();


    /**
     * Rollback queries without ending the transaction.
     */
    public function rollback();


    /**
     * Lock some tables for exlusive write access
     * But allow read access to other processes
     */
    public function lockTables($tables);


    /**
     * Unlock all tables previously locked.
     */
    public function unlockTables();


    public function getDatabases();


    public function getTables($database);


    public function getViews($database);
}
