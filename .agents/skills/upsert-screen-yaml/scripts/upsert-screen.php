#!/usr/bin/env php
<?php

/**
 * Create or update a ProcessMaker screen by title using YAML file content.
 *
 * The screen title is the YAML filename without extension.
 * POST /api/1.0/deploy-screen (ProcessMaker core)
 *
 * Reads PM_BASE_URL and PM_SYNC_TOKEN from the package-skeleton root .env
 *
 * Usage:
 *   php scripts/upsert-screen.php path/to/example-screen.yaml
 *   php scripts/upsert-screen.php path/to/example-screen.yaml --host http://127.0.0.1:8000 --token TOKEN
 */

const ENDPOINT_PATH = '/api/1.0/deploy-screen';

$projectRoot = dirname(__DIR__, 4);
$envPath = $projectRoot . '/.env';

[$yamlFile, $hostOverride, $tokenOverride, $versionName] = parseCliArgs(array_slice($argv, 1));

if ($yamlFile === null) {
    fwrite(STDERR, "Usage: php upsert-screen.php path/to/screen.yaml [--host URL] [--token TOKEN] [--version-name TEXT] [--insecure]\n");
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
$body = [
    'name' => $name,
    'yml_content' => $ymlContent,
];
if ($versionName !== null && $versionName !== '') {
    $body['version_name'] = $versionName;
}
$payload = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

$ch = curl_init($url);
curl_setopt_array($ch, tlsOptions() + [
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

if ($body === false) {
    fwrite(STDERR, "Request failed: {$error}\n");
    exit(1);
}

$data = json_decode($body, true);
if (!is_array($data) || empty($data['success'])) {
    fwrite(STDERR, "HTTP {$status} from {$url}\n");
    fwrite(STDERR, $body . "\n");
    printVersionNameHint((string) $body);
    exit(1);
}

echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
exit(0);

/**
 * @return array{0: ?string, 1: ?string, 2: ?string, 3: ?string}
 */
function parseCliArgs(array $args): array
{
    $yamlFile = null;
    $host = null;
    $token = null;
    $versionName = null;

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
        if ($arg === '--version-name') {
            $versionName = $args[++$i] ?? '';
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
        if (str_starts_with($arg, '--version-name=')) {
            $versionName = substr($arg, 15);
            continue;
        }
        if ($yamlFile === null && !str_starts_with($arg, '--')) {
            $yamlFile = $arg;
        }
    }

    return [$yamlFile, $host, $token, $versionName];
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

/**
 * TLS options for a local HTTPS instance with a self-signed or private CA.
 *
 * --insecure or PM_INSECURE=1 skips verification (local development only).
 * PM_CA_BUNDLE or CURL_CA_BUNDLE points to a CA file and keeps verification on.
 *
 * @return array<int, mixed>
 */
function tlsOptions(): array
{
    $env = loadDotEnv(dirname(__DIR__, 4) . '/.env');
    $flag = strtolower((string) ($env['PM_INSECURE'] ?? getenv('PM_INSECURE') ?: ''));
    if (in_array('--insecure', $GLOBALS['argv'] ?? [], true) || in_array($flag, ['1', 'true', 'yes'], true)) {
        fwrite(STDERR, "Warning: TLS verification is disabled (--insecure or PM_INSECURE).\n");

        return [CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => 0];
    }

    $bundle = (string) ($env['PM_CA_BUNDLE'] ?? getenv('PM_CA_BUNDLE') ?: getenv('CURL_CA_BUNDLE') ?: '');

    return $bundle !== '' && is_file($bundle) ? [CURLOPT_CAINFO => $bundle] : [];
}

/**
 * Updating an existing asset needs a version name; say so instead of only printing the raw 422.
 */
function printVersionNameHint(string $body): void
{
    if (stripos($body, 'version name') !== false) {
        fwrite(STDERR, "Hint: this asset already exists, so an update needs --version-name \"short change summary\".\n");
    }
}
