<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | The storefront is a separate Vite app on its own origin, so every origin
    | allowed to call this API has to be listed here. An origin is scheme +
    | host + port with no trailing slash — "https://example.com/" never
    | matches what the browser actually sends.
    |
    | After editing this file on the server run `php artisan config:clear`,
    | or the cached config keeps serving the old list.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        // Local development — Vite moves up a port when one is already in use.
        'http://localhost:5173',
        'http://localhost:5174',
        'http://localhost:5175',

        // Production storefront.
        'https://naziabotanics.com',
        'https://www.naziabotanics.com',
        'https://naz.biggbrodaclothing.com',

        // The Vercel deployment the custom domain points at.
        'https://nazia-botanics.vercel.app',
    ],

    'allowed_origins_patterns' => [
        // Vercel mints a new hostname per deployment, e.g.
        // nazia-botanics-git-main-eddy.vercel.app
        '#^https://nazia-botanics.*\.vercel\.app$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 86400,

    // The dashboard authenticates with a bearer token rather than cookies, so
    // this can stay false. It is only needed if the browser has to send
    // cookies cross-origin — and with it on, no origin may be '*'.
    'supports_credentials' => false,

];
