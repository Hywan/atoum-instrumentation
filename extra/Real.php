<?php

require __DIR__ . DIRECTORY_SEPARATOR . 'Autoloader.php';
require '/usr/local/lib/Hoa/Core/Core.php';

use atoum\instrumentation\stream\wrapper;
use atoum\instrumentation\mole;
use atoum\instrumentation\codecoverage;

wrapper::register();


require 'instrument://options=+moles,+coverage-transition/resource=/Users/hywan/Development/Hoa/Project/Central/Hoa/Registry/Registry.php';

Hoa\Registry::set('foo', 'bar');

mole::register(['Hoa\Registry\Registry', 'get'], function ( $index ) {

    return 'yolo';
});

if(Hoa\Registry::isRegistered('foo'))
    var_dump(resolve('hoa://Library/Registry#foo'));




print_r(codecoverage::getRawScores());
var_dump(codecoverage::getScore('/Hoa\\\Registry.+/'));
