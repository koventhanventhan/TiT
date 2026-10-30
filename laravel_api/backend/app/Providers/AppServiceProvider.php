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
        // Bind VectorStoreInterface to current driver (DatabaseVectorStore)
        $this->app->bind(
            \App\Contracts\Rag\VectorStoreInterface::class,
            \App\Services\Rag\DatabaseVectorStore::class
        );

        // Bind GeminiClientInterface
        $this->app->bind(
            \App\Contracts\Rag\GeminiClientInterface::class,
            \App\Services\Rag\GeminiRagClient::class
        );

        // 100% Foolproof method for cPanel (Addon domains & public_html)
        if (isset($_SERVER['DOCUMENT_ROOT']) && is_dir($_SERVER['DOCUMENT_ROOT'])) {
            $this->app->bind('path.public', function() {
                return $_SERVER['DOCUMENT_ROOT'];
            });
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Normalize base URL if request arrives with internal script prefix
        $baseUrl = rtrim(request()->getBaseUrl(), '/');
        if (str_ends_with($baseUrl, '/laravel_api/backend/public') || $baseUrl === '/laravel_api/backend/public') {
            \Illuminate\Support\Facades\URL::forceRootUrl(request()->getSchemeAndHttpHost());
        }

        // Fix for cPanel subfolder API routing
        if (request()->is('api/*') || request()->is('api')) {
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

        // Dedicated view composer for admin layout to inject tenant-isolated theme preset
        \Illuminate\Support\Facades\View::composer(['layouts.admin', 'admin.*'], function ($view) {
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('site_settings')) {
                    $instituteId = auth()->check() ? auth()->user()->institute_id : null;
                    $preset = \App\Models\SiteSetting::getForInstitute('theme_preset', $instituteId, 'royal');
                    $data = $view->getData();
                    $settings = $data['site_settings'] ?? null;
                    if ($settings instanceof \Illuminate\Support\Collection) {
                        $settings->put('theme_preset', $preset);
                    } elseif (is_array($settings)) {
                        $settings['theme_preset'] = $preset;
                        $view->with('site_settings', $settings);
                    } else {
                        $allSettings = \App\Models\SiteSetting::where('institute_id', $instituteId ?: 1)->pluck('value', 'key');
                        if ($allSettings->isEmpty()) {
                            $allSettings = \App\Models\SiteSetting::all()->pluck('value', 'key');
                        }
                        $allSettings->put('theme_preset', $preset);
                        $view->with('site_settings', $allSettings);
                    }
                    $view->with('current_theme_preset', $preset);
                }
            } catch (\Exception $e) {
                // Graceful fallback
            }
        });

        // Login alert listener is intentionally DISABLED (no email on login).
        // To re-enable, uncomment the lines below.
        // \Illuminate\Support\Facades\Event::listen(
        //     \Illuminate\Auth\Events\Login::class,
        //     [\App\Listeners\SendLoginAlert::class, 'handle']
        // );
    }
}
