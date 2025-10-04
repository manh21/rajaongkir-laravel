<?php

return [
    'validation' => [
        'invalid_courier' => ':attribute harus berupa kode kurir yang valid. Pilihan yang tersedia: :couriers',
        'courier_not_found' => ':attribute yang dipilih tidak didukung.',

        // Pesan validasi untuk kalkulasi ongkos kirim
        'origin_required' => 'ID distrik asal wajib diisi.',
        'origin_must_be_integer' => 'ID distrik asal harus berupa angka yang valid.',
        'origin_must_be_positive' => 'ID distrik asal harus berupa angka positif.',
        'destination_required' => 'ID distrik tujuan wajib diisi.',
        'destination_must_be_integer' => 'ID distrik tujuan harus berupa angka yang valid.',
        'destination_must_be_positive' => 'ID distrik tujuan harus berupa angka positif.',
        'destination_must_be_different' => 'Tujuan harus berbeda dengan asal.',
        'weight_required' => 'berat paket wajib diisi.',
        'weight_must_be_integer' => 'Berat paket harus berupa angka yang valid.',
        'weight_must_be_positive' => 'Berat paket harus lebih dari 0 gram.',
        'weight_exceeds_limit' => 'Berat paket tidak boleh melebihi 30.000 gram (30kg).',
        'courier_required' => 'Minimal satu kurir harus dipilih.',
        'courier_must_be_array' => 'Pilihan kurir harus berupa array.',
        'courier_minimum_selection' => 'Minimal satu kurir harus dipilih.',
        'courier_maximum_selection' => 'Maksimal 5 kurir dapat dipilih sekaligus.',
        'sort_by_invalid' => 'Opsi sorting harus "lowest" atau "highest".',
    ],

    'attributes' => [
        'origin_id' => 'distrik asal',
        'destination_id' => 'distrik tujuan',
        'weight' => 'berat paket',
        'courier' => 'kurir',
        'sort_by' => 'opsi sorting',
    ],

    'api' => [
        '500' => 'Terjadi kesalahan pada server RajaOngkir. Silakan coba lagi nanti.',
    ],
];
