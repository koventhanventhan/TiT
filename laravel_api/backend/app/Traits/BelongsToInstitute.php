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

        // Automatically assign institute_id when creating if not set
        static::creating(function ($model) {
            if (!$model->institute_id) {
                if (auth()->check()) {
                    $user = auth()->user();
                    if ($user->role !== 'super_admin') {
                        $model->institute_id = $user->institute_id;
                    }
                } elseif (function_exists('current_institute_id') && current_institute_id()) {
                    $model->institute_id = current_institute_id();
                } elseif (!empty($model->user_id)) {
                    $parentUser = \App\Models\User::withoutGlobalScopes()->find($model->user_id);
                    if ($parentUser && $parentUser->institute_id) {
                        $model->institute_id = $parentUser->institute_id;
                    }
                }

                // If still empty, fall back to default institute ID
                if (!$model->institute_id) {
                    $defaultId = (int) config('tenant.default_institute_id', 1);
                    if ($defaultId > 0) {
                        $model->institute_id = $defaultId;
                    }
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
