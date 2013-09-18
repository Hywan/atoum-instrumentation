<?php

namespace {

from('Hoa')
-> import('Stream.Filter.~')
-> import('Stream.Filter.LateComputed');

from('Hoathis')
-> import('Instrumentation.Sequence.Matching');

}

namespace Hoathis\Instrumentation\Stream {

class Filter extends \Hoa\Stream\Filter\LateComputed {

    public function compute ( ) {

        $matching = new \Hoathis\Instrumentation\Sequence\Matching(
            token_get_all($this->_buffer)
        );
        $matching->skip(array(T_WHITESPACE));
        $matching->match(array(
            array(
                array('if', '(', …, ')'),
                array('if', '(', 'mark_cond(', '\3', ')', ')')
            ),
            array(
                array('return', …, ';'),
                array('mark_line(__LINE__)', ';', 'return ', '\2', ';'),
                $matching::SHIFT_REPLACEMENT_END
            ),
            array(
                array(';'),
                array(';', 'mark_line(__LINE__)', ';'),
                $matching::SHIFT_REPLACEMENT_END
            )
        ));

        $buffer = null;

        foreach($matching->getSequence() as $token)
            if(is_array($token))
                echo $token[$matching::TOKEN_VALUE];
            else
                echo $token;

        $this->_buffer = $buffer;

        return;
    }
}

}
