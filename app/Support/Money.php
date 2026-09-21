<?php

namespace App\Support;

class Money
{
    /** Convert toman (store/display unit) to rials for Iranian payment gateways. */
    public static function tomanToRials(int $toman): int
    {
        return $toman * 10;
    }
}
