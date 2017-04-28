<?php

require __DIR__ . '/vendor/autoload.php';

\Tracy\Debugger::enable(\Tracy\Debugger::DEVELOPMENT, __DIR__ . '/log');

require __DIR__ . '/functions.php';

dibi::connect([
    'driver' => 'pdo',
    'dsn' => 'mysql:host=localhost;dbname=higurashi',
    'username' => 'root',
    'password' => 'root',
    'charset' => 'utf8',
]);
