<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class InstituteScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        // Prevent infinite recursion when checking auth
        if (app()->runningInConsole()) {
            return;
        }

        // We use a static variable to track if we are already in the middle of a scope check 
        // for the User model to prevent recursive loops when auth()->check() triggers a User load.
        static $isChecking = false;
        
        if ($isChecking) {
            return;
        }

        $isChecking = true;

        try {
            if (auth()->check()) {
                $user = auth()->user();
                
                // Super admins see everything
                if ($user->role === 'super_admin') {
                    $isChecking = false;
                    return;
                }

                // Other users are scoped to their institute
                if ($user->institute_id) {
                    $builder->where($model->getTable() . '.institute_id', $user->institute_id);
                } else {
                    // If user has no institute and isn't super admin, they see nothing by default
                    $builder->whereRaw('1 = 0');
                }
            }
        } finally {
            $isChecking = false;
        }
    }
}
