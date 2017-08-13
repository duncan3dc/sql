<?php

namespace duncan3dc\Sql;

use duncan3dc\Sql\Driver\ServerInterface;
use duncan3dc\Sql\Exceptions\UnexpectedValueException;

class Factory
{
    /**
     * @var string $default The default server to use.
     */
    private static $default;

    /**
     * @var ServerInterface[] $servers The server definitions that have been registered.
     */
    private static $servers = [];

    /**
     * @var array Sql[] $instances The instances that have previously been created.
     */
    private static $instances = [];


    /**
     * Register a server.
     *
     * @param string $name The name of this server
     * @param ServerInterface $server The server instance
     *
     * @return void
     * */
    public static function addServer(string $name, ServerInterface $server)
    {
        if (strlen($name) === 0) {
            throw new UnexpectedValueException("A name must be specified for the server");
        }

        if (array_key_exists($name, static::$servers)) {
            throw new UnexpectedValueException("This server has already been defined: {$name}");
        }

        if (static::$default === null) {
            static::$default = $name;
        }

        static::$servers[$name] = $server;
    }


    /**
     * Get an instance using a previous registered server.
     *
     * This method will return the same object if called multiple times.
     *
     * @param string $name The name of the server to get
     *
     * @return SqlInterface
     */
    public static function getInstance($name = null)
    {
        # If no server was specified then default to the first one defined
        if ($name === null) {
            if (static::$default === null) {
                throw new UnexpectedValueException("No servers have been defined, use Factory::addServer() before attempting to get an instance");
            }
            $name = static::$default;
        }

        if (!array_key_exists($name, static::$instances)) {
            static::$instances[$name] = static::getNewInstance($name);
        }

        return static::$instances[$name];
    }


    /**
     * Get a new instance using a previous registered server.
     *
     * @param string $name The name of the server to get
     *
     * @return SqlInterface
     */
    public static function getNewInstance($name)
    {
        if (!array_key_exists($name, static::$servers)) {
            throw new UnexpectedValueException("Unknown SQL Server: {$name}");
        }

        $server = clone static::$servers[$name];

        return new Sql($server, $name);
    }
}
