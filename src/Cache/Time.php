<?php

namespace duncan3dc\Sql\Cache;

class Time
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
        return self::minutes($hours * 60);
    }


    public static function days($days)
    {
        return self::hours($days * 24);
    }
}
