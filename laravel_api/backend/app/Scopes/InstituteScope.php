<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class InstituteScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        // Prevent application in console unless an explicit tenant context is set via Tenant::run() or Tenant::set()
        if (app()->runningInConsole() && !app()->bound('current_institute')) {
            return;
        }

        // We use a static variable to track if we are already in the middle of a scope check 
        // to prevent recursive loops when auth()->user() triggers a model load that uses this scope.
        static $isChecking = false;
        
        if ($isChecking) {
            return;
        }

        $isChecking = true;

        try {
            // derivation of institute_id
            $instituteId = null;

            // 1. Check if user is authenticated via Sanctum or Web
            if (auth()->check()) {
                $user = auth()->user();
                
                // Super admins see everything across all institutes
                if ($user->role === 'super_admin') {
                    return;
                }

                $instituteId = $user->institute_id;
            } 
            // 2. Check current resolved institute from container (ResolveTenant / Tenant::run)
            else if (function_exists('current_institute_id') && current_institute_id()) {
                $instituteId = current_institute_id();
            }
            // 3. Fallback to header for local/testing contexts if needed
            else if (request() && request()->header('X-Institute-Id') && (app()->environment(['local', 'testing']) || in_array(request()->getHost(), ['localhost', '127.0.0.1']))) {
                $instituteId = request()->header('X-Institute-Id');
            }

            // Apply the scope if we have a context
            if ($instituteId) {
                $builder->where($model->getTable() . '.institute_id', $instituteId);
            } else if (auth()->check()) {
                // If user is logged in but has no institute (and isn't super admin), they see nothing
                $builder->whereRaw('1 = 0');
            }
            // If not logged in and no header, we don't apply the filter here 
            // (Public routes should handle their own logic or be globally available)
            
        } finally {
            $isChecking = false;
        }
    }
}
