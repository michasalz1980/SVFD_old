<?php
return [
    // OpenAI API-Key
    'openai_api_key' => getenv('OPENAI_API_KEY') ?: 'REPLACE_WITH_ENV_OPENAI_API_KEY',

    // Datenbankverbindung
    'db' => [
        'host' => 'localhost',
        'name' => 'svfd_schedule',
        'user' => 'svfd_Schedule',
        'pass' => 'rq*6X4s82',
        'charset' => 'utf8mb4'
    ],
    'prompt' => file_get_contents('prompt_metadaten.php')
];
