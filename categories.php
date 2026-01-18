<?php
session_start();
require_once '../config.php';
requireLogin();

// Handle category actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $name_ku = sanitize($_POST['name_ku']);
        $description = sanitize($_POST['description']);
        $status = $_POST['status'] ?? 'active';
        
        if (!empty($name_ku)) {
            $db->query("
                INSERT INTO categories (name_ku, description, status) 
                VALUES (?, ?, ?)
            ", [$name_ku, $description, $status]);
            
            $success = "پۆلەکە بە سەرکەوتوویی زیادکرا.";
        } else {
            $error = "تکایە هەموو خانە پێویستەکان پڕ بکەرەوە.";
        }
    }
    
    if ($action === 'edit') {
        $id = (int)$_POST['id'];
        $name_ku = sanitize($_POST['name_ku']);
        $description = sanitize($_POST['description']);
        $status = $_POST['status'] ?? 'active';
        
        if (!empty($name_ku) && $id > 0) {
            $db->query("
                UPDATE categories 
                SET name_ku = ?, description = ?, status = ?, updated_at = NOW()
                WHERE id = ?
            ", [$name_ku, $description, $status, $id]);
            
            $success = "پۆلەکە بە سەرکەوتوویی نوێکرایەوە.";
        } else {
            $error = "تکایە هەموو خانە پێویستەکان پڕ بکەرەوە.";
        }
    }
}

// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Check if category has products
    $productCount = $db->fetch("SELECT COUNT(*) as count FROM products WHERE category_id = ?", [$id])['count'];
    
    if ($productCount > 0) {
        $error = "ناتوانرێت ئەم پۆلە بسڕدرێتەوە چونکە " . $productCount . " بەرهەمی تێدایە.";
    } else {
        $db->query("DELETE FROM categories WHERE id = ?", [$id]);
        $success = "پۆلەکە بە سەرکەوتوویی سڕایەوە.";
    }
}

// Get categories with product count
$categories = $db->fetchAll("
    SELECT c.*, 
           COUNT(p.id) as product_count
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id 
    GROUP BY c.id 
    ORDER BY c.created_at DESC
");

// Get category for editing
$editCategory = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editCategory = $db->fetch("SELECT * FROM categories WHERE id = ?", [(int)$_GET['edit']]);
}
?>

<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بەڕێوەبردنی پۆلەکان - Sanvito</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100" x-data="{ sidebarOpen: false, showModal: false, editMode: <?= $editCategory ? 'true' : 'false' ?> }">
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
            <a href="categories.php" class="flex items-center px-6 py-3 text-gray-700 bg-gray-200">
                <i class="fas fa-tags ml-3"></i>
                پۆلەکان
            </a>
            
            <a href="admins.php" class="flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100">
                <i class="fas fa-cog ml-3"></i>
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
                
                <h2 class="text-xl font-semibold text-gray-800">بەڕێوەبردنی پۆلەکان</h2>
                
                <button @click="showModal = true; editMode = false" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-plus ml-2"></i>
                    پۆلی نوێ
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

            <!-- Categories Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    ناوی کوردی
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    وەسف
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    ژمارەی بەرهەم
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
                            <?php foreach ($categories as $category): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($category['name_ku']) ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                                    <?= htmlspecialchars($category['description']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= $category['product_count'] ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                          <?= $category['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= $category['status'] === 'active' ? 'چالاک' : 'ناچالاک' ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('Y/m/d', strtotime($category['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="?edit=<?= $category['id'] ?>" 
                                       class="text-indigo-600 hover:text-indigo-900 ml-3">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($category['product_count'] == 0): ?>
                                    <a href="?delete=<?= $category['id'] ?>" 
                                       class="text-red-600 hover:text-red-900"
                                       onclick="return confirm('دڵنیایت لە سڕینەوەی ئەم پۆلە؟')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (empty($categories)): ?>
                <div class="text-center py-8">
                    <p class="text-gray-500">هیچ پۆلێک نەدراوە.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add/Edit Category Modal -->
    <div x-show="showModal || editMode" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
         @click.self="showModal = false; editMode = false; window.location.href = 'categories.php'">
        
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 shadow-lg rounded-md bg-white">
            <!-- Modal Header -->
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">
                    <span x-show="!editMode">زیادکردنی پۆلی نوێ</span>
                    <span x-show="editMode">گۆڕینی پۆل</span>
                </h3>
                <button @click="showModal = false; editMode = false; window.location.href = 'categories.php'" 
                        class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <form method="POST" action="">
                <input type="hidden" name="action" :value="editMode ? 'edit' : 'add'">
                <?php if ($editCategory): ?>
                <input type="hidden" name="id" value="<?= $editCategory['id'] ?>">
                <?php endif; ?>

                <div class="grid grid-cols-1 gap-4">
                    <!-- Kurdish Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                           ناوی پۆل <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name_ku" 
                               value="<?= $editCategory['name_ku'] ?? '' ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               required>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            وەسف
                        </label>
                        <textarea name="description" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?= $editCategory['description'] ?? '' ?></textarea>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            دۆخ
                        </label>
                        <select name="status" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="active" <?= ($editCategory['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>
                                چالاک
                            </option>
                            <option value="inactive" <?= ($editCategory['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>
                                ناچالاک
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex justify-end mt-6 space-x-3">
                    <button type="button" 
                            @click="showModal = false; editMode = false; window.location.href = 'categories.php'"
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
    <?php if ($editCategory): ?>
    <script>
        document.addEventListener('alpine:init', () => {
            // Auto-open edit modal
            setTimeout(() => {
                window.dispatchEvent(new CustomEvent('alpine:init'));
            }, 100);
        });
    </script>
    <?php endif; ?>
</body>
</html>