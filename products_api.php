<?php
require_once 'config.php';

// Get all products with category name
$products = $db->fetchAll("
    SELECT 
        p.id, p.title, p.description, p.price, p.original_price+0 as original_price, 
        c.name_ku as category, p.badge, p.main_image
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.status = 'active'
    ORDER BY p.created_at DESC
    LIMIT 30
");

// Get colors for each product
foreach ($products as &$product) {
    $product['colors'] = $db->fetchAll("SELECT color_name as name, color_value as value, image_path as image FROM product_colors WHERE product_id = ?", [$product['id']]);
    if (empty($product['colors'])) {
        $product['colors'][] = [
            'name' => 'Default',
            'value' => '#cccccc',
            'image' => $product['main_image'] ? 'uploads/images/' . $product['main_image'] : null
        ];
    } else {
        foreach ($product['colors'] as &$color) {
            if ($color['image']) {
                $color['image'] = 'uploads/images/' . $color['image'];
            } else {
                $color['image'] = $product['main_image'] ? 'uploads/images/' . $product['main_image'] : null;
            }
        }
    }
    unset($color);
}

// Get sizes for each product
foreach ($products as &$product) {
    $sizes = $db->fetchAll("SELECT size_name FROM product_sizes WHERE product_id = ?", [$product['id']]);
    $product['sizes'] = array_column($sizes, 'size_name');
}
unset($product);

jsonResponse($products);
?>