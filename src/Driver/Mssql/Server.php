<?php

namespace duncan3dc\Sql\Driver\Mssql;

use duncan3dc\Sql\Driver\AbstractServer;
use duncan3dc\Sql\Exceptions\NotImplementedException;
use duncan3dc\Sql\Result as ResultInterface;

class Server extends AbstractServer
{
    /**
     * @var string $hostname The host or ip address of the database server.
     */
    protected $hostname;

    /**
     * @var string $username The user to authenticate with.
     */
    protected $username;

    /**
     * @var string $password The password to authenticate with.
     */
    protected $password;

    /**
     * @var int $port The port number to connect on.
     */
    private $port;

    /**
     * Create a new instance.
     *
     * @param string $hostname The host or ip address of the database server
     * @param string $username The user to authenticate with
     * @param string $password The password to authenticate with
     * @param int $port The port number to connect on
     */
    public function __construct($hostname, $username, $password, $port = 1433)
    {
        $this->hostname = $hostname;
        $this->username = $username;
        $this->password = $password;
        $this->port = $port;
    }


    /**
     * Get the quote characters that this driver uses for quoting identifiers.
     *
     * @return string[]
     */
    public function getQuoteChars()
    {
        return ["[", "]"];
    }


    public function connect()
    {
        $this->server = sqlsrv_connect("tcp:{$this->hostname},{$this->port}", [
            "Uid"   =>  $this->username,
            "PWD"   =>  $this->password,
        ]);

        if (!$this->server) {
            return false;
        }

        return true;
    }


    public function query($query, array $params = null, $preparedQuery)
    {
        $result = sqlsrv_query($this->server, $preparedQuery);
        if ($result) {
            return new Result($result);
        }
    }


    public function changeQuerySyntax($query)
    {
        $query = preg_replace("/\bIFNULL\(/", "ISNULL(", $query);
        $query = preg_replace("/\bSUBSTR\(/", "SUBSTRING(", $query);
        return $query;
    }


    public function quoteTable($table)
    {
        return "[" . $table . "]";
    }


    public function quoteField($field)
    {
        return "[" . $field . "]";
    }


    public function quoteValue($value)
    {
        return "'" . str_replace("'", "''", $value) . "'";
    }


    public function getErrorMessage()
    {
        $errorMsg = "";
        foreach (sqlsrv_errors() as $error) {
            $errorMsg .= $error["message"] . " (" . $error["code"] . ") [" . $error["SQLSTATE"] . "]\n";
        }
        return trim($errorMsg);
    }


    public function getId(ResultInterface $result)
    {
        throw new NotImplementedException("getId() not available in this mode");
    }


    public function getDatabases()
    {
        $databases = [];

        $result = $this->sql->query("SELECT name FROM master..sysdatabases");
        foreach ($result as $row) {
            $databases[] = $row["name"];
        }

        return $databases;
    }


    public function getTables($database)
    {
        $tables = [];

        $query = "SELECT name FROM " . $this->quoteTable($database) . ".sys.tables";
        $result = $this->sql->query($query);

        foreach ($result as $row) {
            $tables[] = $row["name"];
        }

        return $tables;
    }


    public function getViews($database)
    {
        $views = [];

        $query = "SELECT name FROM " . $this->quoteTable($database) . ".sys.views";
        $result = $this->sql->query($query);

        foreach ($result as $row) {
            $views[] = $row["name"];
        }

        return $views;
    }


    public function startTransaction()
    {
        throw new NotImplementedException("startTransaction() not available in this mode");
    }


    public function endTransaction()
    {
        throw new NotImplementedException("endTransaction() not available in this mode");
    }


    public function commit()
    {
        throw new NotImplementedException("commit() not available in this mode");
    }


    public function rollback()
    {
        throw new NotImplementedException("rollback() not available in this mode");
    }


    public function lockTables(array $tables)
    {
        throw new NotImplementedException("lockTables() not available in this mode");
    }


    public function unlockTables()
    {
        throw new NotImplementedException("unlockTables() not available in this mode");
    }


    public function disconnect()
    {
        if (!$this->server) {
            return;
        }

        return sqlsrv_close($this->server);
    }
}
