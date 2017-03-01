<?php

namespace duncan3dc\Sql\Driver\Sqlite;

use duncan3dc\PhpIni\State;
use duncan3dc\Sql\Driver\ServerInterface;
use duncan3dc\Sql\Exceptions\QueryException;
use duncan3dc\Sql\Driver\AbstractServer;
use duncan3dc\Sql\Exceptions\NotImplementedException;
use duncan3dc\Sql\Result as ResultInterface;
use duncan3dc\Sql\Sql;

class Server extends AbstractServer
{
    /**
     * @var \Sqlite3 $server The connection to the database.
     */
    private $server;

    /**
     * @var string $database The filename containing the database.
     */
    private $database;

    /**
     * @var array $attached The sqlite databases that have been attached
     */
    private $attached = [];

    /**
     * @var State $ini An ini state object to supress warnings.
     */
    private $ini;

    /**
     * @var \Exception $connectException An exception that was raising during connection.
     */
    private $connectException;


    /**
     * Create a new instance.
     *
     * @param string $database The filename containing the database
     */
    public function __construct(string $database)
    {
        $this->database = $database;

        $this->ini = new State;
        $this->ini->set("error_reporting", error_reporting() ^ \E_WARNING);
    }


    /**
     * Attach another sqlite database to the current connection.
     *
     * @param string $filename The filename containing the database
     * @param string $database The alias to give the database
     *
     * @return $this
     */
    public function attachDatabase(string $filename, string $database = null): ServerInterface
    {
        if ($database === null) {
            $database = pathinfo($filename, \PATHINFO_FILENAME);
        }

        $query = "ATTACH DATABASE '{$filename}' AS " . $this->quoteValue($database);

        if (!$this->query($query, [], "")) {
            throw new QueryException($this->getErrorMessage(), $this->getErrorCode());
        }

        $this->attached[$database] = $filename;

        return $this;
    }


    /**
     * Connect to the database using the previously supplied credentials.
     *
     * @return bool
     */
    public function connect(): bool
    {
        try {
            $this->server = new \Sqlite3($this->database);
        } catch (\Exception $e) {
            $this->connectException = $e;
            return false;
        }

        return true;
    }


    /**
     * Check if this server supports the TRUNCATE TABLE statement.
     *
     * @return bool
     */
    public function canTruncateTables()
    {
        return false;
    }


    /**
     * Run a query.
     *
     * @param string $query The query to run
     * @param array $params The parameters to substitute in the query string
     * @param string $preparedQuery A simulated prepared query (if the server doesn't support prepared statements)
     *
     * @return ResultInterface|null Successful statements should return a Result instance
     */
    public function query(string $query, array $params, string $preparedQuery)
    {
        $statement = $this->ini->call(function () use ($query) {
            return $this->server->prepare($query);
        });
        if (!$statement) {
            return;
        }

        $position = 0;
        foreach ($params as $key => $val) {
            ++$position;
            $statement->bindValue($position, $val);
        }

        $result = $this->ini->call(function () use ($statement) {
            return $statement->execute();
        });

        if ($result) {
            return new Result($result);
        }
    }


    /**
     * Quote the supplied string with the relevant characters used by the database driver.
     *
     * @param string $value The string to quote
     *
     * @return string The quoted string
     */
    public function quoteValue(string $value): string
    {
        return "'" . $this->server->escapeString($value) . "'";
    }


    /**
     * Get the error code of the last error.
     *
     * @return mixed
     */
    public function getErrorCode()
    {
        if (!$this->server && $this->connectException) {
            return $this->connectException->getCode();
        }

        return $this->server->lastErrorCode();
    }


    /**
     * Get the error message text of the last error.
     *
     * @return string
     */
    public function getErrorMessage(): string
    {
        if (!$this->server && $this->connectException) {
            return $this->connectException->getMessage();
        }

        return $this->server->lastErrorMsg();
    }


    /**
     * Get the quote characters that this driver uses for quoting identifiers.
     *
     * @return string
     */
    public function getQuoteChars()
    {
        return '`';
    }


    public function changeQuerySyntax($query)
    {
        $query = preg_replace("/\bISNULL\(/", "IFNULL(", $query);
        return $query;
    }


    public function quoteTable($table)
    {
        return "`" . $table . "`";
    }


    public function quoteField($field)
    {
        return "`" . $field . "`";
    }


    public function getId(ResultInterface $result)
    {
        return $this->sql->query("SELECT last_insert_rowid()")->fetch(Sql::FETCH_ROW)[0];
    }


    public function getDatabases()
    {
        throw new NotImplementedException("getDatabases() not available in this mode");
    }


    public function getTables($database)
    {
        throw new NotImplementedException("getTables() not available in this mode");
    }


    public function getViews($database)
    {
        throw new NotImplementedException("getViews() not available in this mode");
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


    /**
     * Close the sql connection.
     *
     * @return bool
     */
    public function disconnect(): bool
    {
        if (!$this->server) {
            return true;
        }

        $result = $this->server->close();

        $this->server = null;

        return $result;
    }
}
