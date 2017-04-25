<?php

namespace duncan3dc\Sql\Driver;

interface ServerInterface
{

    /**
     * Connect to the database using the previously supplied credentials.
     *
     * @return bool
     */
    public function connect(): bool;


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
}
