<?php
// Directory permissions check - فایلێکی جیا دروستبکە بۆ ئەمە
// File name: check_permissions.php

echo "<h2>Directory Permissions Check</h2>";

$directories = [
    __DIR__ . '/uploads/',
    __DIR__ . '/uploads/images/',
    __DIR__ . '/uploads/images/products/',
];

foreach ($directories as $dir) {
    echo "<h3>Directory: $dir</h3>";
    echo "Exists: " . (file_exists($dir) ? '✓ YES' : '✗ NO') . "<br>";
    echo "Is Directory: " . (is_dir($dir) ? '✓ YES' : '✗ NO') . "<br>";
    echo "Readable: " . (is_readable($dir) ? '✓ YES' : '✗ NO') . "<br>";
    echo "Writable: " . (is_writable($dir) ? '✓ YES' : '✗ NO') . "<br>";
    
    if (file_exists($dir)) {
        $perms = fileperms($dir);
        $octal = sprintf('%o', $perms);
        echo "Permissions: $octal<br>";
        
        // Try to create a test file
        $testFile = $dir . 'test_' . time() . '.txt';
        if (file_put_contents($testFile, 'test')) {
            echo "Write Test: ✓ SUCCESS<br>";
            unlink($testFile); // Clean up
        } else {
            echo "Write Test: ✗ FAILED<br>";
        }
    }
    
    echo "<hr>";
}

// Test file upload constants
echo "<h3>Upload Constants</h3>";
echo "ALLOWED_EXTENSIONS: " . print_r(ALLOWED_EXTENSIONS, true) . "<br>";
echo "MAX_FILE_SIZE: " . MAX_FILE_SIZE . " bytes (" . (MAX_FILE_SIZE / 1024 / 1024) . " MB)<br>";

// Test PHP upload settings
echo "<h3>PHP Upload Settings</h3>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "<br>";
echo "file_uploads: " . (ini_get('file_uploads') ? 'ON' : 'OFF') . "<br>";

// Test if we can create the directories
echo "<h3>Directory Creation Test</h3>";
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "Created: $dir ✓<br>";
        } else {
            echo "Failed to create: $dir ✗<br>";
        }
    }
}
?>