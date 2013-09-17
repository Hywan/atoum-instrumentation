<?php

namespace Hoathis\Instrumentation\Sequence {

class Matching {

    protected $_sequence = null;
    protected $_index    = 0;
    protected $_max      = 0;



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

    public function searchAndReplace ( $rules ) {

        foreach($rules 
    }
}

}
