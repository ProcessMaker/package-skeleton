#!/usr/bin/env php
<?php

const DEPLOY_ENDPOINT = '/api/1.0/deploy-process';
const GET_ENDPOINT = '/api/1.0/process-bpmn';

$projectRoot = dirname(__DIR__, 4);
$envPath = $projectRoot . '/.env';

[$command, $target, $options] = parseArguments(array_slice($argv, 1));
if (!in_array($command, ['upsert', 'get'], true) || $target === null) {
    usage();
}

$env = loadDotEnv($envPath);
$host = firstNonEmpty(
    $options['host'],
    $env['PM_BASE_URL'] ?? null,
    getenv('PM_BASE_URL') ?: null
);
$token = firstNonEmpty(
    $options['token'],
    $env['PM_SYNC_TOKEN'] ?? null,
    getenv('PM_SYNC_TOKEN') ?: null
);

if ($host === '') {
    fail("PM_BASE_URL is required. Set it in {$envPath} or pass --host.");
}
if ($token === '') {
    fail("PM_SYNC_TOKEN is required. Set it in {$envPath} or pass --token.");
}
if (!function_exists('curl_init')) {
    fail('The PHP cURL extension is required.');
}

if ($command === 'upsert') {
    upsertProcess($target, $host, $token, $options);
}

getProcess($target, $host, $token, $options['output']);

function upsertProcess(string $file, string $host, string $token, array $options): never
{
    $path = resolvePath($file);
    if (!is_file($path)) {
        fail("BPMN file not found: {$path}");
    }

    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if (!in_array($extension, ['bpmn', 'xml'], true)) {
        fail("File must be .bpmn or .xml: {$path}");
    }

    $bpmn = file_get_contents($path);
    if ($bpmn === false) {
        fail("Could not read BPMN file: {$path}");
    }

    $description = resolveProcessDescription($path, $options['description'] ?? null);

    $payload = [
        'name' => pathinfo($path, PATHINFO_FILENAME),
        'bpmn' => $bpmn,
    ];
    if ($description !== null) {
        $payload['description'] = $description;
    }

    $response = request(
        'POST',
        rtrim($host, '/') . DEPLOY_ENDPOINT,
        $token,
        $payload
    );

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(0);
}

function getProcess(
    string $name,
    string $host,
    string $token,
    ?string $output
): never {
    $url = rtrim($host, '/') . GET_ENDPOINT . '?' . http_build_query(
        ['name' => $name],
        '',
        '&',
        PHP_QUERY_RFC3986
    );
    $response = request('GET', $url, $token);
    $bpmn = $response['bpmn'] ?? null;

    if (!is_string($bpmn)) {
        fail('The API response did not contain BPMN.');
    }

    if ($output === null) {
        echo $bpmn;
        if (!str_ends_with($bpmn, PHP_EOL)) {
            echo PHP_EOL;
        }
        exit(0);
    }

    $path = resolvePath($output);
    $directory = dirname($path);
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        fail("Could not create output directory: {$directory}");
    }
    if (file_put_contents($path, $bpmn) === false) {
        fail("Could not write BPMN file: {$path}");
    }

    fwrite(STDOUT, "Wrote {$path}" . PHP_EOL);
    exit(0);
}

function request(
    string $method,
    string $url,
    string $token,
    ?array $payload = null
): array {
    $handle = curl_init($url);
    $options = [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
        ],
    ];

    if ($payload !== null) {
        $encoded = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            fail('Could not encode the request payload.');
        }
        $options[CURLOPT_POSTFIELDS] = $encoded;
    }

    curl_setopt_array($handle, $options);
    $body = curl_exec($handle);
    $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
    $error = curl_error($handle);
    curl_close($handle);

    if ($body === false) {
        fail("Request failed: {$error}");
    }

    $data = json_decode($body, true);
    if (
        $status < 200
        || $status >= 300
        || !is_array($data)
        || empty($data['success'])
    ) {
        fwrite(STDERR, "HTTP {$status} from {$url}" . PHP_EOL);
        fwrite(STDERR, $body . PHP_EOL);
        exit(1);
    }

    return $data;
}

/**
 * @return array{0: ?string, 1: ?string, 2: array{host: ?string, token: ?string, output: ?string, description: ?string}}
 */
function parseArguments(array $arguments): array
{
    $command = array_shift($arguments);
    $target = null;
    $options = ['host' => null, 'token' => null, 'output' => null, 'description' => null];

    for ($index = 0; $index < count($arguments); $index++) {
        $argument = $arguments[$index];

        foreach (array_keys($options) as $option) {
            if ($argument === "--{$option}") {
                $options[$option] = $arguments[++$index] ?? '';
                continue 2;
            }
            if (str_starts_with($argument, "--{$option}=")) {
                $options[$option] = substr($argument, strlen($option) + 3);
                continue 2;
            }
        }

        if ($target === null && !str_starts_with($argument, '--')) {
            $target = $argument;
            continue;
        }

        fail("Unknown argument: {$argument}");
    }

    return [$command, $target, $options];
}

function resolvePath(string $path): string
{
    return str_starts_with($path, '/') ? $path : getcwd() . '/' . $path;
}

/**
 * CLI --description, else projects/{slug}/process-description.txt when BPMN is under projects/{slug}/bpmn/.
 */
function resolveProcessDescription(string $bpmnPath, ?string $cliDescription): ?string
{
    if ($cliDescription !== null && $cliDescription !== '') {
        return $cliDescription;
    }

    $canonical = realpath($bpmnPath) ?: $bpmnPath;
    $projectsDir = DIRECTORY_SEPARATOR . 'projects' . DIRECTORY_SEPARATOR;
  /** @var array{0: string, 1: string}|false $parts */
    $parts = preg_match('#^(.*)' . preg_quote($projectsDir, '#') . '([^/]+)/bpmn/[^/]+$#', $canonical, $matches)
        ? [$matches[1], $matches[2]]
        : false;
    if ($parts === false) {
        return null;
    }

    $descriptionFile = $parts[0] . $projectsDir . $parts[1] . DIRECTORY_SEPARATOR . 'process-description.txt';
    if (!is_file($descriptionFile)) {
        return null;
    }

    $text = file_get_contents($descriptionFile);
    if ($text === false) {
        return null;
    }

    $text = trim($text);

    return $text === '' ? null : $text;
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

    $variables = [];
    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        return [];
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
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
        $variables[$key] = $value;
    }

    return $variables;
}

function usage(): never
{
    fwrite(STDERR, <<<TEXT
Usage:
  php sync-process.php upsert path/to/process.bpmn [--host URL] [--token TOKEN] [--description TEXT]
  php sync-process.php get process-name [--output path/to/process.bpmn] [--host URL] [--token TOKEN]

TEXT);
    exit(1);
}

function fail(string $message): never
{
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}
