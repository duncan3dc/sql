<?php

namespace duncan3dc\Sql\Driver\Postgres;

use duncan3dc\Sql\Driver\ResultInterface;
use duncan3dc\Sql\Driver\AbstractResult;

class Result extends AbstractResult
{
    /**
     * @param mixed $result The driver's result reference.
     */
    private $result;


    /**
     * Create a new instance.
     *
     * @param mixed $result Something returned by pg_query_params()
     */
    public function __construct($result)
    {
        $this->result = $result;
    }


    /**
     * Fetch the next row from the result set.
     *
     * @return array|null
     */
    public function getNextRow()
    {
        $row = pg_fetch_assoc($this->result);

        if (!is_array($row)) {
            return null;
        }

        foreach ($row as $key => &$value) {
            $type = pg_field_type($this->result, pg_field_num($this->result, $key));

            if (substr($type, 0, 3) === "int") {
                $value = (int) $value;
            }

            if (substr($type, 0, 5) === "float") {
                $value = (float) $value;
            }

            if ($type === "bool") {
                if ($value === "t") {
                    $value = true;
                }
                if ($value === "f") {
                    $value = false;
                }
            }
        }
        unset($value);

        return $row;
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
        pg_result_seek($this->result, $position);
    }


    /**
     * Get the number of rows in the result set.
     *
     * @return int
     */
    public function count()
    {
        return pg_num_rows($this->result);
    }


    /**
     * Get the number of columns in the result set.
     *
     * @return int
     */
    public function columnCount()
    {
        return $this->result->field_count;
    }


    /**
     * Fetch an individual value from the result set.
     *
     * @param int $row The index of the row to fetch (zero-based)
     * @param int $col The index of the column to fetch (zero-based)
     *
     * @return string
     */
    public function result($row, $col)
    {
        return pg_fetch_result($this->result, $row, $col);
    }


    /**
     * Free the memory used by the result resource.
     *
     * @return void
     */
    public function free()
    {
        if (is_resource($this->result)) {
            pg_free_result($this->result);
            $this->result = null;
        }
    }
}
