<?php

namespace App\Providers;

use App\Models\User;
use App\Observers\UserObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once app_path('Support/feature_helper.php');
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);
        User::observe(UserObserver::class);
    }
}
