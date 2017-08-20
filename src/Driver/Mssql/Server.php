<?php

namespace duncan3dc\Sql\Driver\Mssql;

use duncan3dc\Sql\Driver\ServerInterface;

class Server implements ServerInterface
{
    /**
     * @var resource $sqlsrv The connection to the database server.
     */
    private $sqlsrv;

    /**
     * @var string $hostname The host or ip address of the database server.
     */
    private $hostname;

    /**
     * @var string $username The user to authenticate with.
     */
    private $username;

    /**
     * @var string $password The password to authenticate with.
     */
    private $password;

    /**
     * @var int $port The port to connect on.
     */
    private $port = 1433;

    /**
     * @var State $ini An ini state object to supress warnings.
     */
    private $ini;


    /**
     * Create a new instance.
     *
     * @param string $hostname The host or ip address of the database server
     * @param string $username The user to authenticate with
     * @param string $password The password to authenticate with
     */
    public function __construct($hostname, $username, $password)
    {
        $this->hostname = $hostname;
        $this->username = $username;
        $this->password = $password;
    }


    /**
     * Set the port to connect on.
     *
     * @param int $port The port number
     *
     * @return $this
     */
    public function setPort(int $port): ServerInterface
    {
        $this->port = $port;

        return $this;
    }


    /**
     * Connect to the database using the previously supplied credentials.
     *
     * @return bool
     */
    public function connect(): bool
    {
        $this->sqlsrv = sqlsrv_connect("tcp:{$this->hostname},{$this->port}", [
            "Uid"   =>  $this->username,
            "PWD"   =>  $this->password,
        ]);
/*
        $this->ini->call(function () use ($connect) {
            $this->sqlsrv = sqlsrv_connect("tcp:{$this->hostname},{$this->port}", [
                "Uid"   =>  $this->username,
                "PWD"   =>  $this->password,
            ]);
        });
*/
        if (!$this->sqlsrv) {
            return false;
        }

        return true;
    }


    /**
     * Run a query.
     *
     * @param string $query The query to run
     * @param array $params The parameters to substitute in the query string
     * @param string $preparedQuery A simulated prepared query (if the server doesn't support prepared statements)
     *
     * @return ResultInterface|null Successful statements should return a Result instance
     */
    public function query(string $query, array $params, string $preparedQuery)
    {
        $result = sqlsrv_query($this->sqlsrv, $preparedQuery);

        if ($result) {
            return new Result($result);
        }
    }


    /**
     * Quote the supplied string with the relevant characters used by the database driver.
     *
     * @param string $value The string to quote
     *
     * @return string The quoted string
     */
    public function quoteValue(string $value): string
    {
        return "'" . str_replace("'", "''", $value) . "'";
    }


    /**
     * Get the error code of the last error.
     *
     * @return mixed
     */
    public function getErrorCode()
    {
        return 0;
    }


    /**
     * Get the error message text of the last error.
     *
     * @return string
     */
    public function getErrorMessage(): string
    {
        $errorMsg = "";
        foreach (sqlsrv_errors() as $error) {
            $errorMsg .= $error["message"] . " (" . $error["code"] . ") [" . $error["SQLSTATE"] . "]\n";
        }
        return trim($errorMsg);
    }


    /**
     * Close the sql connection.
     *
     * @return bool
     */
    public function disconnect(): bool
    {
        if (!$this->sqlsrv) {
            return true;
        }

        $result = sqlsrv_close($this->sqlsrv);

        $this->sqlsrv = null;

        return $result;
    }
}
