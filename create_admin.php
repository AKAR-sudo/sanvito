<?php

require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $email = $_POST['email'];
    $full_name = $_POST['full_name'];
    $role = 'admin';
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $pdo = new PDO('mysql:host=localhost;dbname=sanvito;charset=utf8', 'root', '');
    $stmt = $pdo->prepare("INSERT INTO admin_users (username, password, email, full_name, role) VALUES (?, ?, ?, ?, ?)");
    try {
        if ($stmt->execute([$username, $hash, $email, $full_name, $role])) {
            echo "<p style='color:green'>بەسەرکەوتوویی زیادکرا!</p>";
        } else {
            echo "<p style='color:red'>هەڵە لە زیادکردن!</p>";
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo "<p style='color:red'>ناوی بەکارهێنەر پێشتر بوونی هەیە!</p>";
        } else {
            echo "<p style='color:red'>هەڵە: " . $e->getMessage() . "</p>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ku">
<head>
    <meta charset="UTF-8">
    <title>زیادکردنی بەکارهێنەری نوێ</title>
</head>
<body>
    <h2>زیادکردنی بەکارهێنەری بەڕێوەبەر</h2>
    <form method="post">
        <input type="text" name="username" placeholder="ناوی بەکارهێنەر" required>
        <input type="password" name="password" placeholder="وشەی نهێنی" required>
        <input type="text" name="full_name" placeholder="ناوی تەواو" required>
        <input type="email" name="email" placeholder="ئیمەیل" required>
        <button type="submit">زیادکردن</button>
    </form>
</body>
</html>