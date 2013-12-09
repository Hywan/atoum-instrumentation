<?php

namespace Example;

class C {

    public function f ( $x ) {

        return $x * 2;
    }

    protected function g ( $x, $y ) {

        return $this->f($x + $y);
    }

    private function h ( ) {

        return 'bar';
    }

    public static function i ( $x, $y ) {

        return 'baz';
    }

    public function x ( $x, $y ) {

        return $this->g($x, $y);
    }
}
