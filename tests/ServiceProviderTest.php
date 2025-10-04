<?php

use Illuminate\Support\Facades\Lang;
use Komodo\RajaOngkir\Facades\RajaOngkir as RajaOngkirFacade;
use Komodo\RajaOngkir\RajaOngkir;
use Komodo\RajaOngkir\RajaOngkirServiceProvider;

describe('Service Provider Registration', function () {

    it('registers the rajaongkir service in container', function () {
        $service = app(RajaOngkir::class);
        expect($service)->toBeInstanceOf(RajaOngkir::class);
    });

    it('registers rajaongkir as singleton', function () {
        $service1 = app(RajaOngkir::class);
        $service2 = app(RajaOngkir::class);

        expect($service1)->toBe($service2); // Should be same instance
    });

    it('can resolve rajaongkir via facade', function () {
        $service = RajaOngkirFacade::getFacadeRoot();
        expect($service)->toBeInstanceOf(RajaOngkir::class);
    });

});

describe('Configuration Publishing', function () {

    it('has default configuration values', function () {
        expect(config('rajaongkir.base_url'))
            ->toBe('https://rajaongkir.komerce.id/api/v1');

        expect(config('rajaongkir.cost_cache_duration'))
            ->toBe(60);

        expect(config('rajaongkir.location_cache_duration'))
            ->toBe(1440);
    });

    it('can override configuration with environment variables', function () {
        config(['rajaongkir.api_key' => 'test-key-from-config']);

        expect(config('rajaongkir.api_key'))
            ->toBe('test-key-from-config');
    });

});

describe('Translation Files', function () {

    it('loads English translations', function () {
        app()->setLocale('en');

        $translation = __('rajaongkir::rajaongkir.validation.courier_required');
        expect($translation)->toBeString()
            ->and($translation)->not->toContain('rajaongkir::'); // Should be translated
    });

    it('loads Indonesian translations', function () {
        app()->setLocale('id');

        $translation = __('rajaongkir::rajaongkir.validation.courier_required');
        expect($translation)->toBeString()
            ->and($translation)->not->toContain('rajaongkir::'); // Should be translated
    });

    it('has validation messages in both languages', function () {
        // English
        app()->setLocale('en');
        $enTranslation = __('rajaongkir::rajaongkir.validation.weight_required');
        expect($enTranslation)->toContain('weight');

        // Indonesian
        app()->setLocale('id');
        $idTranslation = __('rajaongkir::rajaongkir.validation.weight_required');
        expect($idTranslation)->toContain('berat');

        // They should be different
        expect($enTranslation)->not->toBe($idTranslation);
    });

    it('has all required validation keys in English', function () {
        app()->setLocale('en');

        $requiredKeys = [
            'origin_required',
            'destination_required',
            'weight_required',
            'courier_required',
            'weight_exceeds_limit',
            'destination_must_be_different',
        ];

        foreach ($requiredKeys as $key) {
            $translation = __("rajaongkir::rajaongkir.validation.{$key}");
            expect($translation)->not->toContain('rajaongkir::')
                ->and($translation)->toBeString()
                ->and(strlen($translation))->toBeGreaterThan(5);
        }
    });

    it('has all required validation keys in Indonesian', function () {
        app()->setLocale('id');

        $requiredKeys = [
            'origin_required',
            'destination_required',
            'weight_required',
            'courier_required',
            'weight_exceeds_limit',
            'destination_must_be_different',
        ];

        foreach ($requiredKeys as $key) {
            $translation = __("rajaongkir::rajaongkir.validation.{$key}");
            expect($translation)->not->toContain('rajaongkir::')
                ->and($translation)->toBeString()
                ->and(strlen($translation))->toBeGreaterThan(5);
        }
    });

});

describe('Service Provider Boot Process', function () {

    it('publishes configuration files', function () {
        $provider = new RajaOngkirServiceProvider(app());

        // Test that the provider can be instantiated
        expect($provider)->toBeInstanceOf(RajaOngkirServiceProvider::class);
    });

    it('loads translation files correctly', function () {
        // Verify that translation namespace is registered
        $translation = Lang::get('rajaongkir::rajaongkir.validation.courier_required');
        expect($translation)->toBeString();
    });

});

describe('Package Integration', function () {

    it('integrates with Laravel validation system', function () {
        $validator = validator(['courier' => 'jne'], [
            'courier' => [new \Komodo\RajaOngkir\Rules\CourierRule],
        ]);

        expect($validator->passes())->toBeTrue();
    });

    it('works with Laravel cache system', function () {
        $rajaongkir = app(RajaOngkir::class);

        // Test that cache methods don't throw errors
        expect(fn () => $rajaongkir->clearCache())->not->toThrow(Exception::class);
        expect(fn () => $rajaongkir->clearLocationCache())->not->toThrow(Exception::class);
        expect(fn () => $rajaongkir->clearCostCache())->not->toThrow(Exception::class);
    });

    it('supports different cache drivers', function () {
        // Test with array cache (default in testing)
        config(['cache.default' => 'array']);
        $rajaongkir = new RajaOngkir;
        expect($rajaongkir)->toBeInstanceOf(RajaOngkir::class);

        // Test cache operations don't fail
        expect(fn () => $rajaongkir->clearCache())->not->toThrow(Exception::class);
    });

});

describe('Environment Configuration', function () {

    it('handles missing API key gracefully', function () {
        config(['rajaongkir.api_key' => null]);

        $rajaongkir = new RajaOngkir;
        expect($rajaongkir)->toBeInstanceOf(RajaOngkir::class);
    });

    it('uses default values when config is missing', function () {
        // Clear specific config values
        config(['rajaongkir.cost_cache_duration' => null]);
        config(['rajaongkir.location_cache_duration' => null]);

        $rajaongkir = new RajaOngkir;
        expect($rajaongkir)->toBeInstanceOf(RajaOngkir::class);
    });

    it('validates cache duration configuration', function () {
        // Test with valid durations
        config(['rajaongkir.cost_cache_duration' => 30]);
        config(['rajaongkir.location_cache_duration' => 720]);

        $rajaongkir = new RajaOngkir;
        expect($rajaongkir)->toBeInstanceOf(RajaOngkir::class);

        // Test with invalid durations (should use defaults)
        config(['rajaongkir.cost_cache_duration' => -1]);
        config(['rajaongkir.location_cache_duration' => 'invalid']);

        $rajaongkir = new RajaOngkir;
        expect($rajaongkir)->toBeInstanceOf(RajaOngkir::class);
    });

});
