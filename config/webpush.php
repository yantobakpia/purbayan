<?php

return [

    /*
    |--------------------------------------------------------------------------
    | VAPID Keys
    |--------------------------------------------------------------------------
    |
    | Kunci VAPID dipakai untuk mengidentifikasi server ke push service
    | (FCM untuk Android/Chrome, Apple Push untuk iOS/Safari).
    | Buat sekali dengan: php artisan webpush:keys
    |
    | Subject harus berupa URL situs atau mailto: alamat email yang valid.
    |
    */

    'subject' => env('VAPID_SUBJECT', env('APP_URL', 'http://localhost')),

    'public_key' => env('VAPID_PUBLIC_KEY'),

    'private_key' => env('VAPID_PRIVATE_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Pengiriman via Queue
    |--------------------------------------------------------------------------
    |
    | Kalau true, pengiriman push dijalankan lewat queue supaya request
    | tidak tertahan menunggu push service. Butuh queue worker aktif
    | (php artisan queue:work). Set false kalau tidak menjalankan worker.
    |
    */

    'queue' => env('PUSH_QUEUE', true),

    /*
    |--------------------------------------------------------------------------
    | Time To Live
    |--------------------------------------------------------------------------
    |
    | Berapa lama (detik) push service menyimpan notifikasi kalau perangkat
    | sedang offline. Default 12 jam.
    |
    */

    'ttl' => env('PUSH_TTL', 43200),

    /*
    |--------------------------------------------------------------------------
    | Default Notifikasi
    |--------------------------------------------------------------------------
    */

    'icon' => '/icons/icon-192.png',

    'badge' => '/icons/badge-72.png',

];
