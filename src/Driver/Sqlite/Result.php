<?php

namespace duncan3dc\Sql\Driver\Sqlite;

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
     * @param mixed $result Something returned by \SQLite3Stmt::execute()
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
        $result = $this->result->fetchArray(\SQLITE3_ASSOC);

        if (!is_array($result)) {
            return null;
        }

        return $result;
    }


    /**
     * Free the memory used by the result resource.
     *
     * @return void
     */
    public function free()
    {
        if ($this->result instanceof \SQLite3Result) {
            $this->result->finalize();
            $this->result = null;
        }
    }
}
