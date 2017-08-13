<?php

namespace duncan3dc\SqlTests;

use duncan3dc\Sql\Driver\ServerInterface;
use duncan3dc\Sql\Exceptions\UnexpectedValueException;
use duncan3dc\Sql\Factory;
use duncan3dc\Sql\SqlInterface;
use Mockery;
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
{
    private $properties = [];

    public function setUp()
    {
        $factory = new \ReflectionClass(Factory::class);
        foreach ($factory->getStaticProperties() as $name => $value) {
            $property = $factory->getProperty($name);
            $property->setAccessible(true);
            $this->properties[$name] = $property->getValue();
        }
    }


    public function tearDown()
    {
        Mockery::close();

        $factory = new \ReflectionClass(Factory::class);
        foreach ($this->properties as $name => $value) {
            $property = $factory->getProperty($name);
            $property->setAccessible(true);
            $property->setValue($value);
        }
    }


    public function testAddServer1()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("A name must be specified for the server");
        Factory::addServer("", Mockery::mock(ServerInterface::class));
    }
    public function testAddServer2()
    {
        Factory::addServer("db1", Mockery::mock(ServerInterface::class));

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("This server has already been defined: db1");
        Factory::addServer("db1", Mockery::mock(ServerInterface::class));
    }


    public function testGetInstance1()
    {
        Factory::addServer("db1", Mockery::mock(ServerInterface::class));

        $sql = Factory::getInstance();
        $this->assertInstanceOf(SqlInterface::class, $sql);
        $this->assertSame("db1", $sql->getName());
    }
    public function testGetInstance2()
    {
        Factory::addServer("db1", Mockery::mock(ServerInterface::class));

        $sql1 = Factory::getInstance();
        $sql2 = Factory::getInstance();
        $this->assertSame($sql1, $sql2);
    }
    public function testGetInstance3()
    {
        Factory::addServer("db1", Mockery::mock(ServerInterface::class));

        $server1 = Factory::getInstance()->getServer();
        $server2 = Factory::getInstance()->getServer();
        $this->assertSame($server1, $server2);
    }
    public function testGetInstance4()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("No servers have been defined, use Factory::addServer() before attempting to get an instance");
        Factory::getInstance();
    }


    public function testGetNewInstance1()
    {
        Factory::addServer("db1", Mockery::mock(ServerInterface::class));

        $sql = Factory::getNewInstance("db1");
        $this->assertInstanceOf(SqlInterface::class, $sql);
        $this->assertSame("db1", $sql->getName());
    }
    public function testGetNewInstance2()
    {
        Factory::addServer("db1", Mockery::mock(ServerInterface::class));

        $sql1 = Factory::getNewInstance("db1");
        $sql2 = Factory::getNewInstance("db1");
        $this->assertNotSame($sql1, $sql2);
    }
    public function testGetNewInstance3()
    {
        Factory::addServer("db1", Mockery::mock(ServerInterface::class));

        $server1 = Factory::getNewInstance("db1")->getServer();
        $server2 = Factory::getNewInstance("db1")->getServer();
        $this->assertNotSame($server1, $server2);
    }
    public function testGetNewInstance4()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("Unknown SQL Server: db1");
        Factory::getNewInstance("db1");
    }
}
