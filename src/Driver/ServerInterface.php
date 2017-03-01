<?php

namespace duncan3dc\Sql\Driver;

use duncan3dc\Sql\Result;

interface ServerInterface
{

    /**
     * Connect to the database using the previously supplied credentials.
     *
     * @return bool
     */
    public function connect(): bool;


    /**
     * Check if this server supports the TRUNCATE TABLE statement.
     *
     * @return bool
     */
    public function canTruncateTables();


    /**
     * Get the quote characters that this driver uses for quoting identifiers.
     *
     * @return string|string[] Can either be a single quote character that it used, or an array of 2 elements (1 for the start and 1 for the end character)
     */
    public function getQuoteChars();


    /**
     * Run a query.
     *
     * @param string $query The query to run
     * @param array $params The parameters to substitute in the query string
     * @param string $preparedQuery A simulated prepared query (if the server doesn't support prepared statements)
     *
     * @return ResultInterface|null Successful statements should return a Result instance
     */
    public function query(string $query, array $params, string $preparedQuery);


    /**
     * Convert any supported function for this database mode.
     *
     * eg, the sqlite class would replace SUBSTRING() with SUBSTR()
     *
     * @param string $query The query to manipulate
     *
     * @return string The modified query
     */
    public function changeQuerySyntax($query);


    /**
     * Quote the supplied table with the relevant characters used by the database driver.
     *
     * @param string $table The table name
     *
     * @return string The quoted table name
     */
    public function quoteTable($table);


    /**
     * Quote the supplied string with the relevant characters used by the database driver.
     *
     * @param string $table The string to quote
     *
     * @return string The quoted string
     */
    public function quoteValue(string $string): string;


    /**
     * Get the error code of the last error.
     *
     * @return mixed
     */
    public function getErrorCode();


    /**
     * Get the error message text of the last error.
     *
     * @return string
     */
    public function getErrorMessage(): string;


    /**
     * Close the sql connection.
     *
     * @return bool
     */
    public function disconnect(): bool;

    public function bulkInsert($table, array $params, $extra = null);

    public function getId(Result $result);

    public function startTransaction();

    public function endTransaction();

    public function commit();

    public function rollback();

    public function lockTables(array $tables);

    public function unlockTables();

    public function getDatabases();

    public function getTables($database);

    public function getViews($database);
}
