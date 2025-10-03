<?php

namespace Komodo\RajaOngkirLaravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Komodo\RajaOngkirLaravel\RajaOngkirLaravel
 */
class RajaOngkirLaravel extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Komodo\RajaOngkirLaravel\RajaOngkirLaravel::class;
    }
}
