<?php

namespace duncan3dc\SqlTests\Driver\Sqlite;

use PHPUnit\Framework\TestCase;

define(__NAMESPACE__ . "\\TEMP_PATH", sys_get_temp_dir() . "/phpunit");

abstract class AbstractTest extends TestCase
{

    public static function setUpBeforeClass()
    {
        if (!is_dir(TEMP_PATH)) {
            mkdir(TEMP_PATH);
            chmod(TEMP_PATH, 0777);
        }
    }


    public static function tearDownAfterClass()
    {
        $files = glob(TEMP_PATH . "/*.sqlite");
        array_map("unlink", $files);
    }
}
