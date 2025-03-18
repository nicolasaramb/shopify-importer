<?php

return [
    'ims' => [
        'api_url' => env('IMS_API_URL'),
        'username' => env('IMS_USERNAME'),
        'password' => env('IMS_PASSWORD'),
    ],
    
    'shopify' => [
        'store' => env('SHOPIFY_STORE'),
        'access_token' => env('SHOPIFY_ACCESS_TOKEN'),
    ],
];
