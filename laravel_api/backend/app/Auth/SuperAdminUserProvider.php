<?php

namespace App\Auth;

use App\Scopes\InstituteScope;
use Illuminate\Auth\EloquentUserProvider;

class SuperAdminUserProvider extends EloquentUserProvider
{
    /**
     * Retrieve a new query builder for the model without InstituteScope
     * and strictly restricted to active super_admin users.
     */
    protected function newModelQuery($model = null)
    {
        $query = is_null($model)
            ? $this->createModel()->newQuery()
            : $model->newQuery();

        $query->withoutGlobalScope(InstituteScope::class)
            ->where('role', 'super_admin')
            ->whereNull('deactivated_at');

        with($query, $this->queryCallback);

        return $query;
    }
}
