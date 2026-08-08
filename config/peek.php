<?php

// config for Blemli/Peek
return [

    // Kill switch for the whole plugin.
    'enabled' => env('PEEK_ENABLED', true),

    // Milliseconds of hover before the popover opens.
    'hover_delay' => 400,

    // Touch devices: open the peek with a long-press.
    'mobile' => [
        'enabled' => true,
        'long_press_duration' => 500, // ms
    ],

    // Default nesting depth for peeks inside peeks. 1 = no nesting.
    'depth' => 1,

    // Peek on any anchor that links to a resource record (Recently menu,
    // activity-log timelines, breadcrumbs, ...). Relationship columns and
    // infolist entries peek regardless of this setting.
    'anchors' => true,

    // "Stephan peeked at XY" — requires spatie/laravel-activitylog (suggested, not required).
    'activity_log' => [
        'enabled' => false,
        'log_name' => 'peek',
        'throttle_minutes' => 10, // max one entry per user+record per window
    ],

    // Global noPeeking list: these are never previewed anywhere.
    'no_peeking' => [
        'models' => [
            // App\Models\Salary::class,
        ],
        'resources' => [
            // App\Filament\Resources\SalaryResource::class,
        ],
    ],
];
