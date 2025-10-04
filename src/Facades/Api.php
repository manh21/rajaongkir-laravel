<?php

namespace Komodo\RajaOngkir\Facades;

use Illuminate\Support\Facades\Facade;
use Komodo\RajaOngkir\Services\ApiServices;

/**
 * @see ApiServices
 */
class Api extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ApiServices::class;
    }
}
