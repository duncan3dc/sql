<?php

namespace duncan3dc\Sql\Driver\Postgres;

use duncan3dc\PhpIni\State;
use duncan3dc\Sql\Driver\ServerInterface;

class Server implements ServerInterface
{
    /**
     * @var resource $server The connection to the database server.
     */
    private $server;

    /**
     * @var string $database The database to use.
     */
    private $database;

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
    private $port;

    /**
     * @var State $ini An ini state object to supress warnings.
     */
    private $ini;


    /**
     * Create a new instance.
     *
     * @param string $database The database to use
     * @param string $hostname The host or ip address of the database server
     * @param string $username The user to authenticate with
     * @param string $password The password to authenticate with
     */
    public function __construct(string $database, string $hostname, string $username, string $password)
    {
        $this->database = $database;
        $this->hostname = $hostname;
        $this->username = $username;
        $this->password = $password;

        $this->ini = new State;
        $this->ini->set("error_reporting", error_reporting() ^ \E_WARNING);
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
        $options = [
            "host"      =>  $this->hostname,
            "user"      =>  $this->username,
            "password"  =>  $this->password,
            "dbname"    =>  $this->database,
            "port"      =>  $this->port,
        ];

        $connect = "";
        foreach ($options as $key => $val) {
            if ($val === null) {
                continue;
            }
            $connect .= "{$key}={$val} ";
        }

        $this->ini->call(function () use ($connect) {
            $this->server = pg_connect($connect, \PGSQL_CONNECT_FORCE_NEW);
        });

        if (!$this->server) {
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
        $tmpQuery = $query;
        $query = "";

        $i = 1;
        while ($pos = strpos($tmpQuery, "?")) {
            $query .= substr($tmpQuery, 0, $pos) . "\$" . $i++;
            $tmpQuery = substr($tmpQuery, $pos + 1);
        }
        $query .= $tmpQuery;

        foreach ($params as &$value) {
            if (is_null($value)) {
                $value = "NULL";
            }
        }
        unset($value);

        $result = $this->ini->call(function () use ($query, $params) {
            return pg_query_params($this->server, $query, $params);
        });

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
        return pg_escape_literal($this->server, $value);
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
        if (!$this->server) {
            $error = error_get_last();
            if (array_key_exists("message", $error)) {
                return explode("\n", $error["message"])[0];
            }
        }

        return pg_last_error($this->server);
    }


    /**
     * Close the sql connection.
     *
     * @return bool
     */
    public function disconnect(): bool
    {
        if (!$this->server) {
            return true;
        }

        $result = pg_close($this->server);

        $this->server = null;

        return $result;
    }
}
