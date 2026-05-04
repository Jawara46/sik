<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Versi Aplikasi
    |--------------------------------------------------------------------------
    */
    'version' => '1.4.2',
    'app_name' => 'SIK-T',
    'app_fullname' => 'Sistem Informasi Kelulusan Terpadu',
    'developer' => 'Yazid Digital',
    'developer_url' => 'https://yazid.my.id',
    'github_repo' => 'Jawara46/sik',

    /*
    |--------------------------------------------------------------------------
    | Pengumuman Kelulusan
    |--------------------------------------------------------------------------
    */
    'announcement_date' => env('ANNOUNCEMENT_DATE'),
    'announcement_timezone' => env('ANNOUNCEMENT_TIMEZONE', env('APP_TIMEZONE', 'Asia/Jakarta')),

    /*
    |--------------------------------------------------------------------------
    | Locale
    |--------------------------------------------------------------------------
    */
    'supported_locales' => ['id', 'en'],
];
