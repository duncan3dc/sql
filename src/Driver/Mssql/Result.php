<?php

namespace duncan3dc\Sql\Driver\Mssql;

use duncan3dc\Sql\Driver\AbstractResult;

class Result extends AbstractResult
{

    /**
     * Internal method to fetch the next row from the result set.
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
     * The driver doesn't support seeking, so we fetch specific rows in getNextRow().
     *
     * @param int $position The index of the row to position to (zero-based)
     *
     * @return void
     */
    public function seek($position)
    {
        $this->position = $position;
    }


    /**
     * Get the number of rows in the result set.
     *
     * @return int
     */
    public function count()
    {
        return sqlsrv_num_rows($this->result);
    }


    /**
     * Get the number of columns in the result set.
     *
     * @return int
     */
    public function columnCount()
    {
        return sqlsrv_num_fields($this->result);
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
        return sqlsrv_result($this->result, $row, $col);
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
