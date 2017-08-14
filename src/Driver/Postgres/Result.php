<?php

namespace duncan3dc\Sql\Driver\Postgres;

use duncan3dc\Sql\Driver\ResultInterface;

class Result implements ResultInterface
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
