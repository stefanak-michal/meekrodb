#!/usr/bin/php
<?php

class SimpleTest
{
    public function assert($boolean)
    {
        if (!$boolean) $this->fail();
    }

    protected function fail($msg = '')
    {
        echo "FAILURE! $msg\n";
        debug_print_backtrace();
        die;
    }
}

ini_set('date.timezone', 'America/Los_Angeles');

error_reporting(E_ALL | E_STRICT);
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'autoload.php';
//require_once __DIR__ . DIRECTORY_SEPARATOR . 'test_setup.php'; //test config values go here
// WARNING: ALL tables in the database will be dropped before the tests, including non-test related tables.

/**
 * Class DB
 * Static wrapper for original access
 */
class DB
{
    /**
     * @var \MeekroDB\MeekroDB
     */
    private static $instance;

    public static function __callStatic($name, $arguments)
    {
        return self::gi()->{$name}(...$arguments);
    }

    /**
     * Get instance
     * @return \MeekroDB\MeekroDB
     */
    public static function gi(): \MeekroDB\MeekroDB
    {
        if (empty(self::$instance)) {
            require_once __DIR__ . DIRECTORY_SEPARATOR . 'test_setup.php'; //test config values go here
            self::$instance = new \MeekroDB\MeekroDB($set_host, $set_db_user, $set_password, $set_db);
        }

        return self::$instance;
    }
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'BasicTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'WalkTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'CallTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'ObjectTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'WhereClauseTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'HookTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'TransactionTest.php';

$classes_to_test = array(
    'BasicTest',
    'WalkTest',
    'CallTest',
    'WhereClauseTest',
    'ObjectTest',
    'HookTest',
    'TransactionTest',
);

$mysql_version = DB::serverVersion();
if ($mysql_version >= '5.5') {
    require_once __DIR__ . '/TransactionTest_55.php';
    $classes_to_test[] = 'TransactionTest_55';
} else {
    echo "MySQL 5.5 not available (version is $mysql_version) -- skipping MySQL 5.5 tests\n";
}

$time_start = microtime(true);
foreach ($classes_to_test as $class) {
    $object = new $class();

    foreach (get_class_methods($object) as $method) {
        if (substr($method, 0, 4) != 'test') continue;
        echo "Running $class::$method..\n";
        $object->$method();
    }
}
$time = round(microtime(true) - $time_start, 2);

echo "Completed in $time seconds\n";
