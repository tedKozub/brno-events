<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
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

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $isLocalHost = false;
        try {
            $host = request()->getHost();
            $isLocalHost = in_array($host, ['127.0.0.1', 'localhost']);
        } catch (\Throwable $e) {
            $isLocalHost = false;
        }

        if (!$isLocalHost && (app()->environment('production') || str_starts_with(config('app.url'), 'https://'))) {
            URL::forceScheme('https');
        }
    }
}
