<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Komodo\RajaOngkir\Constants\Courier;
use Komodo\RajaOngkir\RajaOngkir;
use Komodo\RajaOngkir\Rules\CourierRule;

beforeEach(function () {
    // Set up test configuration
    config([
        'rajaongkir.api_key' => 'test-api-key',
        'rajaongkir.base_url' => 'https://rajaongkir.komerce.id/api/v1',
        'rajaongkir.cost_cache_duration' => 60,
        'rajaongkir.location_cache_duration' => 1440,
        'cache.default' => 'array',
    ]);

    // Clear cache before each test
    Cache::flush();

    $this->rajaongkir = new RajaOngkir();
});

describe('RajaOngkir Main Class', function () {
    
    it('can instantiate RajaOngkir class', function () {
        expect($this->rajaongkir)->toBeInstanceOf(RajaOngkir::class);
    });

    it('can detect cache tagging support', function () {
        // Use reflection to test protected method
        $reflection = new ReflectionClass($this->rajaongkir);
        $method = $reflection->getMethod('cacheSupportsTagging');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->rajaongkir);
        expect($result)->toBeBool();
    });

    it('can get cache instance', function () {
        // Use reflection to test protected method
        $reflection = new ReflectionClass($this->rajaongkir);
        $method = $reflection->getMethod('getCacheInstance');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->rajaongkir, ['test-tag']);
        expect($result)->toBeInstanceOf(\Illuminate\Contracts\Cache\Repository::class);
    });

    it('can set custom cache durations', function () {
        $result = $this->rajaongkir->setLocationCacheDuration(7200);
        expect($result)->toBeInstanceOf(RajaOngkir::class);
        
        $result = $this->rajaongkir->setCostCacheDuration(1800);
        expect($result)->toBeInstanceOf(RajaOngkir::class);
    });

    it('reads cache durations from config', function () {
        config(['rajaongkir.cost_cache_duration' => 30]);
        config(['rajaongkir.location_cache_duration' => 720]);
        
        $rajaongkir = new RajaOngkir();
        expect($rajaongkir)->toBeInstanceOf(RajaOngkir::class);
    });

});

describe('Cache Management', function () {

    it('can clear all cache', function () {
        // This should not throw an exception
        expect(fn() => $this->rajaongkir->clearCache())->not->toThrow(Exception::class);
    });

    it('can clear location cache', function () {
        expect(fn() => $this->rajaongkir->clearLocationCache())->not->toThrow(Exception::class);
    });

    it('can clear cost cache', function () {
        expect(fn() => $this->rajaongkir->clearCostCache())->not->toThrow(Exception::class);
    });

    it('can clear specific location type cache', function () {
        expect(fn() => $this->rajaongkir->clearLocationTypeCache('provinces'))->not->toThrow(Exception::class);
        expect(fn() => $this->rajaongkir->clearLocationTypeCache('cities'))->not->toThrow(Exception::class);
        expect(fn() => $this->rajaongkir->clearLocationTypeCache('districts'))->not->toThrow(Exception::class);
        expect(fn() => $this->rajaongkir->clearLocationTypeCache('subdistricts'))->not->toThrow(Exception::class);
    });

});

describe('API Methods with Mocked Responses', function () {

    beforeEach(function () {
        // Mock successful API responses
        Http::fake([
            '*/province*' => Http::response([
                'status' => 200,
                'data' => [
                    ['id' => 1, 'name' => 'DKI Jakarta'],
                    ['id' => 2, 'name' => 'Jawa Barat'],
                ]
            ], 200),
            
            '*/city/*' => Http::response([
                'status' => 200,
                'data' => [
                    ['id' => 1, 'name' => 'Jakarta Pusat', 'province_id' => 1],
                    ['id' => 2, 'name' => 'Jakarta Selatan', 'province_id' => 1],
                ]
            ], 200),
            
            '*/district/*' => Http::response([
                'status' => 200,
                'data' => [
                    ['id' => 1, 'name' => 'Gambir', 'city_id' => 1],
                    ['id' => 2, 'name' => 'Tanah Abang', 'city_id' => 1],
                ]
            ], 200),
            
            '*/subdistrict/*' => Http::response([
                'status' => 200,
                'data' => [
                    ['id' => 1, 'name' => 'Gambir', 'district_id' => 1],
                    ['id' => 2, 'name' => 'Cideng', 'district_id' => 2],
                ]
            ], 200),
            
            '*/calculate/*' => Http::response([
                'status' => 200,
                'data' => [
                    'origin' => 1,
                    'destination' => 2,
                    'costs' => [
                        [
                            'service' => 'REG',
                            'cost' => 9000,
                            'etd' => '2-3'
                        ]
                    ]
                ]
            ], 200),
            
            '*/destination/*' => Http::response([
                'status' => 200,
                'data' => [
                    ['id' => 1, 'name' => 'Jakarta Pusat', 'type' => 'city'],
                    ['id' => 2, 'name' => 'Bandung', 'type' => 'city'],
                ]
            ], 200),
            
            '*/track/*' => Http::response([
                'status' => 200,
                'data' => [
                    'waybill' => 'TEST123456',
                    'courier' => 'jne',
                    'status' => 'delivered'
                ]
            ], 200),
        ]);
    });

    it('can get provinces', function () {
        $result = $this->rajaongkir->getProvinces();
        
        expect($result)->toBeArray()
            ->and($result)->toHaveCount(2)
            ->and($result[0])->toHaveKey('id')
            ->and($result[0])->toHaveKey('name');
    });

    it('can get cities by province', function () {
        $result = $this->rajaongkir->getCities(1);
        
        expect($result)->toBeArray()
            ->and($result)->toHaveCount(2)
            ->and($result[0])->toHaveKey('id')
            ->and($result[0])->toHaveKey('name')
            ->and($result[0])->toHaveKey('province_id');
    });

    it('can get districts by city', function () {
        $result = $this->rajaongkir->getDistricts(1);
        
        expect($result)->toBeArray()
            ->and($result)->toHaveCount(2)
            ->and($result[0])->toHaveKey('id')
            ->and($result[0])->toHaveKey('name')
            ->and($result[0])->toHaveKey('city_id');
    });

    it('can get subdistricts by district', function () {
        $result = $this->rajaongkir->getSubdistricts(1);
        
        expect($result)->toBeArray()
            ->and($result)->toHaveCount(2)
            ->and($result[0])->toHaveKey('id')
            ->and($result[0])->toHaveKey('name')
            ->and($result[0])->toHaveKey('district_id');
    });

    it('can search domestic destinations', function () {
        $result = $this->rajaongkir->searchDomesticDestinations('Jakarta', 10, 0);
        
        expect($result)->toBeArray()
            ->and($result)->toHaveCount(2)
            ->and($result[0])->toHaveKey('id')
            ->and($result[0])->toHaveKey('name')
            ->and($result[0])->toHaveKey('type');
    });

    it('can search international destinations', function () {
        $result = $this->rajaongkir->searchInternationalDestinations('Singapore', 10, 0);
        
        expect($result)->toBeArray()
            ->and($result)->toHaveCount(2)
            ->and($result[0])->toHaveKey('id')
            ->and($result[0])->toHaveKey('name')
            ->and($result[0])->toHaveKey('type');
    });

});