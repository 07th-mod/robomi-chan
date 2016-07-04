<?php

require __DIR__ . '/vendor/autoload.php';

\Tracy\Debugger::enable(\Tracy\Debugger::DEVELOPMENT, __DIR__ . '/log');

require __DIR__ . '/functions.php';

dibi::connect([
    'driver' => 'mysql',
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'higurashi',
    'charset' => 'utf8',
]);
