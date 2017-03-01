<?php

namespace duncan3dc\Sql\Exceptions;

use duncan3dc\Sql\SqlInterface;

class SqlException extends \Exception implements Exception
{

    public static function fromSql(SqlInterface $sql)
    {
        return new static($sql->getErrorMessage(), $sql->getErrorCode());
    }
}
