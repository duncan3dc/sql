<?php

namespace duncan3dc\SqlTests;

use duncan3dc\ObjectIntruder\Intruder;
use duncan3dc\Sql\Driver\ResultInterface as DriverResultInterface;
use duncan3dc\Sql\Driver\ServerInterface;
use duncan3dc\Sql\Exceptions\ConnectionException;
use duncan3dc\Sql\Exceptions\QueryException;
use duncan3dc\Sql\ResultInterface;
use duncan3dc\Sql\Sql;
use Mockery;
use PHPUnit\Framework\TestCase;

class SqlTest extends TestCase
{
    private $sql;
    private $driver;


    public function setUp()
    {
        $this->driver = Mockery::mock(ServerInterface::class);
        $this->sql = new Sql($this->driver);
        $this->intruder = new Intruder($this->sql);
    }


    public function tearDown()
    {
        $this->intruder->connected = false;
        Mockery::close();
    }


    private function connect()
    {
        $this->intruder->connected = true;
    }


    public function testGetServer()
    {
        $server = $this->sql->getServer();
        $this->assertSame($this->driver, $server);
    }


    public function testConnect1()
    {
        $this->driver->shouldReceive("connect")->once()->with()->andReturn(true);

        $result = $this->sql->connect();
        $this->assertSame($this->sql, $result);
    }
    public function testConnect2()
    {
        $this->driver->shouldReceive("connect")->once()->with()->andReturn(false);
        $this->driver->shouldReceive("getErrorCode")->once()->with()->andReturn(124);
        $this->driver->shouldReceive("getErrorMessage")->once()->with()->andReturn("Whoops");

        $this->expectException(ConnectionException::class, "Whoops");
        $this->sql->connect();
    }
    public function testConnect3()
    {
        $this->driver->shouldReceive("connect")->once()->with()->andReturn(true);

        $this->sql->connect();

        $result = $this->sql->connect();
        $this->assertSame($this->sql, $result);
    }


    public function testQuery1()
    {
        $this->driver->shouldReceive("connect")->once()->with()->andReturn(true);

        $driver = Mockery::mock(DriverResultInterface::class);
        $driver->shouldReceive("free");
        $this->driver->shouldReceive("query")->once()->with("SELECT", [], "SELECT")->andReturn($driver);

        $result = $this->sql->query("SELECT");
        $this->assertInstanceOf(ResultInterface::class, $result);
    }
    public function testQuery2()
    {
        $this->connect();

        $this->driver->shouldReceive("query")->once()->with("SELECT", [], "SELECT")->andReturn(null);
        $this->driver->shouldReceive("getErrorCode")->once()->with()->andReturn(303);
        $this->driver->shouldReceive("getErrorMessage")->once()->with()->andReturn("Can't select");

        $this->expectException(QueryException::class, "Can't select");
        $this->sql->query("SELECT");
    }


    public function testModifyQuery()
    {
        $result = $this->intruder->modifyQuery("SELECT 'SELECT' FROM table", function ($part) {
            return str_replace("SELECT", "MAGIC", $part);
        });

        $this->assertSame("MAGIC 'SELECT' FROM table", $result);
    }


    public function testPrepareQuery()
    {
        $this->driver->shouldReceive("quoteValue")->once()->with("string")->andReturn("'string'");

        $result = $this->intruder->prepareQuery("SELECT ?, ?, ?, ?, ? FROM table", ["string", 123, true, 4.5, null]);

        $this->assertSame("SELECT 'string', 123, 1, 4.5, NULL FROM table", $result);
    }


    public function testGetErrorCode()
    {
        $this->driver->shouldReceive("getErrorCode")->once()->with()->andReturn(404);

        $result = $this->sql->getErrorCode();
        $this->assertSame(404, $result);
    }


    public function testGetErrorMessage()
    {
        $this->driver->shouldReceive("getErrorMessage")->once()->with()->andReturn("No tables");

        $result = $this->sql->getErrorMessage();
        $this->assertSame("No tables", $result);
    }


    public function testDisconnect1()
    {
        $this->driver->shouldReceive("connect")->twice()->with()->andReturn(true);
        $this->driver->shouldReceive("disconnect")->once()->with()->andReturn(true);

        $this->sql->connect();

        $result = $this->sql->disconnect();
        $this->assertSame($this->sql, $result);

        $this->sql->connect();
    }
    public function testDisconnect2()
    {
        $this->driver->shouldReceive("connect")->once()->with()->andReturn(true);
        $this->driver->shouldReceive("disconnect")->once()->with()->andReturn(false);

        $this->sql->connect();

        $result = $this->sql->disconnect();
        $this->assertSame($this->sql, $result);

        $this->sql->connect();
    }
    public function testDisconnect3()
    {
        $result = $this->sql->disconnect();
        $this->assertSame($this->sql, $result);
    }
}
