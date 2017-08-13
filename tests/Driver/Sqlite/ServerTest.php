<?php

namespace duncan3dc\SqlTests\Driver\Sqlite;

use duncan3dc\Sql\Driver\Sqlite\Result;
use duncan3dc\Sql\Driver\Sqlite\Server;
use duncan3dc\Sql\Exceptions\QueryException;

class ServerTest extends AbstractTest
{
    private $server;


    public function setUp()
    {
        $this->server = new Server(TEMP_PATH . "/test.sqlite");
    }


    public function tearDown()
    {
        $this->server->disconnect();
    }


    public function testConnect1()
    {
        $result = $this->server->connect();
        $this->assertTrue($result);
    }
    public function testConnect2()
    {
        $server = new Server("/dev/null/nope.sqlite");

        $result = $server->connect();

        $this->assertFalse($result);
        $this->assertStringStartsWith("Unable to ", $server->getErrorMessage());
        $this->assertSame(0, $server->getErrorCode());
    }


    public function testAttachDatabase1()
    {
        $this->server->connect();

        $result = $this->server->attachDatabase(TEMP_PATH . "/db1.sqlite");
        $result = $this->server->attachDatabase(TEMP_PATH . "/db2.sqlite");

        $this->server->query("CREATE TABLE db1.bands (name VARCHAR(20))", [], "");
        $this->server->query("CREATE TABLE db2.bands (name VARCHAR(20))", [], "");

        $this->server->query("INSERT INTO db1.bands VALUES ('metallica')", [], "");
        $this->server->query("INSERT INTO db2.bands VALUES ('protest the hero')", [], "");

        $row = $this->server->query("SELECT name FROM db1.bands", [], "")->getNextRow();
        $this->assertSame("metallica", $row["name"]);

        $row = $this->server->query("SELECT name FROM db2.bands", [], "")->getNextRow();
        $this->assertSame("protest the hero", $row["name"]);
    }
    public function testAttachDatabase2()
    {
        $this->server->connect();

        $result = $this->server->attachDatabase(TEMP_PATH . "/db3.sqlite", "custom_name");
        $this->assertSame($this->server, $result);

        $this->server->query("CREATE TABLE custom_name.bands (name VARCHAR(20))", [], "");
        $this->server->query("INSERT INTO custom_name.bands VALUES ('metallica')", [], "");
        $row = $this->server->query("SELECT name FROM custom_name.bands", [], "")->getNextRow();
        $this->assertSame("metallica", $row["name"]);
    }
    public function testAttachDatabase3()
    {
        $this->server->connect();

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage("unable to open database: /dev/null/db.sqlite");

        $this->server->attachDatabase("/dev/null/db.sqlite");
    }


    public function testQuery1()
    {
        $this->server->connect();

        $result = $this->server->query("SELECT date('now')", [], "");
        $this->assertInstanceOf(Result::class, $result);
    }
    public function testQuery2()
    {
        $this->server->connect();

        $result = $this->server->query("", [], "NOPE");
        $this->assertNull($result);
    }
    public function testQuery3()
    {
        $this->server->connect();

        $this->server->query("CREATE TABLE data_types (type_bool BOOLEAN, type_int INTEGER, type_float REAL, type_text TEXT)", [], "");
        $this->server->query("INSERT INTO data_types VALUES (1, 7, 3.14, 'hello')", [], "");

        $this->assertSame(true, (bool) $this->server->query("SELECT * FROM data_types WHERE type_bool = ?", [true], "")->getNextRow()["type_bool"]);
        $this->assertSame(7, $this->server->query("SELECT * FROM data_types WHERE type_int = ?", [7], "")->getNextRow()["type_int"]);
        $this->assertSame(3.14, $this->server->query("SELECT * FROM data_types WHERE type_float = ?", [3.14], "")->getNextRow()["type_float"]);
        $this->assertSame("hello", $this->server->query("SELECT type_text FROM data_types WHERE type_text = ?", ["hello"], "")->getNextRow()["type_text"]);
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


    public function testGetErrorCode()
    {
        $this->server->connect();

        $this->server->query("NOPE", [], "");

        $this->assertSame(1, $this->server->getErrorCode());
    }
    public function testGetErrorMessage()
    {
        $this->server->connect();

        $this->server->query("NOPE", [], "");

        $this->assertSame("near \"NOPE\": syntax error", $this->server->getErrorMessage());
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
