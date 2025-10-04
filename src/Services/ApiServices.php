<?php

namespace Komodo\RajaOngkir\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Komodo\RajaOngkir\Exceptions\ApiException;

class ApiServices
{
    private static ?array $response;
    private static ?array $data;

    /**
     * Generate header
     *
     * @param string $path
     * @return array
     */
    public static function getHeader(string $path): array
    {
        return [
            'Content-Type'  => 'application/x-www-form-urlencoded',
            'Accept'        => 'application/json',
            'Location'      => $path,
            'key'           => config('rajaongkir.api_key'),
        ];
    }

    /**
     * Api generator
     *
     * @param string $path
     * @param string $method
     * @param array $bodies
     * @return ApiServices
     */
    public static function api(string $path, string $method, array $bodies = [], array $params = []): ApiServices
    {
        $header = self::getHeader($path);
        $baseUrl = config('rajaongkir.base_url', '');
        $res = Http::withHeaders($header)
            ->withQueryParameters($params)
            ->$method($baseUrl.$path, $bodies);

        if ($res->failed()) {
            Log::critical($res->body());
            if ($res->status() < 500) {
                throw ApiException::fromResponse($res->body(), $res->status());
            } else {
                throw new ApiException(__("rajaongkir::rajaongkir.api.500"), 500);
            }
        }

        self::$response = $res;
        self::$data = self::dataToArray($res->json());

        // make sure the data is not empty or null
        if(is_null(self::$data) || empty(self::$data)){
            throw new ApiException(__("rajaongkir::rajaongkir.api.no_data"), 204);
        }

        return new self;
    }

    protected function dataToArray($result): ?array
    {
        return Arr::get($result, 'data', null);
    }

    public function all(): ?array
    {
        return self::$response;
    }

    public function data(): ?array
    {
        return self::$data;
    }
}
