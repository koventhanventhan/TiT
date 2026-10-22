<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\BelongsToInstitute;

class SiteSetting extends Model
{
    use HasFactory, BelongsToInstitute;

    protected $fillable = [
        'key',
        'value',
        'group',
        'institute_id',
    ];

    /**
     * Get a setting value by key.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Get a setting value by key for a specific institute.
     *
     * @param string $key
     * @param int|null $instituteId
     * @param mixed $default
     * @return mixed
     */
    public static function getForInstitute(string $key, ?int $instituteId = null, $default = null)
    {
        $instituteId = $instituteId ?: (auth()->check() ? auth()->user()->institute_id : null) ?: 1;
        $setting = self::withoutGlobalScopes()
            ->where('institute_id', $instituteId)
            ->where('key', $key)
            ->first();

        return $setting ? $setting->value : $default;
    }

    /**
     * Set a setting value by key.
     *
     * @param string $key
     * @param mixed $value
     * @param string $group
     * @return self
     */
    public static function set($key, $value, $group = 'general')
    {
        $instituteId = auth()->check() && auth()->user()->institute_id ? auth()->user()->institute_id : 1;
        return self::withoutGlobalScopes()->updateOrCreate(
            ['key' => $key, 'institute_id' => $instituteId],
            ['value' => $value, 'group' => $group]
        );
    }

    /**
     * Set a setting value by key for a specific institute.
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $instituteId
     * @param string $group
     * @return self
     */
    public static function setForInstitute(string $key, $value, ?int $instituteId = null, string $group = 'general')
    {
        $instituteId = $instituteId ?: (auth()->check() ? auth()->user()->institute_id : null) ?: 1;
        return self::withoutGlobalScopes()->updateOrCreate(
            ['key' => $key, 'institute_id' => $instituteId],
            ['value' => $value, 'group' => $group]
        );
    }

    public static function isRecordingGradeDisabled(?string $grade): bool
    {
        $disabled = json_decode(self::get('zoom_recordings_disabled_grades', '[]'), true);
        if (!is_array($disabled) || empty($grade)) return false;
        preg_match('/(\d+)/', $grade, $g);
        foreach ($disabled as $d) {
            preg_match('/(\d+)/', (string) $d, $m);
            if (isset($g[1], $m[1]) ? $g[1] === $m[1] : strtolower(trim((string) $d)) === strtolower(trim($grade))) return true;
        }
        return false;
    }
}
