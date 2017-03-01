<?php

namespace duncan3dc\SqlTests;

use duncan3dc\ObjectIntruder\Intruder;
use duncan3dc\Sql\Driver\ResultInterface;
use duncan3dc\Sql\Result;
use Mockery;
use PHPUnit\Framework\TestCase;

class ResultTest extends TestCase
{
    private $result;
    private $driver;


    public function setUp()
    {
        $this->driver = Mockery::mock(ResultInterface::class);
        $this->driver->shouldReceive("free");

        $this->result = new Result($this->driver);
        $this->intruder = new Intruder($this->result);
    }


    public function tearDown()
    {
        Mockery::close();
    }


    public function testGetNextRow1()
    {
        $this->driver->shouldReceive("getNextRow")->once()->with()->andReturn(null);

        $result = $this->intruder->getNextRow();
        $this->assertNull($result);
    }
    public function testGetNextRow2()
    {
        $this->driver->shouldReceive("getNextRow")->once()->with()->andReturn(["yep"]);

        $result = $this->intruder->getNextRow();
        $this->assertSame(["yep"], $result);
    }


    public function testFetch1()
    {
        $this->driver->shouldReceive("getNextRow")->once()->with()->andReturn(null);

        $result = $this->result->fetch();
        $this->assertNull($result);
    }
    public function testfetch2()
    {
        $this->driver->shouldReceive("getNextRow")->once()->with()->andReturn(["key" => "yep"]);

        $result = $this->intruder->fetch();
        $this->assertEquals((object) ["key" => "yep"], $result);
    }
    public function testfetch3()
    {
        $this->driver->shouldReceive("getNextRow")->once()->with()->andReturn(["key" => " yep "]);

        $result = $this->intruder->fetch();
        $this->assertEquals((object) ["key" => " yep"], $result);
    }
}
