<?php

return [
    'group_a' => [
        // Production-safe default: the pilot is opt-in per environment.
        'enabled' => (bool) env('PERMISSION_PILOT_GROUP_A_ENABLED', false),
        'routes' => [
            [
                'method' => 'GET,HEAD',
                'uri' => 'dashboard',
                'name' => 'dashboard',
                'action' => 'App\\Http\\Controllers\\DashboardController@index',
                'key' => 'dashboard.view',
            ],
        ],
    ],
];
