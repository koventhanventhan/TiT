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
        // Load global helpers
        if (!function_exists('current_institute')) {
            require_once __DIR__ . '/../helpers.php';
        }

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

        $this->configureQueueTenantContext();

        \Illuminate\Support\Facades\Gate::policy(
            \App\Models\InstituteIntegration::class,
            \App\Policies\InstituteIntegrationPolicy::class
        );
    }

    /**
     * Embed current tenant `institute_id` into queued job payloads at dispatch time
     * and restore/clear tenant + per-institute mailer configuration in queue workers.
     */
    protected function configureQueueTenantContext(): void
    {
        \Illuminate\Support\Facades\Queue::createPayloadUsing(function ($connection, $queue, $payload) {
            $instituteId = \App\Support\InstituteMail::$dispatchingInstituteId
                ?? \App\Support\Tenant::id()
                ?? (auth()->check() ? auth()->user()?->institute_id : null);

            if (empty($instituteId)) {
                $command = $payload['data']['command'] ?? null;
                if (is_object($command)) {
                    if (isset($command->instituteId) && is_numeric($command->instituteId)) {
                        $instituteId = (int) $command->instituteId;
                    } elseif (isset($command->institute_id) && is_numeric($command->institute_id)) {
                        $instituteId = (int) $command->institute_id;
                    } elseif (isset($command->mailable) && is_object($command->mailable)) {
                        $mailable = $command->mailable;
                        $instituteId = $mailable->user->institute_id
                            ?? $mailable->student->institute_id
                            ?? $mailable->payment->institute_id
                            ?? null;
                    }
                }
            }

            return !empty($instituteId) && (int) $instituteId > 0
                ? ['institute_id' => (int) $instituteId]
                : [];
        });

        $snapshots = [];

        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Queue\Events\JobProcessing::class,
            function (\Illuminate\Queue\Events\JobProcessing $event) use (&$snapshots): void {
                $payload = $event->job->payload();
                $instituteId = isset($payload['institute_id']) ? (int) $payload['institute_id'] : 0;

                if ($instituteId <= 0) {
                    return;
                }

                $key = spl_object_id($event->job);
                $snapshots[$key] = [
                    'tenant' => \App\Support\Tenant::current(),
                    'default_mailer' => config('mail.default'),
                    'mail_from' => config('mail.from'),
                    'institute_id' => $instituteId,
                ];

                $institute = \Illuminate\Support\Facades\Schema::hasTable('institutes')
                    ? \App\Models\Institute::withoutGlobalScopes()->find($instituteId)
                    : null;

                \App\Support\Tenant::set($institute);

                $configured = \App\Support\InstituteMail::configureMailer($instituteId);
                if (!empty($configured['mailer']) && !empty($configured['creds'])) {
                    config([
                        'mail.default' => $configured['mailer'],
                        'mail.from' => [
                            'address' => $configured['creds']->fromAddress,
                            'name' => $configured['creds']->fromName,
                        ],
                    ]);
                }
            }
        );

        $restoreContext = function (object $job) use (&$snapshots): void {
            $key = spl_object_id($job);
            if (!array_key_exists($key, $snapshots)) {
                \App\Support\Tenant::set(null);
                return;
            }

            $snap = $snapshots[$key];
            unset($snapshots[$key]);

            config([
                'mail.default' => $snap['default_mailer'],
                'mail.from' => $snap['mail_from'],
            ]);
            \App\Support\Tenant::set($snap['tenant']);
        };

        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Queue\Events\JobProcessed::class,
            function (\Illuminate\Queue\Events\JobProcessed $event) use ($restoreContext): void {
                $restoreContext($event->job);
            }
        );

        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Queue\Events\JobFailed::class,
            function (\Illuminate\Queue\Events\JobFailed $event) use ($restoreContext): void {
                try {
                    $payload = $event->job->payload();
                    $instituteId = isset($payload['institute_id']) ? (int) $payload['institute_id'] : 0;
                    $displayName = (string) ($payload['displayName'] ?? '');
                    $commandName = (string) ($payload['data']['commandName'] ?? '');

                    if (
                        $instituteId > 0
                        && (
                            $commandName === \Illuminate\Mail\SendQueuedMailable::class
                            || is_subclass_of($displayName, \Illuminate\Contracts\Mail\Mailable::class)
                        )
                    ) {
                        \App\Support\InstituteMail::handleFailure($instituteId, $event->exception);
                    }
                } finally {
                    $restoreContext($event->job);
                }
            }
        );
    }
}
