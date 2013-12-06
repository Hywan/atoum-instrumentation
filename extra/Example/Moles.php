<?php

class C {

    public function f ( $x ) {

        return $x * 2;
    }

    protected function g ( Array $a, $x, $y ) {

        if(empty($a))
            return 'foo';

        return $this->f($x + $y);
    }

    private function h ( ) {

        return 'bar';
    }

    public static function i ( ) {

        return 'baz';
    }
}
