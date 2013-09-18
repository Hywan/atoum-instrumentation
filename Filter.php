<?php

namespace {

from('Hoa')
-> import('Stream.Filter.~')
-> import('Stream.Filter.LateComputed');

from('Hoathis')
-> import('Instrumentation.Sequence.Matching');

}

namespace Hoathis\Instrumentation {

class Filter extends \Hoa\Stream\Filter\LateComputed {

    protected $_tokens = null;

    public function compute ( ) {

        $m = new Sequence\Matching(token_get_all($this->_buffer));
        $m->skip(array(T_WHITESPACE));
        $m->match(array(
            array(
                array('if', '(', …, ')'),
                array('if', '(', 'mark_cond(', '\3', ')', ')')
            ),
            array(
                array('return', …, ';'),
                array('mark_line(__LINE__)', ';', 'return ', '\2', ';'),
                $m::SHIFT_REPLACEMENT_END
            ),
            array(
                array(';'),
                array(';', 'mark_line(__LINE__)', ';'),
                $m::SHIFT_REPLACEMENT_END
            )
        ));

        $buffer = null;

        foreach($m->getSequence() as $token)
            if(is_array($token))
                echo $token[1];
            else
                echo $token;

        $this->_buffer = $buffer;

        return;
    }
}

}
