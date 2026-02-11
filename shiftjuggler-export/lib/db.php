<?php
function connectDB($config) {
    $db = $config['db'];
    $dsn = "mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}";

    return new PDO($dsn, $db['user'], $db['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
}
