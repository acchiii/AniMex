<?php

return [

    'recaptcha' => [
        'enabled' => env('RECAPTCHA_ENABLED', false),
        'site_key' => env('RECAPTCHA_SITE_KEY', ''),
        'secret_key' => env('RECAPTCHA_SECRET_KEY', ''),
    ],

    'consumet' => [
        'base_url' => env('CONSUMET_BASE_URL', 'https://animex-consumet.vercel.app'),
    ],

    'anipub' => [
        'base_url' => env('ANIPUB_BASE_URL', 'https://anipub.xyz'),
    ],

    'jikan' => [
        'base_url' => env('JIKAN_BASE_URL', 'https://api.jikan.moe/v4'),
    ],

    'anilist' => [
        'graphql_url' => env('ANILIST_GRAPHQL_URL', 'https://graphql.anilist.co'),
    ],

    'opensubtitles' => [
        'api_key' => env('OPENSUBTITLES_API_KEY', ''),
    ],

    'jimaku' => [
        'api_key' => env('JIMAKU_API_KEY', ''),
    ],

];
