<?php

namespace {

from('Hoa')
-> import('Stream.Filter.~')
-> import('Stream.Filter.LateComputed');

}

namespace Hoathis\Instrumentation {

class Filter extends \Hoa\Stream\Filter\LateComputed {

    protected $_tokens = null;

    public function compute ( ) {

        $this->_tokens = token_get_all($this->_buffer);
        $buffer        = null;

        for($i = 0, $max = count($this->_tokens); $i < $max; ++$i)
            $this->consumeToken($i, $buffer);

        $this->_buffer = $buffer;

        return;
    }

    protected function consumeToken ( &$i, &$buffer ) {

        list($_id, $_token, $_line) = $this->getToken($i);

        if(T_IF === $_id) {

            $buffer .= $_token . '(mark_cond(';

            $i += 2;

            while(   ($nextToken = $this->getToken($i++))
                  && ')' !== $nextToken[1]) {

                $buffer .= $nextToken[1];
            }

            $buffer .= ')) ';

            return;
        }
        elseif(T_FUNCTION === $_id) {

            $buffer .= $_token;
            $this->skip($i, $buffer);

            list($_nId, $_nToken, $_nLine) = $this->getToken(++$i);

            if(T_STRING === $_nId) {

                $buffer    .= $_nToken;
                $arguments  = array();

                do {

                    $this->skip($i, $buffer);
                    list($_pId, $_pToken, $_pLine) = $this->getToken(++$i);

                    if(T_VARIABLE === $_pId)
                        $arguments[] = $_pToken;

                    $buffer .= $_pToken;

                } while('{' !== $_pToken);

                $_whitespace = $this->getToken($i + 1);
                $whitespace  = '';

                if(T_WHITESPACE === $_whitespace[0])
                    $whitespace = $_whitespace[1];

                $buffer .= $whitespace . 'if(mole_exist(__CLASS__ . \'::' . $_nToken . '\')) ' .
                           "\n" . '            return mole_call(__CLASS__ . \'::' . $_nToken . '\', ' .
                           'array(' . implode(', ', $arguments ) . '));';
            }

            return;
        }
        elseif(';' === $_token) {

            $buffer .= $_token . ' mark_line(' . $_line . ');';

            return;
        }

        $buffer .= $_token;

        return;
    }

    protected function skip ( &$i, &$buffer ) {

        while(  (        null !== $nextToken = $this->getToken($i + 1))
              && T_WHITESPACE === $nextToken[0]) {

            $buffer .= $nextToken[1];
            ++$i;
        }
    }

    public function getToken ( $i ) {

        if(!isset($this->_tokens[$i]))
            return null;

        if(!is_array($this->_tokens[$i])) {

            $line = -1;

            if(null !== $previousToken = $this->getToken($i - 1))
                $line = $previousToken[2];

            return array(-1, $this->_tokens[$i], $line);
        }

        return $this->_tokens[$i];
    }

    protected function dump ( $token ) {

        $token[0] = token_name($token[0]);
        print_r($token);
    }
}

}
