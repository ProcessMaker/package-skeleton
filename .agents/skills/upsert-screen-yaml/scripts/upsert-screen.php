#!/usr/bin/env php
<?php

/**
 * Create or update a ProcessMaker screen by title using YAML file content.
 *
 * The screen title is the YAML filename without extension.
 * POST /api/1.0/package-skeleton/deploy-screen
 *
 * Reads PM_BASE_URL and PM_SYNC_TOKEN from the package-skeleton root .env
 *
 * Usage:
 *   php scripts/upsert-screen.php path/to/military-transcript.yaml
 *   php scripts/upsert-screen.php path/to/military-transcript.yaml --host http://127.0.0.1:8000 --token TOKEN
 */

const ENDPOINT_PATH = '/api/1.0/package-skeleton/deploy-screen';

$projectRoot = dirname(__DIR__, 4);
$envPath = $projectRoot . '/.env';

[$yamlFile, $hostOverride, $tokenOverride] = parseCliArgs(array_slice($argv, 1));

if ($yamlFile === null) {
    fwrite(STDERR, "Usage: php upsert-screen.php path/to/screen.yaml [--host URL] [--token TOKEN]\n");
    exit(1);
}

$yamlPath = resolvePath($yamlFile, getcwd());
if (!is_file($yamlPath)) {
    fwrite(STDERR, "YAML file not found: {$yamlPath}\n");
    exit(1);
}

$extension = strtolower(pathinfo($yamlPath, PATHINFO_EXTENSION));
if (!in_array($extension, ['yaml', 'yml'], true)) {
    fwrite(STDERR, "File must be .yaml or .yml: {$yamlPath}\n");
    exit(1);
}

$env = loadDotEnv($envPath);
$host = $hostOverride ?: ($env['PM_BASE_URL'] ?? getenv('PM_BASE_URL') ?: '');
$token = $tokenOverride ?: ($env['PM_SYNC_TOKEN'] ?? getenv('PM_SYNC_TOKEN') ?: '');

if ($host === '') {
    fwrite(STDERR, "PM_BASE_URL is required. Set it in {$envPath} or pass --host.\n");
    exit(1);
}
if ($token === '') {
    fwrite(STDERR, "PM_SYNC_TOKEN is required. Set it in {$envPath} or pass --token.\n");
    exit(1);
}

$name = pathinfo($yamlPath, PATHINFO_FILENAME);
$ymlContent = file_get_contents($yamlPath);
if ($ymlContent === false) {
    fwrite(STDERR, "Could not read YAML file: {$yamlPath}\n");
    exit(1);
}

$url = rtrim($host, '/') . ENDPOINT_PATH;
$payload = json_encode([
    'name' => $name,
    'yml_content' => $ymlContent,
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
exit(0);

/**
 * @return array{0: ?string, 1: ?string, 2: ?string}
 */
function parseCliArgs(array $args): array
{
    $yamlFile = null;
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
        if ($yamlFile === null && !str_starts_with($arg, '--')) {
            $yamlFile = $arg;
        }
    }

    return [$yamlFile, $host, $token];
}

function resolvePath(string $path, string $cwd): string
{
    if (str_starts_with($path, '/')) {
        return $path;
    }

    return $cwd . '/' . $path;
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
