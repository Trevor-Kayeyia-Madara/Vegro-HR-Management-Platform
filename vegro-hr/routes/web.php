<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Single-Domain Routes - Serves Vue.js Frontend + Laravel Backend
|
*/

// Serve Vue.js frontend
Route::get('/', function () {
    $frontendPath = public_path('vegro-hr-frontend/dist/index.html');
    
    if (file_exists($frontendPath)) {
        return file_get_contents($frontendPath);
    }
    
    // Fallback to API info if frontend not found
    $base = rtrim(config('app.url'), '/');
    return response()->json([
        'message' => 'Welcome to Vegro HR API',
        'frontend' => 'https://vegro-hr.invodtechltd.com',
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

// Serve Vue.js frontend assets
Route::get('/{path}', function ($path) {
    // Check if it's an API route
    if (str_starts_with($path, 'api/')) {
        return abort(404);
    }
    
    // Check if it's a helper script
    if (in_array($path, ['generate_key.php', 'link_storage.php', 'migrate.php', 'clear_cache.php', 'fix_routes.php', 'debug_env.php', 'test_direct_db.php'])) {
        return abort(404);
    }
    
    // Try to serve frontend assets
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