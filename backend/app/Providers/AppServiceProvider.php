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
            
            // Configure Livewire to use the working /api path on cPanel
            if (class_exists(\Livewire\Livewire::class)) {
                \Livewire\Livewire::setUpdateRoute(function ($handle) {
                    return \Illuminate\Support\Facades\Route::post('/api/livewire/update', $handle);
                });
                
                \Livewire\Livewire::setScriptRoute(function ($handle) {
                    return \Illuminate\Support\Facades\Route::get('/api/livewire/livewire.js', $handle);
                });
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
