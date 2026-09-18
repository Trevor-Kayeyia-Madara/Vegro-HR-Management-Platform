<?php
/**
 * Copy Storage Files (Alternative to Symlink)
 * This script copies storage files instead of creating a symlink
 * Use this when symlink() is disabled on shared hosting
 */

ini_set('output_buffering', 'off');
ini_set('zlib.output_compression', false);
while (@ob_end_flush());

header('Content-Type: text/plain; charset=utf-8');
header('X-Accel-Buffering: no');

echo "=== Storage Copy Script ===\n\n";

$sourceDir = __DIR__ . '/../storage/app/public';
$targetDir = __DIR__ . '/storage';

echo "Source directory: $sourceDir\n";
echo "Target directory: $targetDir\n\n";

if (!is_dir($sourceDir)) {
    echo "Creating source directory structure...\n";
    if (!mkdir($sourceDir, 0755, true)) {
        echo "ERROR: Failed to create source directory\n";
        exit(1);
    }
    echo "Source directory created\n\n";
}

if (!is_dir($targetDir)) {
    echo "Creating target directory: $targetDir\n";
    if (!mkdir($targetDir, 0755, true)) {
        echo "ERROR: Failed to create target directory\n";
        exit(1);
    }
    echo "Target directory created\n\n";
}

function copyDirectory($source, $target) {
    echo "Copying from $source to $target\n";
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    $fileCount = 0;
    $dirCount = 0;
    
    foreach ($iterator as $item) {
        $relativePath = $iterator->getSubPathName();
        $targetPath = $target . '/' . $relativePath;
        
        if ($item->isDir()) {
            if (!is_dir($targetPath)) {
                if (!mkdir($targetPath, 0755, true)) {
                    echo "ERROR: Failed to create directory: $targetPath\n";
                    return false;
                }
                $dirCount++;
                echo "  Created directory: $relativePath\n";
            }
        } else {
            if (!copy($item->getPathname(), $targetPath)) {
                echo "ERROR: Failed to copy file: $relativePath\n";
                return false;
            }
            $fileCount++;
            echo "  Copied file: $relativePath\n";
        }
    }
    
    echo "Copied $fileCount files and $dirCount directories\n";
    return true;
}

echo "Starting copy operation...\n";
if (copyDirectory($sourceDir, $targetDir)) {
    echo "\n✓ Storage copy completed successfully!\n";
    echo "Files are now available at: public/storage/\n";
    echo "\nIMPORTANT: You can delete this script after successful deployment.\n";
} else {
    echo "\n✗ Storage copy failed.\n";
    echo "Please check file permissions and try again.\n";
    exit(1);
}
