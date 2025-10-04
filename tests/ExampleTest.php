<?php

/**
 * RajaOngkir Laravel Package Test Suite Overview
 * 
 * This test suite covers the complete functionality of the RajaOngkir Laravel package,
 * including enums, validation rules, caching, service provider integration, and more.
 * 
 * Test Coverage:
 * - ✅ BasicFunctionalityTest.php - Core components and validation
 * - ⚠️  CourierEnumTest.php - Comprehensive enum testing (needs API fixes)
 * - ⚠️  CourierRuleTest.php - Validation rule testing (needs API fixes) 
 * - ⚠️  CostCalculationTest.php - Cost calculation with mocking (needs API fixes)
 * - ⚠️  IntegrationTest.php - Full workflow testing (needs API fixes)
 * - ⚠️  ServiceProviderTest.php - Service provider registration (needs translation fixes)
 * - ✅ RajaOngkirTest.php - Main class testing (basic parts work)
 */

use Komodo\RajaOngkir\Constants\Courier;
use Komodo\RajaOngkir\RajaOngkir;
use Komodo\RajaOngkir\Rules\CourierRule;

describe('RajaOngkir Package Test Suite', function () {
    
    it('can instantiate main components', function () {
        expect(new RajaOngkir())->toBeInstanceOf(RajaOngkir::class);
        expect(new CourierRule())->toBeInstanceOf(CourierRule::class);
        expect(Courier::JNE)->toBeInstanceOf(Courier::class);
    });
    
    it('has proper package structure', function () {
        // Test that all main classes exist
        expect(class_exists(RajaOngkir::class))->toBeTrue();
        expect(class_exists(CourierRule::class))->toBeTrue();
        expect(enum_exists(Courier::class))->toBeTrue();
    });

    it('package is properly integrated with Laravel', function () {
        // Test service provider registration
        expect(app()->bound(RajaOngkir::class))->toBeTrue();
        
        // Test configuration is loaded  
        expect(config('rajaongkir'))->toBeArray();
        
        // Basic integration works (translations need service provider fixes)
        expect(app(RajaOngkir::class))->toBeInstanceOf(RajaOngkir::class);
    });

    it('core functionality works correctly', function () {
        // Enum functionality
        expect(Courier::JNE->value)->toBe('jne');
        expect(Courier::TIKI->value)->toBe('tiki');
        
        // Courier conversion
        $converted = CourierRule::convertCouriersToValues([Courier::JNE, 'tiki']);
        expect($converted)->toBe(['jne', 'tiki']);
        
        // Validation
        expect(CourierRule::validateCouriers(['jne', 'tiki']))->toBeTrue();
        expect(CourierRule::validateCouriers(['invalid']))->toBeFalse();
        
        // Cache support detection
        $rajaongkir = new RajaOngkir();
        expect(fn() => $rajaongkir->clearCache())->not->toThrow(Exception::class);
    });

});
