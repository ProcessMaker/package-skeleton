<?php

// @phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
/**
 * Prerequisite check for the ProcessMaker package skeleton.
 *
 * Dependency-free on purpose: it runs before `composer install`, from the
 * `composer doctor` script and from `rename-project.php`.
 *
 * Usage:
 *   php setup/requirements.php           # print table, exit 1 when something is missing
 *   php setup/requirements.php --json    # machine-readable output for agents
 */

const REQUIRED_PHP = '8.4.0';
const REQUIRED_NODE_MAJOR = 20;
const REQUIRED_COMPOSER_MAJOR = 2;
const REQUIRED_EXTENSIONS = ['curl', 'json', 'mbstring', 'tokenizer', 'xmlwriter', 'simplexml'];

/**
 * Run a command and return its trimmed stdout, or null when it is not available.
 */
function requirementsRun(string $command): ?string
{
    $isWindows = DIRECTORY_SEPARATOR === '\\';
    $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $process = @proc_open($command . ($isWindows ? '' : ' 2>/dev/null'), $descriptors, $pipes);
    if (!is_resource($process)) {
        return null;
    }
    $output = stream_get_contents($pipes[1]);
    stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    return $exitCode === 0 ? trim((string) $output) : null;
}

function requirementsExtractVersion(?string $text): ?string
{
    if ($text !== null && preg_match('/(\d+\.\d+(?:\.\d+)?)/', $text, $matches)) {
        return $matches[1];
    }

    return null;
}

/**
 * @return array{tool: string, required: string, found: string, ok: bool}
 */
function requirementsRow(string $tool, string $required, ?string $found, bool $ok): array
{
    return ['tool' => $tool, 'required' => $required, 'found' => $found ?? 'missing', 'ok' => $ok];
}

/**
 * @return list<array{tool: string, required: string, found: string, ok: bool}>
 */
function checkRequirements(): array
{
    $rows = [];

    $rows[] = requirementsRow('php', '>= ' . REQUIRED_PHP, PHP_VERSION, version_compare(PHP_VERSION, REQUIRED_PHP, '>='));

    $missingExtensions = array_values(array_filter(
        REQUIRED_EXTENSIONS,
        static fn (string $extension): bool => !extension_loaded($extension)
    ));
    $rows[] = requirementsRow(
        'php extensions',
        implode(', ', REQUIRED_EXTENSIONS),
        $missingExtensions === [] ? 'all loaded' : 'missing: ' . implode(', ', $missingExtensions),
        $missingExtensions === []
    );

    $composer = requirementsExtractVersion(requirementsRun('composer --version'));
    $rows[] = requirementsRow(
        'composer',
        (string) REQUIRED_COMPOSER_MAJOR . '.x',
        $composer,
        $composer !== null && (int) explode('.', $composer)[0] >= REQUIRED_COMPOSER_MAJOR
    );

    $node = requirementsExtractVersion(requirementsRun('node --version'));
    $rows[] = requirementsRow(
        'node',
        '>= ' . REQUIRED_NODE_MAJOR,
        $node,
        $node !== null && (int) explode('.', $node)[0] >= REQUIRED_NODE_MAJOR
    );

    foreach (['npm', 'npx'] as $tool) {
        $version = requirementsExtractVersion(requirementsRun($tool . ' --version'));
        $rows[] = requirementsRow($tool, 'installed', $version, $version !== null);
    }

    $git = requirementsExtractVersion(requirementsRun('git --version'));
    $rows[] = requirementsRow('git', 'installed', $git, $git !== null);

    return $rows;
}

/**
 * @param list<array{tool: string, required: string, found: string, ok: bool}> $rows
 */
function requirementsPassed(array $rows): bool
{
    foreach ($rows as $row) {
        if (!$row['ok']) {
            return false;
        }
    }

    return true;
}

/**
 * @param list<array{tool: string, required: string, found: string, ok: bool}> $rows
 */
function printRequirementsTable(array $rows): void
{
    $widths = ['tool' => 14, 'required' => 52, 'found' => 24];
    foreach ($rows as $row) {
        $widths['tool'] = max($widths['tool'], strlen($row['tool']));
        $widths['required'] = max($widths['required'], strlen($row['required']));
        $widths['found'] = max($widths['found'], strlen($row['found']));
    }

    $line = static fn (string $status, array $row): string => sprintf(
        '  %-4s %-' . $widths['tool'] . 's  %-' . $widths['required'] . 's  %s',
        $status,
        $row['tool'],
        $row['required'],
        $row['found']
    );

    echo $line('', ['tool' => 'tool', 'required' => 'required', 'found' => 'found']) . "\n";
    foreach ($rows as $row) {
        echo $line($row['ok'] ? 'OK' : 'FAIL', $row) . "\n";
    }
}

/**
 * @param list<array{tool: string, required: string, found: string, ok: bool}> $rows
 * @return list<string> Names of the failing tools
 */
function failedRequirements(array $rows): array
{
    return array_values(array_map(
        static fn (array $row): string => $row['tool'],
        array_filter($rows, static fn (array $row): bool => !$row['ok'])
    ));
}

if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $rows = checkRequirements();

    if (in_array('--json', $argv ?? [], true)) {
        echo json_encode(['ok' => requirementsPassed($rows), 'requirements' => $rows], JSON_PRETTY_PRINT) . "\n";
        exit(requirementsPassed($rows) ? 0 : 1);
    }

    echo "\n";
    printRequirementsTable($rows);
    echo "\n";

    if (!requirementsPassed($rows)) {
        fwrite(STDERR, '  Missing or outdated: ' . implode(', ', failedRequirements($rows)) . "\n");
        fwrite(STDERR, "  Install them (see README.md > Requirements) and run `composer doctor` again.\n\n");
        exit(1);
    }

    echo "  All requirements are satisfied.\n\n";
}
