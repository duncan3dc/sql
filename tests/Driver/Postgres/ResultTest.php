<?php

namespace duncan3dc\SqlTests\Driver\Postgres;

use duncan3dc\Sql\Driver\Postgres\Result;
use duncan3dc\Sql\Driver\Postgres\Server;

class ResultTest extends AbstractTest
{
    private $server;


    public function setUp()
    {
        $this->server = new Server("pgdb", "127.0.0.1", "postgres", $_ENV["POSTGRES_PASSWORD"]);
        $this->server->connect();
    }


    public function tearDown()
    {
        $this->server->disconnect();
    }


    public function testGetNextRow()
    {
        $this->server->query("CREATE TEMPORARY TABLE bands (name VARCHAR(20), year INTEGER)", [], "");
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
        $result = $this->server->query("SELECT current_database()", [], "");

        $result->free();
        $result->free();

        $this->assertInstanceOf(Result::class, $result);
    }
}
