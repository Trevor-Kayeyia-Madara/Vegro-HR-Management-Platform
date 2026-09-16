<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Root `/` route returns JSON with dynamic API URLs based on APP_URL.
|
*/
/*Route::get('/clear-cache', function () {
    Artisan::call('route:clear');
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('view:clear');

    return "Cache cleared";
});


Route::get('/deploy', function () {

    DB::statement('SET FOREIGN_KEY_CHECKS=0;');

    // Step 1: Drop everything
    Artisan::call('migrate:fresh', [
        '--force' => true,
        '--path' => 'database/migrations',
    ]);

    // Seed
    Artisan::call('db:seed', ['--force' => true]);

    return "Deployment completed";
});
*/
Route::get('/', function () {
    // Serve the Vue.js frontend
    $frontendPath = public_path('vegro-hr-frontend/dist/index.html');

    if (file_exists($frontendPath)) {
        return file_get_contents($frontendPath);
    }

    // Fallback to API info if frontend not found
    $base = rtrim(config('app.url'), '/');

    return response()->json([
        'message' => 'Welcome to Vegro HR API',
        'routes' => [
            'departments' => $base . '/api/departments',
            'employees' => $base . '/api/employees',
            'payrolls' => $base . '/api/payrolls',
            'payslips' => $base . '/api/payslips',
            'attendances' => $base . '/api/attendances',
            'leave-requests' => $base . '/api/leave-requests',
            'auth' => [
                'login' => $base . '/api/auth/login',
                'register' => $base . '/api/auth/register',
                'logout' => $base . '/api/auth/logout',
                'me' => $base . '/api/auth/me',
                'check' => $base . '/api/auth/check',
            ]
        ]
    ]);
});

// Serve Vue.js frontend assets
Route::get('/{path}', function ($path) {
    $frontendPath = public_path('vegro-hr-frontend/dist/' . $path);

    if (file_exists($frontendPath)) {
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $mimeTypes = [
            'js' => 'application/javascript',
            'css' => 'text/css',
            'html' => 'text/html',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject',
        ];

        $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';

        return response()->file($frontendPath, ['Content-Type' => $mimeType]);
    }

    // For SPA routing, return index.html for non-existent routes
    $indexPath = public_path('vegro-hr-frontend/dist/index.html');
    if (file_exists($indexPath)) {
        return file_get_contents($indexPath);
    }

    return abort(404);
})->where('path', '.*');