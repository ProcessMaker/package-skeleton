<?php

// @phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
//use => php rename-project.php string_to_replace

if (isset($argc)) {
    for ($i = 0; $i < $argc; $i++) {
        echo 'Argument #' . $i . ' - ' . $argv[$i] . "\n";
    }
} else {
    echo "argc and argv disabled\n";
    return;
}

if (!isset($argv[1])) {
    $argv[1] = basename(getcwd());
    echo 'Name project: ' . $argv[1] . "\n";
}

function replaceStringInFile($filename, $stringToReplace, $replaceWith)
{
    $content = file_get_contents($filename);
    $content_chunks = explode($stringToReplace, $content);
    $content = implode($replaceWith, $content_chunks);
    file_put_contents($filename, $content);
}

function searchDirectoryFiles($path, $stringToReplace, $replaceWith)
{
    if (is_dir($path)) {
        if ($dh = opendir($path)) {
            while (($file = readdir($dh)) !== false) {
                if (
                    is_dir($path . $file)
                    && $file != "."
                    && $file != ".."
                    && strpos($file, 'node_modules') === false
                    && strpos($file, '.git') === false
                ) {
                    searchDirectoryFiles($path . $file . '/', $stringToReplace, $replaceWith);
                } elseif (!is_dir($path . $file)) {
                    echo "\n Replacing: $path$file";

                    replaceStringInFile($path . $file, $stringToReplace, $replaceWith);
                }
            }
            closedir($dh);
        }
    } else {
        echo "\n Invalid path...";
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

searchDirectoryFiles(getcwd() . '/', 'package-skeleton', $argv[1]);

searchDirectoryFiles(getcwd() . '/', 'PackageSkeleton', dashesToCamelCase($argv[1], true));

searchDirectoryFiles(getcwd() . '/', 'Package Skeleton', dashesToCamelCase($argv[1], true, ' '));

// ProcessMaker URL.
$processmakerPlaceholder = '__PROCESSMAKER_' . 'SERVER__';
$processmakerServer = trim((string) getenv('NAYRA_SERVER'));
if ($processmakerServer === '' && function_exists('stream_isatty') && stream_isatty(STDIN)) {
    echo "\n\nProcessMaker Server URL(eg. https://pm.example.com): ";
    $processmakerServer = trim((string) fgets(STDIN));
}

if ($processmakerServer !== '') {
    searchDirectoryFiles(getcwd() . '/', $processmakerPlaceholder, rtrim($processmakerServer, '/'));
} else {
    echo "\nNo ProcessMaker URL was provided; keeping '" . $processmakerPlaceholder . "' as is.\n";
}

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
        while ($skillsProfile === '') {
            echo "\nProject type:\n";
            echo "  1) Migration Project from PM3 to PM4 (pm3, pm4 and migration skills)\n";
            echo "  2) PM4 Project (pm4 skills only)\n";
            echo "Option: ";
            $answer = fgets(STDIN);
            if ($answer === false) {
                break;
            }
            $answer = strtolower(trim($answer));
            if ($answer === '1' || $answer === 'migration') {
                $skillsProfile = 'migration';
            } elseif ($answer === '2' || $answer === 'pm4') {
                $skillsProfile = 'pm4';
            } else {
                echo "Please choose 1 or 2.\n";
            }
        }
    }
}

if ($skillsProfile === '') {
    echo "\nNo skills profile was provided; skipping skills installation.\n";
} else {
    $selectedSkills = $skillsProfile === 'pm4'
        ? $pm4Skills
        : array_merge($pm3Skills, $pm4Skills, $migrationSkills);

    $command = 'npx skills add processmaker/skills -y';
    foreach ($selectedSkills as $skill) {
        $command .= ' --skill ' . escapeshellarg($skill);
    }

    echo "\nInstalling ProcessMaker skills (" . $skillsProfile . ")...\n";
    echo $command . "\n";
    passthru($command, $skillsExitCode);
    if ($skillsExitCode !== 0) {
        echo "\nFailed to install ProcessMaker skills (exit code " . $skillsExitCode . ").\n";
    }
}
