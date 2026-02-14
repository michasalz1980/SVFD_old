<?php
$config = require __DIR__ . '/config.php';

$database = $config['database'] ?? [];
$weatherApi = $config['weather_api'] ?? [];

function failWeatherJob(string $message, int $httpCode = 500, array $context = []): void
{
    $contextJson = empty($context) ? '' : ' ' . json_encode($context);
    error_log('[jobs/getWeather.php] ' . $message . $contextJson);
    http_response_code($httpCode);
    echo 'ERROR: ' . $message;
    exit(1);
}

function toFloatOrDefault($value, float $default = 0.0): float
{
    if ($value === null || $value === '') {
        return $default;
    }
    return is_numeric($value) ? (float)$value : $default;
}

function toIntOrDefault($value, int $default = 0): int
{
    if ($value === null || $value === '') {
        return $default;
    }
    return is_numeric($value) ? (int)$value : $default;
}

$requiredWeatherKeys = ['base_url', 'lat', 'lon', 'api_key'];
foreach ($requiredWeatherKeys as $requiredKey) {
    if (empty($weatherApi[$requiredKey])) {
        failWeatherJob('Missing weather_api config key', 500, ['key' => $requiredKey]);
    }
}

$requiredDbKeys = ['host', 'username', 'password', 'name'];
foreach ($requiredDbKeys as $requiredKey) {
    if (!array_key_exists($requiredKey, $database)) {
        failWeatherJob('Missing database config key', 500, ['key' => $requiredKey]);
    }
}

$urlApi = sprintf(
    '%s?lat=%s&lon=%s&units=metric&APPID=%s',
    $weatherApi['base_url'],
    $weatherApi['lat'],
    $weatherApi['lon'],
    $weatherApi['api_key']
);

$httpContext = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 20,
        'ignore_errors' => true,
        'header' => "User-Agent: SVFD-WeatherForecast-Job/2.0\r\n",
    ],
]);

$responseRaw = @file_get_contents($urlApi, false, $httpContext);
if ($responseRaw === false) {
    $lastError = error_get_last();
    failWeatherJob('Weather API request failed', 502, ['detail' => $lastError['message'] ?? 'unknown']);
}

$response = json_decode($responseRaw, true);
if (!is_array($response)) {
    failWeatherJob('Weather API returned invalid JSON', 502, ['json_error' => json_last_error_msg()]);
}

if (!isset($response['list']) || !is_array($response['list'])) {
    failWeatherJob('Weather API payload missing list[]', 502);
}

$conn = new mysqli($database['host'], $database['username'], $database['password'], $database['name']);
if ($conn->connect_error) {
    failWeatherJob('Database connection failed', 500, ['detail' => $conn->connect_error]);
}
$conn->set_charset('utf8mb4');

$stmt = $conn->prepare(
    'INSERT INTO ffd_weather_forecast '
    . '(dateTime, temp, feels_like, temp_min, temp_max, pressure, humidity, cloud, created) '
    . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
);

if ($stmt === false) {
    $conn->close();
    failWeatherJob('Could not prepare SQL statement', 500, ['detail' => $conn->error]);
}

$createdAt = date('Y-m-d H:i:s');
$insertedRows = 0;

$conn->begin_transaction();
try {
    foreach ($response['list'] as $item) {
        if (!is_array($item)) {
            continue;
        }

        $main = $item['main'] ?? [];
        $clouds = $item['clouds'] ?? [];
        $dateTime = (string)($item['dt_txt'] ?? '');

        if ($dateTime === '') {
            continue;
        }

        $temp = toFloatOrDefault($main['temp'] ?? null);
        $feelsLike = toFloatOrDefault($main['feels_like'] ?? null);
        $tempMin = toFloatOrDefault($main['temp_min'] ?? null);
        $tempMax = toFloatOrDefault($main['temp_max'] ?? null);
        $pressure = toIntOrDefault($main['pressure'] ?? null);
        $humidity = toIntOrDefault($main['humidity'] ?? null);
        $cloud = toIntOrDefault($clouds['all'] ?? null);

        $ok = $stmt->bind_param(
            'sddddiiis',
            $dateTime,
            $temp,
            $feelsLike,
            $tempMin,
            $tempMax,
            $pressure,
            $humidity,
            $cloud,
            $createdAt
        );
        if (!$ok || !$stmt->execute()) {
            throw new RuntimeException('Insert failed: ' . $stmt->error);
        }
        $insertedRows++;
    }

    if ($insertedRows === 0) {
        throw new RuntimeException('No valid forecast rows to insert');
    }

    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    $stmt->close();
    $conn->close();
    failWeatherJob('Forecast import failed', 500, ['detail' => $e->getMessage()]);
}

$stmt->close();
$conn->close();

http_response_code(200);
echo 'OK: forecast rows inserted=' . $insertedRows;
