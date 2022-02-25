<?php

return [
    'paypal' => [
        'client_id'     => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
    ],

    'firefly' => [
        'uri'     => env('FIREFLY_URI'),
        'token'   => env('FIREFLY_TOKEN'),
        'account' => env('FIREFLY_PAYPAL_ACCOUNT_ID'),
    ],
];
