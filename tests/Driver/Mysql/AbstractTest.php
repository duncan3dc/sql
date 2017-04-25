<?php

namespace duncan3dc\SqlTests\Driver\Mysql;

use duncan3dc\Sql\Driver\Mysql\Server;
use PHPUnit\Framework\TestCase;

abstract class AbstractTest extends TestCase
{
    private static $databases = ["db1", "db2"];
    private static $server;

    public static function setUpBeforeClass()
    {
        if (!isset($_ENV["MYSQL_PASSWORD"])) {
            $_ENV["MYSQL_PASSWORD"] = "";
        }

        self::$server = new Server("127.0.0.1", "root", $_ENV["MYSQL_PASSWORD"]);
        self::$server->connect();

        foreach (self::$databases as $database) {
            self::$server->query("", [], "CREATE DATABASE {$database}");
        }
    }


    public static function tearDownAfterClass()
    {
        foreach (self::$databases as $database) {
            self::$server->query("", [], "DROP DATABASE {$database}");
        }
    }
}
