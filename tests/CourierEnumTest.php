<?php

use Komodo\RajaOngkir\Constants\Courier;

describe('Courier Enum', function () {

    it('has all required courier cases', function () {
        $expectedCouriers = [
            'pos', 'lion', 'ninja', 'ide', 'sicepat', 'sap', 
            'rex', 'sentral', 'jne', 'tiki', 'wahana', 'jnt'
        ];

        $actualCouriers = array_map(fn($case) => $case->value, Courier::cases());

        foreach ($expectedCouriers as $expected) {
            expect($actualCouriers)->toContain($expected);
        }
    });

    it('can get courier by value', function () {
        expect(Courier::JNE->value)->toBe('jne');
        expect(Courier::TIKI->value)->toBe('tiki');
        expect(Courier::SICEPAT->value)->toBe('sicepat');
        expect(Courier::JNT->value)->toBe('jnt');
    });

    it('can convert to string using value property', function () {
        expect(Courier::JNE->value)->toBe('jne');
        expect(Courier::TIKI->value)->toBe('tiki');
    });

    it('supports all major Indonesian couriers', function () {
        // Test major couriers exist
        expect(Courier::JNE)->toBeInstanceOf(Courier::class);
        expect(Courier::TIKI)->toBeInstanceOf(Courier::class);
        expect(Courier::POS_INDONESIA)->toBeInstanceOf(Courier::class);
        expect(Courier::SICEPAT)->toBeInstanceOf(Courier::class);
        expect(Courier::JNT)->toBeInstanceOf(Courier::class);
        expect(Courier::WAHANA)->toBeInstanceOf(Courier::class);
        expect(Courier::NINJA_XPRESS)->toBeInstanceOf(Courier::class);
    });

    it('can be used in arrays', function () {
        $couriers = [Courier::JNE, Courier::TIKI, Courier::SICEPAT];
        
        expect($couriers)->toHaveCount(3)
            ->and($couriers[0])->toBeInstanceOf(Courier::class)
            ->and($couriers[0]->value)->toBe('jne');
    });

    it('can be compared', function () {
        expect(Courier::JNE === Courier::JNE)->toBeTrue();
        expect(Courier::JNE === Courier::TIKI)->toBeFalse();
        expect(Courier::JNE->value === 'jne')->toBeTrue();
    });

});

describe('Courier Cases Coverage', function () {

    it('includes JNE courier', function () {
        expect(Courier::JNE->value)->toBe('jne');
    });

    it('includes POS Indonesia courier', function () {
        expect(Courier::POS_INDONESIA->value)->toBe('pos');
    });

    it('includes TIKI courier', function () {
        expect(Courier::TIKI->value)->toBe('tiki');
    });

    it('includes Lion Parcel', function () {
        expect(Courier::LION_PARCEL->value)->toBe('lion');
    });

    it('includes Ninja Xpress', function () {
        expect(Courier::NINJA_XPRESS->value)->toBe('ninja');
    });

    it('includes ID Express', function () {
        expect(Courier::ID_EXPRESS->value)->toBe('ide');
    });

    it('includes Wahana courier', function () {
        expect(Courier::WAHANA->value)->toBe('wahana');
    });

    it('includes SiCepat courier', function () {
        expect(Courier::SICEPAT->value)->toBe('sicepat');
    });

    it('includes J&T Express', function () {
        expect(Courier::JNT->value)->toBe('jnt');
    });

    it('includes SAP Express', function () {
        expect(Courier::SAP_EXPRESS->value)->toBe('sap');
    });

    it('includes Royal Express Indonesia', function () {
        expect(Courier::REX->value)->toBe('rex');
    });

    it('includes Sentral Cargo', function () {
        expect(Courier::SENTRAL->value)->toBe('sentral');
    });

});