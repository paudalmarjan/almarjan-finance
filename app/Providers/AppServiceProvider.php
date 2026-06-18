<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $isHttps = env('FORCE_HTTPS') === true ||
                   env('FORCE_HTTPS') === 'true' ||
                   str_starts_with(config('app.url'), 'https://') ||
                   request()->header('X-Forwarded-Proto') === 'https' ||
                   request()->server('HTTP_X_FORWARDED_PROTO') === 'https' ||
                   (request()->header('CF-Visitor') && str_contains(request()->header('CF-Visitor'), 'https')) ||
                   request()->secure();

        if ($isHttps) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
