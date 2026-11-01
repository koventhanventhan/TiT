<?php

use App\Models\Institute;
use App\Support\Tenant;

if (!function_exists('current_institute')) {
    /**
     * Get the current resolved institute.
     */
    function current_institute(): ?Institute
    {
        return Tenant::current();
    }
}

if (!function_exists('current_institute_id')) {
    /**
     * Get the current resolved institute ID.
     */
    function current_institute_id(): ?int
    {
        return Tenant::id();
    }
}

