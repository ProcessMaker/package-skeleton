#!/usr/bin/env php
<?php

/**
 * Fetch the latest ProcessMaker screen YAML by title.
 *
 * GET /api/1.0/package-skeleton/screen-yaml?name={title}
 *
 * Reads PM_BASE_URL and PM_SYNC_TOKEN from the package-skeleton root .env
 *
 * Usage:
 *   php scripts/get-screen.php path/to/military-transcript.yaml
 *   php scripts/get-screen.php --name military-transcript
 *   php scripts/get-screen.php --name military-transcript --out path/to/military-transcript.yaml
 */

const ENDPOINT_PATH = '/api/1.0/package-skeleton/screen-yaml';

$projectRoot = dirname(__DIR__, 4);
$envPath = $projectRoot . '/.env';

[$positional, $nameOverride, $outOverride, $hostOverride, $tokenOverride] = parseCliArgs(array_slice($argv, 1));

$outPath = $outOverride;
$name = $nameOverride;

if ($positional !== null) {
    $ext = strtolower(pathinfo($positional, PATHINFO_EXTENSION));
    if (in_array($ext, ['yaml', 'yml'], true)) {
        $resolved = resolvePath($positional, getcwd());
        $name = $name ?: pathinfo($resolved, PATHINFO_FILENAME);
        $outPath = $outPath ?: $resolved;
    } elseif ($name === null) {
        $name = $positional;
    }
}

if ($name === null || $name === '') {
    fwrite(STDERR, "Usage: php get-screen.php path/to/screen.yaml [--host URL] [--token TOKEN]\n");
    fwrite(STDERR, "       php get-screen.php --name screen-title [--out path/to/screen.yaml]\n");
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

$url = rtrim($host, '/') . ENDPOINT_PATH . '?name=' . rawurlencode($name);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_HTTPGET => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 60,
    CURLOPT_HTTPHEADER => [
        'Accept: text/yaml, application/json',
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

if ($status !== 200) {
    fwrite(STDERR, "HTTP {$status} from {$url}\n");
    fwrite(STDERR, $body . "\n");
    exit(1);
}

if ($outPath) {
    $dir = dirname($outPath);
    if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
        fwrite(STDERR, "Could not create directory: {$dir}\n");
        exit(1);
    }
    if (file_put_contents($outPath, $body) === false) {
        fwrite(STDERR, "Could not write YAML file: {$outPath}\n");
        exit(1);
    }
    fwrite(STDERR, "Wrote latest YAML for \"{$name}\" to {$outPath}\n");
} else {
    echo $body;
    if (!str_ends_with($body, "\n")) {
        echo "\n";
    }
}

exit(0);

/**
 * @return array{0: ?string, 1: ?string, 2: ?string, 3: ?string, 4: ?string}
 */
function parseCliArgs(array $args): array
{
    $positional = null;
    $name = null;
    $out = null;
    $host = null;
    $token = null;

    for ($i = 0; $i < count($args); $i++) {
        $arg = $args[$i];
        if ($arg === '--name') {
            $name = $args[++$i] ?? '';
            continue;
        }
        if ($arg === '--out') {
            $out = $args[++$i] ?? '';
            continue;
        }
        if ($arg === '--host') {
            $host = $args[++$i] ?? '';
            continue;
        }
        if ($arg === '--token') {
            $token = $args[++$i] ?? '';
            continue;
        }
        if (str_starts_with($arg, '--name=')) {
            $name = substr($arg, 7);
            continue;
        }
        if (str_starts_with($arg, '--out=')) {
            $out = substr($arg, 6);
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
        if ($positional === null && !str_starts_with($arg, '--')) {
            $positional = $arg;
        }
    }

    return [$positional, $name, $out, $host, $token];
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
