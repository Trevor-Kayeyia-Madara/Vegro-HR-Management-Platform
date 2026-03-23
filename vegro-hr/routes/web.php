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
    $base = rtrim(config('app.url'), '/'); // Get APP_URL from .env

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