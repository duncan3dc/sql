<?php

namespace duncan3dc\SqlTests;

use duncan3dc\Sql\Driver\Sqlite\Server;
use duncan3dc\Sql\Sql;
use PHPUnit\Framework\TestCase;

abstract class AbstractTest extends TestCase
{
    protected $sql;
    protected $database;
    protected $reflection;
    protected $driver;


    public function setUp()
    {
        $this->database = "/tmp/phpunit_" . microtime(true) . ".sqlite";
        if (file_exists($this->database)) {
            unlink($this->database);
        }

        $driver = new Server("/tmp/phpunit.sqlite");
        $this->sql = new Sql($driver);

        $this->sql->definitions([
            "table1"    =>  "test1",
            "table2"    =>  "test2",
        ]);

        $this->reflection = new \ReflectionClass($this->sql);
        $this->driver = $this->reflection->getProperty("driver");
        $this->driver->setAccessible(true);
        $this->connected = $this->reflection->getProperty("connected");
        $this->connected->setAccessible(true);
        $this->connected->setValue($this->sql, true);

        $this->setMode("sqlite");
    }


    public function tearDown()
    {
        $this->setMode(null);
        unset($this->sql);
        unlink($this->database);
    }


    protected function setMode($mode)
    {
        $this->sql->mode = $mode;

        $driver = null;
        if ($mode === "sqlite") {
            $driver = new Server("/tmp/phpunit.sqlite");
            $driver->attachDatabase($this->database, "test1");
        } else {
            $class = "\\duncan3dc\\Sql\\Driver\\" . ucfirst($mode) . "\\Server";
            if (class_exists($class)) {
                $driver = new $class;
            }
        }

        $this->driver->setValue($this->sql, $driver);

        if ($mode === "sqlite") {
            $this->connected->setValue($this->sql, false);
            $this->sql->query("DROP TABLE IF EXISTS {table1}");
            $this->sql->query("CREATE TABLE {table1} (field1 VARCHAR(10), field2 INT)");
        }
    }


    protected function callProtectedMethod($methodName, &$params)
    {
        $method = $this->reflection->getMethod($methodName);
        $method->setAccessible(true);

        if (is_array($params)) {
            return $method->invokeArgs($this->sql, $params);
        } elseif ($params) {
            return $method->invokeArgs($this->sql, [&$params]);
        } else {
            return $method->invokeArgs($this->sql);
        }
    }
}
