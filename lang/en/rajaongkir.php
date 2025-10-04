<?php

return [
    'validation' => [
        'invalid_courier' => 'The :attribute must be a valid courier code. Valid options are: :couriers',
        'courier_not_found' => 'The selected :attribute is not supported.',
        
        // Calculate cost validation messages
        'origin_required' => 'Origin district ID is required.',
        'origin_must_be_integer' => 'Origin district ID must be a valid integer.',
        'origin_must_be_positive' => 'Origin district ID must be a positive number.',
        'destination_required' => 'Destination district ID is required.',
        'destination_must_be_integer' => 'Destination district ID must be a valid integer.',
        'destination_must_be_positive' => 'Destination district ID must be a positive number.',
        'destination_must_be_different' => 'Destination must be different from origin.',
        'weight_required' => 'Package weight is required.',
        'weight_must_be_integer' => 'Package weight must be a valid integer.',
        'weight_must_be_positive' => 'Package weight must be greater than 0 grams.',
        'weight_exceeds_limit' => 'Package weight cannot exceed 30,000 grams (30kg).',
        'courier_required' => 'At least one courier must be selected.',
        'courier_must_be_array' => 'Courier selection must be an array.',
        'courier_minimum_selection' => 'At least one courier must be selected.',
        'courier_maximum_selection' => 'You can select maximum 5 couriers at once.',
        'sort_by_invalid' => 'Sort option must be either "lowest" or "highest".',
    ],

    'attributes' => [
        'origin_id' => 'origin district',
        'destination_id' => 'destination district', 
        'weight' => 'package weight',
        'courier' => 'courier',
        'sort_by' => 'sort option',
    ],

    'api' => [
        '500' => 'An error occurred on the RajaOngkir server. Please try again later.',
    ],
];
