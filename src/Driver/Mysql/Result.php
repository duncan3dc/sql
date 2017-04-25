<?php

namespace duncan3dc\Sql\Driver\Mysql;

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
     * @param mixed $result Something returned by \Mysqli::query()
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
        return $this->result->fetch_assoc();
    }


    /**
     * Free the memory used by the result resource.
     *
     * @return void
     */
    public function free()
    {
        if ($this->result instanceof \mysqli_result) {
            $this->result->free();
            $this->result = null;
        }
    }
}
