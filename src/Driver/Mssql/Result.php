<?php

namespace duncan3dc\Sql\Driver\Mssql;

use duncan3dc\Sql\Driver\ResultInterface;

class Result implements ResultInterface
{
    private $result;


    /**
     * Create a new instance.
     *
     * @param mixed $result Something returned by sqlsrv_query()
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
        sqlsrv_fetch($this->result, SQLSRV_SCROLL_ABSOLUTE, $this->position);
        $row = sqlsrv_fetch_array($this->result, SQLSRV_FETCH_ASSOC);

        if (!$row) {
            return;
        }

        foreach ($row as &$value) {
            if ($value instanceof \DateTime) {
                $value = $value->format(\DateTime::ISO8601);
            }
        }

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
            sqlsrv_free_stmt($this->result);
        }
    }
}
