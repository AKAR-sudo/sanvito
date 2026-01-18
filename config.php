<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sanvito');

// Site Configuration
define('SITE_URL', 'http://localhost/sanvito/');
define('ADMIN_URL', 'http://localhost/sanvito/admin/');
define('UPLOAD_PATH', 'uploads/');
define('IMAGES_PATH', __DIR__ . '/uploads/images/');

// Upload Settings
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Session Configuration
// ini_set('session.cookie_httponly', 1);
// ini_set('session.use_strict_mode', 1);
// ini_set('session.cookie_secure', 1);
// session_start();

// Database Connection Class
class Database {
    private $connection;
    
    public function __construct() {
        try {
            $this->connection = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function query($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
}

// Initialize Database
$db = new Database();

// Helper Functions
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect(ADMIN_URL . 'login.php');
    }
}

function formatPrice($price) {
    return number_format($price, 0) . ' د';
}

function deleteImage($imagePath) {
    $fullPath = IMAGES_PATH . $imagePath;
    if (file_exists($fullPath)) {
        return unlink($fullPath);
    }
    return false;
}

function uploadImage($file, $folder = 'products') {
    error_log('uploadImage called with: ' . print_r($file, true));
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        error_log('Upload error: ' . ($file['error'] ?? 'file not set'));
        return false;
    }
    $uploadDir = IMAGES_PATH . $folder . '/';
    // Create folder if it does not exist
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            error_log('Failed to create directory: ' . $uploadDir);
            return false;
        }
    }
    // Check if folder is writable
    if (!is_writable($uploadDir)) {
        error_log('Directory not writable: ' . $uploadDir);
        return false;
    }
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExtension, ALLOWED_EXTENSIONS)) {
        error_log('File extension not allowed: ' . $fileExtension);
        return false;
    }
    if ($file['size'] > MAX_FILE_SIZE) {
        error_log('File size too large: ' . $file['size']);
        return false;
    }
    $uniqueName = uniqid() . '_' . date('YmdHis') . '.' . $fileExtension;
    $destination = $uploadDir . $uniqueName;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        error_log('Failed to move uploaded file to: ' . $destination);
        return false;
    }
    // Return relative path from images folder
    return $folder . '/' . $uniqueName;
}

// Error Reporting (Disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Asia/Baghdad');

// Response helper for AJAX
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

// CSRF Protection
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token ?? '');
}

$title = isset($_POST['title']) ? sanitize($_POST['title']) : '';
$description = isset($_POST['description']) ? sanitize($_POST['description']) : '';
$price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
$original_price = isset($_POST['original_price']) && !empty($_POST['original_price']) ? (float)$_POST['original_price'] : null;
$category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
$badge = isset($_POST['badge']) ? sanitize($_POST['badge']) : '';
$status = isset($_POST['status']) ? $_POST['status'] : '';
$featured = isset($_POST['featured']) ? 1 : 0;
?>