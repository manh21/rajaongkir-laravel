<?php

namespace Komodo\RajaongkirLaravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Komodo\RajaongkirLaravel\RajaongkirLaravel
 */
class RajaongkirLaravel extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Komodo\RajaongkirLaravel\RajaongkirLaravel::class;
    }
}
