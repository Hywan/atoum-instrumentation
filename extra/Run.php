<?php

require __DIR__ . DIRECTORY_SEPARATOR . 'Autoloader.php';

atoum\instrumentation\stream\wrapper::register();

$file = __DIR__ . DIRECTORY_SEPARATOR . 'Example' . DIRECTORY_SEPARATOR .
       'Simple.php';

array_shift($argv);
$options = array();

foreach($argv as $argument)
    if('-' === $argument[0] || '+' === $argument[0])
        $options[] = $argument;
    else
        $file = $argument;

$out = file_get_contents(
    'instrument://options=' . implode(',', $options) . '/resource=' . $file
);

if(false === in_array('-out', $options))
    echo $out;
