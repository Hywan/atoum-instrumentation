<?php

namespace atoum\instrumentation\sequence;

const … = '__atoum_fill';

class matching {

    const TOKEN_ALL             = -1;
    const TOKEN_ID              = 0;
    const TOKEN_VALUE           = 1;
    const TOKEN_LINE            = 2;
    const SHIFT_REPLACEMENT_END = 0;

    protected $_sequence  = null;
    protected $_index     = 0;
    protected $_max       = 0;
    protected $_skip      = array();
    protected $_rules     = array();
    protected $_variables = array();



    public function __construct ( Array $sequence ) {

        $this->setSequence($sequence);

        return;
    }

    protected function setSequence ( Array $sequence ) {

        for($i = 0, $max = count($sequence) - 1; $i <= $max; ++$i) {

            $token = &$sequence[$i];

            if(!is_array($token))
                $token = array(
                    -1,
                    $token,
                    0 < $i ? $sequence[$i - 1][2] : 0
                );
        }

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

        // TODO:
        //   • a rule cannot start by “…”.
        //   • “…” followed by “…” is not allow.

        $state = 0;
        // 0  done
        // 1  ongoing
        // 2  class, trait
        // 4  method
        // 8  rest…
        $this->_variables = array(
            'class' => array(
                // 'abstract' => false,
                'name' => null,
            ),
            'method' => array(
                //'abstract'   => false,
                'visibility' => 'public',
                'static'     => false,
                'name'       => null
            )
        );

        for(; $this->_index < $this->_max; ++$this->_index) {

            $token = &$this->_sequence[$this->_index];

            if(T_CLASS === $token[0]) {

                $state = 3;

                ++$this->_index;
                while(T_STRING !== $this->_sequence[$this->_index++][0]);
                --$this->_index;

                $nextToken = &$this->_sequence[$this->_index];
                $this->_variables['class']['name'] = $nextToken[1];

                continue;
            }

            if('{' === $token[1]) {

                if(3 === $state) {

                    $state = 2;

                    if(isset($rules['class::start'])) {

                        $old          = $this->_rules;
                        $this->_rules = $rules['class::start'];
                        $this->_match();
                        $this->_rules = $old;
                    }
                }
                elseif(5 === $state) {

                    $state = 4;

                    if(isset($rules['method::start'])) {

                        $old          = $this->_rules;
                        $this->_rules = $rules['method::start'];
                        $this->_match();
                        $this->_rules = $old;
                    }
                }
                else
                    $state = 8;

                continue;
            }
            elseif('}' === $token[1]) {

                if(2 === $state) {

                    $state = 0;

                    if(isset($rules['class:end'])) {

                        $old          = $this->_rules;
                        $this->_rules = $rules['class::end'];
                        $this->_match();
                        $this->_rules = $old;
                    }

                    $this->_variables['class']['name'] = null;
                }
                elseif(4 === $state) {

                    $state = 2;

                    if(isset($rules['method:end'])) {

                        $old          = $this->_rules;
                        $this->_rules = $rules['method::end'];
                        $this->_match();
                        $this->_rules = $old;
                    }

                    $this->_variables['method']['name'] = null;
                }
                else
                    $state = 4;

                continue;
            }

            if(2 === $state) {

                if(T_FUNCTION === $token[0]) {

                    $state = 5;

                    $oldIndex = $this->_index;

                    for($i = 0; $i <= 1; ++$i) {

                        do {

                            $previousToken = $this->_sequence[--$this->_index][0];

                        } while(   T_WHITESPACE  === $previousToken
                                || T_COMMENT     === $previousToken
                                || T_DOC_COMMENT === $previousToken);

                        $previousToken = $this->_sequence[$this->_index][0];

                        if(   T_STATIC    !== $previousToken
                           && T_PUBLIC    !== $previousToken
                           && T_PROTECTED !== $previousToken
                           && T_PRIVATE   !== $previousToken)
                            continue;

                        if(T_STATIC === $previousToken)
                            $this->_variables['method']['static'] = true;
                        elseif(T_PUBLIC === $previousToken)
                            $this->_variables['method']['visibility'] = 'public';
                        elseif(T_PROTECTED === $previousToken)
                            $this->_variables['method']['visibility'] = 'protected';
                        elseif(T_PRIVATE === $previousToken)
                            $this->_variables['method']['visibility'] = 'private';
                    }

                    $this->_index = $oldIndex;

                    ++$this->_index;
                    while(T_STRING !== $this->_sequence[$this->_index++][0]);
                    --$this->_index;

                    $nextToken = &$this->_sequence[$this->_index];
                    $this->_variables['method']['name'] = $nextToken[1];

                    continue;
                }
            }
            elseif(4 === $state) {

                if(isset($rules['method::body'])) {

                    $old          = $this->_rules;
                    $this->_rules = $rules['method::body'];
                    $this->_match();
                    $this->_rules = $old;
                }
            }
        }

        if(isset($rules['file::end'])) {

            $old          = $this->_rules;
            $this->_rules = $rules['file::end'];
            $this->_match();
            $this->_rules = $old;
        }

        return;
    }

    protected function _match ( ) {

        $i       = $this->_index;
        $set     = null;
        $length  = 0;
        $matches = array();

        if(true === $this->skipable($i))
            return;

        foreach($this->_rules as $rule) {

            list($pattern, $replace) = $rule;

            if(empty($pattern)) {

                $set = $rule;
                break;
            }

            $gotcha  = false;
            $length  = 0;
            $matches = array();

            for($j = 0, $max = count($pattern) - 1;
                $j <= $max && $i + $j < $this->_max;
                ++$j) {

                if(true === $this->skipable($i + $j)) {

                    ++$i;
                    --$j;
                    ++$length;

                    continue;
                }

                $token    = &$this->getToken($i + $j);
                $pToken   = $pattern[$j];
                $_matches = null;

                if(… === $pToken) {

                    $pNextToken = $pattern[$j + 1];
                    $_length    = 0;
                    $_skipped   = null;

                    for($_i = $i + $j, $_max = $this->_max - 1;
                        $_i <= $_max && ++$_length;
                        ++$_i) {

                        if(true === $this->skipable($_i)) {

                            $_skipped .= $this->getToken($_i);

                            continue;
                        }

                        $nextToken = &$this->getToken($_i);
                        $gotcha    = $pNextToken === $nextToken;

                        if(true === $gotcha) {

                            $length += $_length;
                            ++$j;

                            if(   isset($pattern[$j + 1])
                               && … === $pattern[$j + 1])
                                $i++;

                            $matches[] = $_matches;
                            $_matches  = $_skipped . $nextToken;
                            $_skipped  = null;

                            break;
                        }
                        else {

                            $_matches .= $_skipped . $nextToken;
                            $_skipped  = null;
                        }
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
            return;

        if(is_array($set[1]))
            foreach($set[1] as &$_tokens) {

                if(false === strpos($_tokens, '\\'))
                    continue;

                $_tokens = preg_replace_callback(
                    '#\\\(\w+)\.(\w+)#',
                    function ( Array $m ) {

                        if(!isset($this->_variables[$m[1]]))
                            return '';

                        if(!isset($this->_variables[$m[1]][$m[2]]))
                            return '';

                        return $this->_variables[$m[1]][$m[2]];
                    },
                    $_tokens
                );
                $_tokens = preg_replace_callback(
                    '#\\\(\d+)#',
                    function ( Array $m ) use ( $matches ) {

                        $x = $m[1] - 1;

                        if(!isset($matches[$x]))
                            return null;

                        return $matches[$x];
                    },
                    $_tokens
                );
            }
        elseif(is_callable($set[1])) {

            $callable = $set[1];
            $set[1]   = $callable($this->_variables);
        }

        array_splice(
            $this->_sequence,
            $this->_index,
            $length,
            $set[1]
        );

        if(   isset($set[2])
           && static::SHIFT_REPLACEMENT_END === $set[2])
            $this->_index += count($set[1]) - 1;

        if(   isset($set[3])
           && is_callable($set[3])) {

            $callable = $set[3];
            $callable($this->_variables);
        }

        $this->_max = count($this->_sequence);

        return;
    }

    protected function &getToken ( $i, $index = self::TOKEN_VALUE ) {

        $out = &$this->_sequence[$i];

        if(static::TOKEN_ALL === $index)
            return $out;

        if(!is_array($out))
            return $out;

        $outt = $this->_sequence[$i][$index];

        return $outt;
    }

    protected function skipable ( $index ) {

        return in_array($this->getToken($index, static::TOKEN_ID), $this->_skip);
    }
}
