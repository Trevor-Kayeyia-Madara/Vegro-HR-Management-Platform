<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
 * DirectAdmin deployment layout:
 *   public_html/      (this file)
 *   laravel/          (the application, outside the web root)
 */
$laravelPath = __DIR__ . '/../laravel';
$environmentPath = $laravelPath . '/.env';
if (!is_file($environmentPath)) {
    $parentEnvironmentPath = __DIR__ . '/../.env';
    $exampleEnvironmentPath = $laravelPath . '/.env.example';
    if (is_file($parentEnvironmentPath)) {
        @copy($parentEnvironmentPath, $environmentPath);
    } elseif (is_file($exampleEnvironmentPath)) {
        @copy($exampleEnvironmentPath, $environmentPath);
    }
}

if (file_exists($maintenance = __DIR__ . '/../laravel/storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__ . '/../laravel/vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__ . '/../laravel/bootstrap/app.php';

$app->handleRequest(Request::capture());
