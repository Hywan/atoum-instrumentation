<?php

namespace atoum\instrumentation;

use atoum\instrumentation\exception;

class mole {

    protected static $_moles = array();

    public static function exists ( $id ) {

        return true === array_key_exists(ltrim($id, '\\'), static::$_moles);
    }

    public static function register ( $id, $callable ) {

        return static::$_moles[ltrim($id, '\\')] = $callable;
    }

    public static function unregister ( $id ) {

        unset(static::$_moles[ltrim($id, '\\')]);

        return;
    }

    public static function call ( $id, Array $arguments ) {

        $id = ltrim($id, '\\');

        if(false === static::exists($id))
            throw new exception(vprintf('Call the unregistered mole %s.', $id), 0);

        return call_user_func_array(static::$_moles[$id], $arguments);
    }
}
