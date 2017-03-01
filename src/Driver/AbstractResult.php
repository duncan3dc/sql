<?php

namespace duncan3dc\Sql\Driver;

use duncan3dc\Sql\Sql;

abstract class AbstractResult implements ResultInterface
{
    public function __construct($result)
    {
        $this->result = $result;
    }


    abstract public function free();
}
