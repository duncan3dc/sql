<?php

namespace duncan3dc\SqlTests\Driver\Sqlite;

use duncan3dc\Sql\Driver\Sqlite\Result;
use duncan3dc\Sql\Driver\Sqlite\Server;

class ResultTest extends AbstractTest
{
    private $server;


    public function setUp()
    {
        $this->server = new Server(TEMP_PATH . "/test.sqlite");
        $this->server->connect();
    }


    public function tearDown()
    {
        $this->server->disconnect();
    }


    public function testFetch()
    {
        $this->server->query("CREATE TEMPORARY TABLE bands (name VARCHAR(20), year INT(10))", [], "");
        $this->server->query("INSERT INTO bands VALUES ('metallica', 1981), ('protest the hero', 2002)", [], "");

        $result = $this->server->query("SELECT name, year FROM bands", [], "");

        $this->assertSame([
            "name"  =>  "metallica",
            "year"  =>  1981,
        ], $result->getNextRow());

        $this->assertSame([
            "name"  =>  "protest the hero",
            "year"  =>  2002,
        ], $result->getNextRow());

        $this->assertNull($result->getNextRow());
    }


    public function testFree()
    {
        $result = $this->server->query("PRAGMA database_list", [], "");

        $result->free();
        $result->free();

        $this->assertInstanceOf(Result::class, $result);
    }
}
