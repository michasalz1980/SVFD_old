<?php
return [
    'db' => [
        'host' => 'localhost',
        'dbname' => 'svfd_schedule',
        'charset' => 'utf8mb4',
        'user' => 'svfd_Schedule',
        'pass' => 'rq*6X4s82'
    ],
    'api' => [
        'base_url' => 'https://freibad-dabringhausen-api.shiftjuggler.com/api/',
        'user' => 'salz.hausbau@gmail.com',
        'password' => 'hoh8Xoi42!'
    ],
    'main_location_id' => 1,
    'workplace_id' => 10,
    'default_break_time' => 30,
    'allowed_user_types' => ['aushilfe'],
    'dry_run' => true,
    'log_file' => __DIR__ . '/logs/export.log'
];
