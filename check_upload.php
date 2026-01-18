<?php
// فایلی پشکنین: check_upload.php
// ئەم فایلە لە فۆڵدەری admin دابنێ و بیکەرەوە

echo "<h2>پشکنینی ڕێکخستنی PHP بۆ Upload</h2>";

echo "<h3>Upload Settings:</h3>";
echo "file_uploads: " . (ini_get('file_uploads') ? 'On' : 'Off') . "<br>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "<br>";
echo "max_execution_time: " . ini_get('max_execution_time') . "<br>";
echo "memory_limit: " . ini_get('memory_limit') . "<br>";

echo "<h3>Upload Directory Check:</h3>";
$uploadDir = __DIR__ . '/../uploads/images/products/';
echo "Upload directory: " . $uploadDir . "<br>";
echo "Directory exists: " . (file_exists($uploadDir) ? 'Yes' : 'No') . "<br>";
echo "Directory writable: " . (is_writable($uploadDir) ? 'Yes' : 'No') . "<br>";

if (!file_exists($uploadDir)) {
    echo "Creating directory...<br>";
    if (mkdir($uploadDir, 0755, true)) {
        echo "Directory created successfully<br>";
    } else {
        echo "Failed to create directory<br>";
    }
}

echo "<h3>Test Upload Form:</h3>";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['test_image'])) {
    echo "<h4>Upload Test Results:</h4>";
    echo "FILES array: <pre>" . print_r($_FILES, true) . "</pre>";
    
    if ($_FILES['test_image']['error'] === UPLOAD_ERR_OK) {
        $testFile = $_FILES['test_image'];
        $fileName = 'test_' . date('YmdHis') . '_' . $testFile['name'];
        $uploadPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($testFile['tmp_name'], $uploadPath)) {
            echo "Test upload successful!<br>";
            echo "File saved to: " . $uploadPath . "<br>";
            echo "File size: " . filesize($uploadPath) . " bytes<br>";
            
            // Clean up test file
            unlink($uploadPath);
            echo "Test file cleaned up.<br>";
        } else {
            echo "Test upload failed!<br>";
            echo "Error: " . print_r(error_get_last(), true) . "<br>";
        }
    } else {
        echo "Upload error code: " . $_FILES['test_image']['error'] . "<br>";
    }
}
?>

<form method="POST" enctype="multipart/form-data">
    <input type="file" name="test_image" accept="image/*" required>
    <button type="submit">Test Upload</button>
</form>