<?php

namespace duncan3dc\Sql;

class Cache
{
    private $seconds;


    private function __construct($seconds)
    {
        $this->seconds = $seconds;
    }


    public function getSeconds()
    {
        return $this->seconds;
    }


    public static function minutes($minutes)
    {
        return new self($minutes * 60);
    }


    public static function hours($hours)
    {
        return new self($hours * 3600);
    }


    public static function hour()
    {
        return new self(3600);
    }


    public static function day()
    {
        return new self(86400);
    }
}
