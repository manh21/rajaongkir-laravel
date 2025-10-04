# RajaOngkir Komerce Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/manh21/rajaongkir-laravel.svg?style=flat-square)](https://packagist.org/packages/manh21/rajaongkir-laravel)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/manh21/rajaongkir-laravel/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/manh21/rajaongkir-laravel/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/manh21/rajaongkir-laravel/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/manh21/rajaongkir-laravel/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/manh21/rajaongkir-laravel.svg?style=flat-square)](https://packagist.org/packages/manh21/rajaongkir-laravel)

**RajaOngkir Komerce Laravel** adalah package PHP yang menyediakan integrasi mudah dan lengkap dengan API RajaOngkir V2 by Komerce. Package ini dirancang khusus untuk aplikasi Laravel dengan fitur-fitur modern seperti caching, validasi, dan dukungan multi-bahasa.

## ✨ Fitur Utama

- 🚀 **Perhitungan Ongkos Kirim**: Mendukung perhitungan ongkir domestik dengan multiple kurir
- 📍 **Data Lokasi Lengkap**: Akses data provinsi, kota, kecamatan, dan kelurahan
- 🏷️ **Validasi Terintegrasi**: Form Request validation dengan CourierRule yang robust  
- 💾 **Caching Pintar**: Sistem cache dengan tags untuk performa optimal
- 🌐 **Multi-bahasa**: Dukungan bahasa Indonesia dan Inggris
- 🛡️ **Exception Handling**: Penanganan error yang komprehensif
- 📦 **Laravel Ready**: Dibuat khusus untuk ekosistem Laravel

## 📋 Kurir yang Didukung

Mendukung 13+ kurir populer di Indonesia:
- JNE (Jalur Nugraha Ekakurir)
- TIKI (Citra Van Titipan Kilat) 
- POS Indonesia
- SiCepat Express
- J&T Express
- Ninja Xpress
- ID Express
- SAP Express
- Wahana Express
- Lion Parcel
- Royal Express Indonesia (REX)
- Sentral Cargo
- Dan lainnya...

## Installation

You can install the package via composer:

```bash
composer require manh21/rajaongkir-laravel
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="rajaongkir-laravel-config"
```

This is the contents of the published config file:

```php
return [
    'api_key' => env('RAJAONGKIR_API_KEY'),
    'base_url' => env('RAJAONGKIR_BASE_URL', 'https://rajaongkir.komerce.id/api/v1'),
    'cost_cache_duration' => env('RAJAONGKIR_COST_CACHE_DURATION', 60), // in minutes
    'location_cache_duration' => env('RAJAONGKIR_LOCATION_CACHE_DURATION', 1440), // in minutes (1 day
];
```

## 🚀 Usage

### Basic Usage

```php
use Komodo\RajaOngkir\Rajaongkir;
use Komodo\RajaOngkir\Constants\Courier;

$rajaongkir = new Rajaongkir();
```

### Mendapatkan Data Lokasi

```php
// Ambil semua provinsi
$provinces = $rajaongkir->getProvinces();

// Ambil kota berdasarkan provinsi
$cities = $rajaongkir->getCities(11); // DKI Jakarta

// Ambil kecamatan berdasarkan kota
$districts = $rajaongkir->getDistricts(152); // Jakarta Pusat

// Ambil kelurahan berdasarkan kecamatan  
$subdistricts = $rajaongkir->getSubdistricts(1234);
```

### Perhitungan Ongkos Kirim

#### Menggunakan Individual Parameters

```php
$cost = $rajaongkir->calculateDistrictCost(
    originId: 152,           // ID Kecamatan asal
    destinationId: 153,      // ID Kecamatan tujuan
    weight: 1000,           // Berat dalam gram (1kg)
    courier: [              // Array kurir
        Courier::JNE->value,
        Courier::TIKI->value,
        Courier::SICEPAT->value
    ],
    sortBy: 'lowest'        // Urutkan berdasarkan harga terendah
);
```

#### Menggunakan Form Request (Untuk Controller)

```php
use Komodo\RajaOngkir\Requests\CalculateCostRequest;

public function calculateCost(CalculateCostRequest $request, Rajaongkir $rajaongkir)
{
    // Validasi otomatis melalui Form Request
    $result = $rajaongkir->districtCalculateCost($request);
    
    return response()->json([
        'success' => true,
        'data' => $result
    ]);
}
```

### Menggunakan Courier Enum

```php
use Komodo\RajaOngkir\Constants\Courier;

// Menggunakan enum untuk type safety
$courierCode = Courier::JNE->value; // 'jne'

// Cek kemampuan kurir
use Komodo\RajaOngkir\Rules\CourierRule;

if (CourierRule::supportsInternationalCost('jne')) {
    // JNE mendukung ongkir internasional
}

if (CourierRule::supportsAwb('sicepat')) {
    // SiCepat mendukung pelacakan resi
}
```

### Cache Management

```php
// Set custom cache duration
$rajaongkir->setLocationCacheDuration(7200)  // 2 jam untuk data lokasi
          ->setCostCacheDuration(1800);      // 30 menit untuk ongkir

// Clear cache
$rajaongkir->clearLocationCache();           // Hapus cache lokasi
$rajaongkir->clearCostCache();              // Hapus cache ongkir
$rajaongkir->clearCache();                  // Hapus semua cache
```

### Exception Handling

```php
use Komodo\RajaOngkir\Exceptions\ApiException;
use Illuminate\Validation\ValidationException;

try {
    $cost = $rajaongkir->calculateDistrictCost(
        originId: 152,
        destinationId: 152, // Error: sama dengan origin
        weight: 50000,      // Error: melebihi batas
        courier: ['invalid'] // Error: kurir tidak valid
    );
} catch (ValidationException $e) {
    // Handle validation errors
    $errors = $e->validator->errors();
    
} catch (ApiException $e) {
    // Handle API errors
    $message = $e->getMessage();
    $statusCode = $e->getStatusCode();
    $response = $e->getResponse();
}
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Naufal Hakim](https://github.com/manh21)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
