<?php

namespace duncan3dc\Sql;

use duncan3dc\Helpers\Helper;
use duncan3dc\Sql\Driver\ResultInterface as DriverResultInterface;
use duncan3dc\Sql\Driver\ServerInterface;
use duncan3dc\Sql\Exceptions\QueryException;
use duncan3dc\Sql\Exceptions\ConnectionException;

/**
 * Main class that allows interaction with databases.
 */
class Sql implements SqlInterface
{
    /**
     * @var ServerInterface $driver The instance of the driver class handling the abstraction.
     */
    private $driver;

    /**
     * @var bool $connected Flag to indicate whether we are connected to the server yet.
     */
    private $connected = false;


    /**
     * Create a new instance.
     *
     * @param ServerInterface $driver The instance of the driver class handling the abstraction
     */
    public function __construct(ServerInterface $driver)
    {
        $this->driver = $driver;
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

        $preparedQuery = $this->prepareQuery($query, $params);

        $result = $this->driver->query($query, $params, $preparedQuery);

        if (!$result instanceof DriverResultInterface) {
            throw QueryException::fromSql($this);
        }

        return new Result($result);
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
