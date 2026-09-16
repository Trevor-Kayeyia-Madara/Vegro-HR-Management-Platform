<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| API-Only Routes for Vegro HR Backend
| Frontend is served separately on app.vegro-hr.invodtechltd.com
|
*/

// API information endpoint
Route::get('/', function () {
    $base = rtrim(config('app.url'), '/');

    return response()->json([
        'message' => 'Vegro HR API',
        'version' => '1.0.0',
        'frontend' => 'https://app.vegro-hr.invodtechltd.com',
        'api_endpoints' => [
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