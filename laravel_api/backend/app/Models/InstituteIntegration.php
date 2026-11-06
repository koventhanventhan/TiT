<?php

namespace App\Models;

use App\Services\ActivityLogService;
use App\Support\Redact;
use App\Traits\BelongsToInstitute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstituteIntegration extends Model
{
    use HasFactory, BelongsToInstitute;

    public const PROVIDER_PAYHERE = 'payhere';
    public const PROVIDER_WHATSAPP = 'whatsapp';
    public const PROVIDER_MAIL = 'mail';

    public const PROVIDERS = [
        self::PROVIDER_PAYHERE,
        self::PROVIDER_WHATSAPP,
        self::PROVIDER_MAIL,
    ];

    public const STATUS_UNCONFIGURED = 'unconfigured';
    public const STATUS_CONFIGURED = 'configured';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_FAILING = 'failing';

    public const STATUSES = [
        self::STATUS_UNCONFIGURED,
        self::STATUS_CONFIGURED,
        self::STATUS_VERIFIED,
        self::STATUS_FAILING,
    ];

    protected $fillable = [
        'institute_id',
        'provider',
        'credentials',
        'settings',
        'status',
        'last_verified_at',
        'last_error',
    ];

    /**
     * Never serialize raw or decrypted credentials in arrays/JSON.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'credentials',
    ];

    protected $casts = [
        'credentials' => 'encrypted:array',
        'settings' => 'array',
        'last_verified_at' => 'datetime',
    ];

    /**
     * Transient snapshot used during Eloquent update lifecycle to record changed field names only.
     *
     * @var array{fields: array<int, string>, cleared: bool}|null
     */
    protected ?array $pendingAuditDiff = null;

    protected static function booted(): void
    {
        static::created(function (self $model): void {
            $fields = $model->collectPresentFieldNames(
                is_array($model->credentials) ? $model->credentials : [],
                is_array($model->settings) ? $model->settings : []
            );

            $action = $model->status === self::STATUS_UNCONFIGURED && empty($fields)
                ? 'integration.cleared'
                : 'integration.created';

            self::recordAudit($model, $action, $fields);
        });

        static::updating(function (self $model): void {
            $oldCreds = [];
            if ($model->getRawOriginal('credentials') !== null) {
                try {
                    $decrypted = $model->castAttribute('credentials', $model->getRawOriginal('credentials'));
                    $oldCreds = is_array($decrypted) ? $decrypted : [];
                } catch (\Throwable) {
                    $oldCreds = [];
                }
            }

            $oldSettingsRaw = $model->getRawOriginal('settings');
            $oldSettings = is_string($oldSettingsRaw)
                ? (json_decode($oldSettingsRaw, true) ?: [])
                : (is_array($oldSettingsRaw) ? $oldSettingsRaw : []);

            $newCreds = is_array($model->credentials) ? $model->credentials : [];
            $newSettings = is_array($model->settings) ? $model->settings : [];

            $changedFields = self::diffFieldNames($oldCreds, $newCreds, $oldSettings, $newSettings);
            $wasCleared = ($model->status === self::STATUS_UNCONFIGURED)
                && ($model->getOriginal('status') !== self::STATUS_UNCONFIGURED || !empty($oldCreds));

            $model->pendingAuditDiff = [
                'fields' => $changedFields,
                'cleared' => $wasCleared && empty($newCreds),
            ];
        });

        static::updated(function (self $model): void {
            $diff = $model->pendingAuditDiff;
            $model->pendingAuditDiff = null;

            if (!$diff) {
                return;
            }

            if ($diff['cleared']) {
                self::recordAudit($model, 'integration.cleared', $diff['fields']);
                return;
            }

            if (!empty($diff['fields'])) {
                self::recordAudit($model, 'integration.updated', $diff['fields']);
            }
        });

        static::deleted(function (self $model): void {
            $fields = $model->collectPresentFieldNames(
                is_array($model->credentials) ? $model->credentials : [],
                is_array($model->settings) ? $model->settings : []
            );
            self::recordAudit($model, 'integration.cleared', $fields);
        });
    }

    /**
     * Return masked credential representations (`••••` + last 4 chars only).
     * Never returns full secret values.
     *
     * @return array<string, string>
     */
    public function maskedCredentials(): array
    {
        $creds = is_array($this->credentials) ? $this->credentials : [];
        $masked = [];

        foreach ($creds as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            if (is_scalar($value)) {
                $masked[(string) $key] = Redact::maskValue((string) $value);
            }
        }

        return $masked;
    }

    /**
     * Check whether a non-empty secret exists for the given key without exposing it.
     */
    public function hasCredential(string $key): bool
    {
        $creds = is_array($this->credentials) ? $this->credentials : [];
        $val = $creds[$key] ?? null;

        return is_scalar($val) && trim((string) $val) !== '';
    }

    /**
     * Sanitise any error text before storing on the model so secrets can never be persisted in plaintext.
     */
    public function setLastErrorAttribute(?string $value): void
    {
        $this->attributes['last_error'] = Redact::sanitizeText($value, $this->secretValues());
    }

    /**
     * Clear stored credentials and mark integration unconfigured, logging the clear action.
     */
    public function clearCredentials(bool $clearSettings = false): self
    {
        $this->credentials = null;
        if ($clearSettings) {
            $this->settings = null;
        }
        $this->status = self::STATUS_UNCONFIGURED;
        $this->last_verified_at = null;
        $this->last_error = null;
        $this->save();

        return $this;
    }

    /**
     * Record a runtime failure (sanitised) and mark status as failing without triggering a config-change audit entry.
     */
    public function recordFailure(string $errorMessage, array $extraSecrets = []): void
    {
        $secrets = array_merge($this->secretValues(), $extraSecrets);
        $this->status = self::STATUS_FAILING;
        $this->attributes['last_error'] = Redact::sanitizeText($errorMessage, $secrets);
        $this->saveQuietly();
    }

    /**
     * Mark integration verified after a live verification / webhook / send succeeded.
     */
    public function markVerified(): void
    {
        $this->status = self::STATUS_VERIFIED;
        $this->last_verified_at = now();
        $this->attributes['last_error'] = null;
        $this->saveQuietly();
    }

    /**
     * Prevent var_dump / print_r / exception frames from dumping raw or decrypted credentials.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        $vars = get_object_vars($this);
        if (isset($vars['attributes']['credentials'])) {
            $vars['attributes']['credentials'] = '[REDACTED]';
        }
        if (isset($vars['original']['credentials'])) {
            $vars['original']['credentials'] = '[REDACTED]';
        }
        if (isset($vars['classCastCache']['credentials'])) {
            $vars['classCastCache']['credentials'] = '[REDACTED]';
        }

        return $vars;
    }

    /**
     * @return array<int, string>
     */
    private function secretValues(): array
    {
        try {
            $creds = is_array($this->credentials) ? $this->credentials : [];
        } catch (\Throwable) {
            return [];
        }

        $values = [];
        foreach ($creds as $val) {
            if (is_string($val) && $val !== '') {
                $values[] = $val;
            }
        }

        return $values;
    }

    /**
     * @param array<string, mixed> $creds
     * @param array<string, mixed> $settings
     * @return array<int, string>
     */
    private function collectPresentFieldNames(array $creds, array $settings): array
    {
        $fields = [];
        foreach ($creds as $key => $val) {
            if ($val !== null && $val !== '') {
                $fields[] = (string) $key;
            }
        }
        foreach ($settings as $key => $val) {
            if (is_array($val)) {
                foreach ($val as $subKey => $subVal) {
                    if ($subVal !== null && $subVal !== '') {
                        $fields[] = "{$key}.{$subKey}";
                    }
                }
            } elseif ($val !== null && $val !== '') {
                $fields[] = (string) $key;
            }
        }

        return array_values(array_unique($fields));
    }

    /**
     * Compute field names (never values) that changed across credentials and settings.
     *
     * @param array<string, mixed> $oldCreds
     * @param array<string, mixed> $newCreds
     * @param array<string, mixed> $oldSettings
     * @param array<string, mixed> $newSettings
     * @return array<int, string>
     */
    private static function diffFieldNames(array $oldCreds, array $newCreds, array $oldSettings, array $newSettings): array
    {
        $changed = [];

        $credKeys = array_unique(array_merge(array_keys($oldCreds), array_keys($newCreds)));
        foreach ($credKeys as $key) {
            $before = $oldCreds[$key] ?? null;
            $after = $newCreds[$key] ?? null;
            if ($before !== $after) {
                $changed[] = (string) $key;
            }
        }

        $settingKeys = array_unique(array_merge(array_keys($oldSettings), array_keys($newSettings)));
        foreach ($settingKeys as $key) {
            $before = $oldSettings[$key] ?? null;
            $after = $newSettings[$key] ?? null;

            if (is_array($before) || is_array($after)) {
                $beforeArr = is_array($before) ? $before : [];
                $afterArr = is_array($after) ? $after : [];
                $subKeys = array_unique(array_merge(array_keys($beforeArr), array_keys($afterArr)));
                foreach ($subKeys as $subKey) {
                    if (($beforeArr[$subKey] ?? null) !== ($afterArr[$subKey] ?? null)) {
                        $changed[] = "{$key}.{$subKey}";
                    }
                }
            } elseif ($before !== $after) {
                $changed[] = (string) $key;
            }
        }

        return array_values(array_unique($changed));
    }

    /**
     * @param array<int, string> $changedFields
     */
    private static function recordAudit(self $model, string $action, array $changedFields): void
    {
        $actor = auth()->check()
            ? ('user:' . auth()->id())
            : 'cli';

        $verb = match ($action) {
            'integration.created' => 'configured',
            'integration.cleared' => 'cleared',
            default => 'updated',
        };

        $fieldList = empty($changedFields) ? 'none' : implode(', ', $changedFields);
        $description = sprintf(
            'Integration [%s] %s for institute #%d by %s (fields: %s)',
            $model->provider,
            $verb,
            (int) $model->institute_id,
            $actor,
            $fieldList
        );

        ActivityLogService::log(
            $action,
            $description,
            [
                'provider' => $model->provider,
                'institute_id' => (int) $model->institute_id,
                'actor' => $actor,
                'changed_fields' => array_values($changedFields),
            ],
            (int) $model->institute_id
        );
    }
}
