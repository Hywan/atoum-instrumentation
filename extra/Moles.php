<?php

require __DIR__ . DIRECTORY_SEPARATOR . 'Autoloader.php';

use atoum\instrumentation\stream\wrapper;
use atoum\instrumentation\mole;

wrapper::register();

require 'instrument://options=-nodes,-edges/resource=Example/Moles.php';

$c = new C();

var_dump($c->f(42)); // int(84):

mole::register('C::f', function ( $x ) {

    return $x / 2;
});

var_dump($c->f(42)); // int(21);
