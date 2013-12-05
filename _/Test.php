<?php

$a = 1;
$b = 2;

if(1 === $a) {

    $a += 3;
    $b  = 5;
}

class Foobar {

    public function firstMethod ( $x, $y = 5 ) {

        $this->compute($x);

        if($y < 5) {

            $this->compute($y);
        }

        return $x * $y;
    }
}

var_dump($a, $b);
