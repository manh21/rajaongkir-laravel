<?php

namespace Komodo\RajaOngkir\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Komodo\RajaOngkir\Constants\Courier;

class CourierRule implements ValidationRule
{
    public function validate(string $attribute, $value, Closure $fail): void
    {
        // Get all valid courier codes from the enum
        $validCouriers = array_column(Courier::cases(), 'value');
        
        if (!in_array($value, $validCouriers, true)) {
            $fail(__('rajaongkir::rajaongkir.validation.invalid_courier', [
                'attribute' => $attribute,
                'couriers' => implode(', ', $validCouriers)
            ]));
        }
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
     * Check if a courier supports domestic cost checking
     *
     * @param string $courierCode
     * @return bool
     */
    public static function supportsDomesticCost(string $courierCode): bool
    {
        // All couriers in the table support domestic cost checking
        return in_array($courierCode, self::getValidCouriers(), true);
    }
    
    /**
     * Check if a courier supports international cost checking
     *
     * @param string $courierCode
     * @return bool
     */
    public static function supportsInternationalCost(string $courierCode): bool
    {
        // Only JNE, TIKI, and POS support international cost checking
        $internationalSupportedCouriers = ['jne', 'tiki', 'pos'];
        
        return in_array($courierCode, $internationalSupportedCouriers, true);
    }
    
    /**
     * Check if a courier supports AWB (Airway Bill) checking
     *
     * @param string $courierCode
     * @return bool
     */
    public static function supportsAwb(string $courierCode): bool
    {
        // Couriers that support AWB checking based on your table
        $awbSupportedCouriers = [
            'jne', 'sicepat', 'sap', 'ninja', 'jnt', 'tiki', 'wahana', 'pos', 'lion'
        ];
        
        return in_array($courierCode, $awbSupportedCouriers, true);
    }
}
