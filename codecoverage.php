<?php

namespace atoum\instrumentation;

class codecoverage {

    protected static $_scores = array();


    public static function export ( Array $export ) {

        $scores = &static::$_scores;

        foreach($export as $method => $markerCount) {

            $scores[$method] = new \SplFixedArray($markerCount);
            $scoresMethod    = &$scores[$method];

            foreach($scoresMethod as $index => $_)
                $scoresMethod[$index] = false;
        }

        return;
    }

    public static function mark ( $id, $index ) {

        if(isset(static::$_scores[$id]))
            static::$_scores[$id][$index] = true;
    }

    public static function markCondition ( $id, $index, $condition ) {

        if(isset(static::$_scores[$id]))
            static::$_scores[$id][$index] =    static::$_scores[$id][$index]
                                            || true == $condition;

        return $condition;
    }

    public static function markJoin ( $id, $index ) {

        if(isset(static::$_scores[$id]))
            static::$_scores[$id][$index] = true;

        return;
    }

    public static function getScore ( $id ) {

        if(false === @preg_match($id, '')) {

            if(!isset(static::$_scores[$id]))
                throw new exception(sprintf('Method %s does not exist.', $id), 0);

            $iterator = array(static::$_scores[$id]);
        }
        else
            $iterator = new \RegexIterator(
                new \ArrayIterator(static::$_scores),
                $id,
                \RegexIterator::MATCH,
                \RegexIterator::USE_KEY
            );

        $count = 0;
        $total = 0;

        foreach($iterator as $score)
            foreach($score as $value) {

                $count += (int) (true === $value);
                ++$total;
            }

        if(0 === $total)
            return 0;

        return $count / $total;
    }

    public static function getRawScores ( ) {

        return static::$_scores;
    }

    public static function reset ( $soft = true ) {

        if(false === $soft) {

            static::$_scores = array();

            return;
        }

        foreach(static::$_scores as $score)
            foreach($score as $marker => $_)
                $score[$marker] = false;

        return;
    }
}
