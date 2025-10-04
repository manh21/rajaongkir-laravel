<?php

use Illuminate\Support\Facades\Http;
use Komodo\RajaOngkir\RajaOngkir;
use Komodo\RajaOngkir\Constants\Courier;

beforeEach(function () {
    config([
        'rajaongkir.api_key' => 'test-api-key',
        'rajaongkir.base_url' => 'https://rajaongkir.komerce.id/api/v1',
        'cache.default' => 'array',
    ]);
});

describe('API Services Type Safety', function () {

    it('handles API responses without type errors', function () {
        // Mock a successful API response
        Http::fake([
            '*/province*' => Http::response([
                'status' => 200,
                'data' => [
                    ['id' => 1, 'name' => 'DKI Jakarta'],
                    ['id' => 2, 'name' => 'Jawa Barat'],
                ]
            ], 200),
        ]);

        $rajaongkir = new RajaOngkir();
        
        // This should work without type errors
        $result = $rajaongkir->getProvinces();
        
        expect($result)->toBeArray()
            ->and($result)->toHaveCount(2)
            ->and($result[0])->toHaveKey('id')
            ->and($result[0])->toHaveKey('name');
            
        Http::assertSentCount(1);
    });

    it('handles validation errors properly', function () {
        $rajaongkir = new RajaOngkir();
        
        // Test validation without making API calls
        expect(function () use ($rajaongkir) {
            $rajaongkir->calculateDistrictCost(
                originId: 0,  // Invalid - should trigger validation error
                destinationId: 1,
                weight: 1000,
                courier: [Courier::JNE]
            );
        })->toThrow(\Illuminate\Validation\ValidationException::class);
    });

    it('can clear cache without errors', function () {
        $rajaongkir = new RajaOngkir();
        
        // These should work with any cache driver
        expect(fn() => $rajaongkir->clearCache())->not->toThrow(Exception::class);
        expect(fn() => $rajaongkir->clearLocationCache())->not->toThrow(Exception::class);
        expect(fn() => $rajaongkir->clearCostCache())->not->toThrow(Exception::class);
    });

});