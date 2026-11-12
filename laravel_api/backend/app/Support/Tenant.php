<?php

namespace App\Support;

use App\Models\Institute;
use Closure;

class Tenant
{
    /**
     * Get the current resolved institute from the application container.
     */
    public static function current(): ?Institute
    {
        return app()->bound('current_institute') ? app('current_institute') : null;
    }

    /**
     * Alias for current().
     */
    public static function get(): ?Institute
    {
        return static::current();
    }

    /**
     * Get the current resolved institute ID.
     */
    public static function id(): ?int
    {
        return static::current()?->id;
    }

    /**
     * Bind or unbind the current institute in the application container.
     */
    public static function set(?Institute $institute): void
    {
        if ($institute) {
            app()->instance('current_institute', $institute);
        } else {
            app()->forgetInstance('current_institute');
        }
    }

    /**
     * Execute a callback in the context of a specific institute.
     * Restores previous context upon completion.
     */
    public static function run(Institute|int $institute, Closure $callback): mixed
    {
        $previous = static::current();
        $inst = $institute instanceof Institute ? $institute : Institute::find($institute);
        static::set($inst);

        try {
            return $callback($inst);
        } finally {
            static::set($previous);
        }
    }

    /**
     * Execute a callback for each active institute in the system.
     */
    public static function each(Closure $callback): void
    {
        $institutes = Institute::all();
        foreach ($institutes as $institute) {
            static::run($institute, $callback);
        }
    }
}

