<?php
return [
    // OpenAI API-Key
    'openai_api_key' => 'REDACTED',

    // Datenbankverbindung
    'db' => [
        'host' => 'localhost',
        'name' => 'svfd_schedule',
        'user' => 'svfd_Schedule',
        'pass' => 'REDACTED',
        'charset' => 'utf8mb4'
    ],
    'prompt' => file_get_contents('prompt_metadaten.php')
];