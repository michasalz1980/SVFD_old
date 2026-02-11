<?php
function logAction($msg) {
    $log = date('[Y-m-d H:i:s] ') . $msg . PHP_EOL;
    file_put_contents(__DIR__ . '/../logs/export.log', $log, FILE_APPEND);
    echo $log;
}
