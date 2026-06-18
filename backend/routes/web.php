<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json([
    'name' => 'Form Submission API',
    'frontend' => 'Separate project in ../frontend (Vite). Run: npm install && npm run dev',
    'endpoints' => [
        'GET /api/form',
        'POST /api/submissions',
        'GET /api/submissions/{id}',
    ],
]));
