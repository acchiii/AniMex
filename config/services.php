<?php

return [

    'recaptcha' => [
        'enabled' => env('RECAPTCHA_ENABLED', false),
        'site_key' => env('RECAPTCHA_SITE_KEY', ''),
        'secret_key' => env('RECAPTCHA_SECRET_KEY', ''),
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

    'anivexa' => [
        'base_url' => env('ANIVEXA_BASE_URL', ''),
        'providers' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('ANIVEXA_PROVIDERS', 'anihq,reanime,anikoto,animegg,animenosub,anizone,aniwaves,pahenime,kaa,animedunya,animeonsen,anipm'))
        ))),
    ],

    'opensubtitles' => [
        'api_key' => env('OPENSUBTITLES_API_KEY', ''),
    ],

    'jimaku' => [
        'api_key' => env('JIMAKU_API_KEY', ''),
    ],

];
