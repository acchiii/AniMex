<?php

return [

    'recaptcha' => [
        'enabled' => env('RECAPTCHA_ENABLED', false),
        'site_key' => env('RECAPTCHA_SITE_KEY', ''),
        'secret_key' => env('RECAPTCHA_SECRET_KEY', ''),
    ],

    'consumet' => [
        'base_url' => env('CONSUMET_BASE_URL', 'http://localhost:3000'),
        'provider' => env('CONSUMET_PROVIDER', 'gogoanime'),
        'fallback_providers' => explode(',', env('CONSUMET_FALLBACK_PROVIDERS', 'zoro,enime')),
    ],

    'anipub' => [
        'base_url' => env('ANIPUB_BASE_URL', 'https://anipub.xyz'),
    ],

];
