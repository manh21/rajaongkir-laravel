<?php

namespace Komodo\RajaOngkir\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Komodo\RajaOngkir\Constants\Courier;

class CourierRule implements ValidationRule
{
    public function validate(string $attribute, $value, Closure $fail): void
    {
        // Check if value is null or not a string or Courier enum
        if (empty($value) || is_null($value) || $value === "" || (! is_string($value) && ! $value instanceof Courier)) {
            $fail(__('rajaongkir::rajaongkir.validation.invalid_courier', [
                'attribute' => $attribute,
                'couriers' => implode(', ', self::getValidCouriers()),
            ]));
            return;
        }

        // Convert enum to string value if it's a Courier enum
        $courierValue = $this->getCourierValue($value);

        // Get all valid courier codes from the enum
        $validCouriers = array_column(Courier::cases(), 'value');

        if (! in_array($courierValue, $validCouriers, true)) {
            $fail(__('rajaongkir::rajaongkir.validation.invalid_courier', [
                'attribute' => $attribute,
                'couriers' => implode(', ', $validCouriers),
            ]));
        }
    }

    /**
     * Convert courier enum or string to string value
     *
     * @param  mixed  $courier
     */
    protected function getCourierValue($courier): string
    {
        if ($courier instanceof Courier) {
            return $courier->value;
        }

        return (string) $courier;
    }

    /**
     * Get all valid courier codes
     *
     * @return array<string>
     */
    public static function getValidCouriers(): array
    {
        return array_column(Courier::cases(), 'value');
    }

    /**
     * Convert array of courier enums or strings to array of string values
     *
     * @param  array  $couriers  Array of Courier enums or strings
     * @return array<string>
     */
    public static function convertCouriersToValues(array $couriers): array
    {
        return array_map(function ($courier) {
            if ($courier instanceof Courier) {
                return $courier->value;
            }

            return (string) $courier;
        }, $couriers);
    }

    /**
     * Validate array of courier enums or strings
     *
     * @param  array  $couriers  Array of Courier enums or strings
     */
    public static function validateCouriers(array $couriers): bool
    {
        // Empty array is invalid - at least one courier must be provided
        if (empty($couriers)) {
            return false;
        }

        $validCouriers = self::getValidCouriers();
        $courierValues = self::convertCouriersToValues($couriers);

        foreach ($courierValues as $courier) {
            if (! in_array($courier, $validCouriers, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get invalid couriers from array
     *
     * @param  array  $couriers  Array of Courier enums or strings
     * @return array<string> Invalid courier codes
     */
    public static function getInvalidCouriers(array $couriers): array
    {
        $validCouriers = self::getValidCouriers();
        $courierValues = self::convertCouriersToValues($couriers);
        $invalidCouriers = [];

        foreach ($courierValues as $courier) {
            if (! in_array($courier, $validCouriers, true)) {
                $invalidCouriers[] = $courier;
            }
        }

        return array_unique($invalidCouriers);
    }

    /**
     * Check if a courier supports domestic cost checking
     */
    public static function supportsDomesticCost(string $courierCode): bool
    {
        // All couriers in the table support domestic cost checking
        return in_array($courierCode, self::getValidCouriers(), true);
    }

    /**
     * Check if a courier supports international cost checking
     */
    public static function supportsInternationalCost(string $courierCode): bool
    {
        // Only JNE, TIKI, and POS support international cost checking
        $internationalSupportedCouriers = ['jne', 'tiki', 'pos'];

        return in_array($courierCode, $internationalSupportedCouriers, true);
    }

    /**
     * Check if a courier supports AWB (Airway Bill) checking
     */
    public static function supportsAwb(string $courierCode): bool
    {
        // Couriers that support AWB checking based on your table
        $awbSupportedCouriers = [
            'jne', 'sicepat', 'sap', 'ninja', 'jnt', 'tiki', 'wahana', 'pos', 'lion',
        ];

        return in_array($courierCode, $awbSupportedCouriers, true);
    }
}
