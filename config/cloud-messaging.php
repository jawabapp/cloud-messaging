<?php

$path = env('JAWAB_CLOUD_MESSAGING_PATH', 'jawab-notifications');

return [
    'middleware' => [
        'web'
    ],
    'path' => $path,
    'big_query' => [
        'key_file_path' => env('GOOGLE_APPLICATION_CREDENTIALS'),
        'project_id' => env('BIG_QUERY_PROJECT_ID'),
        'table_name' => env('BIG_QUERY_TABLE_NAME'),
    ],
    'notification_open_event_name' => env('NOTIFICATION_OPEN_EVENT_NAME'),
    'user_model' => \App\Models\User::class,
    'notifiable_model' => \App\Models\User::class,
    'country_code_column' => null,
    'routes' => [
        'target_audience' => "/{$path}/api/target-audience",
        'filter_prefix' => "/{$path}/api",
        'campaign_prefix' => 'https://trends.jawab.app/',
        'campaign_parser_prefix' => "/{$path}/api/parse",
    ],
    'image_mimetypes' => 'image/jpeg,image/png,image/webp,image/gif,image/svg+xml',
    'filter_types' => [
        [
            'value' => 'countries',
            'label' => 'Country/Region',
            'selectLabel' => 'Countries',
            'type' => 'MULTI_SELECT',
            'conditions' => [
                [
                    'value' => 'is_in',
                    'label' => 'Is in',
                ],
                [
                    'value' => 'is_not_in',
                    'label' => 'Is not in',
                ]
            ]
        ],
        [
            'value' => 'registers',
            'label' => 'Register @',
            'selectLabel' => 'Registers',
            'type' => 'MULTI_SELECT',
            'conditions' => [
                [
                    'value' => 'is_in',
                    'label' => 'Is in',
                ],
                [
                    'value' => 'is_not_in',
                    'label' => 'Is not in',
                ]
            ]
        ]
    ],
    'extra_info' => [
        'conversion' => '',
    ]
];
