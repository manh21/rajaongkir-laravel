<?php

namespace Komodo\RajaOngkirLaravel;

use Komodo\RajaongkirLaravel\Commands\RajaongkirLaravelCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Komodo\RajaOngkirLaravel\Commands\RajaOngkirLaravelCommand;

class RajaOngkirLaravelServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('rajaongkir-laravel')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_rajaongkir_laravel_table')
            ->hasCommand(RajaOngkirLaravelCommand::class);
    }
}
