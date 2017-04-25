<?php

namespace duncan3dc\SqlTests\Driver\Mysql;

use duncan3dc\ObjectIntruder\Intruder;
use duncan3dc\Sql\Driver\Mysql\Result;
use duncan3dc\Sql\Driver\Mysql\Server;
use duncan3dc\Sql\Exceptions\QueryException;

class ServerTest extends AbstractTest
{
    private $server;
    private $intruder;


    public function setUp()
    {
        $this->server = new Server("127.0.0.1", "root", $_ENV["MYSQL_PASSWORD"]);
        $this->intruder = new Intruder($this->server);
    }


    public function tearDown()
    {
        $this->server->disconnect();
    }


    public function testConnect()
    {
        $result = $this->server->connect();
        $this->assertTrue($result);
    }
    public function testConnect2()
    {
        $server = new Server("127.0.0.1", "no-one", "secret");

        $result = $server->connect();

        $this->assertFalse($result);
        $this->assertSame("Access denied for user 'no-one'@'localhost' (using password: YES)", $server->getErrorMessage());
        $this->assertSame(1045, $server->getErrorCode());
    }


    public function testSetDatabase1()
    {
        $this->server->connect();

        $result = $this->server->setDatabase("db2");
        $this->assertSame($this->server, $result);

        $result = $this->server->query("", [], "SELECT DATABASE()");
        $row = $result->getNextRow();
        $this->assertSame("db2", reset($row));
    }
    public function testSetDatabase2()
    {
        $result = $this->server->setDatabase("db2");
        $this->assertSame($this->server, $result);

        $this->server->connect();

        $result = $this->server->query("", [], "SELECT DATABASE()");
        $row = $result->getNextRow();
        $this->assertSame("db2", reset($row));
    }
    public function testSetDatabase3()
    {
        $this->server->connect();

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage("Failed to switch to database dbx");
        $this->server->setDatabase("dbx");
    }


    public function testSetCharset1()
    {
        $this->server->connect();

        $result = $this->server->setCharset("cp852");
        $this->assertSame($this->server, $result);

        $result = $this->intruder->server->character_set_name();
        $this->assertSame("cp852", $result);
    }
    public function testSetCharset2()
    {
        $result = $this->server->setCharset("cp852");
        $this->assertSame($this->server, $result);

        $this->server->connect();

        $result = $this->intruder->server->character_set_name();
        $this->assertSame("cp852", $result);
    }


    public function testSetTimezone1()
    {
        $this->server->connect();

        $result = $this->server->setTimezone("+10:00");
        $this->assertSame($this->server, $result);

        $result = $this->server->query("", [], "SELECT @@session.time_zone");
        $row = $result->getNextRow();
        $this->assertSame("+10:00", reset($row));
    }
    public function testSetTimezone2()
    {
        $result = $this->server->setTimezone("+10:00");
        $this->assertSame($this->server, $result);

        $this->server->connect();

        $result = $this->server->query("", [], "SELECT @@session.time_zone");
        $row = $result->getNextRow();
        $this->assertSame("+10:00", reset($row));
    }
    public function testSetTimezone3()
    {
        $result = $this->server->setTimezone("NOPE");
        $this->assertSame($this->server, $result);
    }
    public function testSetTimezone4()
    {
        $this->server->connect();

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage("Unknown or incorrect time zone: 'NOPE'");
        $this->server->setTimezone("NOPE");
    }
    public function testSetTimezone5()
    {
        $this->server->connect();

        ini_set("date.timezone", "Europe/Berlin");

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage("Unknown or incorrect time zone: 'Europe/Berlin'");
        $this->server->setTimezone("");
    }


    public function testQuery1()
    {
        $this->server->connect();

        $result = $this->server->query("", [], "SELECT NOW()");
        $this->assertInstanceOf(Result::class, $result);
    }
    public function testQuery2()
    {
        $this->server->connect();

        $result = $this->server->query("", [], "NOPE");
        $this->assertNull($result);
    }



    public function quoteValueProvider()
    {
        $data = [
            "ok"    =>  "ok",
            "ok's"  =>  "ok\\'s",
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
