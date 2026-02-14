<?php
return [
    'emails' => [
        'aushilfe' => [
            'all' => [],
            'admin' => ['michasalz@gmail.com', 'kb@freibad-dabringhausen.de'],
        ],
        'kassenkraft' => [
            'all' => [],
            'admin' => ['kassendienst@freibad-Dabringhausen.de'],
        ],
        'kassenabschluss' => [
            'admin' => ['michasalz@gmail.com', 'kb@freibad-dabringhausen.de'],
        ],
        'default_email' => 'kb@freibad-dabringhausen.de',
    ],
    'database' => [
        'host' => 'localhost',
        'username' => 'svfd_Schedule',
        'password' => 'rq*6X4s82',
        'name' => 'svfd_schedule',
    ],
    'start_date' => '2026-07-17',
    'end_date' => '2025-09-06',
    'base_url' => 'https://personal.freibad-dabringhausen.de/schedule/',
    'smtp' => [
        'host' => 'freibad-dabringhausen.de',
        'username' => 'info@freibad-dabringhausen.de',
        'password' => 'Sabilokizu',
        'port' => 465,
        'encryption' => 'ssl'
    ]
];
