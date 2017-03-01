<?php

namespace duncan3dc\Sql;

use duncan3dc\Sql\ResultInterface;
use duncan3dc\Sql\Driver\ResultInterface as DriverResultInterface;

/**
 * Result class for reading rows for a result set.
 */
class Result implements ResultInterface
{
    /**
     * @var ResultInterface $driver The instance of the driver class handling the abstraction
     */
    private $driver;


    /**
     * Create a Result instance to provide extra functionality
     *
     * @param DriverResultInterface $driver The driver's Result instance
     */
    public function __construct(DriverResultInterface $driver)
    {
        $this->driver = $driver;
    }


    /**
     * Internal method to fetch the next row from the result set.
     *
     * @return array|null
     */
    private function getNextRow()
    {
        $row = $this->driver->getNextRow();

        # If the fetch fails then there are no rows left to retrieve
        if (!is_array($row)) {
            return null;
        }

        return $row;
    }


    /**
     * Fetch the next row from the result set and clean it up
     *
     * All field values have rtrim() called on them to remove trailing space
     *
     * @return \stdClass|null
     */
    public function fetch()
    {
        # If the fetch fails then there are no rows left to retrieve
        if (!$data = $this->getNextRow()) {
            return null;
        }

        # Remove any trailing space from strings
        $data = array_map(function ($value) {
            if (is_string($value)) {
                $value = rtrim($value);
            }
            return $value;
        }, $data);

        return (object) $data;
    }


    /**
     * If the result source is still available then free it before tearing down the object
     *
     * @return void
     */
    public function __destruct()
    {
        $this->driver->free();
    }
}
