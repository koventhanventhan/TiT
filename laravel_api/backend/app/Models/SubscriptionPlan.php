<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'monthly_price',
        'yearly_price',
        'max_students',
        'max_teachers',
        'features',
    ];

    protected $casts = [
        'features' => 'array',
    ];

    public function institutes()
    {
        return $this->hasMany(Institute::class);
    }

    /**
     * Resolve attribute column name for a limit dimension ('max_students'|'students' or 'max_teachers'|'teachers').
     */
    protected function resolveDimensionColumn(string $dimension): string
    {
        return match ($dimension) {
            'students', 'max_students' => 'max_students',
            'teachers', 'max_teachers' => 'max_teachers',
            default => $dimension,
        };
    }

    /**
     * Both historical sentinel values (0 and -1) as well as null mean "Unlimited".
     */
    public function isUnlimited(string $dimension): bool
    {
        $column = $this->resolveDimensionColumn($dimension);
        $raw = $this->getAttribute($column);

        if ($raw === null || $raw === '') {
            return true;
        }

        return (int) $raw <= 0;
    }

    /**
     * Return positive integer cap for $dimension, or null when unlimited.
     */
    public function limitFor(string $dimension): ?int
    {
        if ($this->isUnlimited($dimension)) {
            return null;
        }

        $column = $this->resolveDimensionColumn($dimension);

        return (int) $this->getAttribute($column);
    }

    public function formattedLimit(string $dimension): string
    {
        $limit = $this->limitFor($dimension);

        return $limit === null ? 'Unlimited' : number_format($limit);
    }

    /**
     * Check if a feature is enabled on this plan (supports canonical keys and legacy labels).
     */
    public function hasFeature(string $key): bool
    {
        $rawFeatures = $this->features;
        if (!is_array($rawFeatures)) {
            return false;
        }

        if (in_array($key, $rawFeatures, true)) {
            return true;
        }

        $legacyMap = config('plans.legacy_map', []);
        foreach ($rawFeatures as $raw) {
            if (isset($legacyMap[$raw]) && $legacyMap[$raw] === $key) {
                return true;
            }
        }

        return false;
    }
}
