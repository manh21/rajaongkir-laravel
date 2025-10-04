<?php

use Komodo\RajaOngkir\Constants\Courier;
use Komodo\RajaOngkir\RajaOngkir;
use Komodo\RajaOngkir\Rules\CourierRule;

beforeEach(function () {
    config([
        'rajaongkir.api_key' => 'test-api-key',
        'rajaongkir.base_url' => 'https://rajaongkir.komerce.id/api/v1',
        'cache.default' => 'array',
    ]);
});

describe('Basic Package Functionality', function () {

    it('can instantiate RajaOngkir class', function () {
        $rajaongkir = new RajaOngkir();
        expect($rajaongkir)->toBeInstanceOf(RajaOngkir::class);
    });

    it('can instantiate CourierRule', function () {
        $rule = new CourierRule();
        expect($rule)->toBeInstanceOf(CourierRule::class);
    });

    it('can work with Courier enum', function () {
        expect(Courier::JNE->value)->toBe('jne');
        expect(Courier::TIKI->value)->toBe('tiki');
        expect(Courier::SICEPAT->value)->toBe('sicepat');
    });

    it('courier rule validates valid courier strings using Laravel validator', function () {
        $validator = validator(['courier' => 'jne'], ['courier' => new CourierRule()]);
        expect($validator->passes())->toBeTrue();
        
        $validator = validator(['courier' => 'tiki'], ['courier' => new CourierRule()]);
        expect($validator->passes())->toBeTrue();
        
        $validator = validator(['courier' => 'sicepat'], ['courier' => new CourierRule()]);
        expect($validator->passes())->toBeTrue();
    });

    it('courier rule validates valid courier enums using Laravel validator', function () {
        $validator = validator(['courier' => Courier::JNE], ['courier' => new CourierRule()]);
        expect($validator->passes())->toBeTrue();
        
        $validator = validator(['courier' => Courier::TIKI], ['courier' => new CourierRule()]);
        expect($validator->passes())->toBeTrue();
        
        $validator = validator(['courier' => Courier::SICEPAT], ['courier' => new CourierRule()]);
        expect($validator->passes())->toBeTrue();
    });

    it('courier rule rejects invalid courier using Laravel validator', function () {
        $validator = validator(['courier' => 'invalid'], ['courier' => new CourierRule()]);
        expect($validator->passes())->toBeFalse();
        
        $validator = validator(['courier' => 'xyz'], ['courier' => new CourierRule()]);
        expect($validator->passes())->toBeFalse();
        
        $validator = validator(['courier' => null], ['courier' => new CourierRule()]);
        expect($validator->passes())->toBeFalse();
    });

    it('can convert courier enums to values', function () {
        $couriers = [Courier::JNE, Courier::TIKI];
        $values = CourierRule::convertCouriersToValues($couriers);
        
        expect($values)->toBe(['jne', 'tiki']);
    });

    it('can convert mixed courier array to values', function () {
        $couriers = [Courier::JNE, 'tiki', Courier::SICEPAT];
        $values = CourierRule::convertCouriersToValues($couriers);
        
        expect($values)->toBe(['jne', 'tiki', 'sicepat']);
    });

    it('validates courier arrays correctly', function () {
        expect(CourierRule::validateCouriers(['jne', 'tiki']))->toBeTrue();
        expect(CourierRule::validateCouriers(['jne', 'invalid']))->toBeFalse();
        expect(CourierRule::validateCouriers([]))->toBeFalse();
    });

});

describe('Cache Support Detection', function () {

    it('can detect cache tagging support', function () {
        $rajaongkir = new RajaOngkir();
        
        // Use reflection to test protected method
        $reflection = new ReflectionClass($rajaongkir);
        $method = $reflection->getMethod('cacheSupportsTagging');
        $method->setAccessible(true);
        
        $result = $method->invoke($rajaongkir);
        expect($result)->toBeBool();
    });

    it('can get cache instance', function () {
        $rajaongkir = new RajaOngkir();
        
        // Use reflection to test protected method
        $reflection = new ReflectionClass($rajaongkir);
        $method = $reflection->getMethod('getCacheInstance');
        $method->setAccessible(true);
        
        $result = $method->invoke($rajaongkir, ['test-tag']);
        expect($result)->toBeInstanceOf(\Illuminate\Contracts\Cache\Repository::class);
    });

    it('cache clearing methods do not throw errors', function () {
        $rajaongkir = new RajaOngkir();
        
        expect(fn() => $rajaongkir->clearCache())->not->toThrow(Exception::class);
        expect(fn() => $rajaongkir->clearLocationCache())->not->toThrow(Exception::class);
        expect(fn() => $rajaongkir->clearCostCache())->not->toThrow(Exception::class);
        expect(fn() => $rajaongkir->clearLocationTypeCache('provinces'))->not->toThrow(Exception::class);
    });

});

describe('Configuration', function () {

    it('reads configuration correctly', function () {
        expect(config('rajaongkir.api_key'))->toBe('test-api-key');
        expect(config('rajaongkir.base_url'))->toBe('https://rajaongkir.komerce.id/api/v1');
    });

    it('can set cache durations', function () {
        $rajaongkir = new RajaOngkir();
        
        $result1 = $rajaongkir->setLocationCacheDuration(7200);
        expect($result1)->toBeInstanceOf(RajaOngkir::class);
        
        $result2 = $rajaongkir->setCostCacheDuration(1800);
        expect($result2)->toBeInstanceOf(RajaOngkir::class);
    });

    it('handles missing cache configuration gracefully', function () {
        config(['rajaongkir.cost_cache_duration' => null]);
        config(['rajaongkir.location_cache_duration' => null]);
        
        $rajaongkir = new RajaOngkir();
        expect($rajaongkir)->toBeInstanceOf(RajaOngkir::class);
    });

});

describe('Service Provider Integration', function () {

    it('registers service in container', function () {
        $service = app(RajaOngkir::class);
        expect($service)->toBeInstanceOf(RajaOngkir::class);
    });

    it('service is singleton', function () {
        $service1 = app(RajaOngkir::class);
        $service2 = app(RajaOngkir::class);
        
        expect($service1)->toBe($service2);
    });

    it('configuration is loaded', function () {
        expect(config('rajaongkir'))->toBeArray();
    });

});