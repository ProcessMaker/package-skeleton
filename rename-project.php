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
