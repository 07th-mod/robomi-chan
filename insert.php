<?php

require __DIR__ . '/bootstrap.php';

$files = \Nette\Utils\Finder::findFiles('*.txt')
    ->in($_SERVER['argv'][1]);

foreach ($files as $file) {
    echo 'Processing file ' . $file->getFilename() . PHP_EOL;
    processFile($file->getPathname());
}
