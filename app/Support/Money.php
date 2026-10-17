<?php

namespace App\Support;

class Money
{
    /**
     * Internal store/display currency for amounts kept in toman.
     * Gateways and public schema that declare IRR must convert via tomanToRials().
     */
    public const STORE_CURRENCY = 'IRT';

    /** Convert toman (store/display unit) to rials for Iranian payment gateways. */
    public static function tomanToRials(int $toman): int
    {
        return $toman * 10;
    }
}
