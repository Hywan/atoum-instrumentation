<?php

namespace atoum\instrumentation\sequence;

const … = '__atoum_fill';

!defined('T_FINALLY')   && define('T_FINALLY',   -2);
!defined('T_INSTEADOF') && define('T_INSTEADOF', -2);
!defined('T_TRAIT')     && define('T_TRAIT',     -2);
!defined('T_TRAIT_C')   && define('T_TRAIT_C',   -2);
!defined('T_YIELD')     && define('T_YIELD',     -2);

class matching {

    const TOKEN_ALL             = -1;
    const TOKEN_ID              = 0;
    const TOKEN_VALUE           = 1;
    const TOKEN_LINE            = 2;
    const SHIFT_REPLACEMENT_END = 0;

    protected static $_structures          = array(
        T_CATCH,
        T_DECLARE,
        T_DO,
        T_ELSE,
        T_ELSEIF,
        T_FINALLY,
        T_FOR,
        T_FOREACH,
        T_IF,
        T_SWITCH,
        T_TRY,
        T_WHILE
    );
    protected static $_structuresAsStrings = array(
        T_CATCH    => 'catch',
        T_DECLARE  => 'declare',
        T_DO       => 'do',
        T_CASE     => 'case',
        T_DEFAULT  => 'default',
        T_ELSE     => 'else',
        T_ELSEIF   => 'if',
        T_FINALLY  => 'finally',
        T_FOR      => 'for',
        T_FOREACH  => 'foreach',
        T_IF       => 'if',
        T_SWITCH   => 'switch',
        T_TRY      => 'try',
        T_WHILE    => 'while'
    );
    protected $_sequence                   = null;
    protected $_index                      = 0;
    protected $_max                        = 0;
    protected $_skip                       = array();
    protected $_rules                      = array();
    protected $_variables                  = array();



    public function __construct ( Array $sequence ) {

        $this->setSequence($sequence);

        return;
    }

    protected function setSequence ( Array $sequence ) {

        $old             = $this->_sequence;
        $this->_sequence = $sequence;

        for($i = 0, $max = count($this->_sequence) - 1; $i <= $max; ++$i) {

            $recompute = $this->addMissingBrackets($i);

            if(true === $recompute)
                $max = count($this->_sequence) - 1;
        }

        $this->_index = 0;
        $this->_max   = count($this->_sequence) - 1;

        return $old;
    }

    public function addMissingBrackets ( $index ) {

        $token = $this->getToken($index);

        if(false === in_array($token[static::TOKEN_ID], static::$_structures))
            return false;

        $tokenId = $token[static::TOKEN_ID];

        if(T_ELSE === $tokenId) {

            $nextIndex   = $index;
            $nextTokenId = $this->getNextSignificantToken(
                $nextIndex,
                static::TOKEN_ID
            );

            if(T_IF === $nextTokenId) {

                $token[static::TOKEN_ID]    = T_ELSEIF;
                $token[static::TOKEN_VALUE] = 'elseif';
                array_splice(
                    $this->_sequence,
                    $index,
                    $nextIndex - $index + 1,
                    array(
                        $token
                    )
                );
                $this->_max = count($this->_sequence) - 1;

                return $this->addMissingBrackets($index);
            }
        }

        $rightParenthesisIndex = $index;
        $opened                = 0;

        if(   T_DO      !== $tokenId
           && T_ELSE    !== $tokenId
           && T_TRY     !== $tokenId
           && T_FINALLY !== $tokenId)
            do {

                $nextToken = $this->getNextSignificantToken(
                    $rightParenthesisIndex,
                    static::TOKEN_VALUE
                );

                if(')' === $nextToken)
                    --$opened;
                elseif('(' === $nextToken)
                    ++$opened;
            }
            while(   true === $this->tokenExists($rightParenthesisIndex)
                  && $opened > 0);

        $nextIndex = $rightParenthesisIndex;
        $nextToken = $this->getNextSignificantToken($nextIndex);

        // control-structure ( condition ) { statement }
        if('{' === $nextToken[static::TOKEN_VALUE])
            return false;

        // control-structure ( condition ) ;
        if(';' === $nextToken[static::TOKEN_VALUE]) {

            array_splice(
                $this->_sequence,
                $nextIndex,
                1,
                array(
                    array(
                        -1,
                        '{',
                        $nextToken[static::TOKEN_LINE]
                    ),
                    array(
                        -1,
                        '}',
                        $nextToken[static::TOKEN_LINE]
                    )
                )
            );
            $this->_max = count($this->_sequence) - 1;

            return true;
        }

        // control-structure ( condition ) statement ;
        array_splice(
            $this->_sequence,
            $rightParenthesisIndex + 1,
            0,
            array(
                array(
                    -1,
                    '{',
                    $token[static::TOKEN_LINE]
                )
            )
        );
        $this->_max = count($this->_sequence) - 1;

        // statement is a structure.
        if(true === in_array($nextToken[static::TOKEN_ID], static::$_structures)) {

            ++$nextIndex;
            $typeOfStructure = $nextToken[static::TOKEN_ID];
            $structureIndex  = $nextIndex;

            do {

                $this->addMissingBrackets($structureIndex);
                $blockIndex = $structureIndex;

                // now we have fresh brackets, jump to the right index.
                while('{' !== $this->getToken($blockIndex++, static::TOKEN_VALUE));

                $opened = 1;

                do {

                    $blockToken = $this->getToken($blockIndex++, static::TOKEN_VALUE);

                    if('{' === $blockToken)
                        ++$opened;
                    elseif('}' === $blockToken)
                        --$opened;

                } while(0 < $opened);

                $nextStructureIndex  = $blockIndex;
                $nextTypeOfStructure = $this->getNextSignificantToken(
                    $nextStructureIndex,
                    static::TOKEN_ID
                );

                // no new statement (that is a structure).
                if(false === in_array($nextTypeOfStructure, static::$_structures))
                    break;

                // the only statements that can be linked are T_IF, T_ELSEIF,
                // T_ELSE, but no statement is allowed after a T_ELSE.
                if(!(   (T_IF === $typeOfStructure
                         &&    (T_ELSEIF === $nextTypeOfStructure
                            ||  T_ELSE   === $nextTypeOfStructure))
                     || (T_ELSEIF === $typeOfStructure
                         &&    (T_ELSEIF === $nextTypeOfStructure
                            ||  T_ELSE   === $nextTypeOfStructure))))
                    break;

                $structureIndex  = $nextStructureIndex;
                $typeOfStructure = $nextTypeOfStructure;

            } while(true);

            array_splice(
                $this->_sequence,
                $blockIndex,
                0,
                array(
                    array(
                        -1,
                        '}',
                        $this->getToken($blockIndex - 1, static::TOKEN_LINE)
                    )
                )
            );
            $this->_max = count($this->_sequence) - 1;

            return true;
        }

        // statement is not a structure.
        $nextNextIndex = $nextIndex;
        $opened        = 0;

        do {

            $nextNextToken = $this->getToken(++$nextNextIndex);

            if('{' === $nextNextToken[static::TOKEN_VALUE])
                ++$opened;
            elseif('}' === $nextNextToken[static::TOKEN_VALUE])
                --$opened;

        } while(0 < $opened || ';' !== $nextNextToken[static::TOKEN_VALUE]);

        array_splice(
            $this->_sequence,
            $nextNextIndex + 1,
            0,
            array(
                array(
                    -1,
                    '}',
                    $nextNextToken[static::TOKEN_LINE]
                )
            )
        );
        $this->_max = count($this->_sequence) - 1;

        return true;
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
        //  0  done
        //  1  ongoing
        //  2  class, trait
        //  4  method
        $this->_variables = array(
            'namespace' => null,

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
        $blocks = new \SplStack();

        for(; $this->_index < $this->_max; ++$this->_index) {

            $token   = $this->getToken($this->_index);
            $tokenId = $token[static::TOKEN_ID];

            if(T_NAMESPACE === $tokenId) {

                $namespace = null;

                while(   ($nextToken = $this->getNextSignificantToken($this->_index))
                      && (T_STRING === $nextToken[static::TOKEN_ID]
                      ||  T_NS_SEPARATOR === $nextToken[static::TOKEN_ID]))
                    $namespace .= $nextToken[static::TOKEN_VALUE];

                $this->_variables['namespace'] = $namespace;

                continue;
            }

            if(T_CLASS === $tokenId) {

                $state = 3;

                ++$this->_index;
                while(T_STRING !== $this->getToken($this->_index++, static::TOKEN_ID));
                --$this->_index;

                $this->_variables['class']['name'] =
                    $this->_variables['namespace'] . '\\' .
                    $this->getToken($this->_index, static::TOKEN_VALUE);

                continue;
            }

            if(2 === $state) {

                if(T_FUNCTION === $tokenId) {

                    $state       = 5;
                    $flyingIndex = $this->_index;

                    for($i = 0; $i <= 1; ++$i) {

                        do {

                            $previousToken = $this->getToken(
                                --$flyingIndex,
                                static::TOKEN_ID
                            );

                        } while(   T_WHITESPACE  === $previousToken
                                || T_COMMENT     === $previousToken
                                || T_DOC_COMMENT === $previousToken);

                        if(T_STATIC === $previousToken)
                            $this->_variables['method']['static'] = true;
                        elseif(T_PUBLIC === $previousToken)
                            $this->_variables['method']['visibility'] = 'public';
                        elseif(T_PROTECTED === $previousToken)
                            $this->_variables['method']['visibility'] = 'protected';
                        elseif(T_PRIVATE === $previousToken)
                            $this->_variables['method']['visibility'] = 'private';
                        else
                            break;
                    }

                    $this->_variables['method']['name'] = $this->getNextSignificantToken(
                        $this->_index,
                        static::TOKEN_VALUE
                    );

                    continue;
                }
            }
            elseif(4 === $state) {

                if(T_FUNCTION === $tokenId) { // closure

                    $blocks->push($tokenId);
                    while('{' !== $this->getNextSignificantToken($this->_index, static::TOKEN_VALUE));

                    continue;
                }

                if(true === in_array($tokenId, static::$_structures)) {

                    $blocks->push($tokenId);
                    $structureString = static::$_structuresAsStrings[$tokenId];

                    if(   T_DO      === $tokenId
                       || T_ELSE    === $tokenId
                       || T_TRY     === $tokenId
                       || T_FINALLY === $tokenId) {

                        $this->getNextSignificantToken($this->_index);

                        if(isset($rules[$structureString . '::block::start'])) {

                            $old          = $this->_rules;
                            $this->_rules = $rules[$structureString . '::block::start'];
                            $this->_match();
                            $this->_rules = $old;
                        }

                        continue;
                    }

                    $this->getNextSignificantToken($this->_index);

                    if(isset($rules[$structureString . '::condition::start'])) {

                        $old          = $this->_rules;
                        $this->_rules = $rules[$structureString . '::condition::start'];
                        $this->_match();
                        $this->_rules = $old;
                    }

                    $opened = 1;

                    do {

                        $nextToken = $this->getToken(++$this->_index, static::TOKEN_VALUE);

                        if(')' === $nextToken)
                            --$opened;
                        elseif('(' === $nextToken)
                            ++$opened;

                    } while(0 < $opened);

                    if(isset($rules[$structureString . '::condition::end'])) {

                        $old          = $this->_rules;
                        $this->_rules = $rules[$structureString . '::condition::end'];
                        $this->_match();
                        $this->_rules = $old;
                    }

                    continue;
                }

                if(   T_CASE    === $tokenId
                   || T_DEFAULT === $tokenId) {

                    $structureString = static::$_structuresAsStrings[$tokenId];

                    while(':' !== $this->getToken(++$this->_index, static::TOKEN_VALUE));

                    if(isset($rules[$structureString . '::start'])) {

                        $old          = $this->_rules;
                        $this->_rules = $rules[$structureString . '::start'];
                        $this->_match();
                        $this->_rules = $old;
                    }

                    continue;
                }

                if(isset($rules['method::body'])) {

                    $old          = $this->_rules;
                    $this->_rules = $rules['method::body'];
                    $this->_match();
                    $this->_rules = $old;
                }
            }

            if('{' === $token[static::TOKEN_VALUE]) {

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

                continue;
            }
            elseif('}' === $token[static::TOKEN_VALUE]) {

                if(2 === $state) {

                    $state = 0;

                    if(isset($rules['class::end'])) {

                        $old          = $this->_rules;
                        $this->_rules = $rules['class::end'];
                        $this->_match();
                        $this->_rules = $old;
                    }

                    $this->_variables['class']['name'] = null;
                }
                elseif(4 === $state) {

                    if(0 >= $blocks->count()) {

                        $state = 2;

                        if(isset($rules['method::end'])) {

                            $old          = $this->_rules;
                            $this->_rules = $rules['method::end'];
                            $this->_match();
                            $this->_rules = $old;
                        }

                        $this->_variables['method']['visibility'] = 'public';
                        $this->_variables['method']['static']     = false;
                        $this->_variables['method']['name']       = null;

                        continue;
                    }

                    $block = $blocks->pop();

                    if(false === in_array($block, static::$_structures))
                        continue;

                    $structureString = static::$_structuresAsStrings[$block];

                    if(   isset($rules[$structureString . '::block::end'])
                       && (T_ELSE    === $block
                       ||  T_WHILE   === $block
                       ||  T_FOR     === $block
                       ||  T_FOREACH === $block)) {

                        $old          = $this->_rules;
                        $this->_rules = $rules[$structureString . '::block::end'];
                        $this->_match();
                        $this->_rules = $old;
                    }
                }

                continue;
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

        $index         = $this->_index;
        $selectedRules = array();

        foreach($this->_rules as $rule) {

            $i       = $index;
            $pattern = $rule[0];
            $length  = 0;

            if(empty($pattern)) {

                $selectedRules[] = array($rule, $length);
                continue;
            }

            for($j = 0, $max = count($pattern) - 1;
                $j <= $max && $i + $j < $this->_max;
                ++$j) {

                if(true === $this->skipable($i + $j)) {

                    ++$i;
                    --$j;
                    ++$length;

                    continue;
                }

                $realToken    = $this->getToken($i + $j, static::TOKEN_VALUE);
                $patternToken = $pattern[$j];

                if($realToken !== $patternToken)
                    continue 2;

                ++$length;
            }

            $selectedRules[] = array($rule, $length);
        }

        foreach($selectedRules as $bucket) {

            list($rule, $length) = $bucket;
            list($pattern, $replace) = $rule;

            if(is_array($replace)) {

                foreach($replace as &$_tokens) {

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
            }
            elseif(is_callable($replace))
                $replace = $replace($this->_variables);

            array_splice(
                $this->_sequence,
                $index,
                $length,
                $replace
            );

            if(   isset($rule[2])
               && static::SHIFT_REPLACEMENT_END === $rule[2])
                $this->_index += count($replace) - 1;

            if(   isset($rule[3])
               && is_callable($rule[3])) {

                $callable = $rule[3];
                $callable($this->_variables);
            }
        }

        $this->_max = count($this->_sequence) - 1;

        return;
    }

    public function tokenExists ( $i ) {

        return isset($this->_sequence[$i]);
    }

    public function &getToken ( $i, $index = self::TOKEN_ALL ) {

        if(!isset($this->_sequence[$i])) {

            $out = null;

            return $out;
        }

        if(!is_array($this->_sequence[$i]))
            $this->_sequence[$i] = array(
                -1,
                $this->_sequence[$i],
                0 < $i ? $this->getToken($i - 1, static::TOKEN_LINE) : 0
            );

        if(static::TOKEN_ALL === $index)
            return $this->_sequence[$i];

        return $this->_sequence[$i][$index];
    }

    public function &getNextSignificantToken ( &$i, $index = self::TOKEN_ALL ) {

        do {

            $token = &$this->getToken(++$i, static::TOKEN_ID);

        } while(   T_WHITESPACE  === $token
                || T_COMMENT     === $token
                || T_DOC_COMMENT === $token);

        return $this->getToken($i, $index);
    }

    protected function skipable ( $index ) {

        return in_array($this->getToken($index, static::TOKEN_ID), $this->_skip);
    }

    public function __toString ( ) {

        $out = null;

        foreach($this->_sequence as $i => $_)
            $out .= $this->getToken($i, static::TOKEN_VALUE);

        return $out;
    }
}
