<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->name('*.php')
    ->ignoreVCSIgnored(true)
    ->exclude('.git/')
    ->exclude('.tools/')
    ->exclude('.vscode/')
    ->exclude('node_modules/')
    ->exclude('public/')
    ->exclude('vendor/')
    ->notPath(['artisan'])
;
return (new PhpCsFixer\Config())
    // available rule sets and rules: https://github.com/FriendsOfPHP/PHP-CS-Fixer/tree/master/doc
    ->setRules([
        '@PSR12' => true,
    ])
    ->setRiskyAllowed(true)
    ->setCacheFile(__DIR__ . '/.tools/.php-cs-fixer.cache')
    ->setIndent("    ")
    ->setLineEnding("\n")
    ->setFinder($finder)
;
