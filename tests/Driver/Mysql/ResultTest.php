<?php

namespace duncan3dc\SqlTests\Driver\Mysql;

use duncan3dc\Sql\Driver\Mysql\Result;
use duncan3dc\Sql\Driver\Mysql\Server;

class ResultTest extends AbstractTest
{
    private $server;


    public function setUp()
    {
        $this->server = new Server("127.0.0.1", "root", $_ENV["MYSQL_PASSWORD"]);
        $this->server->connect();
        $this->server->setDatabase("db1");
    }


    public function tearDown()
    {
        $this->server->disconnect();
    }


    public function testFetch()
    {
        $this->server->query("", [], "CREATE TEMPORARY TABLE bands (name VARCHAR(20), year INT(10))");
        $this->server->query("", [], "INSERT INTO bands VALUES ('metallica', 1981), ('protest the hero', 2002)");

        $result = $this->server->query("", [], "SELECT name, year FROM bands");

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
        $result = $this->server->query("", [], "SELECT DATABASE()");

        $result->free();
        $result->free();

        $this->assertInstanceOf(Result::class, $result);
    }
}
