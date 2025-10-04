<?php

namespace Komodo\RajaOngkir\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Komodo\RajaOngkir\Rules\CourierRule;

class CalculateCostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'origin_id' => [
                'required',
                'integer',
                'min:1',
            ],
            'destination_id' => [
                'required', 
                'integer',
                'min:1',
                'different:origin_id',
            ],
            'weight' => [
                'required',
                'integer',
                'min:1',
                'max:30000', // RajaOngkir max weight limit (30kg in grams)
            ],
            'courier' => [
                'required',
                'array',
                'min:1',
                'max:5', // Reasonable limit for courier selection
            ],
            'courier.*' => [
                'required',
                'string',
                new CourierRule(),
            ],
            'sort_by' => [
                'nullable',
                'string',
                'in:lowest,highest',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'origin_id.required' => __('rajaongkir::rajaongkir.validation.origin_required'),
            'origin_id.integer' => __('rajaongkir::rajaongkir.validation.origin_must_be_integer'),
            'origin_id.min' => __('rajaongkir::rajaongkir.validation.origin_must_be_positive'),
            'destination_id.required' => __('rajaongkir::rajaongkir.validation.destination_required'),
            'destination_id.integer' => __('rajaongkir::rajaongkir.validation.destination_must_be_integer'),
            'destination_id.min' => __('rajaongkir::rajaongkir.validation.destination_must_be_positive'),
            'destination_id.different' => __('rajaongkir::rajaongkir.validation.destination_must_be_different'),
            'weight.required' => __('rajaongkir::rajaongkir.validation.weight_required'),
            'weight.integer' => __('rajaongkir::rajaongkir.validation.weight_must_be_integer'),
            'weight.min' => __('rajaongkir::rajaongkir.validation.weight_must_be_positive'),
            'weight.max' => __('rajaongkir::rajaongkir.validation.weight_exceeds_limit'),
            'courier.required' => __('rajaongkir::rajaongkir.validation.courier_required'),
            'courier.array' => __('rajaongkir::rajaongkir.validation.courier_must_be_array'),
            'courier.min' => __('rajaongkir::rajaongkir.validation.courier_minimum_selection'),
            'courier.max' => __('rajaongkir::rajaongkir.validation.courier_maximum_selection'),
            'sort_by.in' => __('rajaongkir::rajaongkir.validation.sort_by_invalid'),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'origin_id' => __('rajaongkir::rajaongkir.attributes.origin_id'),
            'destination_id' => __('rajaongkir::rajaongkir.attributes.destination_id'),
            'weight' => __('rajaongkir::rajaongkir.attributes.weight'),
            'courier' => __('rajaongkir::rajaongkir.attributes.courier'),
            'sort_by' => __('rajaongkir::rajaongkir.attributes.sort_by'),
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure sort_by has a default value
        if (!$this->has('sort_by') || is_null($this->input('sort_by'))) {
            $this->merge([
                'sort_by' => 'lowest'
            ]);
        }
    }

    /**
     * Handle a passed validation attempt.
     */
    protected function passedValidation(): void
    {
        // Additional business logic validation can go here
        // For example, checking if origin and destination districts exist in database
    }

    /**
     * Get validated data as array suitable for the calculate cost method
     *
     * @return array
     */
    public function getCalculateCostData(): array
    {
        $validated = $this->validated();
        
        return [
            'origin_id' => $validated['origin_id'],
            'destination_id' => $validated['destination_id'],
            'weight' => $validated['weight'],
            'courier' => $validated['courier'],
            'sort_by' => $validated['sort_by'] ?? 'lowest',
        ];
    }
}