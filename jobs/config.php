<?php
return [
    'emails' => [
        'aushilfe' => [
            'all' => [],
            'admin' => ['michasalz@gmail.com', 'kb@freibad-dabringhausen.de'],
        ],
        'kassenkraft' => [
            'all' => [],
            'admin' => ['kg@freibad-dabringhausen.de'],
        ],
        'kassenabschluss' => [
            'admin' => ['michasalz@gmail.com', 'kb@freibad-dabringhausen.de'],
        ],
        'default_email' => 'kb@freibad-dabringhausen.de',
    ],
    'database' => [
        'host' => 'localhost',
        'username' => 'svfd_Schedule',
        'password' => 'REDACTED',
        'name' => 'svfd_schedule',
    ],
    'weather_api' => [
        'lat' => '51.085620',
        'lon' => '7.192630',
        'api_key' => 'REDACTED',
        'base_url' => 'https://api.openweathermap.org/data/2.5/forecast',
    ],
    'start_date' => '2024-07-05',
    'end_date' => '2024-08-25',
    'base_url' => 'https://personal.freibad-dabringhausen.de/schedule/',
    'smtp' => [
        'host' => 'freibad-dabringhausen.de',
        'username' => 'info@freibad-dabringhausen.de',
        'password' => 'REDACTED',
        'port' => 465,
        'encryption' => 'ssl'
    ]
];
?>
