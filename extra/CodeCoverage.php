<?php

require __DIR__ . DIRECTORY_SEPARATOR . 'Autoloader.php';

use atoum\instrumentation\stream\wrapper;
use atoum\instrumentation\mole;
use atoum\instrumentation\codecoverage;

wrapper::register();
require 'instrument://options=-moles/resource=Example/CodeCoverage.php';

$c = new Example\C();
var_dump($c->firstMethod('bar', 2));
print_r(codecoverage::getRawScores()['Example\C::firstMethod']);
var_dump(codecoverage::getScore('#.*#'));
var_dump($c->firstMethod(1, 7));
print_r(codecoverage::getRawScores()['Example\C::firstMethod']);
var_dump(codecoverage::getScore('#.*#'));
var_dump($c->firstMethod('baz', 7));
print_r(codecoverage::getRawScores()['Example\C::firstMethod']);
var_dump(codecoverage::getScore('#.*#'));
var_dump($c->firstMethod('baz', 2));
print_r(codecoverage::getRawScores()['Example\C::firstMethod']);
var_dump(codecoverage::getScore('#.*#'));

echo "\n\n";
$c->thirdMethod();
var_dump(codecoverage::getScore('Example\C::firstMethod'));
var_dump(codecoverage::getScore('Example\C::secondMethod'));
print_r(codecoverage::getRawScores());
