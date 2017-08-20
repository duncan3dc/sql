<?php

namespace duncan3dc\SqlTests\Driver\Mssql;

use duncan3dc\Sql\Driver\Mssql\Server;
use PHPUnit\Framework\TestCase;

abstract class AbstractTest extends TestCase
{
    private static $server;

    public static function setUpBeforeClass()
    {
        if (\PHP_OS === "Linux") {
            return;
        }

        if (!isset($_ENV["MSSQL_PASSWORD"])) {
            $_ENV["MSSQL_PASSWORD"] = "pass";
        }

        self::$server = new Server("127.0.0.1", "mssql", $_ENV["MSSQL_PASSWORD"]);
        self::$server->connect();
        self::$server->query("CREATE DATABASE pgdb", [], "");
    }


    public static function tearDownAfterClass()
    {
        if (\PHP_OS === "Linux") {
            return;
        }

        self::$server->query("DROP DATABASE pgdb", [], "");
    }
}
