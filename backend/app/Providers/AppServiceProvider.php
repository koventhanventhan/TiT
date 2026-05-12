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

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') === 'production') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
            
            // Force Livewire to use the working API path on cPanel
            if (class_exists(\Livewire\Livewire::class)) {
                $baseUrl = 'https://titjaffna.lk/api';
                
                \Livewire\Livewire::setUpdateRoute(function ($handle) use ($baseUrl) {
                    return \Illuminate\Support\Facades\Route::post('/livewire/update', $handle);
                });
                
                \Livewire\Livewire::setScriptRoute(function ($handle) use ($baseUrl) {
                    return \Illuminate\Support\Facades\Route::get('/livewire/livewire.js', $handle);
                });
                
                // Set the asset URL so it uses the /api prefix
                config(['livewire.asset_url' => $baseUrl]);
            }
        }

        \Illuminate\Support\Facades\Schema::defaultStringLength(191);
        \Illuminate\Pagination\Paginator::useBootstrap();

        // Share site settings with all views
        if (!app()->runningInConsole()) {
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('site_settings')) {
                    \Illuminate\Support\Facades\View::share('site_settings', \App\Models\SiteSetting::all()->pluck('value', 'key'));
                } else {
                    \Illuminate\Support\Facades\View::share('site_settings', collect());
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Database connection failed in AppServiceProvider: ' . $e->getMessage());
                \Illuminate\Support\Facades\View::share('site_settings', collect());
            }
        }

        // Register login alert listener
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Login::class,
            [\App\Listeners\SendLoginAlert::class, 'handle']
        );
    }
}
