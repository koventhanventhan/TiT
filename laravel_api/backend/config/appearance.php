<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Enabled Theme Presets
    |--------------------------------------------------------------------------
    |
    | Defines which theme presets are enabled for institute portals.
    | Default: royal, ocean, emerald.
    | When midnight is not in this list, the admin Appearance page hides it,
    | saving it is rejected, and institutes with midnight fall back to royal
    | on the public site and student/teacher/parent consoles while keeping
    | the dark admin console.
    |
    */
    'enabled_presets' => array_map('trim', explode(',', env('ENABLED_THEME_PRESETS', 'royal,ocean,emerald'))),
];
