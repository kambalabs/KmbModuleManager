<?php
return [
    'log' => [
        'Logger' => [
            'writers' => [
                'null' => [
                    'name' => 'null',
                ],
            ],
        ],
    ],
    'db' => [
        'driver' => 'Pdo',
        'dsn' => 'sqlite::memory:'
    ],
    'forge' => [
        'base_uri' => 'http://localhost:3002',
    ]
];
