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
     * Set a setting value by key.
     *
     * @param string $key
     * @param mixed $value
     * @param string $group
     * @return self
     */
    public static function set($key, $value, $group = 'general')
    {
        return self::updateOrCreate(
            ['key' => $key],
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
