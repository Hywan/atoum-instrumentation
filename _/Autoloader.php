<?php

spl_autoload_register(function ( $class ) {

    $parts = explode('\\', $class);
    array_shift($parts);
    array_shift($parts);
    $path = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR .
            implode(DIRECTORY_SEPARATOR, $parts) . '.php';

    if(false === file_exists($path))
        return false;

    require_once $path;

    return true;
});
