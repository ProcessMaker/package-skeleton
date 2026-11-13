#!/usr/bin/env php
<?php

/**
 * Create or update a ProcessMaker script by title using PHP file content.
 *
 * POST /api/1.0/deploy-script (ProcessMaker core)
 *
 * Reads PM_BASE_URL and PM_SYNC_TOKEN from the package root .env
 */

const ENDPOINT_PATH = '/api/1.0/deploy-script';

$projectRoot = dirname(__DIR__, 4);
$envPath = $projectRoot . '/.env';

[$phpFile, $hostOverride, $tokenOverride] = parseCliArgs(array_slice($argv, 1));

if ($phpFile === null) {
    fwrite(STDERR, "Usage: php upsert-script.php path/to/script.php [--host URL] [--token TOKEN]\n");
    exit(1);
}

$phpPath = resolvePath($phpFile, getcwd());
if (!is_file($phpPath)) {
    fwrite(STDERR, "PHP file not found: {$phpPath}\n");
    exit(1);
}

if (strtolower(pathinfo($phpPath, PATHINFO_EXTENSION)) !== 'php') {
    fwrite(STDERR, "File must be .php: {$phpPath}\n");
    exit(1);
}

$env = loadDotEnv($envPath);
$host = $hostOverride ?: firstNonEmpty(
    $env['PM_BASE_URL'] ?? null,
    getenv('PM_BASE_URL') ?: null
);
$token = $tokenOverride ?: firstNonEmpty(
    $env['PM_SYNC_TOKEN'] ?? null,
    getenv('PM_SYNC_TOKEN') ?: null
);

if ($host === '') {
    fwrite(STDERR, "PM_BASE_URL is required. Set it in {$envPath} or pass --host.\n");
    exit(1);
}
if ($token === '') {
    fwrite(STDERR, "PM_SYNC_TOKEN is required. Set it in {$envPath} or pass --token.\n");
    exit(1);
}

$name = pathinfo($phpPath, PATHINFO_FILENAME);
$code = file_get_contents($phpPath);
if ($code === false) {
    fwrite(STDERR, "Could not read PHP file: {$phpPath}\n");
    exit(1);
}

$url = rtrim($host, '/') . ENDPOINT_PATH;
$payload = json_encode([
    'name' => $name,
    'code' => $code,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 60,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token,
    ],
]);

$body = curl_exec($ch);
$status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($body === false) {
    fwrite(STDERR, "Request failed: {$error}\n");
    exit(1);
}

$data = json_decode($body, true);
if (!is_array($data) || empty($data['success'])) {
    fwrite(STDERR, "HTTP {$status} from {$url}\n");
    fwrite(STDERR, $body . "\n");
    exit(1);
}

echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

if (preg_match('/^use\s+ProcessMaker\\\\Package\\\\/m', $code) === 1) {
    fwrite(STDERR, "Note: Nayra runs script code from the executor image on the server.\n");
    fwrite(STDERR, "Install this package on the instance and rebuild the PHP/Nayra executor if src/ classes changed.\n");
}

exit(0);

/**
 * @return array{0: ?string, 1: ?string, 2: ?string}
 */
function parseCliArgs(array $args): array
{
    $phpFile = null;
    $host = null;
    $token = null;

    for ($i = 0; $i < count($args); $i++) {
        $arg = $args[$i];
        if ($arg === '--host') {
            $host = $args[++$i] ?? '';
            continue;
        }
        if ($arg === '--token') {
            $token = $args[++$i] ?? '';
            continue;
        }
        if (str_starts_with($arg, '--host=')) {
            $host = substr($arg, 7);
            continue;
        }
        if (str_starts_with($arg, '--token=')) {
            $token = substr($arg, 8);
            continue;
        }
        if ($phpFile === null && !str_starts_with($arg, '--')) {
            $phpFile = $arg;
        }
    }

    return [$phpFile, $host, $token];
}

function resolvePath(string $path, string $cwd): string
{
    if (str_starts_with($path, '/')) {
        return $path;
    }

    return $cwd . '/' . $path;
}

function firstNonEmpty(?string ...$values): string
{
    foreach ($values as $value) {
        if ($value !== null && $value !== '') {
            return $value;
        }
    }

    return '';
}

function loadDotEnv(string $path): array
{
    if (!is_file($path)) {
        return [];
    }

    $vars = [];
    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        return [];
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"'))
            || (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }
        $vars[$key] = $value;
    }

    return $vars;
}
