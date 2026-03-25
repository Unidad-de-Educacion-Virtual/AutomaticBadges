<?php
// This file is part of local_automatic_badges - https://moodle.org/.
//
// local_automatic_badges is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// local_automatic_badges is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with local_automatic_badges.  If not, see <https://www.gnu.org/licenses/>.

/**
 * This file is part of local_automatic_badges
 *
 * local_automatic_badges is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * local_automatic_badges is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with local_automatic_badges.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    local_automatic_badges
 * @author     Daniela Alexandra Patiño Dávila
 * @author     Cristian Julian Lamus Lamus
 * @copyright  2026 Daniela Alexandra Patiño Dávila, Cristian Julian Lamus Lamus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/**
 * Build script - creates a Moodle-compatible ZIP for local_automatic_badges.
 * Run from the plugin root: php build_zip.php
 */
// Phpcs:disable -- standalone CLI build script, not a Moodle page.

$pluginName  = 'local_automatic_badges';
$sourceDir   = __DIR__;
$outputZip   = $sourceDir . DIRECTORY_SEPARATOR . $pluginName . '.zip';

// Files and directories to exclude.
$excludeDirs = ['.git', '.vscode', '.idea', 'node_modules', 'tests', 'docs'];
$excludeFiles = [
    'build.ps1', 'build_zip.php', '.gitignore', 'debug.log', 'debug_post.txt', 'debug_rules.php', 'syntax_error.txt', 'temp.html', 'test_logic.php', 'local_automatic_badges.zip', 'GLOBAL_RULES_FEATURE.md', 'TASK_LOCAL_LIBRARIES.md', 'TECHNICAL_ANALYSIS_AWARDING.md', 'README.md', 'salida.txt', 'extract_comments.php',
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

    // Normalize to forward slashes.
    $relativePath = str_replace('\\', '/', $relativePath);

    // Skip excluded top-level dirs.
    $topLevel = explode('/', $relativePath)[0];
    if (in_array($topLevel, $excludeDirs)) {
        $skipped++;
        continue;
    }

    // Skip excluded files (by filename).
    if (in_array(basename($relativePath), $excludeFiles)) {
        $skipped++;
        continue;
    }

    // The ZIP entry path must have the plugin folder as root.
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
