<?php

namespace duncan3dc\SqlTests\Driver\Postgres;

use duncan3dc\Sql\Driver\Postgres\Result;
use duncan3dc\Sql\Driver\Postgres\Server;
use duncan3dc\SqlTests\Handlers;

class ServerTest extends AbstractTest
{
    private $server;


    public function setUp()
    {
        $this->server = new Server("pgdb", "127.0.0.1", "postgres", $_ENV["POSTGRES_PASSWORD"]);
    }


    public function tearDown()
    {
        Handlers::clear();
        $this->server->disconnect();
    }


    public function testConnect()
    {
        $result = $this->server->connect();
        $this->assertTrue($result);
    }
    public function testConnect2()
    {
        $server = new Server("nope", "127.0.0.1", "no-one", "secret");

        $result = $server->connect();

        $this->assertFalse($result);
        $this->assertContains("Unable to connect to PostgreSQL server", $server->getErrorMessage());
        $this->assertSame(0, $server->getErrorCode());
    }


    public function testSetPort()
    {
        $result = "";
        Handlers::handle("pg_connect", function ($connect, $settings) use (&$result) {
            $result = $connect;
        });

        $this->server->setPort(1986);
        $this->server->connect();

        $this->assertContains("port=1986", $result);
    }


    public function testQuery1()
    {
        $this->server->connect();

        $result = $this->server->query("SELECT NOW()", [], "");
        $this->assertInstanceOf(Result::class, $result);
    }
    public function testQuery2()
    {
        $this->server->connect();

        $result = $this->server->query("NOPE", [], "");
        $this->assertNull($result);
    }
    public function testQuery3()
    {
        $this->server->connect();

        $this->server->query("CREATE TABLE data_types (type_bool BOOLEAN, type_int INTEGER, type_float REAL, type_text TEXT)", [], "");
        $this->server->query("INSERT INTO data_types VALUES (true, 7, 3.14, 'hello')", [], "");

        $this->assertSame(true, $this->server->query("SELECT * FROM data_types WHERE type_bool = ?", [true], "")->getNextRow()["type_bool"]);
        $this->assertSame(7, $this->server->query("SELECT * FROM data_types WHERE type_int = ?", [7], "")->getNextRow()["type_int"]);
        $this->assertSame(3.14, $this->server->query("SELECT * FROM data_types WHERE type_float = ?", [3.14], "")->getNextRow()["type_float"]);
        $this->assertSame("hello", $this->server->query("SELECT type_text FROM data_types WHERE type_text = ?", ["hello"], "")->getNextRow()["type_text"]);
    }


    public function testGetErrorMessage()
    {
        $this->server->connect();

        $this->server->query("NOPE", [], "");

        $this->assertContains("ERROR:  syntax error at or near", $this->server->getErrorMessage());
    }


    public function quoteValueProvider()
    {
        $data = [
            "ok"    =>  "ok",
            "ok's"  =>  "ok''s",
        ];
        foreach ($data as $input => $expected) {
            yield [$input, $expected];
        }
    }
    /**
     * @dataProvider quoteValueProvider
     */
    public function testQuoteValue(string $input, string $expected)
    {
        $this->server->connect();

        $result = $this->server->quoteValue($input);
        $this->assertSame("'{$expected}'", $result);
    }


    public function testDisconnect()
    {
        # Check that disconnecting before connecting is cool
        $this->server->disconnect();

        # Check a standard disconnection
        $this->server->connect();
        $this->server->disconnect();

        # Ensure a second disconnect is cool
        $this->assertTrue($this->server->disconnect());
    }
}
