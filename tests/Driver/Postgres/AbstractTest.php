<?php

namespace duncan3dc\SqlTests\Driver\Postgres;

use duncan3dc\Sql\Driver\Postgres\Server;
use duncan3dc\SqlTests\Handlers;
use PHPUnit\Framework\TestCase;

abstract class AbstractTest extends TestCase
{
    private static $server;

    public static function setUpBeforeClass()
    {
        Handlers::register();

        if (!isset($_ENV["POSTGRES_PASSWORD"])) {
            $_ENV["POSTGRES_PASSWORD"] = "pass";
        }

        self::$server = new Server("postgres", "127.0.0.1", "postgres", $_ENV["POSTGRES_PASSWORD"]);
        self::$server->connect();
        self::$server->query("CREATE DATABASE pgdb", [], "");
    }


    public static function tearDownAfterClass()
    {
        self::$server->query("DROP DATABASE pgdb", [], "");
    }
}
