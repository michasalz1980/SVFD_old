<?php
/**
 * CLI CronJob Runner für Python Scripts mit Zeitsteuerung
 * Läuft alle 5 Minuten als Cron, Jobs haben individuelle Intervalle
 * 
  * 
 * Verwendung: Als direkter PHP Aufruf
 */

// CLI-Erkennung
if (php_sapi_name() !== 'cli' && php_sapi_name() !== 'cgi-fcgi') {
    echo "Dieses Script ist nur für Kommandozeilen-Ausführung gedacht.\n";
    exit(1);
}

// Konfiguration
$logFile = __DIR__ . '/logs/cronjob_runner.log';
$lockFile = __DIR__ . '/locks/cronjob_runner.lock';
$lastRunFile = __DIR__ . '/data/last_run_times.json';
$maxExecutionTime = 300; // 5 Minuten max (da alle 5 Minuten aufgerufen)
$executionMode = getenv('SVFD_CRON_RUNNER_MODE') ?: 'local_cli';
$pythonBinary = getenv('SVFD_PYTHON_BIN') ?: 'python3';
$maxRetriesPerScript = max(1, (int)(getenv('SVFD_CRON_MAX_RETRIES') ?: 2));
$retryDelayMs = max(0, (int)(getenv('SVFD_CRON_RETRY_DELAY_MS') ?: 1000));

// Python Scripts mit individuellen Intervallen (in Minuten)
// Wenn interval_minutes = 0 oder nicht gesetzt: wird bei jedem Cron-Aufruf ausgeführt
$pythonScripts = [
    [
        'name' => 'Weather Data Collection',
        'url' => 'http://personal.freibad-dabringhausen.de/jobs/python/cgi_getWeatherToMySQL.py',
        'path' => __DIR__ . '/python/cgi_getWeatherToMySQL.py',
        'timeout' => 60,
        'interval_minutes' => 5  // Alle 5 Minuten (= bei jedem Cron-Aufruf)
    ],
    [
        'name' => 'Solar Data Collection', 
        'url' => 'http://personal.freibad-dabringhausen.de/jobs/python/cgi_getSolarToMySQL.py',
        'path' => __DIR__ . '/python/cgi_getSolarToMySQL.py',
        'timeout' => 60,
        'interval_minutes' => 5  // Alle 5 Minuten (= bei jedem Cron-Aufruf)
    ],
    [
        'name' => 'Water Data Collection',
        'url' => 'http://personal.freibad-dabringhausen.de/jobs/python/cgi_getWaterToMySQL.py', 
        'path' => __DIR__ . '/python/cgi_getWaterToMySQL.py',
        'timeout' => 60,
        'interval_minutes' => 5  // Alle 5 Minuten (= bei jedem Cron-Aufruf)
    ],
    [
        'name' => 'Waste Water Data Collection',
        'url' => 'http://personal.freibad-dabringhausen.de/jobs/python/cgi_getWasteWaterToMySQL.py',
        'path' => __DIR__ . '/python/cgi_getWasteWaterToMySQL.py',
        'timeout' => 60,
        'interval_minutes' => 5  // Alle 5 Minuten (= bei jedem Cron-Aufruf)
    ]
    // 03.09.2025 Job zum Einwintern nach Technik-Aus deaktiviert
    /*,
    [
        'name' => 'Depolox Data Collection',
        'url' => 'https://personal.freibad-dabringhausen.de/jobs/python/cgi_getDepolox.py',
        'timeout' => 60,
        'interval_minutes' => 5  // Alle 5 Minuten (= bei jedem Cron-Aufruf)
    ]
    */
];

/**
 * Logging Funktion im Standard Format
 */
function writeLog($level, $message, $context = []) {
    global $logFile;
    
    // Log Verzeichnis erstellen falls nicht vorhanden
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $pid = getmypid();
    $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
    
    $logEntry = sprintf(
        "[%s] %s.%s: %s%s" . PHP_EOL,
        $timestamp,
        $level,
        $pid,
        $message,
        $contextStr
    );
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * CLI Progress Output (optional, für Debug)
 */
function outputProgress($message, $verbose = false) {
    if ($verbose) {
        echo date('Y-m-d H:i:s') . " - " . $message . "\n";
    }
}

/**
 * Lock-Datei prüfen um doppelte Ausführung zu verhindern
 */
function acquireLock() {
    global $lockFile;
    
    $lockDir = dirname($lockFile);
    if (!is_dir($lockDir)) {
        mkdir($lockDir, 0755, true);
    }
    
    if (file_exists($lockFile)) {
        $lockTime = filemtime($lockFile);
        $currentTime = time();
        
        // Lock älter als 10 Minuten? Dann wahrscheinlich hängengeblieben
        if (($currentTime - $lockTime) > 600) {
            writeLog('WARN', 'Removing stale lock file', ['age_seconds' => ($currentTime - $lockTime)]);
            unlink($lockFile);
        } else {
            writeLog('DEBUG', 'Another instance is running, skipping');
            return false;
        }
    }
    
    file_put_contents($lockFile, getmypid());
    return true;
}

/**
 * Lock-Datei entfernen
 */
function releaseLock() {
    global $lockFile;
    if (file_exists($lockFile)) {
        unlink($lockFile);
    }
}

/**
 * Letzte Ausführungszeiten laden
 */
function loadLastRunTimes() {
    global $lastRunFile;
    
    // Datenverzeichnis erstellen
    $dataDir = dirname($lastRunFile);
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
    }
    
    if (!file_exists($lastRunFile)) {
        return [];
    }
    
    $content = file_get_contents($lastRunFile);
    if ($content === false) {
        return [];
    }
    
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

/**
 * Letzte Ausführungszeiten speichern
 */
function saveLastRunTimes($lastRuns) {
    global $lastRunFile;
    
    $content = json_encode($lastRuns, JSON_PRETTY_PRINT);
    file_put_contents($lastRunFile, $content, LOCK_EX);
}

/**
 * Prüfen ob Job ausgeführt werden soll
 */
function shouldRunScript($script, $lastRuns) {
    $scriptKey = md5($script['name']); // Eindeutiger Key
    $currentTime = time();
    
    // Wenn kein interval_minutes definiert oder 0: immer ausführen
    if (!isset($script['interval_minutes']) || $script['interval_minutes'] <= 0) {
        writeLog('DEBUG', 'Script has no interval restriction, will always run', [
            'script' => $script['name']
        ]);
        return true;
    }
    
    $intervalSeconds = $script['interval_minutes'] * 60;
    
    if (!isset($lastRuns[$scriptKey])) {
        // Erstes Mal -> ausführen
        writeLog('DEBUG', 'Script runs for the first time', [
            'script' => $script['name']
        ]);
        return true;
    }
    
    $lastRunTime = $lastRuns[$scriptKey];
    $timeSinceLastRun = $currentTime - $lastRunTime;
    $shouldRun = $timeSinceLastRun >= $intervalSeconds;
    
    if (!$shouldRun) {
        $nextRunIn = ceil(($intervalSeconds - $timeSinceLastRun) / 60);
        writeLog('DEBUG', 'Script interval not yet reached', [
            'script' => $script['name'],
            'interval_minutes' => $script['interval_minutes'],
            'time_since_last_run_minutes' => round($timeSinceLastRun / 60, 1),
            'next_run_in_minutes' => $nextRunIn
        ]);
    }
    
    return $shouldRun;
}

function trimLogOutput($value, $maxLength = 800) {
    if (!is_string($value)) {
        return $value;
    }
    $trimmed = trim($value);
    if (strlen($trimmed) <= $maxLength) {
        return $trimmed;
    }
    return substr($trimmed, -$maxLength);
}

function executeScriptHttp($script) {
    $startTime = microtime(true);
    $timeout = max(5, (int)($script['timeout'] ?? 60));

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $script['url'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CONNECTTIMEOUT => min(15, $timeout),
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'CLI-CronJob-Runner/3.0'
    ]);

    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $curlErrno = curl_errno($ch);
    curl_close($ch);

    $executionTime = round((microtime(true) - $startTime) * 1000);

    if ($response === false || !empty($error)) {
        return [
            'success' => false,
            'mode' => 'http',
            'execution_time_ms' => $executionTime,
            'error' => $error ?: 'unknown_curl_error',
            'curl_errno' => $curlErrno
        ];
    }

    if ($httpCode !== 200) {
        return [
            'success' => false,
            'mode' => 'http',
            'execution_time_ms' => $executionTime,
            'http_code' => $httpCode,
            'response_tail' => trimLogOutput($response)
        ];
    }

    return [
        'success' => true,
        'mode' => 'http',
        'execution_time_ms' => $executionTime,
        'http_code' => $httpCode,
        'response_length' => strlen($response)
    ];
}

function executeScriptLocal($script) {
    global $pythonBinary;

    $startTime = microtime(true);
    $timeout = max(5, (int)($script['timeout'] ?? 60));
    $scriptPath = $script['path'] ?? null;

    if (empty($scriptPath) || !is_file($scriptPath)) {
        return [
            'success' => false,
            'mode' => 'local_cli',
            'execution_time_ms' => round((microtime(true) - $startTime) * 1000),
            'error' => 'script_path_missing_or_not_found',
            'script_path' => $scriptPath
        ];
    }

    if (!function_exists('proc_open')) {
        return [
            'success' => false,
            'mode' => 'local_cli',
            'execution_time_ms' => round((microtime(true) - $startTime) * 1000),
            'error' => 'proc_open_unavailable',
            'script_path' => $scriptPath
        ];
    }

    $command = escapeshellcmd($pythonBinary) . ' ' . escapeshellarg($scriptPath);
    $descriptorSpec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w']
    ];

    $process = proc_open($command, $descriptorSpec, $pipes, dirname($scriptPath));
    if (!is_resource($process)) {
        return [
            'success' => false,
            'mode' => 'local_cli',
            'execution_time_ms' => round((microtime(true) - $startTime) * 1000),
            'error' => 'proc_open_failed',
            'command' => $command
        ];
    }

    fclose($pipes[0]);
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);

    $stdout = '';
    $stderr = '';
    $timedOut = false;
    $deadline = microtime(true) + $timeout;

    while (true) {
        $stdout .= stream_get_contents($pipes[1]);
        $stderr .= stream_get_contents($pipes[2]);

        $status = proc_get_status($process);
        if (!$status['running']) {
            break;
        }

        if (microtime(true) > $deadline) {
            $timedOut = true;
            proc_terminate($process, 9);
            usleep(100000);
            break;
        }

        usleep(100000);
    }

    $stdout .= stream_get_contents($pipes[1]);
    $stderr .= stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    $exitCode = proc_close($process);
    $executionTime = round((microtime(true) - $startTime) * 1000);

    if ($timedOut) {
        return [
            'success' => false,
            'mode' => 'local_cli',
            'execution_time_ms' => $executionTime,
            'error' => 'timeout',
            'timeout_seconds' => $timeout,
            'stdout_tail' => trimLogOutput($stdout),
            'stderr_tail' => trimLogOutput($stderr)
        ];
    }

    if ($exitCode !== 0) {
        return [
            'success' => false,
            'mode' => 'local_cli',
            'execution_time_ms' => $executionTime,
            'exit_code' => $exitCode,
            'stdout_tail' => trimLogOutput($stdout),
            'stderr_tail' => trimLogOutput($stderr)
        ];
    }

    return [
        'success' => true,
        'mode' => 'local_cli',
        'execution_time_ms' => $executionTime,
        'exit_code' => $exitCode,
        'stdout_tail' => trimLogOutput($stdout),
        'stderr_tail' => trimLogOutput($stderr)
    ];
}

function executeScript($script) {
    global $executionMode, $maxRetriesPerScript, $retryDelayMs;

    writeLog('INFO', 'Starting script execution', [
        'script' => $script['name'],
        'mode' => $executionMode,
        'url' => $script['url'] ?? null,
        'path' => $script['path'] ?? null,
        'interval_minutes' => isset($script['interval_minutes']) ? $script['interval_minutes'] : 'always'
    ]);

    $lastResult = ['success' => false, 'error' => 'no_attempt'];

    $primaryMode = ($executionMode === 'http') ? 'http' : 'local_cli';

    for ($attempt = 1; $attempt <= $maxRetriesPerScript; $attempt++) {
        $lastResult = ($primaryMode === 'http')
            ? executeScriptHttp($script)
            : executeScriptLocal($script);

        if (
            empty($lastResult['success'])
            && $primaryMode === 'local_cli'
            && !empty($script['url'])
            && in_array($lastResult['error'] ?? '', ['script_path_missing_or_not_found', 'proc_open_unavailable', 'proc_open_failed'], true)
        ) {
            $fallbackResult = executeScriptHttp($script);
            $fallbackResult['fallback_from'] = 'local_cli';
            $lastResult = $fallbackResult;
        }

        $context = $lastResult;
        $context['script'] = $script['name'];
        $context['attempt'] = $attempt;
        $context['max_attempts'] = $maxRetriesPerScript;

        if (!empty($lastResult['success'])) {
            writeLog('INFO', 'Script executed successfully', $context);
            return true;
        }

        writeLog('ERROR', 'Script execution failed', $context);

        if ($attempt < $maxRetriesPerScript && $retryDelayMs > 0) {
            usleep($retryDelayMs * 1000);
        }
    }

    return false;
}

/**
 * Signal Handler für graceful shutdown
 */
function signalHandler($signal) {
    writeLog('INFO', 'Received signal, shutting down gracefully', ['signal' => $signal]);
    releaseLock();
    exit(0);
}

// Main Execution
try {
    // Signal Handler registrieren (falls verfügbar)
    if (function_exists('pcntl_signal')) {
        pcntl_signal(SIGTERM, 'signalHandler');
        pcntl_signal(SIGINT, 'signalHandler');
    }
    
    // Maximale Ausführungszeit setzen
    set_time_limit($maxExecutionTime);
    
    // Lock akquirieren (non-blocking für Cron-Betrieb)
    if (!acquireLock()) {
        // Kein Fehler - andere Instanz läuft bereits
        exit(0);
    }
    
    $currentTime = time();
    $lastRuns = loadLastRunTimes();
    $scriptsToRun = [];
    
    // Prüfen welche Scripts ausgeführt werden sollen
    foreach ($pythonScripts as $script) {
        if (shouldRunScript($script, $lastRuns)) {
            $scriptsToRun[] = $script;
        }
    }
    
    if (empty($scriptsToRun)) {
        writeLog('DEBUG', 'No scripts scheduled for execution', [
            'next_runs' => array_map(function($script) use ($lastRuns) {
                $scriptKey = md5($script['name']);
                $lastRun = isset($lastRuns[$scriptKey]) ? $lastRuns[$scriptKey] : 0;
                
                // Wenn kein Intervall: läuft beim nächsten Cron-Aufruf
                if (!isset($script['interval_minutes']) || $script['interval_minutes'] <= 0) {
                    return [
                        'script' => $script['name'],
                        'next_run' => 'next cron execution (no interval restriction)',
                        'minutes_until' => 'always runs'
                    ];
                }
                
                $nextRun = $lastRun + ($script['interval_minutes'] * 60);
                return [
                    'script' => $script['name'],
                    'next_run' => date('Y-m-d H:i:s', $nextRun),
                    'minutes_until' => max(0, ceil(($nextRun - time()) / 60))
                ];
            }, $pythonScripts)
        ]);
        releaseLock();
        exit(0);
    }
    
    writeLog('INFO', 'CLI CronJob Runner started', [
        'pid' => getmypid(),
        'mode' => $executionMode,
        'max_retries_per_script' => $maxRetriesPerScript,
        'retry_delay_ms' => $retryDelayMs,
        'scripts_to_run' => count($scriptsToRun),
        'scripts' => array_column($scriptsToRun, 'name')
    ]);
    
    $startTime = microtime(true);
    $successCount = 0;
    $errorCount = 0;
    
    // Scripts ausführen
    foreach ($scriptsToRun as $script) {
        $scriptKey = md5($script['name']);
        
        if (executeScript($script)) {
            $successCount++;
            // Erfolgreiche Ausführung -> Zeit speichern
            $lastRuns[$scriptKey] = $currentTime;
        } else {
            $errorCount++;
            // Bei Fehler auch Zeit speichern um nicht ständig zu versuchen
            $lastRuns[$scriptKey] = $currentTime;
        }
        
        // Signal verarbeiten (falls verfügbar)
        if (function_exists('pcntl_signal_dispatch')) {
            pcntl_signal_dispatch();
        }
    }
    
    // Letzte Ausführungszeiten speichern
    saveLastRunTimes($lastRuns);
    
    $totalTime = round((microtime(true) - $startTime) * 1000);
    
    writeLog('INFO', 'CLI CronJob Runner completed', [
        'executed_scripts' => count($scriptsToRun),
        'successful' => $successCount,
        'failed' => $errorCount,
        'total_time_ms' => $totalTime
    ]);
    
    // Lock freigeben
    releaseLock();
    
    // Exit Code setzen
    exit($errorCount > 0 ? 1 : 0);
    
} catch (Exception $e) {
    writeLog('ERROR', 'Unexpected error in CLI CronJob Runner', [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    
    releaseLock();
    exit(1);
}
?>
