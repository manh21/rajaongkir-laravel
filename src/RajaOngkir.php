<?php

namespace Komodo\RajaOngkir;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Komodo\RajaOngkir\Exceptions\ApiException;
use Komodo\RajaOngkir\Facades\Api;
use Komodo\RajaOngkir\Requests\CalculateCostRequest;

class RajaOngkir {
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
     * Get provinces
     * @return array
     */
    public function getProvinces(): array
    {
        $cacheKey = 'rajaongkir.provinces';

        return Cache::tags([self::CACHE_TAG_LOCATIONS, self::CACHE_TAG_PROVINCES])
            ->remember($cacheKey, $this->locationCacheDuration, function () {
                $path = '/province';
                $response = Api::api($path, 'get')->data();
                return $response;
            });
    }

    /**
     * Get cities
     * @param int $provinceId
     * @return array
     */
    public function getCities(
        int $provinceId,
    ): array
    {
        $cacheKey = "rajaongkir.cities.province_{$provinceId}";

        return Cache::tags([self::CACHE_TAG_LOCATIONS, self::CACHE_TAG_CITIES])
            ->remember($cacheKey, $this->locationCacheDuration, function () use ($provinceId) {
                $path = '/city/' . $provinceId;
                $response = Api::api($path, 'get')->data();
                return $response;
            });
    }

    /**
     * Get districts
     * @param int $cityId
     * @return array
     */
    public function getDistricts(
        int $cityId,
    ): array
    {
        $cacheKey = "rajaongkir.districts.city_{$cityId}";

        return Cache::tags([self::CACHE_TAG_LOCATIONS, self::CACHE_TAG_DISTRICTS])
            ->remember($cacheKey, $this->locationCacheDuration, function () use ($cityId) {
                $path = '/district/' . $cityId;
                $response = Api::api($path, 'get')->data();
                return $response;
            });
    }

    /**
     * Get subdistricts
     * @param int $districtId
     * @return array
     */
    public function getSubdistricts(
        int $districtId,
    ): array
    {
        $cacheKey = "rajaongkir.subdistricts.district_{$districtId}";

        return Cache::tags([self::CACHE_TAG_LOCATIONS, self::CACHE_TAG_SUBDISTRICTS])
            ->remember($cacheKey, $this->locationCacheDuration, function () use ($districtId) {
                $path = '/subdistrict/' . $districtId;
                $response = Api::api($path, 'get')->data();
                return $response;
            });
    }

    /**
     * District Calculate Cost
     * @param int $originId origin district id
     * @param int $destinationId destination district id
     * @param int $weight weight in grams
     * @param array $courier courier codes array (can be obtained from Courier enum)
     * @param string|null $sortBy
     * @return array
     */
    public function calculateDistrictCost(
        int $originId,
        int $destinationId,
        int $weight,
        array $courier,
        ?string $sortBy = 'lowest'
    ): array
    {
        // Create a request instance with the provided data
        $request = new CalculateCostRequest([
            'origin_id' => $originId,
            'destination_id' => $destinationId,
            'weight' => $weight,
            'courier' => $courier,
            'sort_by' => $sortBy
        ]);

        // Validate the request
        $request->validate();

        $data = $request->getCalculateCostData();

        $courierString = implode(':', $data['courier']);
        $cacheKey = "rajaongkir.cost.{$data['origin_id']}.{$data['destination_id']}.{$data['weight']}.{$courierString}.{$data['sort_by']}";

        return Cache::tags([self::CACHE_TAG_COSTS])
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
     * @param string $search
     * @param int|null $limit
     * @param int|null $offset
     * @return array
     */
    public function searchDomesticDestinations(
        string $search,
        ?int $limit = 10,
        ?int $offset = 0,
    ): array
    {
        $path = '/destination/domestic-destination';
        $params = [
            'search' => $search,
            'limit' => $limit,
            'offset' => $offset,
        ];

        $cacheKey = 'rajaongkir.search_destinations.' . md5(serialize($params));
        return Cache::tags([self::CACHE_TAG_LOCATIONS])
            ->remember($cacheKey, $this->locationCacheDuration, function () use ($path, $params) {
                return Api::api($path, 'get', params: $params)->data();
            });
    }

    /**
     * Search international destinations
     * @param string $search
     * @param int|null $limit
     * @param int|null $offset
     * @return array
     */
    public function searchInternationalDestinations(
        string $search,
        ?int $limit = 10,
        ?int $offset = 0,
    ): array
    {
        $path = '/destination/international-destination';
        $params = [
            'search' => $search,
            'limit' => $limit,
            'offset' => $offset,
        ];

        $cacheKey = 'rajaongkir.search_international_destinations.' . md5(serialize($params));
        return Cache::tags([self::CACHE_TAG_LOCATIONS])
            ->remember($cacheKey, $this->locationCacheDuration, function () use ($path, $params) {
                return Api::api($path, 'get', params: $params)->data();
            });
    }

    /**
     * Calculate domestic cost
     * @param int $originId origin id (can be province, city, or district id)
     * @param int $destinationId destination id (can be province, city, or district id)
     * @param int $weight weight in grams
     * @param array $courier courier codes array (can be obtained from Courier enum)
     * @param string|null $sortBy
     * @return array
     */
    public function calculateDomesticCost(
        int $originId,
        int $destinationId,
        int $weight,
        array $courier,
        ?string $sortBy = 'lowest'
    ): array
    {
        // Create a request instance with the provided data
        $request = new CalculateCostRequest([
            'origin_id' => $originId,
            'destination_id' => $destinationId,
            'weight' => $weight,
            'courier' => $courier,
            'sort_by' => $sortBy
        ]);

        // Validate the request
        $request->validate();

        $data = $request->getCalculateCostData();

        $path = '/calculate/domestic-cost';
        $courierString = implode(':', $courier);

        $bodies['origin'] = $data['origin_id'];
        $bodies['destination'] = $data['destination_id'];
        $bodies['weight'] = $data['weight'];
        $bodies['courier'] = $courierString;
        $bodies['price'] = $data['sort_by'];

        $cacheKey = "rajaongkir.domestic_cost.{$data['origin_id']}.{$data['destination_id']}.{$data['weight']}.{$courierString}.{$data['sort_by']}";
        return Cache::tags([self::CACHE_TAG_COSTS])
            ->remember($cacheKey, $this->costCacheDuration, function () use ($path, $bodies) {
                return Api::api($path, 'post', $bodies)->data();
            });
    }

    /**
     * Calculate international cost
     * @param string $originId origin id (country code)
     * @param string $destinationId destination id (country code)
     * @param int $weight weight in grams
     * @param array $courier courier codes array (can be obtained from Courier enum)
     * @param string|null $sortBy
     * @return array
     */
    public function calculateInternationalCost(
        string $originId,
        string $destinationId,
        int $weight,
        array $courier,
        ?string $sortBy = 'lowest'
    ): array
    {
        // Create a request instance with the provided data
        $request = new CalculateCostRequest([
            'origin_id' => $originId,
            'destination_id' => $destinationId,
            'weight' => $weight,
            'courier' => $courier,
            'sort_by' => $sortBy
        ]);

        // Validate the request
        $request->validate();

        $data = $request->getCalculateCostData();

        $path = '/calculate/international-cost';
        $courierString = implode(':', $courier);

        $bodies['origin'] = $data['origin_id'];
        $bodies['destination'] = $data['destination_id'];
        $bodies['weight'] = $data['weight'];
        $bodies['courier'] = $courierString;
        $bodies['price'] = $data['sort_by'];

        $cacheKey = "rajaongkir.international_cost.{$originId}.{$destinationId}.{$weight}.{$courierString}.{$sortBy}";
        return Cache::tags([self::CACHE_TAG_COSTS])
            ->remember($cacheKey, $this->costCacheDuration, function () use ($path, $bodies) {
                return Api::api($path, 'post', $bodies)->data();
            });
    }

    /**
     * Track AWB (Airway Bill)
     * @param string $waybill
     * @param string $courier courier code from Courier enum
     * @param string|null $last_phone_number last 5 digits of recipient's phone number (optional, some couriers require this)
     * @return array
     */
    public function trackAWB(
        string $waybill,
        string $courier,
        ?string $last_phone_number = null
    ): array
    {
        // Validate courier
        if (!in_array($courier, \Komodo\RajaOngkir\Constants\Courier::getValidCouriers(), true)) {
            throw new \InvalidArgumentException("Invalid courier code: {$courier}");
        }
        
        $path = '/track/waybill';
        $params = [
            'waybill' => $waybill,
            'courier' => $courier,
        ];

        if($last_phone_number) {
            $params['last_phone_number'] = $last_phone_number;
        }

        $cacheKey = "rajaongkir.waybill.{$waybill}.{$courier}." . ($last_phone_number ? $last_phone_number : 'no_phone');
        return Cache::tags([self::CACHE_TAG_COSTS])
            ->remember($cacheKey, $this->costCacheDuration, function () use ($path, $params) {
                return Api::api($path, 'post', param: $params)->data();
            });
    }

    /**
     * Clear all cached data
     *
     * @return void
     */
    public function clearCache(): void
    {
        Cache::tags([
            self::CACHE_TAG_LOCATIONS,
            self::CACHE_TAG_COSTS
        ])->flush();
    }

    /**
     * Clear location cache (provinces, cities, districts, subdistricts)
     *
     * @return void
     */
    public function clearLocationCache(): void
    {
        Cache::tags([self::CACHE_TAG_LOCATIONS])->flush();
    }

    /**
     * Clear cost calculation cache
     *
     * @return void
     */
    public function clearCostCache(): void
    {
        Cache::tags([self::CACHE_TAG_COSTS])->flush();
    }

    /**
     * Clear specific location type cache
     *
     * @param string $locationType provinces|cities|districts|subdistricts
     * @return void
     */
    public function clearLocationTypeCache(string $locationType): void
    {
        $tagMap = [
            'provinces' => self::CACHE_TAG_PROVINCES,
            'cities' => self::CACHE_TAG_CITIES,
            'districts' => self::CACHE_TAG_DISTRICTS,
            'subdistricts' => self::CACHE_TAG_SUBDISTRICTS,
        ];

        if (isset($tagMap[$locationType])) {
            Cache::tags([$tagMap[$locationType]])->flush();
        }
    }

    /**
     * Set custom cache duration for location data
     *
     * @param int $seconds
     * @return self
     */
    public function setLocationCacheDuration(int $seconds): self
    {
        $this->locationCacheDuration = $seconds;
        return $this;
    }

    /**
     * Set custom cache duration for cost calculations
     *
     * @param int $seconds
     * @return self
     */
    public function setCostCacheDuration(int $seconds): self
    {
        $this->costCacheDuration = $seconds;
        return $this;
    }
}
