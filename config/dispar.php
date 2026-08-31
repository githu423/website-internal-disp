<?php

/**
 * Konfigurasi SIPARDIS — mendukung penamaan env model baru (DISPAR_*)
 * maupun yang lama yang dipakai di file . (.env user):
 *   LOGIN_CAPTCHA_ENABLED, PEGAWAI_IMPORT_PATH, DEFAULT_PEGAWAI_PASSWORD, ADMIN_NIP, ADMIN_PASSWORD, ADMIN_NAME
 */
return [
    'captcha' => filter_var(env('DISPAR_CAPTCHA', env('LOGIN_CAPTCHA_ENABLED', false)), FILTER_VALIDATE_BOOLEAN),

    'password_default_pegawai' => env('DISPAR_PEGAWAI_PASSWORD') ?: env('DEFAULT_PEGAWAI_PASSWORD', 'DISPARCiamis2026'),

    'admin' => [
        'nip' => env('ADMIN_NIP', 'ADMIN0000'),
        'password' => env('ADMIN_PASSWORD', 'admin123'),
        'name' => env('ADMIN_NAME', 'Administrator Dispar'),
    ],

    // lokasi file sumber (relatif terhadap root proyek); bila kosong/di luar jangkauan,
    // sistem otomatis memakai salinan cadangan di database/source/
    'sumber' => [
        'pegawai' => env('PEGAWAI_IMPORT_PATH', 'storage/app/private/PEGAWAI_DISPAR.csv'),
        'kinerja' => env('KINERJA_IMPORT_PATH', 'storage/app/private/Kinerja Fisik Sedinas.csv'),
        'pad' => env('PAD_IMPORT_PATH', 'storage/app/private/PAD.csv'),
    ],
];
