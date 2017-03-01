<?php

namespace duncan3dc\Sql;

use duncan3dc\Sql\Driver\ServerInterface;

interface SqlInterface
{
    /**
     * Get the server instance of the driver in use.
     *
     * @return ServerInterface
     */
    public function getServer(): ServerInterface;


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
}
