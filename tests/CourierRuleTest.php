<?php

use Illuminate\Support\Facades\Validator;
use Komodo\RajaOngkir\Constants\Courier;
use Komodo\RajaOngkir\Rules\CourierRule;

beforeEach(function () {
    config([
        'rajaongkir.api_key' => 'test-api-key',
        'cache.default' => 'array',
    ]);

    $this->rule = new CourierRule();
});

describe('CourierRule Validation', function () {

    it('can instantiate courier rule', function () {
        expect($this->rule)->toBeInstanceOf(CourierRule::class);
    });

    it('passes validation with valid enum courier', function () {
        $validator = Validator::make(['courier' => Courier::JNE], ['courier' => new CourierRule()]);
        expect($validator->passes())->toBeTrue();
    });

    it('passes validation with valid string courier', function () {
        $validator = Validator::make(['courier' => 'jne'], ['courier' => new CourierRule()]);
        expect($validator->passes())->toBeTrue();
    });

    it('passes validation with valid courier codes', function () {
        $validCouriers = ['jne', 'tiki', 'pos', 'sicepat', 'jnt'];

        foreach ($validCouriers as $courier) {
            $validator = Validator::make(['courier' => $courier], ['courier' => new CourierRule()]);
            expect($validator->passes())->toBeTrue();
        }
    });

    it('fails validation with invalid courier', function () {
        $validator = Validator::make(['courier' => 'invalid_courier'], ['courier' => new CourierRule()]);
        expect($validator->passes())->toBeFalse();
    });

    it('fails validation with null value', function () {
        $validator = Validator::make(['courier' => null], ['courier' => new CourierRule()]);
        expect($validator->passes())->toBeFalse();
    });

    it('fails validation with empty string', function () {
        $validator = Validator::make(['courier' => ""], ['courier' => new CourierRule()]);
        expect($validator->passes())->toBeFalse();
    });

    it('fails validation with numeric value', function () {
        $validator = Validator::make(['courier' => 123], ['courier' => new CourierRule()]);
        expect($validator->passes())->toBeFalse();
    });

    it('fails validation with array', function () {
        $validator = Validator::make(['courier' => ['jne']], ['courier' => new CourierRule()]);
        expect($validator->passes())->toBeFalse();
    });

    it('returns error message for invalid courier', function () {
        $validator = Validator::make(['courier' => 'invalid_courier'], ['courier' => new CourierRule()]);
        expect($validator->fails())->toBeTrue();

        $errors = $validator->errors();
        expect($errors->has('courier'))->toBeTrue();
    });

});

describe('CourierRule Static Methods', function () {

    it('can get valid couriers list', function () {
        $validCouriers = CourierRule::getValidCouriers();

        expect($validCouriers)->toBeArray()
            ->and($validCouriers)->toContain('jne')
            ->and($validCouriers)->toContain('tiki')
            ->and($validCouriers)->toContain('pos')
            ->and($validCouriers)->toContain('sicepat')
            ->and($validCouriers)->toContain('jnt');
    });

    it('can convert courier enums to values', function () {
        $couriers = [Courier::JNE, Courier::TIKI, Courier::SICEPAT];
        $values = CourierRule::convertCouriersToValues($couriers);

        expect($values)->toBeArray()
            ->and($values)->toEqual(['jne', 'tiki', 'sicepat']);
    });

    it('can convert mixed courier array to values', function () {
        $couriers = [Courier::JNE, 'tiki', Courier::SICEPAT, 'jnt'];
        $values = CourierRule::convertCouriersToValues($couriers);

        expect($values)->toBeArray()
            ->and($values)->toEqual(['jne', 'tiki', 'sicepat', 'jnt']);
    });

    it('handles empty array in conversion', function () {
        $values = CourierRule::convertCouriersToValues([]);
        expect($values)->toBeArray()->and($values)->toBeEmpty();
    });

    it('can validate couriers array', function () {
        $validCouriers = ['jne', 'tiki', 'sicepat'];
        $isValid = CourierRule::validateCouriers($validCouriers);
        expect($isValid)->toBeTrue();

        $invalidCouriers = ['jne', 'invalid', 'tiki'];
        $isValid = CourierRule::validateCouriers($invalidCouriers);
        expect($isValid)->toBeFalse();
    });

    it('validates empty array as invalid', function () {
        $isValid = CourierRule::validateCouriers([]);
        expect($isValid)->toBeFalse();
    });

    it('validates non-array as invalid', function () {
        // Since validateCouriers expects array, we need to test this differently
        expect(fn() => CourierRule::validateCouriers('jne'))->toThrow(TypeError::class);
    });

});

describe('CourierRule Edge Cases', function () {

    it('handles case sensitivity', function () {
        $validator = Validator::make(['courier' => 'JNE'], ['courier' => new CourierRule()]);
        expect($validator->passes())->toBeFalse();

        $validator = Validator::make(['courier' => 'jne'], ['courier' => new CourierRule()]);
        expect($validator->passes())->toBeTrue();
    });

    it('handles whitespace', function () {
        $validator = Validator::make(['courier' => ' jne '], ['courier' => new CourierRule()]);
        expect($validator->passes())->toBeFalse(); // Should not have whitespace

        $validator = Validator::make(['courier' => 'jne'], ['courier' => new CourierRule()]);
        expect($validator->passes())->toBeTrue();
    });

    it('converts all enum cases correctly', function () {
        $allCases = Courier::cases();
        $enumArray = array_map(fn($case) => $case, $allCases);
        $converted = CourierRule::convertCouriersToValues($enumArray);

        expect($converted)->toHaveCount(count($allCases));

        foreach ($converted as $value) {
            expect($value)->toBeString();
            expect(CourierRule::getValidCouriers())->toContain($value);
        }
    });

});
