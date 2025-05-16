<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'oauth/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'https://terranafa.moulweb.com',
        'http://localhost:3000',
        'https://moulweb.com'
    ],

    'allowed_origins_patterns' => [
        '/\.moulweb\.com$/i' // Regex pattern for subdomains
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [
        'Authorization',
        'X-CSRF-TOKEN'
    ],

    'max_age' => 86400,

    'supports_credentials' => true,

];
