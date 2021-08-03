<?php

$path = env('JAWAB_CLOUD_MESSAGING_PATH', 'jawab-notifications');

return [
    'middleware' => [
        'web'
    ],
    'path' => $path,
    'big_query' => [
        'key_file_path' => env('GOOGLE_APPLICATION_CREDENTIALS'),
        'project_id' => env('BIG_QUERY_PROJECT_ID', 1883),
    ],
    'user_model' => \App\Models\User::class,
    'notifiable_model' => \App\Models\User::class,
    'routes' => [
        'target_audience' => "/{$path}/api/target-audience",
        'filter_prefix' => "/{$path}/api",
        'campaign_prefix' => 'https://trends.jawab.app/',
        'campaign_parser_prefix' => "/{$path}/api/parse",
    ],
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
    ]
];
