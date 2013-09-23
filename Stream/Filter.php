<?php

namespace {

from('Hoa')
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

        $rules      = array();
        $parameters = $this->getParameters();

        if(   isset($parameters['nodes'])
           && true === $parameters['nodes'])
            $rules[] = array(
                array('if', '(', …, ')'),
                array('if', '(', 'mark_cond(', '\3', ')', ')'),
                $matching::SHIFT_REPLACEMENT_END
            );

        if(    isset($parameters['edges'])
           && true === $parameters['edges']) {

            $rules[] = array(
                array('return', …, ';'),
                array('mark_line(__LINE__)', ';', 'return ', '\2', ';'),
                $matching::SHIFT_REPLACEMENT_END
            );
            $rules[] = array(
                array(';'),
                array(';', 'mark_line(__LINE__)', ';'),
                $matching::SHIFT_REPLACEMENT_END
            );
        }

        if(   isset($parameters['moles'])
           && true === $parameters['moles'])
            $rules[] = array(
                array('function', …, '(', …, '{'),
                array('function ', '\2', ' ( ', '\4', ' {', ' if(mole_exists(__CLASS__ . \'::\2\')) return mole_call(__CLASS__ . \'::\2\');'),
                $matching::SHIFT_REPLACEMENT_END
            );

        $matching->skip(array(T_WHITESPACE));
        $matching->match($rules);

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
