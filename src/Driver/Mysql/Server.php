<?php

namespace duncan3dc\Sql\Driver\Mysql;

use duncan3dc\PhpIni\State;
use duncan3dc\Sql\Driver\ServerInterface;
use duncan3dc\Sql\Exceptions\QueryException;

class Server implements ServerInterface
{
    /**
     * @var \Mysqli $server The connection to the database server.
     */
    private $server;

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
     * @var string $database The database to use.
     */
    private $database;

    /**
     * @var string $charset The character set to use.
     */
    private $charset;

    /**
     * @var string $timezone The timezone to use.
     */
    private $timezone;

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
    public function __construct(string $hostname, string $username, string $password)
    {
        $this->hostname = $hostname;
        $this->username = $username;
        $this->password = $password;

        $this->ini = new State;
        $this->ini->set("error_reporting", error_reporting() ^ \E_WARNING);
    }


    /**
     * Set the current active database.
     *
     * @param string $database The database to use
     *
     * @return $this
     */
    public function setDatabase(string $database): ServerInterface
    {
        $this->database = $database;

        if ($this->server) {
            if (!$this->server->select_db($this->database)) {
                throw new QueryException("Failed to switch to database {$database}");
            }
        }

        return $this;
    }


    /**
     * Set the character set to use/
     *
     * @param string $charset The character set to use
     *
     * @return $this
     */
    public function setCharset(string $charset): ServerInterface
    {
        $this->charset = $charset;

        if ($this->server) {
            $this->server->set_charset($this->charset);
        }

        return $this;
    }


    /**
     * Set the timezone to use.
     *
     * @param string $time The timezone set to use
     *
     * @return $this
     */
    public function setTimezone(string $timezone = ""): ServerInterface
    {
        if ($timezone === "") {
            $timezone = ini_get("date.timezone");
        }

        $this->timezone = $timezone;

        if ($this->server) {
            $statement = $this->server->prepare("SET time_zone = ?");
            $statement->bind_param("s", $this->timezone);
            if (!$statement->execute()) {
                throw new QueryException($this->getErrorMessage(), $this->getErrorCode());
            }
            $statement->close();
        }

        return $this;
    }


    /**
     * Connect to the database using the previously supplied credentials.
     *
     * @return bool
     */
    public function connect(): bool
    {
        $this->ini->call(function () {
            $this->server = new \Mysqli($this->hostname, $this->username, $this->password);
        });
        if ($this->server->connect_error) {
            return false;
        }

        $this->server->options(\MYSQLI_OPT_INT_AND_FLOAT_NATIVE, true);

        if ($this->charset !== null) {
            $this->setCharset($this->charset);
        }

        if ($this->timezone !== null) {
            $this->setTimezone($this->timezone);
        }

        if ($this->database !== null) {
            $this->setDatabase($this->database);
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
        $result = $this->server->query($preparedQuery);

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
        return "'" . $this->server->real_escape_string($value) . "'";
    }


    /**
     * Get the error code of the last error.
     *
     * @return mixed
     */
    public function getErrorCode()
    {
        if ($this->server->connect_errno) {
            return $this->server->connect_errno;
        }

        return $this->server->errno;
    }


    /**
     * Get the error message text of the last error.
     *
     * @return string
     */
    public function getErrorMessage(): string
    {
        if ($this->server->connect_error) {
            return $this->server->connect_error;
        }

        return $this->server->error;
    }


    /**
     * Close the sql connection.
     *
     * @return bool
     */
    public function disconnect(): bool
    {
        if (!$this->server || $this->server->connect_error) {
            return true;
        }

        $result = $this->server->close();

        $this->server = null;

        return $result;
    }
}
