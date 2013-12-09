<?php

require __DIR__ . DIRECTORY_SEPARATOR . 'Autoloader.php';

use atoum\instrumentation\stream\wrapper;
use atoum\instrumentation\mole;

wrapper::register();
require 'instrument://options=-nodes,-edges/resource=Example/Moles.php';

$c = new C();

var_dump($c->f(42)); // int(84)
var_dump($c->x(10, 5)); // int(30)

mole::register('C::f', function ( $x ) {

    return $x / 2;
});

var_dump($c->f(42)); // int(21)
var_dump($c->x(10, 5)); // float(7.5)

mole::register('C::g', function ( $x, $y ) {

    return $this->f($x - $y);
});

var_dump($c->x(10, 5)); // float(2.5)
