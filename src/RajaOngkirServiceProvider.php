<?php

namespace Komodo\RajaOngkir;

use Komodo\RajaOngkir\Commands\RajaOngkirCommand;
use Komodo\RajaOngkir\Services\ApiServices;
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
            ->hasTranslations()
            ->hasMigration('create_rajaongkir_table')
            ->hasCommand(RajaOngkirCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(RajaOngkir::class, function ($app) {
            return new RajaOngkir;
        });

        $this->app->singleton('rajaongkir', function ($app) {
            return $app->make(RajaOngkir::class);
        });

        $this->app->singleton(ApiServices::class, function ($app) {
            return new ApiServices;
        });
    }
}
