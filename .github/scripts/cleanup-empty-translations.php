#!/usr/bin/env php
<?php

/**
 * This script scans your monorepo for PHP translation files and removes 
 * empty-string keys. If the resulting array is empty, the file is deleted.
 *
 * Adjust paths if your directory structure differs.
 */

// 1. Use a glob to gather all translation PHP files in all packages:
$pattern = __DIR__ . '/../../packages/*/resources/lang/*/*.php';
$phpFiles = glob($pattern);

foreach ($phpFiles as $phpFilePath) {
    cleanupPhpTranslationFile($phpFilePath);
}

/**
 * Loads the translation file as an array, removes empty-string values, 
 * and deletes the file if the array ends up empty.
 */
function cleanupPhpTranslationFile(string $filePath): void
{
    // Attempt to load the file's return array:
    $translations = (static function ($file) {
        return include $file;
    })($filePath);

    // If the file doesn't return an array, skip it
    if (! is_array($translations)) {
        return;
    }

    // Filter out keys whose value is exactly '' (empty string)
    $filtered = array_filter($translations, fn($value) => $value !== '');

    // If there's nothing left, delete the file
    if (empty($filtered)) {
        echo "Deleting completely empty translation file: $filePath\n";
        unlink($filePath);
        return;
    }

    // If changes were made (some keys removed), rewrite the file
    if ($filtered !== $translations) {
        rewritePhpTranslationFile($filePath, $filtered);
    }
}

/**
 * Overwrites the PHP file with a cleaned translation array.
 */
function rewritePhpTranslationFile(string $filePath, array $translations): void
{
    $exported = var_export($translations, true);
    $phpFileContent = "<?php\n\nreturn {$exported};\n";
    file_put_contents($filePath, $phpFileContent);
    echo "Cleaned empty translations in: $filePath\n";
}
