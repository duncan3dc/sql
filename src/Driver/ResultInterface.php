<?php

namespace duncan3dc\Sql\Driver;

interface ResultInterface
{

    /**
     * Internal method to fetch the next row from the result set.
     *
     * @return array|null
     */
    public function getNextRow();


    /**
     * Free the memory used by the result resource.
     *
     * @return void
     */
    public function free();
}
