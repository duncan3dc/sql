<?php

namespace duncan3dc\Sql\Driver\Postgres
{
    use duncan3dc\SqlTests\Handlers;

    function pg_connect($connect, $settings)
    {
        return Handlers::call("pg_connect", $connect, $settings);
    }
}
