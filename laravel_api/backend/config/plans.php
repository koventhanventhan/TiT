<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Plan Features Registry
    |--------------------------------------------------------------------------
    |
    | Canonical feature keys mapped to human-readable names.
    |
    */
    'features' => [
        'zoom' => 'Zoom Classes',
        'ai_assistant' => 'AI Learning Assistant',
        'whatsapp' => 'WhatsApp Messaging',
        'custom_domain' => 'Custom Domain',
        'online_payments' => 'Online Payments (PayHere)',
        'advanced_reports' => 'Advanced Reports',
    ],

    /*
    |--------------------------------------------------------------------------
    | Legacy Free-text Label Mapping
    |--------------------------------------------------------------------------
    |
    | Migration-free mapping for existing legacy free-text feature labels
    | into modern canonical feature keys. Labels returning null are ignored.
    |
    */
    'legacy_map' => [
        'Zoom Integration' => 'zoom',
        'Zoom Classes' => 'zoom',
        'WhatsApp Alerts' => 'whatsapp',
        'WhatsApp Messaging' => 'whatsapp',
        'AI Learning Assistant' => 'ai_assistant',
        'AI Assistant' => 'ai_assistant',
        'Custom Domain' => 'custom_domain',
        'Online Payments' => 'online_payments',
        'PayHere' => 'online_payments',
        'PayHere Integration' => 'online_payments',
        'Advanced Reports' => 'advanced_reports',
        'Grade Management' => null, // ignored per spec
    ],
];

