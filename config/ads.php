<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PropellerAds Configuration
    |--------------------------------------------------------------------------
    |
    | Set your zone IDs from your PropellerAds account.
    | Leave as null/empty to disable.
    |
    | To enable, add to your .env file:
    |   PROPELLERADS_INTERSTITIAL_ZONE=your_zone_id
    |   PROPELLERADS_POPUNDER_ZONE=your_zone_id
    |
    */
    'propellerads' => [
        'interstitial_zone' => env('PROPELLERADS_INTERSTITIAL_ZONE'),
        'popunder_zone' => env('PROPELLERADS_POPUNDER_ZONE'),
    ],
];
