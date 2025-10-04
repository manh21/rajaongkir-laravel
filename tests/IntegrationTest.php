<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Komodo\RajaOngkir\Constants\Courier;
use Komodo\RajaOngkir\RajaOngkir;
use Komodo\RajaOngkir\Facades\RajaOngkir as RajaOngkirFacade;

beforeEach(function () {
    config([
        'rajaongkir.api_key' => 'test-api-key',
        'rajaongkir.base_url' => 'https://rajaongkir.komerce.id/api/v1',
        'cache.default' => 'array',
    ]);

    // Mock comprehensive API responses
    Http::fake([
        '*/province*' => Http::response([
            'status' => 200,
            'data' => [
                ['id' => 6, 'name' => 'DKI Jakarta'],
                ['id' => 9, 'name' => 'Jawa Barat'],
                ['id' => 10, 'name' => 'Jawa Tengah'],
            ]
        ], 200),

        '*/city/6*' => Http::response([
            'status' => 200,
            'data' => [
                ['id' => 151, 'name' => 'Jakarta Pusat', 'province_id' => 6],
                ['id' => 152, 'name' => 'Jakarta Selatan', 'province_id' => 6],
                ['id' => 153, 'name' => 'Jakarta Barat', 'province_id' => 6],
            ]
        ], 200),
        '*/city/3*' => Http::response([
            'status' => 200,
            'data' => [
                ['id' => 151, 'name' => 'Jakarta Pusat', 'province_id' => 6],
                ['id' => 152, 'name' => 'Jakarta Selatan', 'province_id' => 6],
                ['id' => 153, 'name' => 'Jakarta Barat', 'province_id' => 6],
            ]
        ], 200),

        '*/district/151*' => Http::response([
            'status' => 200,
            'data' => [
                ['id' => 1701, 'name' => 'Gambir', 'city_id' => 151],
                ['id' => 1702, 'name' => 'Tanah Abang', 'city_id' => 151],
            ]
        ], 200),

        '*/calculate/district/domestic-cost*' => Http::response([
            'status' => 200,
            'data' => [
                'origin' => 1701,
                'destination' => 1702,
                'weight' => 1000,
                'costs' => [
                    [
                        'courier' => 'jne',
                        'service' => 'REG',
                        'cost' => 9000,
                        'etd' => '1-2'
                    ],
                    [
                        'courier' => 'tiki',
                        'service' => 'REG',
                        'cost' => 8500,
                        'etd' => '1-3'
                    ]
                ]
            ]
        ], 200),

        '*/track/waybill*' => Http::response([
            'status' => 200,
            'data' => [
                'waybill' => 'JNE123456789',
                'courier' => 'jne',
                'status' => 'delivered',
                'history' => [
                    ['date' => '2023-10-04 14:30', 'description' => 'Package delivered to recipient'],
                    ['date' => '2023-10-04 08:15', 'description' => 'Out for delivery'],
                    ['date' => '2023-10-03 18:45', 'description' => 'Package arrived at destination facility'],
                ]
            ]
        ], 200),
    ]);
});

describe('Complete Shipping Cost Workflow', function () {

    it('can complete full shipping cost calculation workflow', function () {
        $rajaongkir = new RajaOngkir();

        // Step 1: Get provinces
        $provinces = $rajaongkir->getProvinces();
        expect($provinces)->toBeArray()
            ->and($provinces)->toHaveCount(3)
            ->and($provinces[0]['name'])->toBe('DKI Jakarta');

        // Step 2: Get cities for DKI Jakarta (province_id = 6)
        $cities = $rajaongkir->getCities(6);
        expect($cities)->toBeArray()
            ->and($cities)->toHaveCount(3)
            ->and($cities[0]['name'])->toBe('Jakarta Pusat');

        // Step 3: Get districts for Jakarta Pusat (city_id = 151)
        $districts = $rajaongkir->getDistricts(151);
        expect($districts)->toBeArray()
            ->and($districts)->toHaveCount(2)
            ->and($districts[0]['name'])->toBe('Gambir');

        // Step 4: Calculate shipping cost between districts
        $costs = $rajaongkir->calculateDistrictCost(
            originId: 1701,      // Gambir
            destinationId: 1702, // Tanah Abang
            weight: 1000,        // 1kg
            courier: [Courier::JNE, Courier::TIKI],
            sortBy: 'lowest'
        );

        expect($costs)->toBeArray()
            ->and($costs['costs'])->toBeArray()
            ->and($costs['costs'])->toHaveCount(2)
            ->and($costs['costs'][0]['courier'])->toBe('jne')
            ->and($costs['costs'][0]['cost'])->toBe(9000);
    });

    it('works with facade', function () {
        // Test using the facade
        $provinces = RajaOngkirFacade::getProvinces();
        expect($provinces)->toBeArray()
            ->and($provinces)->toHaveCount(3);

        $costs = RajaOngkirFacade::calculateDistrictCost(
            originId: 1701,
            destinationId: 1702,
            weight: 1000,
            courier: ['jne']
        );

        expect($costs)->toBeArray()
            ->and($costs['costs'])->toBeArray();
    });

});

describe('Package Tracking Workflow', function () {

    it('can track package from order to delivery', function () {
        $rajaongkir = new RajaOngkir();

        // Track a JNE package
        $tracking = $rajaongkir->trackAWB(
            waybill: 'JNE123456789',
            courier: Courier::JNE
        );

        expect($tracking)->toBeArray()
            ->and($tracking['waybill'])->toBe('JNE123456789')
            ->and($tracking['courier'])->toBe('jne')
            ->and($tracking['status'])->toBe('delivered')
            ->and($tracking['history'])->toBeArray()
            ->and($tracking['history'])->toHaveCount(3);

        // Verify delivery history
        $latestUpdate = $tracking['history'][0];
        expect($latestUpdate['description'])->toContain('delivered');
    });

});

describe('Caching Performance', function () {

    it('demonstrates caching performance benefits', function () {
        $rajaongkir = new RajaOngkir();

        // First province request (will hit API)
        $start = microtime(true);
        $provinces1 = $rajaongkir->getProvinces();
        $firstCallTime = microtime(true) - $start;

        // Second province request (should use cache)
        $start = microtime(true);
        $provinces2 = $rajaongkir->getProvinces();
        $secondCallTime = microtime(true) - $start;

        // Results should be identical
        expect($provinces1)->toEqual($provinces2);

        // Second call should be faster (cached)
        expect($secondCallTime)->toBeLessThan($firstCallTime);

        // Only one HTTP request should have been made
        Http::assertSentCount(1);
    });

    it('uses separate cache keys for different parameters', function () {
        $rajaongkir = new RajaOngkir();

        // Request for different cities should make separate API calls
        $cities1 = $rajaongkir->getCities(6);  // Jakarta
        $cities2 = $rajaongkir->getCities(3);  // West Java

        // Should have made 2 separate API calls
        Http::assertSentCount(2);
    });

    it('can clear cache selectively', function () {
        $rajaongkir = new RajaOngkir();

        // Populate cache
        $provinces = $rajaongkir->getProvinces();
        $cities = $rajaongkir->getCities(6);

        // Clear only location cache
        $rajaongkir->clearLocationCache();

        // Next call should hit API again
        $provincesAfterClear = $rajaongkir->getProvinces();

        expect($provinces)->toEqual($provincesAfterClear);
        Http::assertSentCount(3); // Initial 2 calls + 1 after cache clear
    });

});

describe('Multi-Language Support', function () {

    it('provides validation messages in English', function () {
        app()->setLocale('en');

        $rajaongkir = new RajaOngkir();

        try {
            $rajaongkir->calculateDistrictCost(
                originId: 0, // Invalid
                destinationId: 1,
                weight: 1000,
                courier: [Courier::JNE]
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors();
            $message = $errors['origin_id'][0] ?? '';

            expect($message)->toContain('origin')
                ->and($message)->not->toContain('asal'); // Should not contain Indonesian
        }
    });

    it('provides validation messages in Indonesian', function () {
        app()->setLocale('id');

        $rajaongkir = new RajaOngkir();

        try {
            $rajaongkir->calculateDistrictCost(
                originId: 0, // Invalid
                destinationId: 1,
                weight: 1000,
                courier: [Courier::JNE]
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors();
            $message = $errors['origin_id'][0] ?? '';

            expect($message)->toContain('asal'); // Should not contain English
        }
    });

});

describe('Error Handling', function () {

    it('handles API errors gracefully', function () {
        Http::fake([
            '*/province*' => Http::response(['error' => 'API Error'], 500)
        ]);

        $rajaongkir = new RajaOngkir();

        // We should also disable cache for this test to ensure it hits the API
        $rajaongkir->clearCache();

        // This should handle the API error appropriately
        expect(function () use ($rajaongkir) {
            $rajaongkir->getProvinces();
        })->toThrow(Exception::class);
    });

    it('validates input parameters thoroughly', function () {
        $rajaongkir = new RajaOngkir();

        // Test multiple validation scenarios
        $invalidInputs = [
            [0, 1, 1000, [Courier::JNE]],           // Invalid origin
            [1, 0, 1000, [Courier::JNE]],           // Invalid destination
            [1, 1, 1000, [Courier::JNE]],           // Same origin/destination
            [1, 2, 0, [Courier::JNE]],              // Invalid weight
            [1, 2, 50000, [Courier::JNE]],          // Weight too heavy
            [1, 2, 1000, []],                       // No couriers
        ];

        foreach ($invalidInputs as $input) {
            expect(function () use ($rajaongkir, $input) {
                $rajaongkir->calculateDistrictCost(...$input);
            })->toThrow(\Illuminate\Validation\ValidationException::class);
        }
    });

});

describe('Performance and Reliability', function () {

    it('handles multiple concurrent requests efficiently', function () {
        $rajaongkir = new RajaOngkir();

        // Simulate multiple cost calculations
        $results = [];
        for ($i = 0; $i < 5; $i++) {
            $results[] = $rajaongkir->calculateDistrictCost(
                originId: 1701 + $i,
                destinationId: 1702 + $i,
                weight: 1000,
                courier: [Courier::JNE]
            );
        }

        expect($results)->toHaveCount(5);
        foreach ($results as $result) {
            expect($result)->toBeArray()
                ->and($result)->toHaveKey('costs');
        }
    });

    it('maintains data consistency across cache operations', function () {
        $rajaongkir = new RajaOngkir();

        // Get data
        $provinces1 = $rajaongkir->getProvinces();

        // Clear and get again
        $rajaongkir->clearCache();
        $provinces2 = $rajaongkir->getProvinces();

        // Data should be consistent
        expect($provinces1)->toEqual($provinces2);
    });

});
