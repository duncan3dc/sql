<?php

namespace duncan3dc\Sql;

use duncan3dc\Helpers\Helper;
use duncan3dc\Sql\Driver\ResultInterface as DriverResultInterface;
use duncan3dc\Sql\Driver\ServerInterface;
use duncan3dc\Sql\Exceptions\QueryException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Where
{
    private $fields = [];


    /**
     * Convert an array of parameters into a valid where clause
     */
    public static function fromArray(array $fields)
    {
        $where = new self;

        foreach ($where as $field => $value) {
            $where->and($field, $value);
        }

        return $where;
    }


    private function add($type, $field, $value)
    {
        # Convert arrays to use the in helper
        if (is_array($value)) {
            $value = new Where\In($value);
        }

        # Any parameters not using a helper should use the standard equals helper
        if (!is_object($value)) {
            $value = new Where\Equals($value);
        }

        $this->fields[] = (object) [
            "type"      =>  $type,
            "field"     =>  $field,
            "value"     =>  $value,
        ];

        return $this;
    }


    public function and(string $field, $value)
    {
        return $this->add("AND", $field, $value);
    }


    public function or(string $field, $value)
    {
        return $this->add("OR", $field, $value);
    }


    /**
     * Convert an array of parameters into a valid where clause
     */
    public function __toString()
    {
        $where = "";
do this in Sql
        $firstField = true;

        foreach ($this->fields as $field => $value) {

            # Add the and flag if this isn't the first field
            if ($firstField) {
                $firstField = false;
            } else {
                $query .= "{$field->type} ";
            }

            # Add the field name to the query
            $query .= $this->quoteField($field);

            $query .= " " . $value->getClause() . " ";
            foreach ($value->getValues() as $val) {
                $params[] = $val;
            }
        }

        return $query;
    }


    public static function equalTo($value)
    {
        return new Where\EqualTo($value);
    }


    public static function notEqualTo($value)
    {
        return new Where\NotEqualTo($value);
    }


    public static function like($value)
    {
        return new Where\Like($value);
    }


    public static function notLike($value)
    {
        return new Where\NotLike($value);
    }


    public static function in(...$values)
    {
        return new Where\In(...$values);
    }


    public static function notIn(...$values)
    {
        return new Where\NotIn(...$values);
    }


    public static function between($from, $to)
    {
        return new Where\Between($from, $to);
    }


    public static function greaterThan($value)
    {
        return new Where\GreaterThan($value);
    }


    public static function lessThan($value)
    {
        return new Where\LessThan($value);
    }


    public static function notGreaterThan($value)
    {
        return new Where\NotGreaterThan($value);
    }


    public static function notLessThan($value)
    {
        return new Where\NotLessThan($value);
    }
}
