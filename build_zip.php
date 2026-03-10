<?php
/**
 * Build script - creates a Moodle-compatible ZIP for local_automatic_badges.
 * Run from the plugin root: php build_zip.php
 */

$pluginName  = 'automatic_badges';
$sourceDir   = __DIR__;
$outputZip   = $sourceDir . DIRECTORY_SEPARATOR . $pluginName . '_release.zip';

// Files and directories to exclude
$excludeDirs = ['.git', '.vscode', '.idea', 'node_modules', 'tests', 'docs'];
$excludeFiles = [
    'build.ps1', 'build_zip.php',
    '.gitignore',
    'debug.log', 'debug_post.txt', 'debug_rules.php',
    'syntax_error.txt', 'temp.html', 'test_logic.php',
    'automatic_badges_release.zip',
    'GLOBAL_RULES_FEATURE.md', 'TASK_LOCAL_LIBRARIES.md', 'TECHNICAL_ANALYSIS_AWARDING.md',
    'README.md',
];

if (file_exists($outputZip)) {
    unlink($outputZip);
    echo "Removed old ZIP.\n";
}

$zip = new ZipArchive();
if ($zip->open($outputZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    die("ERROR: Cannot create ZIP file at: $outputZip\n");
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

$added = 0;
$skipped = 0;

foreach ($iterator as $file) {
    $realPath   = $file->getRealPath();
    $relativePath = substr($realPath, strlen($sourceDir) + 1);

    // Normalize to forward slashes
    $relativePath = str_replace('\\', '/', $relativePath);

    // Skip excluded top-level dirs
    $topLevel = explode('/', $relativePath)[0];
    if (in_array($topLevel, $excludeDirs)) {
        $skipped++;
        continue;
    }

    // Skip excluded files (by filename)
    if (in_array(basename($relativePath), $excludeFiles)) {
        $skipped++;
        continue;
    }

    // The ZIP entry path must have the plugin folder as root
    $zipEntryPath = $pluginName . '/' . $relativePath;

    if ($file->isDir()) {
        $zip->addEmptyDir($zipEntryPath);
    } else {
        $zip->addFile($realPath, $zipEntryPath);
        $added++;
    }
}

$zip->close();

echo "======================================\n";
echo "  Plugin: $pluginName\n";
echo "  Files added : $added\n";
echo "  Files skipped: $skipped\n";
echo "  ZIP created at: $outputZip\n";
echo "======================================\n";
