<?php
session_start();
require_once '../config.php';
requireLogin();

// Check if current user is admin (only admins can manage other admins)
$currentUser = $db->fetch("SELECT role FROM admin_users WHERE id = ?", [$_SESSION['admin_id']]);
if ($currentUser['role'] !== 'admin') {
    redirect('dashboard.php');
}

// Handle admin actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $username = sanitize($_POST['username']);
        $password = $_POST['password'];
        $email = sanitize($_POST['email']);
        $full_name = sanitize($_POST['full_name']);
        $role = $_POST['role'] ?? 'editor';
        
        if (!empty($username) && !empty($password) && !empty($email) && !empty($full_name)) {
            // Check if username already exists
            $existingUser = $db->fetch("SELECT id FROM admin_users WHERE username = ?", [$username]);
            
            if ($existingUser) {
                $error = "ئەم ناوی بەکارهێنەرە پێشتر بەکارهاتووە.";
            } else {
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                $db->query("
                    INSERT INTO admin_users (username, password, email, full_name, role) 
                    VALUES (?, ?, ?, ?, ?)
                ", [$username, $hashedPassword, $email, $full_name, $role]);
                
                $success = "ئەدمینی نوێ بە سەرکەوتوویی زیادکرا.";
            }
        } else {
            $error = "تکایە هەموو خانە پێویستەکان پڕ بکەرەوە.";
        }
    }
    
    if ($action === 'edit') {
        $id = (int)$_POST['id'];
        $username = sanitize($_POST['username']);
        $email = sanitize($_POST['email']);
        $full_name = sanitize($_POST['full_name']);
        $role = $_POST['role'] ?? 'editor';
        $password = $_POST['password'];
        
        if (!empty($username) && !empty($email) && !empty($full_name) && $id > 0) {
            // Prevent editing own role
            if ($id == $_SESSION['admin_id'] && $role !== $currentUser['role']) {
                $error = "ناتوانیت ڕۆڵی خۆت بگۆڕیت.";
            } else {
                // Check if username already exists (exclude current user)
                $existingUser = $db->fetch("SELECT id FROM admin_users WHERE username = ? AND id != ?", [$username, $id]);
                
                if ($existingUser) {
                    $error = "ئەم ناوی بەکارهێنەرە پێشتر بەکارهاتووە.";
                } else {
                    // Update user
                    if (!empty($password)) {
                        // Update with new password
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $db->query("
                            UPDATE admin_users 
                            SET username = ?, password = ?, email = ?, full_name = ?, role = ?, updated_at = NOW()
                            WHERE id = ?
                        ", [$username, $hashedPassword, $email, $full_name, $role, $id]);
                    } else {
                        // Update without changing password
                        $db->query("
                            UPDATE admin_users 
                            SET username = ?, email = ?, full_name = ?, role = ?, updated_at = NOW()
                            WHERE id = ?
                        ", [$username, $email, $full_name, $role, $id]);
                    }
                    
                    $success = "زانیاری ئەدمین بە سەرکەوتوویی نوێکرایەوە.";
                }
            }
        } else {
            $error = "تکایە هەموو خانە پێویستەکان پڕ بکەرەوە.";
        }
    }
    
    if ($action === 'toggle_status') {
        $id = (int)$_POST['id'];
        $status = $_POST['status'];
        
        if ($id > 0 && $id != $_SESSION['admin_id']) {
            $db->query("
                UPDATE admin_users 
                SET status = ?, updated_at = NOW()
                WHERE id = ?
            ", [$status, $id]);
            
            $success = "دۆخی ئەدمین گۆڕدرا.";
        } else {
            $error = "ناتوانیت دۆخی خۆت بگۆڕیت.";
        }
    }
}

// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    if ($id == $_SESSION['admin_id']) {
        $error = "ناتوانیت هەژماری خۆت بسڕیتەوە.";
    } else {
        $db->query("DELETE FROM admin_users WHERE id = ?", [$id]);
        $success = "ئەدمین بە سەرکەوتوویی سڕایەوە.";
    }
}

// Get all admins
$admins = $db->fetchAll("
    SELECT * FROM admin_users 
    ORDER BY created_at DESC
");

// Get admin for editing
$editAdmin = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editAdmin = $db->fetch("SELECT * FROM admin_users WHERE id = ?", [(int)$_GET['edit']]);
}
?>

<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بەڕێوەبردنی ئەدمینەکان - Sanvito</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100" x-data="{ sidebarOpen: false, showModal: false, editMode: <?= $editAdmin ? 'true' : 'false' ?> }">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 right-0 z-50 w-64 bg-white shadow-lg transform transition-transform duration-300" 
         :class="sidebarOpen ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'">
        
        <!-- Logo -->
        <div class="flex items-center justify-center h-16 bg-gray-900 text-white">
            <h1 class="text-xl font-bold">SANVITO</h1>
        </div>

        <!-- Navigation -->
        <nav class="mt-8">
            <a href="dashboard.php" class="flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100">
                <i class="fas fa-tachometer-alt ml-3"></i>
                داشبۆرد
            </a>
            <a href="products.php" class="flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100">
                <i class="fas fa-box ml-3"></i>
                بەرهەمەکان
            </a>
            <a href="categories.php" class="flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100">
                <i class="fas fa-tags ml-3"></i>
                پۆلەکان
            </a>
            <a href="admins.php" class="flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100">
                <i class="fas fa-users ml-3"></i>
                ڕێکخستنەکان
            </a>
           
            <a href="logout.php" class="flex items-center px-6 py-3 text-red-600 hover:bg-red-50">
                <i class="fas fa-sign-out-alt ml-3"></i>
                چوونە دەرەوە
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="lg:mr-64">
        <!-- Top Bar -->
        <div class="bg-white shadow-sm border-b border-gray-200">
            <div class="flex items-center justify-between px-6 py-4">
                <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-gray-600">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                
                <h2 class="text-xl font-semibold text-gray-800">بەڕێوەبردنی ئەدمینەکان</h2>
                
                <button @click="showModal = true; editMode = false" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-plus ml-2"></i>
                    ئەدمینی نوێ
                </button>
            </div>
        </div>

        <!-- Content -->
        <div class="p-6">
            <!-- Messages -->
            <?php if (isset($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?= $success ?>
            </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?= $error ?>
            </div>
            <?php endif; ?>

            <!-- Admins Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    ناوی بەکارهێنەر
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    ناوی تەواو
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    ئیمەیڵ
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    ڕۆڵ
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    دۆخ
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    بەروار
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    کردارەکان
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($admins as $admin): ?>
                            <tr class="<?= $admin['id'] == $_SESSION['admin_id'] ? 'bg-blue-50' : '' ?>">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                                <i class="fas fa-user text-gray-600"></i>
                                            </div>
                                        </div>
                                        <div class="mr-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars($admin['username']) ?>
                                                <?php if ($admin['id'] == $_SESSION['admin_id']): ?>
                                                <span class="text-xs text-blue-600">(تۆ)</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($admin['full_name']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= htmlspecialchars($admin['email']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                          <?= $admin['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800' ?>">
                                        <?= $admin['role'] === 'admin' ? 'سەرپەرشتیار' : 'دەستکاری' ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                          <?= ($admin['status'] ?? 'active') === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= ($admin['status'] ?? 'active') === 'active' ? 'چالاک' : 'ناچالاک' ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('Y/m/d', strtotime($admin['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="?edit=<?= $admin['id'] ?>" 
                                       class="text-indigo-600 hover:text-indigo-900 ml-3">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                                    <!-- Toggle Status -->
                                    <form method="POST" class="inline-block ml-3">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="id" value="<?= $admin['id'] ?>">
                                        <input type="hidden" name="status" value="<?= ($admin['status'] ?? 'active') === 'active' ? 'inactive' : 'active' ?>">
                                        <button type="submit" 
                                                class="<?= ($admin['status'] ?? 'active') === 'active' ? 'text-orange-600 hover:text-orange-900' : 'text-green-600 hover:text-green-900' ?>"
                                                onclick="return confirm('دڵنیایت لە گۆڕینی دۆخی ئەم ئەدمینە؟')">
                                            <i class="fas fa-<?= ($admin['status'] ?? 'active') === 'active' ? 'pause' : 'play' ?>"></i>
                                        </button>
                                    </form>
                                    
                                    <a href="?delete=<?= $admin['id'] ?>" 
                                       class="text-red-600 hover:text-red-900 ml-3"
                                       onclick="return confirm('دڵنیایت لە سڕینەوەی ئەم ئەدمینە؟')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (empty($admins)): ?>
                <div class="text-center py-8">
                    <p class="text-gray-500">هیچ ئەدمینێک نەدراوە.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add/Edit Admin Modal -->
    <div x-show="showModal || editMode" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
         @click.self="showModal = false; editMode = false; window.location.href = 'admins.php'">
        
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 shadow-lg rounded-md bg-white">
            <!-- Modal Header -->
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">
                    <span x-show="!editMode">زیادکردنی ئەدمینی نوێ</span>
                    <span x-show="editMode">گۆڕینی ئەدمین</span>
                </h3>
                <button @click="showModal = false; editMode = false; window.location.href = 'admins.php'" 
                        class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <form method="POST" action="">
                <input type="hidden" name="action" :value="editMode ? 'edit' : 'add'">
                <?php if ($editAdmin): ?>
                <input type="hidden" name="id" value="<?= $editAdmin['id'] ?>">
                <?php endif; ?>

                <div class="grid grid-cols-1 gap-4">
                    <!-- Username -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            ناوی بەکارهێنەر <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="username" 
                               value="<?= $editAdmin['username'] ?? '' ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               required>
                    </div>

                    <!-- Full Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            ناوی تەواو <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="full_name" 
                               value="<?= $editAdmin['full_name'] ?? '' ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               required>
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            ئیمەیڵ <span class="text-red-500">*</span>
                        </label>
                        <input type="email" name="email" 
                               value="<?= $editAdmin['email'] ?? '' ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               required>
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            وشەی نهێنی 
                            <span x-show="!editMode" class="text-red-500">*</span>
                            <span x-show="editMode" class="text-sm text-gray-500">(بۆ گۆڕین تەنها)</span>
                        </label>
                        <input type="password" name="password" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               :required="!editMode">
                    </div>

                    <!-- Role -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            ڕۆڵ
                        </label>
                        <select name="role" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="editor" <?= ($editAdmin['role'] ?? 'editor') === 'editor' ? 'selected' : '' ?>>
                                دەستکاری (Editor)
                            </option>
                            <option value="admin" <?= ($editAdmin['role'] ?? '') === 'admin' ? 'selected' : '' ?>>
                                سەرپەرشتیار (Admin)
                            </option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">
                            سەرپەرشتیار: دەستگەیشتنی تەواو | دەستکاری: تەنها بەرهەم و پۆلەکان
                        </p>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex justify-end mt-6 space-x-3">
                    <button type="button" 
                            @click="showModal = false; editMode = false; window.location.href = 'admins.php'"
                            class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 ml-3">
                        پاشگەزبوونەوە
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        <span x-show="!editMode">زیادکردن</span>
                        <span x-show="editMode">نوێکردنەوە</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Auto-open edit modal if edit parameter exists -->
    <?php if ($editAdmin): ?>
    <script>
        document.addEventListener('alpine:init', () => {
            setTimeout(() => {
                window.dispatchEvent(new CustomEvent('alpine:init'));
            }, 100);
        });
    </script>
    <?php endif; ?>
</body>
</html>