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

require_once __DIR__ . '/setup/requirements.php';

$requirements = checkRequirements();
$hr('requirements');
printRequirementsTable($requirements);
echo "\n";
foreach (requirementAdvisories($requirements) as $advisory) {
    $warn($advisory);
    echo "\n";
}

// Node and npx are needed during this wizard (`npx skills add`) and for Vite/Storybook,
// so stop before renaming anything instead of ending up with a project without skills.
$nodeToolsMissing = array_values(array_intersect(failedRequirements($requirements), ['node', 'npm', 'npx']));
if ($nodeToolsMissing !== []) {
    $warn('Missing or outdated: ' . implode(', ', $nodeToolsMissing) . ' (Node.js >= ' . REQUIRED_NODE_MAJOR . ' is required).');
    $warn('Install Node.js, then run: php rename-project.php ' . basename(getcwd()));
    echo "\n";
    exit(1);
}
if (!requirementsPassed($requirements)) {
    $warn('Some requirements are not met (' . implode(', ', failedRequirements($requirements)) . '). Continuing; run `composer doctor` for details.');
    echo "\n";
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
 * MCP servers exposed by ProcessMaker core (routes/ai.php).
 *
 * @return array<string, string> Server name => endpoint path
 */
function processmakerMcpServers(): array
{
    return [
        'processmaker-processes' => '/mcp/processes',
        'processmaker-screens' => '/mcp/screens',
        'processmaker-data-connectors' => '/mcp/data-connectors',
        'processmaker-flow-genies' => '/mcp/flow-genies',
    ];
}

/**
 * @param string $key Top-level key: `mcpServers` (Claude Code, Cursor) or `servers` (VS Code)
 * @return array<string, mixed>
 */
function buildMcpServersConfig(string $server, string $key = 'mcpServers'): array
{
    $base = rtrim($server, '/');
    $servers = [];
    foreach (processmakerMcpServers() as $name => $path) {
        $servers[$name] = ['type' => 'http', 'url' => $base . $path];
    }

    return [$key => $servers];
}

function writeJsonConfig(string $path, array $data): void
{
    $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($encoded === false) {
        return;
    }
    $directory = dirname($path);
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        return;
    }
    file_put_contents($path, $encoded . "\n");
}

function writeMcpConfig(string $projectRoot, string $server): void
{
    $mcp = buildMcpServersConfig($server);
    writeJsonConfig($projectRoot . '/.mcp.json', $mcp);
    writeJsonConfig($projectRoot . '/.cursor/mcp.json', $mcp);
    writeJsonConfig($projectRoot . '/.vscode/mcp.json', buildMcpServersConfig($server, 'servers'));
}

/**
 * Codex keeps MCP servers in the user config, so print the commands instead of writing a file.
 *
 * @return list<string>
 */
function codexMcpCommands(string $server): array
{
    $base = rtrim($server, '/');
    $commands = [];
    foreach (processmakerMcpServers() as $name => $path) {
        $commands[] = 'codex mcp add ' . $name . ' --url ' . $base . $path;
    }
    foreach (array_keys(processmakerMcpServers()) as $name) {
        $commands[] = 'codex mcp login ' . $name;
    }

    return $commands;
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

Important: After create a bpmn process use the skill pm4-bpmn-beautify to beautify the bpmn process.
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

/**
 * Same rule as ProcessMaker core PluginManager::extractRepoName(): core installs the plugin
 * under storage/plugins/{repoName} and runs `{repoName}:install`.
 */
function extractRepoName(string $repoUrl): ?string
{
    $repoUrl = preg_replace('/(\.git|\/)$/', '', trim($repoUrl));
    if (preg_match('#/([^/]+)$#', (string) $repoUrl, $matches)) {
        return $matches[1];
    }

    return null;
}

/**
 * @param callable(string): void $ok
 * @param callable(string): void $warn
 */
function setupGitRepository(string $projectRoot, string $repoUrl, string $branch, callable $ok, callable $warn): void
{
    $root = escapeshellarg(rtrim($projectRoot, '/\\'));
    $run = static function (string $command) use ($root): bool {
        exec('git -C ' . $root . ' ' . $command . ' 2>&1', $output, $exitCode);

        return $exitCode === 0;
    };

    if (!is_dir(rtrim($projectRoot, '/\\') . '/.git')) {
        if (!$run('init')) {
            $warn('git init failed; initialize the repository manually.');
            return;
        }
        $ok('Initialized git repository');
    }

    // Works on every git version (git init -b needs 2.28+).
    $run('symbolic-ref HEAD ' . escapeshellarg('refs/heads/' . $branch));
    $run('config core.hooksPath ./.git-hooks');

    exec('git -C ' . $root . ' remote get-url origin 2>/dev/null', $existingOrigin, $hasOrigin);
    if ($hasOrigin === 0) {
        $run('remote set-url origin ' . escapeshellarg($repoUrl));
    } else {
        $run('remote add origin ' . escapeshellarg($repoUrl));
    }
    $ok('git remote origin → ' . $repoUrl . ' (branch ' . $branch . ')');
}

$pluginRepo = trim((string) getenv('PM_PLUGIN_REPO'));
$pluginBranch = trim((string) getenv('PM_PLUGIN_BRANCH'));
if ($pluginBranch === '') {
    $pluginBranch = 'main';
}

if ($pluginRepo !== '') {
    $repoName = extractRepoName($pluginRepo);
    if ($repoName === null) {
        $warn("Could not extract a repository name from PM_PLUGIN_REPO ({$pluginRepo}).");
        exit(1);
    }
    if ($repoName !== $projectName) {
        // Core installs the plugin as storage/plugins/{repoName} and runs {repoName}:install.
        $warn("Repository name '{$repoName}' must match the package name '{$projectName}'.");
        $warn("Run: composer create-project … {$repoName}   (or set PM_PLUGIN_REPO to the matching repository).");
        echo "\n";
        exit(1);
    }
    if (!str_starts_with($pluginRepo, 'https://')) {
        $warn('PM_PLUGIN_REPO is not an https:// URL; ProcessMaker (Admin › Plugins) only installs plugins from https:// URLs.');
    }
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
    'pm4-bpmn-beautify',
    'pm4-collection',
    'pm4-data-connector',
    'pm4-decision-table',
    'pm4-docusign',
    'pm4-expressions',
    'pm4-flow-genie',
    'pm4-form-manual-task',
    'pm4-idp',
    'pm4-overview',
    'pm4-pdf-generator',
    'pm4-pm-block',
    'pm4-process',
    'pm4-process-health',
    'pm4-screen',
    'pm4-script',
    'pm4-send-email',
    'pm4-signal',
    'pm4-slack',
    'pm4-sub-process',
    'pm4-uipath',
    'pm4-web-entry',
    'vue2-pm4-patterns',
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
// PM_BASE_URL is the canonical name; NAYRA_SERVER is kept as an alias for older instructions.
$processmakerServer = trim((string) getenv('PM_BASE_URL'));
if ($processmakerServer === '') {
    $processmakerServer = trim((string) getenv('NAYRA_SERVER'));
}
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
    $ok('Wrote .mcp.json, .cursor/mcp.json and .vscode/mcp.json (4 MCP servers)');
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

if ($pluginRepo !== '') {
    $hr('git');
    setupGitRepository($projectRoot, $pluginRepo, $pluginBranch, $ok, $warn);
    echo "\n";
}

$hr();
echo '  ' . $green('✦') . ' ' . $paint('1;97', 'Done') . $dim(" — {$projectName} is ready.") . "\n\n";

$info($bold('Next steps'));
$step = 1;
echo '    ' . $step++ . '. npm install && npm run prod' . $dim('   (builds public/, which core does not build)') . "\n";
if ($pluginRepo !== '') {
    echo '    ' . $step++ . '. Commit everything including ' . $bold('public/') . ' and push: git push -u origin ' . $pluginBranch . "\n";
    echo '    ' . $step++ . '. Admin › Plugins › Plugin: enter ' . $bold($pluginRepo) . ' (branch ' . $pluginBranch . ') and click Save to install' . "\n";
} else {
    echo '    ' . $step++ . '. Create an empty git repository named ' . $bold($projectName) . ', commit everything including ' . $bold('public/') . ' and push it' . "\n";
    echo '    ' . $step++ . '. Admin › Plugins › Plugin: enter the repository URL and click Save to install' . "\n";
}
if ($processmakerServer !== '') {
    echo '    ' . $step++ . '. MCP (OAuth): authorize the 4 ProcessMaker servers in Claude Code, Cursor (.cursor/mcp.json) or VS Code (.vscode/mcp.json)' . "\n";
    echo '       Codex:' . "\n";
    foreach (codexMcpCommands($processmakerServer) as $codexCommand) {
        echo '         ' . $dim($codexCommand) . "\n";
    }
    echo '    ' . $step++ . '. Put your ProcessMaker API token in .env as PM_SYNC_TOKEN (used by the upsert-* deploy scripts)' . "\n";
}
if ($skillsProfile === 'migration') {
    echo '    ' . $step++ . '. ai-docs/migration/AGENTS.md' . "\n";
}
echo "\n";
