<?php

namespace App\Traits;

use App\Models\Institute;
use App\Scopes\InstituteScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToInstitute
{
    /**
     * Boot the trait to add the global scope.
     */
    protected static function bootBelongsToInstitute()
    {
        static::addGlobalScope(new InstituteScope);

        // Automatically assign institute_id when creating if not set and user is logged in
        static::creating(function ($model) {
            if (!$model->institute_id && auth()->check()) {
                $user = auth()->user();
                if ($user->role !== 'super_admin') {
                    $model->institute_id = $user->institute_id;
                }
            }
        });
    }

    /**
     * Get the institute that owns this record.
     */
    public function institute(): BelongsTo
    {
        return $this->belongsTo(Institute::class);
    }
}
