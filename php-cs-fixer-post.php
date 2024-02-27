<?php

/**
 * This file identify the number of S,F,I,E. in the string from stdin
 */

$stdin = fopen('php://stdin', 'r');
$stdout = fopen('php://stdout', 'w');

$errors = 0;
$dots = 0;
$f = 0;
$s = 0;
$i = 0;
$e = 0;
$valid = false;
$output = "";
while ($line = fgets($stdin)) {
    $output .= $line;
    $line = trim($line);
    $endsWithPercent = preg_match('/\d+ \/ \d+ \(\s*\d+%\s*\)$/', $line);
    if (!$endsWithPercent) {
        continue;
    }
    $valid = true;
    // Counts the number of dots,F,S,I,E
    $dots += substr_count($line, '.');
    $f += substr_count($line, 'F');
    $s += substr_count($line, 'S');
    $i += substr_count($line, 'I');
    $e += substr_count($line, 'E');
}
$errors += $i + $e;

if (!$valid) {
    fwrite($stdout, "\033[1;31mERROR: No valid input\033[0m\n");
    fwrite($stdout, $output);
    exit(1);
}

// Print a simple report
fwrite($stdout, "Fixed: $f, Skipped: $s, Invalid: $i, Error: $e\n");

// exit with error code if there are errors
if ($errors > 0) {
    fwrite($stdout, $output . "\n");
    fwrite($stdout, "\033[1;31mERRORS: $errors\033[0m\n");
    exit(1);
}
fwrite($stdout, "\033[1;32mphp-cs-fixer SUCCESS\033[0m\n");
