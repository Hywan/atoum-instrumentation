<?php

namespace Example;

class C {

    public function firstMethod ( $x, $y = 5 ) {

        if($y < 5 || $y > 10) {

            if($x === 'bar') {

                // baz;
            }
            else {

                // qux;
            }

            ++$y;
        }
        elseif($x === 'baz') {

            ++$y;
        }
        else {

            $y = 'foo';
        }

        return $y;
    }

    public function secondMethod ( ) {

        if('a')
            foo();
        else
            if('a')
                bar();
            else
                baz();
    }

    public function thirdMethod ( ) {

        $a = 1;
        $i = 0;

        while($i < 10) {

            $a = 42 + $i;
            ++$i;
        }
    }

    public function fourthMethod ( ) {

        foreach($it as $k => $v)
            compute();

        for($i = 0; $i < 10; ++$i)
            compute();

        switch($foo) {

            case 'bar':
              break;

            case 'baz':
                compute();
              break;

            default:
                $foo++;
        }

        try {

            bar();
        }
        catch ( \Exception $e ) {

            try {

                foo();
            }
            catch ( \Exception $f ) {

                baz();
            }
        }

        $a = function ( ) {

            compute();
            if(true)
                x();
            else
                y();
        };
    }

    public function last ( ) {


    }
}
