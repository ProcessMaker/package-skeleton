<?php

// @phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
//use => php rename-project.php string_to_replace

$supportsColor = function_exists('stream_isatty') && stream_isatty(STDOUT)
    && getenv('NO_COLOR') === false
    && strtolower((string) getenv('TERM')) !== 'dumb';

$paint = static function (string $code, string $text) use ($supportsColor): string {
    return $supportsColor ? "\033[{$code}m{$text}\033[0m" : $text;
};

$bold = static fn (string $text): string => $paint('1', $text);
$dim = static fn (string $text): string => $paint('2', $text);
$cyan = static fn (string $text): string => $paint('36', $text);
$green = static fn (string $text): string => $paint('32', $text);
$yellow = static fn (string $text): string => $paint('33', $text);

$logo = <<<'LOGO'
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
%%%%%%%%%%%%%%%%%%% DECISIONS %%%%%%%%%%%%%%%%%%%%
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
%%%%%%%%%+%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%+%%%%%%%%
%%%%%%%%*          :+%%%%%%%%#:          =%%%%%%%%
%%%%%%%%*            :*%%%%#-            =%%%%%%%%
%%%%%%%%*              :*#=              =%%%%%%%%
%%%%%%%%*      +%%%%%%%%+-#%%%%%%%%      =%%%%%%%%
%%%%%%%%*      *%%%%%%+    -%%%%%%%      =%%%%%%%%
%%%%%%%%%#     *%%%%*        -%%%%%     +%%%%%%%%%
%%%%%%%%%%%*   *%%*:           =%%%   =%%%%%%%%%%%
%%%%%%%%%%%%%+ *#:              :+% =#%%%%%%%%%%%%
%%%%%%%%%%%%%%*=                  ==%%%%%%%%%%%%%%
%%%%%%%%%%%%#= *%-              :*% :#%%%%%%%%%%%%
%%%%%%%%%%%+   *%%#-          :*%%%   -#%%%%%%%%%%
%%%%%%%%%+     *%%%%#        +%%%%%     -%%%%%%%%%
%%%%%%%%*      *%%%%%%*    +%%%%%%%      =%%%%%%%%
%%%%%%%%*      =#######*=-*#######*      =%%%%%%%%
%%%%%%%%*              -#%+              =%%%%%%%%
%%%%%%%%*            -#%%%%%=            =%%%%%%%%
%%%%%%%%*          :*%%%%%%%%%-          =%%%%%%%%
%%%%%%%%%#********#%%%%%%%%%%%%%*********%%%%%%%%%
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
%%%%%%%%%%%%%%%%%  PROCESSMAKER  %%%%%%%%%%%%%%%%%
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
LOGO;

echo "\n";
foreach (explode("\n", $logo) as $line) {
    echo $cyan($line) . "\n";
}
echo "\n";
echo '  ' . $paint('1;97', 'ProcessMaker') . ' ' . $dim('·') . ' ' . $cyan('Package Skeleton') . "\n";
echo '  ' . $dim('Rename & configure your new package project') . "\n\n";

$hr = static function (string $label = '') use ($dim, $cyan): void {
    if ($label === '') {
        echo $dim(str_repeat('─', 52)) . "\n";
        return;
    }
    $pad = max(0, 50 - strlen($label));
    echo $dim('─ ') . $cyan($label) . $dim(' ' . str_repeat('─', $pad)) . "\n";
};

$info = static function (string $message) use ($cyan): void {
    echo $cyan('  › ') . $message . "\n";
};

$ok = static function (string $message) use ($green): void {
    echo $green('  ✓ ') . $message . "\n";
};

$warn = static function (string $message) use ($yellow): void {
    echo $yellow('  ! ') . $message . "\n";
};

$ask = static function (string $prompt) use ($bold, $cyan): string {
    echo $cyan('  › ') . $bold($prompt);
    $answer = fgets(STDIN);
    return $answer === false ? '' : trim($answer);
};

if (!isset($argc)) {
    $warn('argc and argv are disabled; cannot continue.');
    echo "\n";
    return;
}

if (!isset($argv[1])) {
    $argv[1] = basename(getcwd());
}

$projectName = $argv[1];
$hr('project');
$info('Working directory: ' . $dim(getcwd()));
$info('Package name:      ' . $bold($projectName));
echo "\n";

$replacedCount = 0;

function replaceStringInFile($filename, $stringToReplace, $replaceWith)
{
    $content = file_get_contents($filename);
    if ($content === false || strpos($content, $stringToReplace) === false) {
        return false;
    }

    $content_chunks = explode($stringToReplace, $content);
    $content = implode($replaceWith, $content_chunks);
    file_put_contents($filename, $content);

    return true;
}

function searchDirectoryFiles($path, $stringToReplace, $replaceWith, &$replacedCount, $dim)
{
    if (!is_dir($path)) {
        echo "\n  Invalid path...\n";
        return;
    }

    if ($dh = opendir($path)) {
        while (($file = readdir($dh)) !== false) {
            if (
                is_dir($path . $file)
                && $file != '.'
                && $file != '..'
                && strpos($file, 'node_modules') === false
                && strpos($file, '.git') === false
            ) {
                searchDirectoryFiles($path . $file . '/', $stringToReplace, $replaceWith, $replacedCount, $dim);
            } elseif (!is_dir($path . $file)) {
                if (replaceStringInFile($path . $file, $stringToReplace, $replaceWith)) {
                    $replacedCount++;
                    $relative = ltrim(str_replace(getcwd(), '', $path . $file), '/');
                    echo $dim('    · ') . $relative . "\n";
                }
            }
        }
        closedir($dh);
    }
}

function dashesToCamelCase($string, $capitalizeFirstCharacter = false, $replace = '')
{
    $str = str_replace('-', $replace, ucwords($string, '-'));

    if (!$capitalizeFirstCharacter) {
        $str = lcfirst($str);
    }

    return $str;
}

/**
 * @return array<string, mixed>
 */
function buildMcpServersConfig(string $server): array
{
  $base = rtrim($server, '/');

  return [
    'mcpServers' => [
      'processmaker-processes' => [
        'type' => 'http',
        'url' => $base . '/mcp/processes',
      ],
      'processmaker-screens' => [
        'type' => 'http',
        'url' => $base . '/mcp/screens',
      ],
    ],
  ];
}

function writeJsonConfig(string $path, array $data): void
{
    $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($encoded === false) {
        return;
    }
    file_put_contents($path, $encoded . "\n");
}

function writeMcpConfig(string $projectRoot, string $server): void
{
    $mcp = buildMcpServersConfig($server);
    writeJsonConfig($projectRoot . '/.mcp.json', $mcp);
    writeJsonConfig($projectRoot . '/.cursor/mcp.json', $mcp);
}

function writeEnvExample(string $projectRoot, string $server): void
{
    $base = rtrim($server, '/');
    $lines = [
        '# ProcessMaker sync target (used by deploy scripts and local tooling).',
        'PM_BASE_URL=' . $base,
        'PM_SYNC_TOKEN=',
        '',
    ];
    file_put_contents($projectRoot . '/.env.example', implode("\n", $lines));
}

function writeLocalEnv(string $projectRoot, string $server, string $token): void
{
    $base = rtrim($server, '/');
    $lines = [
        'PM_BASE_URL=' . $base,
        'PM_SYNC_TOKEN=' . $token,
        '',
    ];
    file_put_contents($projectRoot . '/.env', implode("\n", $lines));
}

function ensureMigrationDirectories(string $projectRoot): void
{
    foreach (['migration/inputs', 'migration/outputs'] as $dir) {
        $path = $projectRoot . '/' . $dir;
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }
}

/**
 * Prepend migration guidance to root AGENTS.md when initializing for PM3 → PM4.
 *
 * @return bool True when the file was updated (or already had the intro).
 */
function prependMigrationAgentsIntro(string $projectRoot): bool
{
    $agentsPath = rtrim($projectRoot, '/\\') . '/AGENTS.md';
    if (!is_file($agentsPath)) {
        return false;
    }

    $existing = file_get_contents($agentsPath);
    if ($existing === false) {
        return false;
    }

    $marker = "# What to do\n";
    if (str_starts_with(ltrim($existing), $marker)) {
        return true;
    }

    $intro = <<<'MD'
# What to do

You are an ProcessMaker expert that will guide the migration of a ProcessMaker 3 process to ProcessMaker 4.

Use the available skills to analyze the files provided by the user to understand the process or processes and its assets. Ask to the user the source files and the name of the project.

Then based on that knowledge create a new project with name provided in the folder ai-docs, write as best as possible all its requirements base of the pmx2 file analysis of its process and assets. Then create the project using the [structure.md](./ai-docs/structure.md). Make sure it contain all the business logic defined and identified in the pmx or pmx2 file and the user interface as similar as possible, use the yaml and vue files as needed and create the required schema models. Make sure to include php unit tests and storybooks

If you find .html files in the source folder, use them as email templates.
If you find .pmt files in the source folder, use them as pmtables or report tables.
If you find a .tar file in the source folder, use it as a plugin code, use it to complete the code, or copy the code, refactor it, include it in the current plugin or better use it as knowledge base to complete the scripts or php code in this project. After the creation and implementation of the process or processes generate a report with all the assets implemented and the list of assets no implemented to review manually with specification about why these were not implemented, also provide the percentage of the implementation.

Important: When you create and deploy a process always add a brief summary of it to its description field.
Important: If you use lanes in the bpmn, always add `<bpmn:collaboration>`

MD;

    return file_put_contents($agentsPath, $intro . $existing) !== false;
}

/**
 * Package-owned deploy skills that must survive `npx skills add`.
 *
 * @return list<string>
 */
function packageUpsertSkillNames(): array
{
    return [
        'upsert-script-php',
        'upsert-screen-yaml',
        'upsert-process-bpmn',
    ];
}

function copyDirectoryRecursive(string $source, string $destination): bool
{
    if (!is_dir($source)) {
        return false;
    }
    if (!is_dir($destination) && !mkdir($destination, 0755, true) && !is_dir($destination)) {
        return false;
    }

    $handle = opendir($source);
    if ($handle === false) {
        return false;
    }

    while (($entry = readdir($handle)) !== false) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $from = $source . DIRECTORY_SEPARATOR . $entry;
        $to = $destination . DIRECTORY_SEPARATOR . $entry;
        if (is_dir($from)) {
            if (!copyDirectoryRecursive($from, $to)) {
                closedir($handle);
                return false;
            }
            continue;
        }
        if (!copy($from, $to)) {
            closedir($handle);
            return false;
        }
    }

    closedir($handle);
    return true;
}

function removeDirectoryRecursive(string $path): void
{
    if (!is_dir($path)) {
        return;
    }

    $handle = opendir($path);
    if ($handle === false) {
        return;
    }

    while (($entry = readdir($handle)) !== false) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $target = $path . DIRECTORY_SEPARATOR . $entry;
        if (is_dir($target)) {
            removeDirectoryRecursive($target);
            continue;
        }
        @unlink($target);
    }

    closedir($handle);
    @rmdir($path);
}

/**
 * @return string|null Absolute backup directory, or null when nothing was copied.
 */
function backupPackageUpsertSkills(string $projectRoot): ?string
{
    $skillsRoot = rtrim($projectRoot, '/\\') . '/.agents/skills';
    $backupRoot = sys_get_temp_dir() . '/pm-package-upsert-' . bin2hex(random_bytes(4));
    $copied = 0;

    foreach (packageUpsertSkillNames() as $name) {
        $source = $skillsRoot . '/' . $name;
        if (!is_dir($source)) {
            continue;
        }
        if (!copyDirectoryRecursive($source, $backupRoot . '/' . $name)) {
            removeDirectoryRecursive($backupRoot);
            return null;
        }
        $copied++;
    }

    return $copied > 0 ? $backupRoot : null;
}

/**
 * @param callable(string): void $ok
 * @param callable(string): void $warn
 */
function restorePackageUpsertSkills(string $projectRoot, ?string $backupRoot, callable $ok, callable $warn): void
{
    if ($backupRoot === null || !is_dir($backupRoot)) {
        return;
    }

    $skillsRoot = rtrim($projectRoot, '/\\') . '/.agents/skills';
    if (!is_dir($skillsRoot) && !mkdir($skillsRoot, 0755, true) && !is_dir($skillsRoot)) {
        $warn('Could not recreate .agents/skills to restore upsert skills.');
        removeDirectoryRecursive($backupRoot);
        return;
    }

    foreach (packageUpsertSkillNames() as $name) {
        $source = $backupRoot . '/' . $name;
        $destination = $skillsRoot . '/' . $name;
        if (!is_dir($source) || is_dir($destination)) {
            continue;
        }
        if (copyDirectoryRecursive($source, $destination)) {
            $ok('Restored package skill ' . $name);
            continue;
        }
        $warn('Could not restore package skill ' . $name);
    }

    removeDirectoryRecursive($backupRoot);
}

/**
 * @param callable(string): void $ok
 * @param callable(string): void $warn
 */
function assertPackageUpsertSkills(string $projectRoot, callable $ok, callable $warn): void
{
    $skillsRoot = rtrim($projectRoot, '/\\') . '/.agents/skills';
    $missing = [];
    foreach (packageUpsertSkillNames() as $name) {
        if (!is_dir($skillsRoot . '/' . $name)) {
            $missing[] = $name;
        }
    }

    if ($missing === []) {
        $ok('Package upsert skills are present under .agents/skills/');
        return;
    }

    $warn('Missing package upsert skills: ' . implode(', ', $missing));
}

$hr('renaming');
$info('Replacing ' . $bold('package-skeleton') . ' → ' . $bold($projectName));
searchDirectoryFiles(getcwd() . '/', 'package-skeleton', $projectName, $replacedCount, $dim);

$pascal = dashesToCamelCase($projectName, true);
$info('Replacing ' . $bold('PackageSkeleton') . ' → ' . $bold($pascal));
searchDirectoryFiles(getcwd() . '/', 'PackageSkeleton', $pascal, $replacedCount, $dim);

$title = dashesToCamelCase($projectName, true, ' ');
$info('Replacing ' . $bold('Package Skeleton') . ' → ' . $bold($title));
searchDirectoryFiles(getcwd() . '/', 'Package Skeleton', $title, $replacedCount, $dim);

$camel = dashesToCamelCase($projectName, false);
$info('Replacing ' . $bold('packageSkeleton') . ' → ' . $bold($camel));
searchDirectoryFiles(getcwd() . '/', 'packageSkeleton', $camel, $replacedCount, $dim);
$ok("Updated {$replacedCount} file(s)");
echo "\n";

// PM4 Skills.
$pm4Skills = [
    'pm4-action-by-email',
    'pm4-collection',
    'pm4-data-connector',
    'pm4-decision-table',
    'pm4-expressions',
    'pm4-flow-genie',
    'pm4-form-manual-task',
    'pm4-overview',
    'pm4-pm-block',
    'pm4-process',
    'pm4-process-health',
    'pm4-screen',
    'pm4-script',
    'pm4-send-email',
    'pm4-signal',
    'pm4-sub-process',
];
$pm3Skills = [
    'pm3-actions-by-email',
    'pm3-case-scheduler',
    'pm3-case-tracker',
    'pm3-classic-process',
    'pm3-consolidated',
    'pm3-custom-cases-list',
    'pm3-db-connection',
    'pm3-dynaform',
    'pm3-email-template',
    'pm3-expressions',
    'pm3-input-document',
    'pm3-message-type',
    'pm3-output-document',
    'pm3-overview',
    'pm3-pm-table',
    'pm3-process',
    'pm3-report-table',
    'pm3-trigger',
    'pm3-variables',
    'pm3-web-entry',
];
$migrationSkills = [
    'pm-analyze',
    'pm3-pm4-email-notifications',
    'pm3-pm4-input-documents',
    'pm3-pm4-migration',
    'pm3-pm4-output-documents',
    'pm3-pm4-subprocess',
    'pm3-pm4-triggers',
    'pm3-pm4-web-entry',
];

$skillsProfile = strtolower(trim((string) getenv('PM_SKILLS_PROFILE')));
if (!in_array($skillsProfile, ['migration', 'pm4'], true)) {
    $skillsProfile = '';
    if (function_exists('stream_isatty') && stream_isatty(STDIN)) {
        $hr('skills');
        echo $cyan('  › ') . $bold('Project type') . "\n\n";
        echo '    ' . $paint('1;36', '1') . '  Migration  ' . $dim('(PM3 → PM4 + migration skills)') . "\n";
        echo '    ' . $paint('1;36', '2') . '  PM4 only   ' . $dim('(pm4 skills)') . "\n\n";

        while ($skillsProfile === '') {
            $answer = strtolower($ask('Option [1/2]: '));
            if ($answer === '1' || $answer === 'migration') {
                $skillsProfile = 'migration';
            } elseif ($answer === '2' || $answer === 'pm4') {
                $skillsProfile = 'pm4';
            } else {
                $warn('Please choose 1 or 2.');
            }
        }
        echo "\n";
    }
}

// ProcessMaker target + MCP (shared by migration and PM4-only).
$processmakerPlaceholder = '__PROCESSMAKER_' . 'SERVER__';
$projectRoot = getcwd() . '/';
$processmakerServer = trim((string) getenv('NAYRA_SERVER'));
$syncToken = trim((string) getenv('PM_SYNC_TOKEN'));

$hr('processmaker');
if ($processmakerServer === '' && function_exists('stream_isatty') && stream_isatty(STDIN)) {
    $processmakerServer = $ask('ProcessMaker Server URL (eg. https://pm.example.com): ');
}

$isInteractive = function_exists('stream_isatty') && stream_isatty(STDIN);
if ($syncToken === '' && $isInteractive && $processmakerServer !== '') {
    $syncToken = $ask('API token (Passport bearer for MCP/deploy; Enter to skip): ');
}

if ($processmakerServer !== '') {
    $server = rtrim($processmakerServer, '/');
    $info('Setting server → ' . $bold($server));
    writeMcpConfig($projectRoot, $server);
    $ok('Wrote .mcp.json and .cursor/mcp.json (processes + screens)');
    writeEnvExample($projectRoot, $server);
    $ok('Wrote .env.example');
    if ($syncToken !== '') {
        writeLocalEnv($projectRoot, $server, $syncToken);
        $ok('Wrote .env with PM_BASE_URL and PM_SYNC_TOKEN');
    } else {
        $warn('No PM_SYNC_TOKEN; add it to .env before deploy or authorize MCP in the IDE.');
    }
    $urlCount = 0;
    searchDirectoryFiles($projectRoot, $processmakerPlaceholder, $server, $urlCount, $dim);
    if ($urlCount > 0) {
        $ok("Replaced {$processmakerPlaceholder} in {$urlCount} other file(s)");
    }
} else {
    $warn("No ProcessMaker URL provided; keeping '{$processmakerPlaceholder}' in templates.");
}
echo "\n";

if ($skillsProfile === 'migration') {
    $hr('migration');
    ensureMigrationDirectories($projectRoot);
    $ok('migration/inputs and migration/outputs are ready');
    if (prependMigrationAgentsIntro($projectRoot)) {
        $ok('Prepended migration guidance to AGENTS.md');
    } else {
        $warn('Could not prepend migration guidance to AGENTS.md');
    }
    $info('Place PM3 exports in ' . $bold('migration/inputs/') . ' — see ' . $bold('ai-docs/migration/AGENTS.md'));
    echo "\n";
}

$upsertSkillsBackup = null;

if ($skillsProfile === '') {
    $warn('No skills profile provided; skipping skills installation.');
} else {
    $selectedSkills = $skillsProfile === 'pm4'
        ? $pm4Skills
        : array_merge($pm3Skills, $pm4Skills, $migrationSkills);

    $command = 'npx skills add processmaker/skills -y';
    foreach ($selectedSkills as $skill) {
        $command .= ' --skill ' . escapeshellarg($skill);
    }

    $hr('install');
    $upsertSkillsBackup = backupPackageUpsertSkills($projectRoot);
    if ($upsertSkillsBackup === null) {
        $warn('Could not backup package upsert skills before install; continuing.');
    }
    $info('Installing ProcessMaker skills (' . $bold($skillsProfile) . ', ' . count($selectedSkills) . ' skills)...');
    echo $dim('    ' . $command) . "\n\n";
    passthru($command, $skillsExitCode);
    restorePackageUpsertSkills($projectRoot, $upsertSkillsBackup, $ok, $warn);
    if ($skillsExitCode !== 0) {
        $warn("Failed to install ProcessMaker skills (exit code {$skillsExitCode}).");
    } else {
        $ok('Skills installed successfully');
    }
}

$hr('package-skills');
assertPackageUpsertSkills($projectRoot, $ok, $warn);

echo "\n";
$hr();
echo '  ' . $green('✦') . ' ' . $paint('1;97', 'Done') . $dim(" — {$projectName} is ready.") . "\n";
if ($processmakerServer !== '') {
    $info('MCP: connect processmaker-processes and processmaker-screens in Cursor with your API token.');
}
if ($skillsProfile === 'migration') {
    $info('Next: ai-docs/migration/AGENTS.md');
}
echo "\n";
