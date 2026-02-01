<?php

/*
|--------------------------------------------------------------------------
| CORS Configuration
|--------------------------------------------------------------------------
|
| Set FRONTEND_URL environment variable to your Vercel frontend URL.
| Example: FRONTEND_URL=https://your-app.vercel.app
|
*/

$frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');

// Parse multiple URLs if comma-separated
$allowedOrigins = array_filter(array_map('trim', explode(',', $frontendUrl)));

// Add localhost for development
if (env('APP_ENV') === 'local') {
    $allowedOrigins = array_merge($allowedOrigins, [
        'http://localhost:5173',
        'http://localhost:3000',
        'http://127.0.0.1:5173',
        'http://127.0.0.1:3000',
    ]);
}

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_unique($allowedOrigins),

    'allowed_origins_patterns' => [
        // Match any Vercel preview/production URLs for your app
        '^https://.*\.vercel\.app$',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => true,
];
