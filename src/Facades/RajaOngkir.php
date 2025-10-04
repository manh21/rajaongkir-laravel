<?php

namespace Komodo\RajaOngkir\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Komodo\RajaOngkir\RajaOngkir
 */
class RajaOngkir extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Komodo\RajaOngkir\RajaOngkir::class;
    }
}
