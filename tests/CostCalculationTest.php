<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Komodo\RajaOngkir\Constants\Courier;
use Komodo\RajaOngkir\RajaOngkir;

beforeEach(function () {
    config([
        'rajaongkir.api_key' => 'test-api-key',
        'rajaongkir.base_url' => 'https://rajaongkir.komerce.id/api/v1',
        'cache.default' => 'array',
    ]);

    $this->rajaongkir = new RajaOngkir();

    // Mock successful cost calculation response
    Http::fake([
        '*/province' => Http::response([
            'status' => 200,
            'data' => [
                ['province_id' => 1, 'province' => 'Province A'],
                ['province_id' => 2, 'province' => 'Province B']
            ]
        ], 200),
        '*/cities/*' => Http::response([
            'status' => 200,
            'data' => [
                ['city_id' => 1, 'city_name' => 'City A'],
                ['city_id' => 2, 'city_name' => 'City B']
            ]
        ], 200),
        '*/subdistricts/*' => Http::response([
            'status' => 200,
            'data' => [
                ['subdistrict_id' => 1, 'subdistrict_name' => 'Subdistrict'],
                ['subdistrict_id' => 2, 'subdistrict_name' => 'Subdistrict B'],
            ]
        ], 200),
        '*/calculate/*' => Http::response([
            'status' => 200,
            'data' => [
                'origin' => 1,
                'destination' => 2,
                'weight' => 1000,
                'costs' => [
                    [
                        'courier' => 'jne',
                        'service' => 'REG',
                        'cost' => 9000,
                        'etd' => '2-3'
                    ],
                    [
                        'courier' => 'tiki',
                        'service' => 'REG',
                        'cost' => 8500,
                        'etd' => '2-4'
                    ]
                ]
            ]
        ], 200),

        '*/track/*' => Http::response([
            'status' => 200,
            'data' => [
                'waybill' => 'TEST123456',
                'courier' => 'jne',
                'status' => 'delivered',
                'history' => [
                    ['date' => '2023-01-01', 'description' => 'Package delivered']
                ]
            ]
        ], 200),
    ]);
});

describe('Cost Calculation Methods', function () {

    it('can calculate district cost with enum couriers', function () {
        $result = $this->rajaongkir->calculateDistrictCost(
            originId: 1,
            destinationId: 2,
            weight: 1000,
            courier: [Courier::JNE, Courier::TIKI],
            sortBy: 'lowest'
        );

        expect($result)->toBeArray()
            ->and($result)->toHaveKey('costs')
            ->and($result['costs'])->toBeArray();
    });

    it('can calculate district cost with string couriers', function () {
        $result = $this->rajaongkir->calculateDistrictCost(
            originId: 1,
            destinationId: 2,
            weight: 1000,
            courier: ['jne', 'tiki'],
            sortBy: 'lowest'
        );

        expect($result)->toBeArray()
            ->and($result)->toHaveKey('costs')
            ->and($result['costs'])->toBeArray();
    });

    it('can calculate domestic cost', function () {
        $result = $this->rajaongkir->calculateDomesticCost(
            originId: 1,
            destinationId: 2,
            weight: 1000,
            courier: [Courier::JNE],
            sortBy: 'lowest'
        );

        expect($result)->toBeArray()
            ->and($result)->toHaveKey('costs');
    });

    it('can calculate international cost', function () {
        $result = $this->rajaongkir->calculateInternationalCost(
            originId: 1,
            destinationId: 2,
            weight: 1000,
            courier: [Courier::JNE],
            sortBy: 'lowest'
        );

        expect($result)->toBeArray()
            ->and($result)->toHaveKey('costs');
    });

});

describe('Cost Calculation Validation', function () {

    it('validates origin_id is required', function () {
        expect(function () {
            $this->rajaongkir->calculateDistrictCost(
                originId: 0, // Invalid
                destinationId: 2,
                weight: 1000,
                courier: [Courier::JNE]
            );
        })->toThrow(ValidationException::class);
    });

    it('validates destination_id is required and different from origin', function () {
        expect(function () {
            $this->rajaongkir->calculateDistrictCost(
                originId: 1,
                destinationId: 1, // Same as origin
                weight: 1000,
                courier: [Courier::JNE]
            );
        })->toThrow(ValidationException::class);
    });

    it('validates weight is required and positive', function () {
        expect(function () {
            $this->rajaongkir->calculateDistrictCost(
                originId: 1,
                destinationId: 2,
                weight: 0, // Invalid
                courier: [Courier::JNE]
            );
        })->toThrow(ValidationException::class);
    });

    it('validates weight does not exceed limit', function () {
        expect(function () {
            $this->rajaongkir->calculateDistrictCost(
                originId: 1,
                destinationId: 2,
                weight: 35000, // Exceeds 30kg limit
                courier: [Courier::JNE]
            );
        })->toThrow(ValidationException::class);
    });

    it('validates courier is required array', function () {
        expect(function () {
            $this->rajaongkir->calculateDistrictCost(
                originId: 1,
                destinationId: 2,
                weight: 1000,
                courier: [] // Empty array
            );
        })->toThrow(ValidationException::class);
    });

    it('validates sort_by parameter', function () {
        expect(function () {
            $this->rajaongkir->calculateDistrictCost(
                originId: 1,
                destinationId: 2,
                weight: 1000,
                courier: [Courier::JNE],
                sortBy: 'invalid' // Invalid sort option
            );
        })->toThrow(ValidationException::class);
    });
});

describe('AWB Tracking', function () {

    it('can track AWB with enum courier', function () {
        $result = $this->rajaongkir->trackAWB(
            waybill: 'TEST123456',
            courier: Courier::JNE
        );

        expect($result)->toBeArray()
            ->and($result)->toHaveKey('waybill')
            ->and($result)->toHaveKey('courier')
            ->and($result)->toHaveKey('status');
    });

    it('can track AWB with string courier', function () {
        $result = $this->rajaongkir->trackAWB(
            waybill: 'TEST123456',
            courier: 'jne'
        );

        expect($result)->toBeArray()
            ->and($result)->toHaveKey('waybill')
            ->and($result)->toHaveKey('courier');
    });

    it('can track AWB with phone number', function () {
        $result = $this->rajaongkir->trackAWB(
            waybill: 'TEST123456',
            courier: Courier::JNE,
            last_phone_number: '12345'
        );

        expect($result)->toBeArray()
            ->and($result)->toHaveKey('waybill');
    });

    it('validates courier for AWB tracking', function () {
        expect(function () {
            $this->rajaongkir->trackAWB(
                waybill: 'TEST123456',
                courier: 'invalid_courier'
            );
        })->toThrow(InvalidArgumentException::class);
    });

});

describe('Caching Behavior', function () {

    it('caches province data', function () {
        // First call
        $result1 = $this->rajaongkir->getProvinces();

        // Second call (should use cache)
        $result2 = $this->rajaongkir->getProvinces();

        expect($result1)->toEqual($result2);
        Http::assertSentCount(1); // Only one API call should be made
    });

    it('caches cost calculation data', function () {
        // First call
        $result1 = $this->rajaongkir->calculateDistrictCost(1, 2, 1000, [Courier::JNE]);

        // Second call with same parameters (should use cache)
        $result2 = $this->rajaongkir->calculateDistrictCost(1, 2, 1000, [Courier::JNE]);

        expect($result1)->toEqual($result2);
        Http::assertSentCount(1); // Only one API call should be made
    });

    it('uses different cache keys for different parameters', function () {
        // Different origin
        $this->rajaongkir->calculateDistrictCost(1, 2, 1000, [Courier::JNE]);
        $this->rajaongkir->calculateDistrictCost(3, 2, 1000, [Courier::JNE]);

        Http::assertSentCount(2); // Two different API calls
    });

});
