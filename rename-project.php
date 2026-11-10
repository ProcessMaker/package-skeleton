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

$hr('renaming');
$info('Replacing ' . $bold('package-skeleton') . ' → ' . $bold($projectName));
searchDirectoryFiles(getcwd() . '/', 'package-skeleton', $projectName, $replacedCount, $dim);

$pascal = dashesToCamelCase($projectName, true);
$info('Replacing ' . $bold('PackageSkeleton') . ' → ' . $bold($pascal));
searchDirectoryFiles(getcwd() . '/', 'PackageSkeleton', $pascal, $replacedCount, $dim);

$title = dashesToCamelCase($projectName, true, ' ');
$info('Replacing ' . $bold('Package Skeleton') . ' → ' . $bold($title));
searchDirectoryFiles(getcwd() . '/', 'Package Skeleton', $title, $replacedCount, $dim);
$ok("Updated {$replacedCount} file(s)");
echo "\n";

// ProcessMaker URL.
$processmakerPlaceholder = '__PROCESSMAKER_' . 'SERVER__';
$processmakerServer = trim((string) getenv('NAYRA_SERVER'));
$hr('processmaker');
if ($processmakerServer === '' && function_exists('stream_isatty') && stream_isatty(STDIN)) {
    $processmakerServer = $ask('ProcessMaker Server URL (eg. https://pm.example.com): ');
}

if ($processmakerServer !== '') {
    $server = rtrim($processmakerServer, '/');
    $info('Setting server → ' . $bold($server));
    $urlCount = 0;
    searchDirectoryFiles(getcwd() . '/', $processmakerPlaceholder, $server, $urlCount, $dim);
    $ok("Server URL written to {$urlCount} file(s)");
} else {
    $warn("No ProcessMaker URL provided; keeping '{$processmakerPlaceholder}' as is.");
}
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
    $info('Installing ProcessMaker skills (' . $bold($skillsProfile) . ', ' . count($selectedSkills) . ' skills)...');
    echo $dim('    ' . $command) . "\n\n";
    passthru($command, $skillsExitCode);
    if ($skillsExitCode !== 0) {
        $warn("Failed to install ProcessMaker skills (exit code {$skillsExitCode}).");
    } else {
        $ok('Skills installed successfully');
    }
}

echo "\n";
$hr();
echo '  ' . $green('✦') . ' ' . $paint('1;97', 'Done') . $dim(" — {$projectName} is ready.") . "\n\n";
