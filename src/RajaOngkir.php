<?php

namespace Komodo\RajaOngkir;

use Illuminate\Support\Facades\Cache;
use Komodo\RajaOngkir\Rules\CourierRule;
use Komodo\RajaOngkir\Services\ApiServices as Api;

class RajaOngkir
{
    /**
     * Cache duration for location data (in seconds)
     * Default: 24 hours
     */
    protected int $locationCacheDuration = 86400;

    /**
     * Cache duration for cost calculations (in seconds)
     * Default: 1 hour
     */
    protected int $costCacheDuration = 3600;

    /**
     * Cache tags for different data types
     */
    protected const CACHE_TAG_LOCATIONS = 'rajaongkir.locations';

    protected const CACHE_TAG_PROVINCES = 'rajaongkir.provinces';

    protected const CACHE_TAG_CITIES = 'rajaongkir.cities';

    protected const CACHE_TAG_DISTRICTS = 'rajaongkir.districts';

    protected const CACHE_TAG_SUBDISTRICTS = 'rajaongkir.subdistricts';

    protected const CACHE_TAG_COSTS = 'rajaongkir.costs';

    /**
     * Constructor to initialize cache durations from config
     */
    public function __construct()
    {
        // Optionally, you can set cache durations from config
        $configLocationDuration = config('rajaongkir.location_cache_duration'); // in minutes
        if (is_int($configLocationDuration) && $configLocationDuration > 0) {
            $this->locationCacheDuration = $configLocationDuration * 60; // convert to seconds
        }

        $configCostDuration = config('rajaongkir.cost_cache_duration'); // in minutes
        if (is_int($configCostDuration) && $configCostDuration > 0) {
            $this->costCacheDuration = $configCostDuration * 60; // convert to seconds
        }
    }

    /**
     * Check if the current cache driver supports tagging
     */
    protected function cacheSupportsTagging(): bool
    {
        try {
            $store = Cache::getStore();

            return method_exists($store, 'tags') && in_array('Illuminate\Contracts\Cache\TaggableStore', class_implements($store));
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get cache instance with optional tagging support
     */
    protected function getCacheInstance(array $tags = []): \Illuminate\Contracts\Cache\Repository
    {
        if ($this->cacheSupportsTagging() && ! empty($tags)) {
            return Cache::tags($tags);
        }

        return Cache::store();
    }

    /**
     * Get provinces
     */
    public function getProvinces(): array
    {
        $cacheKey = 'rajaongkir.provinces';

        return $this->getCacheInstance([self::CACHE_TAG_LOCATIONS, self::CACHE_TAG_PROVINCES])
            ->remember($cacheKey, $this->locationCacheDuration, function () {
                $path = '/province';
                $response = Api::api($path, 'get')->data();

                return $response;
            });
    }

    /**
     * Get cities
     */
    public function getCities(
        int $provinceId,
    ): array {
        $cacheKey = "rajaongkir.cities.province_{$provinceId}";

        return $this->getCacheInstance([self::CACHE_TAG_LOCATIONS, self::CACHE_TAG_CITIES])
            ->remember($cacheKey, $this->locationCacheDuration, function () use ($provinceId) {
                $path = '/city/'.$provinceId;
                $response = Api::api($path, 'get')->data();

                return $response;
            });
    }

    /**
     * Get districts
     */
    public function getDistricts(
        int $cityId,
    ): array {
        $cacheKey = "rajaongkir.districts.city_{$cityId}";

        return $this->getCacheInstance([self::CACHE_TAG_LOCATIONS, self::CACHE_TAG_DISTRICTS])
            ->remember($cacheKey, $this->locationCacheDuration, function () use ($cityId) {
                $path = '/district/'.$cityId;
                $response = Api::api($path, 'get')->data();

                return $response;
            });
    }

    /**
     * Get subdistricts
     */
    public function getSubdistricts(
        int $districtId,
    ): array {
        $cacheKey = "rajaongkir.subdistricts.district_{$districtId}";

        return $this->getCacheInstance([self::CACHE_TAG_LOCATIONS, self::CACHE_TAG_SUBDISTRICTS])
            ->remember($cacheKey, $this->locationCacheDuration, function () use ($districtId) {
                $path = '/subdistrict/'.$districtId;
                $response = Api::api($path, 'get')->data();

                return $response;
            });
    }

    /**
     * District Calculate Cost
     *
     * @param  int  $originId  origin district id
     * @param  int  $destinationId  destination district id
     * @param  int  $weight  weight in grams
     * @param  array  $courier  courier codes array (Courier enum objects or strings)
     */
    public function calculateDistrictCost(
        int $originId,
        int $destinationId,
        int $weight,
        array $courier,
        ?string $sortBy = 'lowest'
    ): array {
        // Convert courier enums to string values if necessary
        $courierValues = CourierRule::convertCouriersToValues($courier);

        // Validate input parameters
        $this->validateCalculateCostInputs([
            'origin_id' => $originId,
            'destination_id' => $destinationId,
            'weight' => $weight,
            'courier' => $courierValues,
            'sort_by' => $sortBy,
        ]);

        $data = [
            'origin_id' => $originId,
            'destination_id' => $destinationId,
            'weight' => $weight,
            'courier' => $courierValues,
            'sort_by' => $sortBy,
        ];

        $courierString = implode(':', $data['courier']);
        $cacheKey = "rajaongkir.cost.{$data['origin_id']}.{$data['destination_id']}.{$data['weight']}.{$courierString}.{$data['sort_by']}";

        return $this->getCacheInstance([self::CACHE_TAG_COSTS])
            ->remember($cacheKey, $this->costCacheDuration, function () use ($data, $courierString) {
                $bodies = [];
                $path = '/calculate/district/domestic-cost';

                $bodies['origin'] = $data['origin_id'];
                $bodies['destination'] = $data['destination_id'];
                $bodies['weight'] = $data['weight'];
                $bodies['courier'] = $courierString;
                $bodies['price'] = $data['sort_by'];

                $response = Api::api($path, 'post', $bodies)->data();

                return $response;
            });
    }

    /**
     * Search domestic destinations
     */
    public function searchDomesticDestinations(
        string $search,
        ?int $limit = 10,
        ?int $offset = 0,
    ): array {
        $path = '/destination/domestic-destination';
        $params = [
            'search' => $search,
            'limit' => $limit,
            'offset' => $offset,
        ];

        $cacheKey = 'rajaongkir.search_destinations.'.md5(serialize($params));

        return $this->getCacheInstance([self::CACHE_TAG_LOCATIONS])
            ->remember($cacheKey, $this->locationCacheDuration, function () use ($path, $params) {
                return Api::api($path, 'get', params: $params)->data();
            });
    }

    /**
     * Search international destinations
     */
    public function searchInternationalDestinations(
        string $search,
        ?int $limit = 10,
        ?int $offset = 0,
    ): array {
        $path = '/destination/international-destination';
        $params = [
            'search' => $search,
            'limit' => $limit,
            'offset' => $offset,
        ];

        $cacheKey = 'rajaongkir.search_international_destinations.'.md5(serialize($params));

        return $this->getCacheInstance([self::CACHE_TAG_LOCATIONS])
            ->remember($cacheKey, $this->locationCacheDuration, function () use ($path, $params) {
                return Api::api($path, 'get', params: $params)->data();
            });
    }

    /**
     * Calculate domestic cost
     *
     * @param  int  $originId  origin id (can be province, city, or district id)
     * @param  int  $destinationId  destination id (can be province, city, or district id)
     * @param  int  $weight  weight in grams
     * @param  array  $courier  courier codes array (Courier enum objects or strings)
     */
    public function calculateDomesticCost(
        int $originId,
        int $destinationId,
        int $weight,
        array $courier,
        ?string $sortBy = 'lowest'
    ): array {
        // Convert courier enums to string values if necessary
        $courierValues = CourierRule::convertCouriersToValues($courier);

        // Validate input parameters
        $this->validateCalculateCostInputs([
            'origin_id' => $originId,
            'destination_id' => $destinationId,
            'weight' => $weight,
            'courier' => $courierValues,
            'sort_by' => $sortBy,
        ]);

        $data = [
            'origin_id' => $originId,
            'destination_id' => $destinationId,
            'weight' => $weight,
            'courier' => $courierValues,
            'sort_by' => $sortBy,
        ];

        $path = '/calculate/domestic-cost';
        $courierString = implode(':', $data['courier']);

        $bodies['origin'] = $data['origin_id'];
        $bodies['destination'] = $data['destination_id'];
        $bodies['weight'] = $data['weight'];
        $bodies['courier'] = $courierString;
        $bodies['price'] = $data['sort_by'];

        $cacheKey = "rajaongkir.domestic_cost.{$data['origin_id']}.{$data['destination_id']}.{$data['weight']}.{$courierString}.{$data['sort_by']}";

        return $this->getCacheInstance([self::CACHE_TAG_COSTS])
            ->remember($cacheKey, $this->costCacheDuration, function () use ($path, $bodies) {
                return Api::api($path, 'post', $bodies)->data();
            });
    }

    /**
     * Calculate international cost
     *
     * @param  int  $originId  origin id (country id)
     * @param  int  $destinationId  destination id (country id)
     * @param  int  $weight  weight in grams
     * @param  array  $courier  courier codes array (Courier enum objects or strings)
     */
    public function calculateInternationalCost(
        int $originId,
        int $destinationId,
        int $weight,
        array $courier,
        ?string $sortBy = 'lowest'
    ): array {
        // Convert courier enums to string values if necessary
        $courierValues = CourierRule::convertCouriersToValues($courier);

        // Validate input parameters for international cost (adjusted for string origin/destination)
        $this->validateCalculateCostInputs([
            'origin_id' => $originId,
            'destination_id' => $destinationId,
            'weight' => $weight,
            'courier' => $courierValues,
            'sort_by' => $sortBy,
        ]);

        $data = [
            'origin_id' => $originId,
            'destination_id' => $destinationId,
            'weight' => $weight,
            'courier' => $courierValues,
            'sort_by' => $sortBy,
        ];

        $path = '/calculate/international-cost';
        $courierString = implode(':', $data['courier']);

        $bodies['origin'] = $data['origin_id'];
        $bodies['destination'] = $data['destination_id'];
        $bodies['weight'] = $data['weight'];
        $bodies['courier'] = $courierString;
        $bodies['price'] = $data['sort_by'];

        $cacheKey = "rajaongkir.international_cost.{$originId}.{$destinationId}.{$weight}.{$courierString}.{$sortBy}";

        return $this->getCacheInstance([self::CACHE_TAG_COSTS])
            ->remember($cacheKey, $this->costCacheDuration, function () use ($path, $bodies) {
                return Api::api($path, 'post', $bodies)->data();
            });
    }

    /**
     * Track AWB (Airway Bill)
     *
     * @param  string|\Komodo\RajaOngkir\Constants\Courier  $courier  courier code (Courier enum or string)
     * @param  string|null  $last_phone_number  last 5 digits of recipient's phone number (optional, some couriers require this)
     */
    public function trackAWB(
        string $waybill,
        $courier,
        ?string $last_phone_number = null
    ): array {
        // Convert courier enum to string value if necessary
        $courierValue = $courier instanceof \Komodo\RajaOngkir\Constants\Courier ? $courier->value : (string) $courier;

        // Validate courier
        if (! in_array($courierValue, CourierRule::getValidCouriers(), true)) {
            throw new \InvalidArgumentException("Invalid courier code: {$courierValue}");
        }

        $path = '/track/waybill';
        $params = [
            'waybill' => $waybill,
            'courier' => $courierValue,
        ];

        if ($last_phone_number) {
            $params['last_phone_number'] = $last_phone_number;
        }

        $cacheKey = "rajaongkir.waybill.{$waybill}.{$courierValue}.".($last_phone_number ? $last_phone_number : 'no_phone');

        return $this->getCacheInstance([self::CACHE_TAG_COSTS])
            ->remember($cacheKey, $this->costCacheDuration, function () use ($path, $params) {
                return Api::api($path, 'post', params: $params)->data();
            });
    }

    /**
     * Clear all cached data
     */
    public function clearCache(): void
    {
        if ($this->cacheSupportsTagging()) {
            Cache::tags([
                self::CACHE_TAG_LOCATIONS,
                self::CACHE_TAG_COSTS,
            ])->flush();
        } else {
            // For non-tagging cache stores, we can only flush all cache or use cache patterns if supported
            Cache::flush();
        }
    }

    /**
     * Clear location cache (provinces, cities, districts, subdistricts)
     */
    public function clearLocationCache(): void
    {
        if ($this->cacheSupportsTagging()) {
            Cache::tags([self::CACHE_TAG_LOCATIONS])->flush();
        } else {
            // For non-tagging stores, clear specific known location cache keys
            $this->clearLocationCacheKeys();
        }
    }

    /**
     * Clear cost calculation cache
     */
    public function clearCostCache(): void
    {
        if ($this->cacheSupportsTagging()) {
            Cache::tags([self::CACHE_TAG_COSTS])->flush();
        } else {
            // For non-tagging stores, we can only flush all cache
            // Cost cache keys are too dynamic to track individually
            Cache::flush();
        }
    }

    /**
     * Clear specific location cache keys for non-tagging stores
     */
    protected function clearLocationCacheKeys(): void
    {
        // Clear provinces cache
        Cache::forget('rajaongkir.provinces');

        // Note: We cannot efficiently clear all city/district/subdistrict caches
        // without tagging support as the keys are dynamic.
        // In practice, users should consider using Redis/Memcached for better cache management.
    }

    /**
     * Clear specific location type cache
     *
     * @param  string  $locationType  provinces|cities|districts|subdistricts
     */
    public function clearLocationTypeCache(string $locationType): void
    {
        if ($this->cacheSupportsTagging()) {
            $tagMap = [
                'provinces' => self::CACHE_TAG_PROVINCES,
                'cities' => self::CACHE_TAG_CITIES,
                'districts' => self::CACHE_TAG_DISTRICTS,
                'subdistricts' => self::CACHE_TAG_SUBDISTRICTS,
            ];

            if (isset($tagMap[$locationType])) {
                Cache::tags([$tagMap[$locationType]])->flush();
            }
        } else {
            // For non-tagging stores, only handle provinces specifically
            if ($locationType === 'provinces') {
                Cache::forget('rajaongkir.provinces');
            }
            // Other location types have dynamic keys and cannot be cleared efficiently
        }
    }

    /**
     * Set custom cache duration for location data
     */
    public function setLocationCacheDuration(int $seconds): self
    {
        $this->locationCacheDuration = $seconds;

        return $this;
    }

    /**
     * Set custom cache duration for cost calculations
     */
    public function setCostCacheDuration(int $seconds): self
    {
        $this->costCacheDuration = $seconds;

        return $this;
    }

    /**
     * Validate calculate cost inputs
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateCalculateCostInputs(array $data): void
    {
        $validator = \Illuminate\Support\Facades\Validator::make($data, [
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
                new CourierRule,
            ],
            'sort_by' => [
                'nullable',
                'string',
                'in:lowest,highest',
            ],
        ], [
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
        ]);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }
    }
}
