<?php

namespace duncan3dc\Sql\Cache;

use duncan3dc\Sql\Driver\ServerInterface;
use duncan3dc\Sql\SqlInterface;
use duncan3dc\Sql\Table;

/**
 * Main class that allows interaction with databases.
 */
class Sql implements SqlInterface
{
    /**
     * @var SqlInterface $sql The instance to run the query via.
     */
    private $sql;

    /**
     * @var OptionsInterface $options The cache options.
     */
    private $options;


    /**
     * Create a new instance.
     *
     * @param SqlInterface $sql The instance to run the query via
     * @param OptionsInterface $options The cache options
     */
    public function __construct(SqlInterface $sql, OptionsInterface $options)
    {
        $this->sql = $sql;
        $this->options = $options;
    }


    /**
     * Get the server instance of the driver in use.
     *
     * @return ServerInterface
     */
    public function getServer(): ServerInterface
    {
        return $this->sql->getServer();
    }


    /**
     * Get the name assigned to this server.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->sql->getName();
    }


    /**
     * If we have not already connected then connect to the database now.
     *
     * @return SqlInterface
     */
    public function connect(): SqlInterface
    {
        $this->sql->connect();
        return $this;
    }


    /**
     * Execute an sql query.
     *
     * @param string $query The query string to run
     * @param array $params The parameters to use in the query
     *
     * @return ResultInterface
     */
    public function query(string $query, array $params = []): ResultInterface
    {
        return new Result($this->sql, $query, $params, $this->options);
    }


    public function table(string $table)
    {
        $tableName = $this->getTableName($table);
        return new Table($tableName, $this);
    }


    public function update(string $table, array $set, $where)
    {
        return $this->sql->update($table, $set, $where);
    }


    public function insert(string $table, array $params, $extra = null)
    {
        return $this->sql->insert($table, $params, $extra);
    }


    public function bulkInsert(string $table, array $params, $extra = null)
    {
        return $this->sql->bulkInsert($table, $params, $extra);
    }


    public function getId(ResultInterface $result)
    {
        return $this->sql->getId($result);
    }


    public function delete(string $table, $where)
    {
        return $this->sql->delete($table, $where);
    }


    /**
     * Grab the first row from a table using the standard select statement
     * This is a convience method for a fieldSelect() where all fields are required
     */
    public function select(string $table, $where, $orderBy = null)
    {
        return $this->table($table)->select($where, $orderBy);
    }


    /**
     * Grab specific fields from the first row from a table using the standard select statement
     */
    public function fieldSelect(string $table, $fields, $where, $orderBy = null)
    {
        return $this->table($table)->fieldSelect($fields, $where, $orderBy);
    }


    /**
     * Create a standard select statement and return the result
     * This is a convience method for a fieldSelectAll() where all fields are required
     */
    public function selectAll(string $table, $where, $orderBy = null)
    {
        return $this->table($table)->selectAll($where, $orderBy);
    }


    /**
     * Create a standard select statement and return the result
     */
    public function fieldSelectAll(string $table, $fields, $where, $orderBy = null)
    {
        return $this->table($table)->fieldSelectAll($fields, $where, $orderBy);
    }


    /**
     * Check if a record exists without fetching any data from it.
     *
     * @param string $table The table name to fetch from
     * @param array|int $where The where clause to use, or the NO_WHERE_CLAUSE constant
     *
     * @return boolean Whether a matching row exists in the table or not
     */
    public function exists(string $table, $where)
    {
        return $this->table($table)->exists($where);
    }


    /**
     * Insert a new record into a table, unless it already exists in which case update it
     */
    public function upsert(string $table, array $set, array $where)
    {
        return $this->sql->upsert($table, $set, $where);
    }


    /**
     * Close the sql connection.
     *
     * @return $this
     */
    public function disconnect(): SqlInterface
    {
        $this->sql->disconnect();

        return $this;
    }
}
