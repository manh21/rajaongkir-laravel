<?php

namespace Komodo\RajaOngkir;

use Komodo\RajaOngkir\Commands\RajaOngkirCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class RajaOngkirServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('rajaongkir')
            ->hasConfigFile('rajaongkir')
            ->hasViews()
            ->hasMigration('create_rajaongkir_table')
            ->hasCommand(RajaOngkirCommand::class);
    }
}
