<?php

require __DIR__ . '/bootstrap.php';

dibi::query('TRUNCATE [voices]');

$files = \Nette\Utils\Finder::findFiles('*.txt')
    ->exclude('s00.txt')
    ->exclude('hou-plus.txt')
    ->in(__DIR__ . '/dev');

foreach ($files as $file) {
    echo 'Loading file ' . $file->getFilename() . PHP_EOL;
    loadFile($file->getPathname());
}
