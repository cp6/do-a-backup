<?php

// platform_check.php @generated by Composer

$issues = array();

$missingExtensions = array();
extension_loaded('ftp') || $missingExtensions[] = 'ftp';
extension_loaded('pdo') || $missingExtensions[] = 'pdo';
extension_loaded('zip') || $missingExtensions[] = 'zip';
extension_loaded('zlib') || $missingExtensions[] = 'zlib';

if ($missingExtensions) {
    $issues[] = 'Your Composer dependencies require the following PHP extensions to be installed: ' . implode(', ', $missingExtensions);
}

if ($issues) {
    echo 'Composer detected issues in your platform:' . "\n\n" . implode("\n", $issues);
    exit(104);
}