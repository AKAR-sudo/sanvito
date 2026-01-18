<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: /sanvito/admin/login.php');
    exit;
}

require_once 'config.php';
requireLogin();

// Get statistics
$totalProducts = $db->fetch("SELECT COUNT(*) as count FROM products")['count'];
$activeProducts = $db->fetch("SELECT COUNT(*) as count FROM products WHERE status = 'active'")['count'];
$totalCategories = $db->fetch("SELECT COUNT(*) as count FROM categories")['count'];
$featuredProducts = $db->fetch("SELECT COUNT(*) as count FROM products WHERE featured = 1")['count'];

// Get recent products
$recentProducts = $db->fetchAll("
    SELECT p.*, c.name_ku as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.created_at DESC 
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پانێڵی بەڕێوەبردن - Sanvito</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100" x-data="{ sidebarOpen: false }">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 right-0 z-50 w-64 bg-white shadow-lg transform transition-transform duration-300" 
         :class="sidebarOpen ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'">
        
        <!-- Logo -->
        <div class="flex items-center justify-center h-16 bg-gray-900 text-white">
            <h1 class="text-xl font-bold">SANVITO</h1>
        </div>

        <!-- Navigation -->
        <nav class="mt-8">
            <a href="admin/dashboard.php" class="flex items-center px-6 py-3 text-gray-700 bg-gray-200">
                <i class="fas fa-tachometer-alt ml-3"></i>
                داشبۆرد
            </a>
            <a href="admin/products.php" class="flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100">
                <i class="fas fa-box ml-3"></i>
                بەرهەمەکان
            </a>
            <a href="admin/categories.php" class="flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100">
                <i class="fas fa-tags ml-3"></i>
                پۆلەکان
            </a>
            <a href="admin/upload.php" class="flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100">
                <i class="fas fa-upload ml-3"></i>
                بارکردنی وێنە
            </a>
            <a href="admin/settings.php" class="flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100">
                <i class="fas fa-cog ml-3"></i>
                ڕێکخستنەکان
            </a>
            <a href="admin/logout.php" class="flex items-center px-6 py-3 text-red-600 hover:bg-red-50">
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
                
                <h2 class="text-xl font-semibold text-gray-800">داشبۆرد</h2>
                
                <div class="flex items-center">
                    <span class="text-gray-600 ml-2">بەخێربێیت،</span>
                    <span class="font-medium text-gray-800"><?= $_SESSION['admin_name'] ?></span>
                </div>
            </div>
        </div>

        <!-- Dashboard Content -->
        <div class="p-6">
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Products -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <i class="fas fa-box text-xl"></i>
                        </div>
                        <div class="mr-4">
                            <p class="text-sm font-medium text-gray-600">کۆی بەرهەمەکان</p>
                            <p class="text-2xl font-bold text-gray-900"><?= $totalProducts ?></p>
                        </div>
                    </div>
                </div>

                <!-- Active Products -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <i class="fas fa-check-circle text-xl"></i>
                        </div>
                        <div class="mr-4">
                            <p class="text-sm font-medium text-gray-600">بەرهەمی چالاک</p>
                            <p class="text-2xl font-bold text-gray-900"><?= $activeProducts ?></p>
                        </div>
                    </div>
                </div>

                <!-- Categories -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                            <i class="fas fa-tags text-xl"></i>
                        </div>
                        <div class="mr-4">
                            <p class="text-sm font-medium text-gray-600">پۆلەکان</p>
                            <p class="text-2xl font-bold text-gray-900"><?= $totalCategories ?></p>
                        </div>
                    </div>
                </div>

                <!-- Featured Products -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                            <i class="fas fa-star text-xl"></i>
                        </div>
                        <div class="mr-4">
                            <p class="text-sm font-medium text-gray-600">بەرهەمی تایبەت</p>
                            <p class="text-2xl font-bold text-gray-900"><?= $featuredProducts ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Products -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">بەرهەمە دوایی</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    ناونیشان
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    پۆل
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    نرخ
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    دۆخ
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    بەروار
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    کردار
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($recentProducts as $product): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <?php if ($product['main_image']): ?>
                                            <img class="h-10 w-10 rounded-full object-cover ml-4" 
                                                 src="../uploads/images/<?= $product['main_image'] ?>" 
                                                 alt="">
                                        <?php else: ?>
                                            <div class="h-10 w-10 rounded-full bg-gray-300 ml-4"></div>
                                        <?php endif; ?>
                                        <div class="text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($product['title']) ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= htmlspecialchars($product['category_name']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= formatPrice($product['price']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                          <?= $product['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= $product['status'] === 'active' ? 'چالاک' : 'ناچالاک' ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('Y/m/d', strtotime($product['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="product-edit.php?id=<?= $product['id'] ?>" 
                                       class="text-indigo-600 hover:text-indigo-900 ml-3">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="product-delete.php?id=<?= $product['id'] ?>" 
                                       class="text-red-600 hover:text-red-900"
                                       onclick="return confirm('دڵنیایت لە سڕینەوەی ئەم بەرهەمە؟')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (empty($recentProducts)): ?>
                <div class="text-center py-8">
                    <p class="text-gray-500">هیچ بەرهەمێ نەدراوە.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>