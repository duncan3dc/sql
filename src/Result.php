<?php

namespace duncan3dc\Sql;

use duncan3dc\Sql\ResultInterface;
use duncan3dc\Sql\Driver\ResultInterface as DriverResultInterface;

/**
 * Result class for reading rows for a result set.
 */
class Result extends AbstractResult
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

        ++$this->position;

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
     * Fetch an individual value from the result set
     *
     * @param int $row The index of the row to fetch (zero-based)
     * @param int $col The index of the column to fetch (zero-based)
     *
     * @return string
     */
    public function result($row, $col)
    {
        $value = $this->driver->result($row, $col);

        if (is_string($value)) {
            $value = rtrim($value);
        }

        return $value;
    }


    /**
     * Seek to a specific record of the result set.
     *
     * @param int $position The index of the row to position to (zero-based)
     *
     * @return void
     */
    public function seek($position)
    {
        $this->driver->seek($position);
        $this->position = $position;
    }


    /**
     * Get the number of rows in the result set.
     *
     * @return int
     */
    public function count()
    {
        $rows = $this->driver->count();

        if (!is_int($rows) || $rows < 0) {
            throw new \Exception("Failed to get the row count from the result set");
        }

        return $rows;
    }


    /**
     * Get the number of columns in the result set.
     *
     * @return int
     */
    public function columnCount()
    {
        $columns = $this->driver->columnCount();

        if (!is_int($columns) || $columns < 0) {
            throw new \Exception("Failed to get the column count from the result set");
        }

        return $columns;
    }


    /**
     * Free the memory used by the result resource.
     *
     * @return void
     */
    public function free()
    {
        $this->driver->free();
    }


    /**
     * If the result source is still available then free it before tearing down the object
     *
     * @return void
     */
    public function __destruct()
    {
        $this->free();
    }
}
