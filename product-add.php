<?php
session_start();
require_once '../config.php';
requireLogin();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = isset($_POST['title']) ? sanitize($_POST['title']) : '';
    $description = isset($_POST['description']) ? sanitize($_POST['description']) : '';
    $price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
    $original_price = isset($_POST['original_price']) && !empty($_POST['original_price']) ? (float)$_POST['original_price'] : null;
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $badge = isset($_POST['badge']) ? sanitize($_POST['badge']) : '';
    $status = isset($_POST['status']) ? $_POST['status'] : 'active';
    $featured = isset($_POST['featured']) ? 1 : 0;
    
    // Validation
    if (empty($title)) {
        $errors[] = 'ناونیشان پێویستە';
    }
    if ($price <= 0) {
        $errors[] = 'نرخ دەبێت لە سفر زیاتر بێت';
    }
    if ($category_id <= 0) {
        $errors[] = 'دیاریکردنی پۆل پێویستە';
    }
    
    if (empty($errors)) {
        try {
            // Insert product
            $productQuery = "INSERT INTO products (title, description, price, original_price, category_id, badge, status, featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $db->query($productQuery, [$title, $description, $price, $original_price, $category_id, $badge, $status, $featured]);
            $productId = $db->lastInsertId();
            
            // Handle main product images
            if (isset($_FILES['main_images']) && !empty($_FILES['main_images']['name'][0])) {
                $totalFiles = count($_FILES['main_images']['name']);
                
                for ($i = 0; $i < $totalFiles; $i++) {
                    if ($_FILES['main_images']['error'][$i] === UPLOAD_ERR_OK) {
                        $fileArray = [
                            'name' => $_FILES['main_images']['name'][$i],
                            'type' => $_FILES['main_images']['type'][$i],
                            'tmp_name' => $_FILES['main_images']['tmp_name'][$i],
                            'error' => $_FILES['main_images']['error'][$i],
                            'size' => $_FILES['main_images']['size'][$i]
                        ];
                        
                        $imagePath = uploadImage($fileArray, 'products');
                        if ($imagePath) {
                            $isMain = ($i === 0) ? 1 : 0; // First image is main
                            $db->query("INSERT INTO product_images (product_id, image_path, is_main, sort_order) VALUES (?, ?, ?, ?)", 
                                      [$productId, $imagePath, $isMain, $i]);
                            
                            // Update main_image in products table for first image
                            if ($isMain) {
                                $db->query("UPDATE products SET main_image = ? WHERE id = ?", [$imagePath, $productId]);
                            }
                        }
                    }
                }
            }
            
            // Handle colors with images
            if (isset($_POST['color_names']) && is_array($_POST['color_names'])) {
                foreach ($_POST['color_names'] as $index => $colorName) {
                    $colorValue = $_POST['color_values'][$index] ?? '#000000';
                    
                    if (!empty($colorName)) {
                        $colorImagePath = null;
                        
                        // Check if there's an image for this color
                        if (isset($_FILES['color_images']) && 
                            isset($_FILES['color_images']['name'][$index]) && 
                            $_FILES['color_images']['error'][$index] === UPLOAD_ERR_OK) {
                            
                            $fileArray = [
                                'name' => $_FILES['color_images']['name'][$index],
                                'type' => $_FILES['color_images']['type'][$index],
                                'tmp_name' => $_FILES['color_images']['tmp_name'][$index],
                                'error' => $_FILES['color_images']['error'][$index],
                                'size' => $_FILES['color_images']['size'][$index]
                            ];
                            
                            $colorImagePath = uploadImage($fileArray, 'colors');
                        }
                        
                        // Insert color
                        $db->query("INSERT INTO product_colors (product_id, color_name, color_value, image_path) VALUES (?, ?, ?, ?)", 
                                  [$productId, $colorName, $colorValue, $colorImagePath]);
                    }
                }
            }
            
            // Handle sizes
            if (isset($_POST['size_names']) && is_array($_POST['size_names'])) {
                foreach ($_POST['size_names'] as $index => $sizeName) {
                    $stockQuantity = (int)($_POST['size_stocks'][$index] ?? 0);
                    
                    if (!empty($sizeName)) {
                        $db->query("INSERT INTO product_sizes (product_id, size_name, stock_quantity) VALUES (?, ?, ?)", 
                                  [$productId, $sizeName, $stockQuantity]);
                    }
                }
            }
            
            $success = 'بەرهەمەکە بە سەرکەوتوویی زیادکرا';
            
        } catch (Exception $e) {
            $errors[] = 'هەڵەیەک ڕوویدا: ' . $e->getMessage();
        }
    }
}

// Get categories
$categories = $db->fetchAll("SELECT * FROM categories WHERE status = 'active' ORDER BY name_ku");
?>

<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>زیادکردنی بەرهەم - Sanvito</title>
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
                
                <h2 class="text-xl font-semibold text-gray-800">زیادکردنی بەرهەم</h2>
                
                <a href="products.php" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
                    <i class="fas fa-arrow-right ml-2"></i>
                    گەڕانەوە
                </a>
            </div>
        </div>

        <!-- Content -->
        <div class="p-6">
            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><i class="fas fa-exclamation-circle ml-2"></i><?= $error ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <i class="fas fa-check-circle ml-2"></i>
                    <?= $success ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <!-- Basic Information -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">زانیاری بنەڕەتی</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">ناونیشان *</label>
                            <input type="text" 
                                   name="title" 
                                   value="<?= htmlspecialchars($title ?? '') ?>"
                                   required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">پۆل *</label>
                            <select name="category_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">پۆلێک هەڵبژێرە</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>" <?= ($category_id ?? 0) == $category['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category['name_ku']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">نرخ *</label>
                            <input type="number" 
                                   name="price" 
                                   value="<?= $price ?? '' ?>"
                                   step="0.01"
                                   min="0"
                                   required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">نرخی بنەڕەتی</label>
                            <input type="number" 
                                   name="original_price" 
                                   value="<?= $original_price ?? '' ?>"
                                   step="0.01"
                                   min="0"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">نیشانە</label>
                            <input type="text" 
                                   name="badge" 
                                   value="<?= htmlspecialchars($badge ?? '') ?>"
                                   placeholder="نموونە: داشکاندن، نوێ"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">دۆخ</label>
                            <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="active" <?= ($status ?? 'active') === 'active' ? 'selected' : '' ?>>چالاک</option>
                                <option value="inactive" <?= ($status ?? '') === 'inactive' ? 'selected' : '' ?>>ناچالاک</option>
                                <option value="out_of_stock" <?= ($status ?? '') === 'out_of_stock' ? 'selected' : '' ?>>تەواوبوو</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">پێناسە</label>
                        <textarea name="description" 
                                  rows="4"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($description ?? '') ?></textarea>
                    </div>
                    
                    <div class="mt-6">
                        <label class="flex items-center">
                            <input type="checkbox" 
                                   name="featured" 
                                   value="1"
                                   <?= ($featured ?? 0) ? 'checked' : '' ?>
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="mr-2 text-sm text-gray-600">بەرهەمی تایبەت</span>
                        </label>
                    </div>
                </div>

                <!-- Product Images -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">وێنەکانی بەرهەم</h3>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">وێنەکان</label>
                        <input type="file" 
                               name="main_images[]" 
                               multiple
                               accept="image/*"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               @change="previewMainImages">
                        <p class="text-sm text-gray-500 mt-1">دەتوانیت چەندین وێنە هەڵبژێریت. یەکەمین وێنە وەک وێنەی سەرەکی لە بەرچاو دەگیرێت.</p>
                        
                        <!-- Preview main images -->
                        <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4" x-show="mainImagePreviews.length > 0">
                            <template x-for="(preview, index) in mainImagePreviews" :key="index">
                                <div class="relative">
                                    <img :src="preview" class="w-full h-32 object-cover rounded-lg border">
                                    <span x-show="index === 0" class="absolute top-2 right-2 bg-blue-500 text-white text-xs px-2 py-1 rounded">سەرەکی</span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Colors -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">ڕەنگەکان</h3>
                        <button type="button" 
                                @click="addColor" 
                                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-plus ml-2"></i>
                            زیادکردنی ڕەنگ
                        </button>
                    </div>
                    
                    <div class="space-y-4">
                        <template x-for="(color, index) in colors" :key="index">
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">ناوی ڕەنگ</label>
                                        <input type="text" 
                                               :name="'color_names[' + index + ']'"
                                               x-model="color.name"
                                               placeholder="سپی، ڕەش، سوور..."
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">کۆدی ڕەنگ</label>
                                        <input type="color" 
                                               :name="'color_values[' + index + ']'"
                                               x-model="color.value"
                                               class="w-full h-10 border border-gray-300 rounded-md cursor-pointer">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">وێنەی ڕەنگ</label>
                                        <input type="file" 
                                               :name="'color_images[' + index + ']'"
                                               accept="image/*"
                                               @change="previewColorImage($event, index)"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    
                                    <div class="flex items-center space-x-2">
                                        <!-- Color preview -->
                                        <div class="w-10 h-10 rounded-md border border-gray-300" :style="'background-color: ' + color.value"></div>
                                        
                                        <!-- Image preview -->
                                        <div class="w-10 h-10 rounded-md border border-gray-300 overflow-hidden" x-show="color.imagePreview">
                                            <img :src="color.imagePreview" class="w-full h-full object-cover">
                                        </div>
                                        
                                        <!-- Remove button -->
                                        <button type="button" 
                                                @click="removeColor(index)" 
                                                class="text-red-600 hover:text-red-800 p-2">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                        
                        <div x-show="colors.length === 0" class="text-center py-8 text-gray-500">
                            <i class="fas fa-palette text-4xl mb-4"></i>
                            <p>هێشتا هیچ ڕەنگێک زیادنەکراوە</p>
                        </div>
                    </div>
                </div>

                <!-- Sizes -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">قەبارەکان</h3>
                        <button type="button" 
                                @click="addSize" 
                                class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                            <i class="fas fa-plus ml-2"></i>
                            زیادکردنی قەبارە
                        </button>
                    </div>
                    
                    <div class="space-y-4">
                        <template x-for="(size, index) in sizes" :key="index">
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">ناوی قەبارە</label>
                                        <input type="text" 
                                               :name="'size_names[' + index + ']'"
                                               x-model="size.name"
                                               placeholder="S, M, L, XL..."
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">ژمارەی کۆگا</label>
                                        <input type="number" 
                                               :name="'size_stocks[' + index + ']'"
                                               x-model="size.stock"
                                               min="0"
                                               placeholder="0"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    
                                    <div>
                                        <button type="button" 
                                                @click="removeSize(index)" 
                                                class="text-red-600 hover:text-red-800 p-2">
                                            <i class="fas fa-trash ml-2"></i>
                                            سڕینەوە
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                        
                        <div x-show="sizes.length === 0" class="text-center py-8 text-gray-500">
                            <i class="fas fa-ruler text-4xl mb-4"></i>
                            <p>هێشتا هیچ قەبارەیەک زیادنەکراوە</p>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-end space-x-4">
                        <a href="products.php" class="bg-gray-300 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-400 ml-4">
                            هەڵوەشاندنەوە
                        </a>
                        <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-save ml-2"></i>
                            پاشەکەوتکردن
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function productForm() {
            return {
                sidebarOpen: false,
                colors: [],
                sizes: [],
                mainImagePreviews: [],
                
                addColor() {
                    this.colors.push({
                        name: '',
                        value: '#000000',
                        imagePreview: null
                    });
                },
                
                removeColor(index) {
                    this.colors.splice(index, 1);
                },
                
                addSize() {
                    this.sizes.push({
                        name: '',
                        stock: 0
                    });
                },
                
                removeSize(index) {
                    this.sizes.splice(index, 1);
                },
                
                previewMainImages(event) {
                    this.mainImagePreviews = [];
                    const files = event.target.files;
                    
                    for (let i = 0; i < files.length; i++) {
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            this.mainImagePreviews.push(e.target.result);
                        };
                        reader.readAsDataURL(files[i]);
                    }
                },
                
                previewColorImage(event, index) {
                    const file = event.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            this.colors[index].imagePreview = e.target.result;
                        };
                        reader.readAsDataURL(file);
                    }
                }
            };
        }
    </script>
</body>
</html>