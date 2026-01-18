<?php
session_start();
require_once '../config.php';
requireLogin();

// Handle product deletion
if (isset($_POST['delete_id'])) {
    $productId = (int)$_POST['delete_id'];
    
    // Get product images to delete
    $images = $db->fetchAll("SELECT image_path FROM product_images WHERE product_id = ?", [$productId]);
    
    // Delete product (cascade will handle related records)
    $db->query("DELETE FROM products WHERE id = ?", [$productId]);
    
    // Delete image files
    foreach ($images as $image) {
        deleteImage($image['image_path']);
    }
    
    $success = 'بەرهەمەکە بە سەرکەوتوویی سڕایەوە';
}

// Get filters
$category_filter = $_GET['category'] ?? '';
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query
$where = [];
$params = [];

if ($category_filter) {
    $where[] = "p.category_id = ?";
    $params[] = $category_filter;
}

if ($status_filter) {
    $where[] = "p.status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $where[] = "p.title LIKE ?";
    $params[] = "%$search%";
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$totalQuery = "SELECT COUNT(*) as count FROM products p $whereClause";
$total = $db->fetch($totalQuery, $params)['count'];
$totalPages = ceil($total / $limit);

// Get products
$productsQuery = "
    SELECT p.*, c.name_ku as category_name,
           (SELECT image_path FROM product_images WHERE product_id = p.id AND is_main = 1 LIMIT 1) as main_image
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    $whereClause
    ORDER BY p.created_at DESC 
    LIMIT $limit OFFSET $offset
";

$products = $db->fetchAll($productsQuery, $params);

// Get categories for filter
$categories = $db->fetchAll("SELECT * FROM categories ORDER BY name_ku");
?>

<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بەرهەمەکان - Sanvito</title>
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
            <a href="dashboard.php" class="flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100">
                <i class="fas fa-tachometer-alt ml-3"></i>
                داشبۆرد
            </a>
            <a href="products.php" class="flex items-center px-6 py-3 text-gray-700 bg-gray-200">
                <i class="fas fa-box ml-3"></i>
                بەرهەمەکان
            </a>
            <a href="categories.php" class="flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100">
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
                
                <h2 class="text-xl font-semibold text-gray-800">بەرهەمەکان</h2>
                
                <a href="product-add.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                    <i class="fas fa-plus ml-2"></i>
                    زیادکردنی بەرهەم
                </a>
            </div>
        </div>

        <!-- Content -->
        <div class="p-6">
            <?php if (isset($success)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <i class="fas fa-check-circle ml-2"></i>
                    <?= $success ?>
                </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">گەڕان</label>
                        <input type="text" 
                               name="search" 
                               value="<?= htmlspecialchars($search) ?>"
                               placeholder="گەڕان بە ناونیشان..."
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">پۆل</label>
                        <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">هەموو پۆلەکان</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>" <?= $category_filter == $category['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name_ku']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">دۆخ</label>
                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">هەموو دۆخەکان</option>
                            <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>چالاک</option>
                            <option value="inactive" <?= $status_filter === 'inactive' ? 'selected' : '' ?>>ناچالاک</option>
                            <option value="out_of_stock" <?= $status_filter === 'out_of_stock' ? 'selected' : '' ?>>تەواوبوو</option>
                        </select>
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                            <i class="fas fa-search ml-2"></i>
                            گەڕان
                        </button>
                    </div>
                </form>
            </div>

            <!-- Products Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    وێنە
                                </th>
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
                                    تایبەت
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
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($product['main_image']): ?>
                                        <img class="h-12 w-12 rounded-lg object-cover" 
                                             src="../uploads/images/<?= $product['main_image'] ?>" 
                                             alt="">
                                    <?php else: ?>
                                        <div class="h-12 w-12 rounded-lg bg-gray-300 flex items-center justify-center">
                                            <i class="fas fa-image text-gray-500"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($product['title']) ?>
                                    </div>
                                    <?php if ($product['badge']): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 mt-1">
                                            <?= htmlspecialchars($product['badge']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= htmlspecialchars($product['category_name']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?= formatPrice($product['price']) ?> $
                                    </div>
                                    <?php if (!empty($product['original_price']) && $product['original_price'] > $product['price']): ?>
                                        <div class="text-sm text-gray-500 line-through">
                                            <?= formatPrice($product['original_price']) ?> $
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $statusClasses = [
                                        'active' => 'bg-green-100 text-green-800',
                                        'inactive' => 'bg-red-100 text-red-800',
                                        'out_of_stock' => 'bg-yellow-100 text-yellow-800'
                                    ];
                                    $statusTexts = [
                                        'active' => 'چالاک',
                                        'inactive' => 'ناچالاک',
                                        'out_of_stock' => 'تەواوبوو'
                                    ];
                                    ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $statusClasses[$product['status']] ?>">
                                        <?= $statusTexts[$product['status']] ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php if ($product['featured']): ?>
                                        <i class="fas fa-star text-yellow-500"></i>
                                        <span class="mr-1">بەڵێ</span>
                                    <?php else: ?>
                                        <span class="text-gray-400">نەخێر</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('Y/m/d', strtotime($product['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center space-x-2">
                                        <a href="product-edit.php?id=<?= $product['id'] ?>" 
                                           class="text-indigo-600 hover:text-indigo-900 ml-3"
                                           title="دەستکاریکردن">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" class="inline" onsubmit="return confirm('دڵنیایت لە سڕینەوەی ئەم بەرهەمە؟')">
                                            <input type="hidden" name="delete_id" value="<?= $product['id'] ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-900" title="سڕینەوە">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (empty($products)): ?>
                <div class="text-center py-8">
                    <i class="fas fa-box text-gray-400 text-4xl mb-4"></i>
                    <p class="text-gray-500">هیچ بەرهەمێک نەدۆزرایەوە.</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6 mt-6 rounded-lg shadow">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <?php if ($page > 1): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" 
                               class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                پێشوو
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" 
                               class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                دواتر
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                نیشاندانی 
                                <span class="font-medium"><?= $offset + 1 ?></span>
                                بۆ 
                                <span class="font-medium"><?= min($offset + $limit, $total) ?></span>
                                لە 
                                <span class="font-medium"><?= $total ?></span>
                                ئەنجام
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" 
                                       class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                                       class="relative inline-flex items-center px-4 py-2 border text-sm font-medium
                                              <?= $i === $page ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" 
                                       class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                <?php endif; ?>
                            </nav>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>