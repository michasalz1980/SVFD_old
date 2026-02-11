<?php
return [
    'emails' => [
        'aushilfe' => [
            'all' => [],
            'admin' => ['michasalz@gmail.com', 'katja.bannier@gmail.com'],
        ],
        'kassenkraft' => [
            'all' => [],
            'admin' => ['kg@freibad-dabringhausen.de', 'Thomas_knab@t-online.de'],
        ],
        'kassenabschluss' => [
            'admin' => ['michasalz@gmail.com', 'katja.bannier@gmail.com'],
        ],
        'default_email' => 'katja.bannier@gmail.com',
    ],
    'database' => [
        'host' => 'localhost',
        'username' => 'svfd_Schedule',
        'password' => 'REDACTED',
        'name' => 'svfd_schedule',
    ],
    // Start and end date per season
    'start_date' => '2024-07-05',
    'end_date' => '2024-08-25',
    'base_url' => 'https://personal.freibad-dabringhausen.de/schedule/',
];