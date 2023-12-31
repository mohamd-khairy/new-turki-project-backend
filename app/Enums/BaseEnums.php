<?php


namespace App\Enums;


class BaseEnums
{
    public static function get($key)
    {
        return static::$values[$key];
    }

    public static function getKey($value)
    {
        $keys = array_flip(static::$values);

        return $keys[$value];
    }

    public static function list()
    {
        return static::$values;
    }
}
