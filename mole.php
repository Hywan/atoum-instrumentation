<?php

namespace atoum\instrumentation;

class mole {

    protected static $_moles = array();

    public static function computeId ( $id, $forceHash = false ) {

        if(is_string($id))
            $id = explode('::', ltrim($id, '\\'));

        list($class, $method) = $id;

        if(is_object($class)) {

            $hash = '#' . spl_object_hash($class);

            if(false === $forceHash && !isset(static::$_moles[$hash]))
                $class = get_class($class);
            else
                $class = $hash;
        }

        return array($class, $method);
    }

    public static function exists ( $id ) {

        $callable = static::computeId($id);

        return    isset(static::$_moles[$callable[0]])
               && isset(static::$_moles[$callable[0]][$callable[1]]);
    }

    public static function register ( $id, $mole ) {

        $callable = static::computeId($id, true);

        return static::$_moles[$callable[0]][$callable[1]] = $mole;
    }

    public static function unregister ( $id ) {

        $callable = static::computeId($id);

        unset(static::$_moles[$callable[0]][$callable[1]]);

        if(   isset(static::$_moles[$callable[0]][$callable[1]])
           && empty(static::$_moles[$callable[0]]))
            unset(static::$_moles[$callable[0]]);

        return;
    }

    public static function call ( $id, Array $arguments ) {

        $callable = static::computeId($id);
        $mole     = static::$_moles[$callable[0]][$callable[1]];

        if(   $mole instanceof \Closure
           && true === is_object($id[0])
           && true === method_exists($mole, 'bindTo'))
            $mole = $mole->bindTo($id[0], get_class($id[0]));

        return call_user_func_array($mole, $arguments);
    }
}
