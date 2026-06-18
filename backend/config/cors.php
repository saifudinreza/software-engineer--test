<?php

return [
    // The frontend is a separate project, so the API must allow cross-origin requests.
    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    // Allow any localhost/127.0.0.1 port (covers the Vite dev server).
    'allowed_origins' => [],

    'allowed_origins_patterns' => [
        '#^http://(localhost|127\.0\.0\.1)(:\d+)?$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // No cookies/session are used by the API, so credentials are not needed.
    'supports_credentials' => false,
];
