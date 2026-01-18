<?php
session_start();
require_once '../config.php';
requireLogin();

// Get product ID
$productId = (int)($_GET['id'] ?? 0);
if (!$productId) {
    redirect('products.php');
}

$errors = [];
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $original_price = !empty($_POST['original_price']) ? (float)$_POST['original_price'] : null;
    $category_id = (int)($_POST['category_id'] ?? 0);
    $badge = sanitize($_POST['badge'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $featured = isset($_POST['featured']) ? 1 : 0;
    
    // Validation
    if (empty($title)) $errors[] = 'ناونیشان پێویستە';
    if (empty($description)) $errors[] = 'وەسف پێویستە';
    if ($price <= 0) $errors[] = 'نرخ دەبێت لە سفر زیاتر بێت';
    if (!$category_id) $errors[] = 'پۆل پێویستە';
    if (!in_array($status, ['active', 'inactive', 'out_of_stock'])) $errors[] = 'دۆخی نادرووست';
    
    if (empty($errors)) {
        try {
            // Update product
            $sql = "UPDATE products SET 
                    title = ?, description = ?, price = ?, original_price = ?, 
                    category_id = ?, badge = ?, status = ?, featured = ?
                    WHERE id = ?";
            
            $db->query($sql, [
                $title, $description, $price, $original_price, 
                $category_id, $badge, $status, $featured, $productId
            ]);
            
            // Handle main image upload
            if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
                // Delete old main image
                $oldMainImage = $db->fetch("SELECT main_image FROM products WHERE id = ?", [$productId]);
                if ($oldMainImage['main_image']) {
                    deleteImage($oldMainImage['main_image']);
                }
                
                $mainImagePath = uploadImage($_FILES['main_image'], 'products');
                if ($mainImagePath) {
                    $db->query("UPDATE products SET main_image = ? WHERE id = ?", [$mainImagePath, $productId]);
                    
                    // Also update or insert into product_images table
                    $existing = $db->fetch("SELECT id FROM product_images WHERE product_id = ? AND is_main = 1", [$productId]);
                    if ($existing) {
                        $db->query("UPDATE product_images SET image_path = ? WHERE id = ?", [$mainImagePath, $existing['id']]);
                    } else {
                        $db->query("INSERT INTO product_images (product_id, image_path, is_main) VALUES (?, ?, 1)", [$productId, $mainImagePath]);
                    }
                }
            }
            
            // Handle additional images
            if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
                foreach ($_FILES['images']['name'] as $key => $name) {
                    if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                        $file = [
                            'name' => $_FILES['images']['name'][$key],
                            'type' => $_FILES['images']['type'][$key],
                            'tmp_name' => $_FILES['images']['tmp_name'][$key],
                            'error' => $_FILES['images']['error'][$key],
                            'size' => $_FILES['images']['size'][$key]
                        ];
                        
                        $imagePath = uploadImage($file, 'products');
                        if ($imagePath) {
                            $db->query("INSERT INTO product_images (product_id, image_path, is_main) VALUES (?, ?, 0)", [$productId, $imagePath]);
                        }
                    }
                }
            }
            
            // Handle colors - First delete existing colors
            $existingColors = $db->fetchAll("SELECT image_path FROM product_colors WHERE product_id = ?", [$productId]);
            foreach ($existingColors as $color) {
                if ($color['image_path']) {
                    deleteImage($color['image_path']);
                }
            }
            $db->query("DELETE FROM product_colors WHERE product_id = ?", [$productId]);
            
            // Add new colors
           if (isset($_POST['colors']) && is_array($_POST['colors'])) {
    
    // Get existing colors
    $existingColors = $db->fetchAll("SELECT id, color_name, color_value, image_path FROM product_colors WHERE product_id = ? ORDER BY id", [$productId]);
    $existingColorIds = array_column($existingColors, 'id');
    
    $processedColorIds = [];
    
    foreach ($_POST['colors'] as $index => $colorData) {
        $colorName = sanitize($colorData['name'] ?? '');
        $colorValue = sanitize($colorData['value'] ?? '');
        
        if (!empty($colorName) && !empty($colorValue)) {
            $colorImagePath = null;
            $existingColorId = isset($existingColors[$index]) ? $existingColors[$index]['id'] : null;
            
            // Handle color image upload
            if (isset($_FILES['color_images']) && 
                isset($_FILES['color_images']['name'][$index]) && 
                $_FILES['color_images']['error'][$index] === UPLOAD_ERR_OK) {
                
                $file = [
                    'name' => $_FILES['color_images']['name'][$index],
                    'type' => $_FILES['color_images']['type'][$index],
                    'tmp_name' => $_FILES['color_images']['tmp_name'][$index],
                    'error' => $_FILES['color_images']['error'][$index],
                    'size' => $_FILES['color_images']['size'][$index]
                ];
                
                $colorImagePath = uploadImage($file, 'colors');
                
                // Delete old image if new one uploaded successfully
                if ($colorImagePath && $existingColorId && isset($existingColors[$index]['image_path'])) {
                    deleteImage($existingColors[$index]['image_path']);
                }
            } else {
                // Keep existing image if no new image uploaded
                if ($existingColorId && isset($existingColors[$index]['image_path'])) {
                    $colorImagePath = $existingColors[$index]['image_path'];
                }
            }
            
            if ($existingColorId) {
                // Update existing color
                $db->query("UPDATE product_colors SET color_name = ?, color_value = ?, image_path = ? WHERE id = ?", 
                          [$colorName, $colorValue, $colorImagePath, $existingColorId]);
                $processedColorIds[] = $existingColorId;
            } else {
                // Insert new color
                $db->query("INSERT INTO product_colors (product_id, color_name, color_value, image_path) VALUES (?, ?, ?, ?)", 
                          [$productId, $colorName, $colorValue, $colorImagePath]);
            }
        }
    }
    
    // Delete colors that were removed from the form
    $colorsToDelete = array_diff($existingColorIds, $processedColorIds);
    if (!empty($colorsToDelete)) {
        foreach ($colorsToDelete as $colorId) {
            // Get image path before deleting
            $colorToDelete = $db->fetch("SELECT image_path FROM product_colors WHERE id = ?", [$colorId]);
            if ($colorToDelete && $colorToDelete['image_path']) {
                deleteImage($colorToDelete['image_path']);
            }
            // Delete the color record
            $db->query("DELETE FROM product_colors WHERE id = ?", [$colorId]);
        }
    }
} else {
    // If no colors in POST, delete all existing colors
    $existingColors = $db->fetchAll("SELECT image_path FROM product_colors WHERE product_id = ?", [$productId]);
    foreach ($existingColors as $color) {
        if ($color['image_path']) {
            deleteImage($color['image_path']);
        }
    }
    $db->query("DELETE FROM product_colors WHERE product_id = ?", [$productId]);
}

// Handle sizes - Same smart approach
if (isset($_POST['sizes']) && is_array($_POST['sizes'])) {
    
    // Get existing sizes
    $existingSizes = $db->fetchAll("SELECT id, size_name, stock_quantity FROM product_sizes WHERE product_id = ? ORDER BY id", [$productId]);
    $existingSizeIds = array_column($existingSizes, 'id');
    
    $processedSizeIds = [];
    
    foreach ($_POST['sizes'] as $index => $sizeData) {
        $sizeName = sanitize($sizeData['name'] ?? '');
        $stockQuantity = (int)($sizeData['stock'] ?? 0);
        
        if (!empty($sizeName)) {
            $existingSizeId = isset($existingSizes[$index]) ? $existingSizes[$index]['id'] : null;
            
            if ($existingSizeId) {
                // Update existing size
                $db->query("UPDATE product_sizes SET size_name = ?, stock_quantity = ? WHERE id = ?", 
                          [$sizeName, $stockQuantity, $existingSizeId]);
                $processedSizeIds[] = $existingSizeId;
            } else {
                // Insert new size
                $db->query("INSERT INTO product_sizes (product_id, size_name, stock_quantity) VALUES (?, ?, ?)", 
                          [$productId, $sizeName, $stockQuantity]);
            }
        }
    }
    
    // Delete sizes that were removed from the form
    $sizesToDelete = array_diff($existingSizeIds, $processedSizeIds);
    if (!empty($sizesToDelete)) {
        foreach ($sizesToDelete as $sizeId) {
            $db->query("DELETE FROM product_sizes WHERE id = ?", [$sizeId]);
        }
    }
} else {
    // If no sizes in POST, delete all existing sizes
    $db->query("DELETE FROM product_sizes WHERE product_id = ?", [$productId]);
}






























            
            // Handle sizes - First delete existing sizes
            $db->query("DELETE FROM product_sizes WHERE product_id = ?", [$productId]);
            
            // Add new sizes
            if (isset($_POST['sizes']) && is_array($_POST['sizes'])) {
                foreach ($_POST['sizes'] as $sizeData) {
                    $sizeName = sanitize($sizeData['name'] ?? '');
                    $stockQuantity = (int)($sizeData['stock'] ?? 0);
                    
                    if (!empty($sizeName)) {
                        $db->query("INSERT INTO product_sizes (product_id, size_name, stock_quantity) VALUES (?, ?, ?)", 
                                  [$productId, $sizeName, $stockQuantity]);
                    }
                }
            }
            
            $success = 'بەرهەمەکە بە سەرکەوتوویی نوێکرایەوە';
            
        } catch (Exception $e) {
            $errors[] = 'هەڵەیەک ڕوویدا: ' . $e->getMessage();
        }
    }
}

// Get product data
$product = $db->fetch("SELECT * FROM products WHERE id = ?", [$productId]);
if (!$product) {
    redirect('products.php');
}

// Get categories
$categories = $db->fetchAll("SELECT * FROM categories ORDER BY name_ku");

// Get product images
$productImages = $db->fetchAll("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_main DESC, sort_order", [$productId]);

// Get product colors
$productColors = $db->fetchAll("SELECT * FROM product_colors WHERE product_id = ? ORDER BY id", [$productId]);

// Get product sizes
$productSizes = $db->fetchAll("SELECT * FROM product_sizes WHERE product_id = ? ORDER BY id", [$productId]);
?>

<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دەستکاریکردنی بەرهەم - Sanvito</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100" x-data="productForm()">
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
                
                <h2 class="text-xl font-semibold text-gray-800">دەستکاریکردنی بەرهەم</h2>
                
                <a href="products.php" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
                    <i class="fas fa-arrow-right ml-2"></i>
                    گەڕانەوە بۆ لیست
                </a>
            </div>
        </div>

        <!-- Content -->
        <div class="p-6">
            <!-- Success Message -->
            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <i class="fas fa-check-circle ml-2"></i>
                    <?= $success ?>
                </div>
            <?php endif; ?>

            <!-- Error Messages -->
            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <ul class="list-disc list-inside">
                        <?php foreach ($errors as $error): ?>
                            <li><?= $error ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <!-- Basic Information -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">زانیاری سەرەکی</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">ناونیشان *</label>
                            <input type="text" 
                                   name="title" 
                                   value="<?= htmlspecialchars($product['title']) ?>"
                                   required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">پۆل *</label>
                            <select name="category_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">پۆلێک هەڵبژێرە</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>" <?= $product['category_id'] == $category['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category['name_ku']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">نرخ *</label>
                            <input type="number" 
                                   name="price" 
                                   value="<?= $product['price'] ?>"
                                   step="0.01" 
                                   min="0" 
                                   required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">نرخی پێشوو</label>
                            <input type="number" 
                                   name="original_price" 
                                   value="<?= $product['original_price'] ?>"
                                   step="0.01" 
                                   min="0"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">نیشانە</label>
                            <input type="text" 
                                   name="badge" 
                                   value="<?= htmlspecialchars($product['badge'] ?? '') ?>"
                                   placeholder="نوێ، پیشکەش، هتد..."
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">دۆخ</label>
                            <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="active" <?= $product['status'] === 'active' ? 'selected' : '' ?>>چالاک</option>
                                <option value="inactive" <?= $product['status'] === 'inactive' ? 'selected' : '' ?>>ناچالاک</option>
                                <option value="out_of_stock" <?= $product['status'] === 'out_of_stock' ? 'selected' : '' ?>>تەواوبوو</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">وەسف *</label>
                        <textarea name="description" 
                                  rows="4" 
                                  required
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($product['description']) ?></textarea>
                    </div>
                    
                    <div class="mt-6">
                        <label class="flex items-center">
                            <input type="checkbox" 
                                   name="featured" 
                                   value="1" 
                                   <?= $product['featured'] ? 'checked' : '' ?>
                                   class="ml-2 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <span class="text-sm font-medium text-gray-700">بەرهەمی تایبەت</span>
                        </label>
                    </div>
                </div>

                <!-- Main Image -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">وێنەی سەرەکی</h3>
                    
                    <?php if ($product['main_image']): ?>
                        <div class="mb-4">
                            <img src="../uploads/images/<?= $product['main_image'] ?>" 
                                 alt="Current main image" 
                                 class="w-32 h-32 object-cover rounded-lg border">
                            <p class="text-sm text-gray-600 mt-2">وێنەی ئێستا</p>
                        </div>
                    <?php endif; ?>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">وێنەی نوێ</label>
                        <input type="file" 
                               name="main_image" 
                               accept="image/*"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-sm text-gray-500 mt-1">فارماتی پەسەندکراو: JPG، PNG، GIF، WEBP - حەدی ئەکبەر: 5MB</p>
                    </div>
                </div>

                <!-- Additional Images -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">وێنەکانی زیاتر</h3>
                    
                    <!-- Current Images -->
                    <?php if (!empty($productImages)): ?>
                        <div class="mb-4">
                            <h4 class="text-md font-medium text-gray-700 mb-2">وێنەکانی ئێستا</h4>
                            <div class="grid grid-cols-4 gap-4">
                                <?php foreach ($productImages as $image): ?>
                                    <?php if (!$image['is_main']): ?>
                                        <div class="relative">
                                            <img src="../uploads/images/<?= $image['image_path'] ?>" 
                                                 alt="Product image" 
                                                 class="w-full h-24 object-cover rounded-lg border">
                                            <button type="button" 
                                                    onclick="deleteProductImage(<?= $image['id'] ?>)"
                                                    class="absolute top-1 left-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">وێنەی نوێ زیادبکە</label>
                        <input type="file" 
                               name="images[]" 
                               accept="image/*" 
                               multiple
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-sm text-gray-500 mt-1">دەتوانیت چەندین وێنە هەڵبژێریت</p>
                    </div>
                </div>

                <!-- Colors -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">ڕەنگەکان</h3>
                        <button type="button" 
                                @click="addColor()" 
                                class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700">
                            <i class="fas fa-plus ml-1"></i>
                            زیادکردنی ڕەنگ
                        </button>
                    </div>
                    
                    <div id="colors-container" class="space-y-4">
                        <?php foreach ($productColors as $index => $color): ?>
                            <div class="color-item border border-gray-300 rounded-lg p-4">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">ناوی ڕەنگ</label>
                                        <input type="text" 
                                               name="colors[<?= $index ?>][name]" 
                                               value="<?= htmlspecialchars($color['color_name']) ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">کۆدی ڕەنگ</label>
                                        <input type="color" 
                                               name="colors[<?= $index ?>][value]" 
                                               value="<?= $color['color_value'] ?>"
                                               class="w-full h-10 border border-gray-300 rounded-md">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">وێنەی ڕەنگ</label>
                                        <div class="flex items-center space-x-2">
                                            <?php if ($color['image_path']): ?>
                                                <img src="../uploads/images/<?= $color['image_path'] ?>" 
                                                     alt="Color image" 
                                                     class="w-10 h-10 object-cover rounded border ml-2">
                                            <?php endif; ?>
                                            <input type="file" 
                                                   name="color_images[<?= $index ?>]" 
                                                   accept="image/*"
                                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <button type="button" 
                                                    onclick="removeColorItem(this)"
                                                    class="bg-red-500 text-white px-2 py-2 rounded hover:bg-red-600">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Sizes -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">قەبارەکان</h3>
                        <button type="button" 
                                @click="addSize()" 
                                class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700">
                            <i class="fas fa-plus ml-1"></i>
                            زیادکردنی قەبارە
                        </button>
                    </div>
                    
                    <div id="sizes-container" class="space-y-4">
                        <?php foreach ($productSizes as $index => $size): ?>
                            <div class="size-item border border-gray-300 rounded-lg p-4">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">قەبارە</label>
                                        <input type="text" 
                                               name="sizes[<?= $index ?>][name]" 
                                               value="<?= htmlspecialchars($size['size_name']) ?>"
                                               placeholder="S, M, L, XL..."
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">ژمارەی کۆگا</label>
                                        <input type="number" 
                                               name="sizes[<?= $index ?>][stock]" 
                                               value="<?= $size['stock_quantity'] ?>"
                                               min="0"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    
                                    <div>
                                        <button type="button" 
                                                onclick="removeSizeItem(this)"
                                                class="bg-red-500 text-white px-3 py-2 rounded hover:bg-red-600">
                                            <i class="fas fa-trash ml-1"></i>
                                            سڕینەوە
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <button type="submit" 
                                class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 font-medium">
                            <i class="fas fa-save ml-2"></i>
                            نوێکردنەوەی بەرهەم
                        </button>
                        
                        <a href="products.php" 
                           class="bg-gray-500 text-white px-6 py-3 rounded-lg hover:bg-gray-600 font-medium">
                            <i class="fas fa-times ml-2"></i>
                            هەڵوەشاندنەوە
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function productForm() {
            return {
                sidebarOpen: false,
                colorIndex: <?= count($productColors) ?>,
                sizeIndex: <?= count($productSizes) ?>,

                addColor() {
                    const container = document.getElementById('colors-container');
                    const colorItem = document.createElement('div');
                    colorItem.className = 'color-item border border-gray-300 rounded-lg p-4';
                    colorItem.innerHTML = `
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">ناوی ڕەنگ</label>
                                <input type="text" 
                                       name="colors[${this.colorIndex}][name]" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">کۆدی ڕەنگ</label>
                                <input type="color" 
                                       name="colors[${this.colorIndex}][value]" 
                                       class="w-full h-10 border border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">وێنەی ڕەنگ</label>
                                <div class="flex items-center space-x-2">
                                    <input type="file" 
                                           name="color_images[${this.colorIndex}]" 
                                           accept="image/*"
                                           class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <button type="button" 
                                            onclick="removeColorItem(this)"
                                            class="bg-red-500 text-white px-2 py-2 rounded hover:bg-red-600">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                    container.appendChild(colorItem);
                    this.colorIndex++;
                }
            }
        }

        // Remove color item (for dynamically added colors)
        function removeColorItem(btn) {
            const colorItem = btn.closest('.color-item');
            if (colorItem) colorItem.remove();
        }

        // Remove size item (already present in your code)
        function removeSizeItem(btn) {
            const sizeItem = btn.closest('.size-item');
            if (sizeItem) sizeItem.remove();
        }
    </script>
</body>
</html>
