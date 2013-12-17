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
        $this->compute($x);
        $this->compute($x);

        if($y < 5 || $y > 10) {

            if('foo' === 'bar') {

                // baz;
            }

            $this->compute($x);
        }
        elseif(false) {

            $this->compute($y);
        }
        else {

            $this->compute('foo');
        }

        return $x * $y;
    }

    public function secondMethod ( ) {

        if('a') {

            // foo;
        }
        else {

            // bar;
        }
    }
}

var_dump($a, $b);
