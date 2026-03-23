<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'check.api.token' => \App\Http\Middleware\CheckApiToken::class,
            'superadmin' => \App\Http\Middleware\RequireSuperAdmin::class,
            'tenant' => \App\Http\Middleware\EnsureCompanyContext::class,
            'tenant.env' => \App\Http\Middleware\EnsureCompanyEnvironment::class,
            'tenant.domain' => \App\Http\Middleware\ResolveCompanyFromDomain::class,
            'role' => \App\Http\Middleware\RequireRole::class,
            'permission' => \App\Http\Middleware\RequirePermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
