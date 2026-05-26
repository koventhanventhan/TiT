<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// Polyfill for PHP 8.4 Dom API which is expected by some newer Symfony components
if (!class_exists('Dom\Node')) {
    eval('namespace Dom; class Node {}');
}
if (!class_exists('Dom\HTMLDocument')) {
    eval('namespace Dom; class HTMLDocument extends \DOMDocument { 
        public static function createFromString(string $source, ?string $encoding = null, int $options = 0): self {
            $doc = new self();
            @$doc->loadHTML($source, $options);
            return $doc;
        }
    }');
}

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
        // Fix for cPanel subfolder API routing
        if (request()->is('api/*') || str_contains(request()->getRequestUri(), '/api/')) {
            config(['app.url' => config('app.url') . '/api']);
        }

        if (config('app.env') === 'production') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
            
            // Re-configure Livewire for cPanel
            if (class_exists(\Livewire\Livewire::class)) {
                \Livewire\Livewire::setUpdateRoute(function ($handle) {
                    return \Illuminate\Support\Facades\Route::post('/livewire/update', $handle);
                });
                
                \Livewire\Livewire::setScriptRoute(function ($handle) {
                    return \Illuminate\Support\Facades\Route::get('/livewire/livewire.js', $handle);
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
