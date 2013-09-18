<?php

namespace Hoathis\Instrumentation\Sequence {

class Matching {

    protected $_sequence = null;
    protected $_index    = 0;
    protected $_max      = 0;
    protected $_skip     = array();



    public function __construct ( Array $sequence ) {

        $this->setSequence($sequence);

        return;
    }

    protected function setSequence ( Array $sequence ) {

        $old             = $this->_sequence;
        $this->_sequence = $sequence;
        $this->_index    = 0;
        $this->_max      = count($this->_sequence) - 1;

        return $old;
    }

    public function getSequence ( ) {

        return $this->_sequence;
    }

    public function skip ( Array $tokens ) {

        foreach($tokens as $token)
            $this->_skip[] = $token;

        return $this;
    }

    public function match ( Array $rules ) {

        foreach($rules as $rule) {

            array_walk($rule[0], function ( &$value ) {

                if(… === $value)
                    $value = '…';
            });

            echo implode(', ', $rule[0]), ' => ', implode(', ', $rule[1]), "\n";
        }

        // a rule cannot start by “…”.
        // “…” suivit de “…” est interdit (ou supprimé en un seul “…”).

        for(; $this->_index <= $this->_max; ++$this->_index) {

            $i       = $this->_index;
            $set     = null;
            $length  = 0;
            $matches = array();

            foreach($rules as $rule) {

                list($pattern, $replace) = $rule;

                $gotcha  = false;
                $length  = 0;
                $matches = array();

                for($j = 0, $max = count($pattern) - 1;
                    $j <= $max && $i + $j < $this->_max;
                    ++$j) {

                    $token    = $this->getToken($i + $j);
                    $pToken   = $pattern[$j];
                    $_matches = null;

                    if(… === $pToken) {

                        $pNextToken  = $pattern[$j + 1];
                        $_matches   .= $token;
                        $_length     = 1;

                        for($_i = $i + $j + 1, $_max = $this->_max - 1;
                            $_i <= $_max && ++$_length;
                            ++$_i) {

                            $nextToken = $this->getToken($_i);
                            $gotcha    = $pNextToken === $nextToken;

                            if(true === $gotcha) {

                                $length    += $_length;
                                $j          = $_i - $i;
                                $matches[]  = $_matches;
                                $_matches   = $nextToken;

                                break;
                            }
                            else
                                $_matches .= $nextToken;
                        }
                    }
                    else {

                        $gotcha = $pToken === $token;

                        if(true === $gotcha) {

                            ++$length;
                            $_matches = $token;
                        }

                    }

                    if(false === $gotcha)
                        break;
                    elseif(null !== $_matches)
                        $matches[] = $_matches;
                }

                if(true === $gotcha) {

                    $set = $rule;
                    break;
                }
            }

            if(null === $set)
                continue;

            foreach($set[1] as &$_tokens)
                $_tokens = preg_replace_callback(
                    '#\\\(\d+)#',
                    function ( Array $m ) use ( $matches ) {

                        var_dump($m);

                        if(!isset($matches[$m[1]]))
                            return null;

                        return $matches[$m[1]];
                    },
                    $_tokens
                );

            array_splice(
                $this->_sequence,
                $i,
                $length,
                $set[1]
            );
            $this->_max = count($this->_sequence);
        }
    }

    protected function getToken ( $i ) {

        return $this->_sequence[$i];
    }
}

}
