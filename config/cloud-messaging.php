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
        'filter_prefix' => '/admin/api',
        'campaign_prefix' => 'https://trends.jawab.app/',
        'campaign_parser_prefix' => '/admin/api/parse',
    ],
    'filter_types' => [
        [
            'value' => 'countries',
            'label' => 'Country/Region',
            'selectLabel' => 'Countries',
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
            'value' => 'tags',
            'label' => 'Tags',
            'selectLabel' => 'Tags',
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
            'value' => 'tag-groups',
            'label' => 'Tag Groups',
            'selectLabel' => 'Tag Groups',
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
            'value' => 'services',
            'label' => 'Services',
            'selectLabel' => 'Services',
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
