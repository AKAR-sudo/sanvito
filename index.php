<?php
require_once 'cache_headers.php';
setCacheHeaders('html');

// کردنەوەی Output Buffering بۆ compression
ob_start();

require_once 'config.php';
// Get all featured products with category name
$featuredProductsRaw = $db->fetchAll("SELECT p.*, c.name_ku as category FROM products p JOIN categories c ON p.category_id = c.id WHERE p.featured = 1 AND p.status = 'active' ORDER BY p.created_at DESC LIMIT 12");
$featuredProducts = [];
foreach ($featuredProductsRaw as $product) {
    $productId = $product['id'];
    // Get colors
    $colors = $db->fetchAll("SELECT color_name as name, color_value as value, image_path as image FROM product_colors WHERE product_id = ?", [$productId]);
    if (empty($colors)) {
        $colors = [["name" => "Default", "value" => "#eee", "image" => null]];
    } else {
        // یەکسان کردنی ڕێگای وێنەکان
        foreach ($colors as &$color) {
            if ($color['image']) {
                $color['image'] = "uploads/images/" . ltrim($color['image'], '/');
            }
        }
        unset($color);
    }
    
    // Get sizes
    $sizes = $db->fetchAll("SELECT size_name FROM product_sizes WHERE product_id = ?", [$productId]);
    $sizes = array_map(function($s) { return $s['size_name']; }, $sizes);
    if (empty($sizes)) {
        $sizes = ["Default"];
    }
    
    // Get main image - یەکسان کردنی ڕێگای وێنەکان
    $mainImage = $product['main_image'];
    if (!$mainImage) {
        $imgRow = $db->fetch("SELECT image_path FROM product_images WHERE product_id = ? AND is_main = 1", [$productId]);
        if ($imgRow) $mainImage = $imgRow['image_path'];
    }
    // Fallback to any image
    if (!$mainImage) {
        $imgRow = $db->fetch("SELECT image_path FROM product_images WHERE product_id = ? ORDER BY sort_order ASC, id ASC LIMIT 1", [$productId]);
        if ($imgRow) $mainImage = $imgRow['image_path'];
    }
    
    $product['colors'] = $colors;
    $product['sizes'] = $sizes;
    // یەکسان کردنی ڕێگای وێنەکان بۆ main image
    $product['image'] = $mainImage ? ("uploads/images/" . ltrim($mainImage, '/')) : null;
    $featuredProducts[] = $product;
}
?>
<script>
// Featured products from database
window.featuredProducts = <?php echo json_encode($featuredProducts, JSON_UNESCAPED_UNICODE); ?>;
</script>
<!DOCTYPE html>
<html lang="ku" dir="rtl">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Sanvito - دوکانی جولوبەرگ</title>
    <!-- بەدوای ئەم meta tags ئەی هەیە زیاد بکە -->
    <meta name="keywords" content="جل, جولوبەرگ, فاشن, کوردی, sanvito" />

    <!-- ئەمانە زیاد بکە: -->
    <meta name="author" content="Sanvito">
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large">

    <!-- Open Graph Tags بۆ Social Media -->
    <meta property="og:site_name" content="Sanvito">
    <meta property="og:url" content="https://yourwebsite.com">
    <meta property="og:image" content="https://yourwebsite.com/uploads/images/og-image.jpg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:locale" content="ku_IQ">

    <!-- Twitter Cards -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@sanvito">
    <meta name="twitter:title" content="Sanvito - دوکانی جولوبەرگ">
    <meta name="twitter:description" content="کۆلێکسیۆنی جدید و کوالیتی بەرز">
    <meta name="twitter:image" content="https://yourwebsite.com/uploads/images/og-image.jpg">

    <!-- Structured Data - JSON-LD -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "ClothingStore",
        "name": "Sanvito",
        "description": "دوکانی جولوبەرگ بە شێوازی کلاسیکی",
        "url": "https://yourwebsite.com",
        "telephone": "+964 750 123 4567",
        "email": "info@sanvito.com",
        "address": {
            "@type": "PostalAddress",
            "addressLocality": "هەولێر",
            "addressRegion": "کوردستان",
            "addressCountry": "IQ"
        },
        "sameAs": [
            "https://instagram.com/sanvito",
            "https://t.me/sanvito"
        ]
    }
    </script>
    <meta name="description" content="Sanvito - دوکانی جولوبەرگ بە شێوازی کلاسیکی - بەرهەمی کوالیتی بەرز" />
    <meta name="keywords" content="جل, جولوبەرگ, فاشن, کوردی, sanvito" />
    <meta property="og:title" content="Sanvito - دوکانی جولوبەرگ" />
    <meta property="og:description" content="کۆلێکسیۆنی جدید و کوالیتی بەرز" />
    <meta property="og:type" content="website" />
    <script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    primary: '#1a1a1a',
                    secondary: '#333333',
                    accent: '#666666',
                    light: '#f8f8f8',
                    cream: '#faf9f7'
                },
                fontFamily: {
                    serif: ['Georgia', 'Times New Roman', 'serif'],
                    sans: ['Helvetica Neue', 'Arial', 'sans-serif']
                },
                keyframes: {
                    'fade-up': {
                        '0%': {
                            opacity: '0',
                            transform: 'translateY(20px)'
                        },
                        '100%': {
                            opacity: '1',
                            transform: 'translateY(0)'
                        }
                    },
                    'fade-in': {
                        '0%': {
                            opacity: '0'
                        },
                        '100%': {
                            opacity: '1'
                        }
                    }
                },
                animation: {
                    'fade-up': 'fade-up 0.8s ease-out both',
                    'fade-in': 'fade-in 0.6s ease-out both'
                }
            }
        }
    }
    </script>
    <style>
    html {
        scroll-behavior: smooth;
    }

    .elegant-shadow {
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.06);
    }

    .elegant-shadow-lg {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    .elegant-hover {
        transition: all 0.3s ease;
    }

    .elegant-hover:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }

    .gradient-overlay {
        background: linear-gradient(135deg, rgba(0, 0, 0, 0.4) 0%, rgba(0, 0, 0, 0.2) 100%);
    }

    .text-shadow {
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    }

    .color-swatch {
        width: 24px;
        height: 24px;
        border: 2px solid #fff;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .color-swatch:hover {
        transform: scale(1.1);
    }

    .color-swatch.active {
        border-color: #1a1a1a;
        transform: scale(1.1);
    }

    .section-divider {
        height: 1px;
        background: linear-gradient(to right, transparent, #e5e5e5, transparent);
    }

    .classic-button {
        background: #1a1a1a;
        color: white;
        padding: 12px 32px;
        font-size: 14px;
        letter-spacing: 1px;
        text-transform: uppercase;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        font-weight: 500;
    }

    .classic-button:hover {
        background: #333333;
        transform: translateY(-1px);
    }

    .classic-button-outline {
        background: transparent;
        color: #1a1a1a;
        border: 1px solid #1a1a1a;
        padding: 11px 31px;
        font-size: 12px;
        letter-spacing: 1px;
        text-transform: uppercase;
        cursor: pointer;
        transition: all 0.3s ease;
        font-weight: 500;
    }

    .classic-button-outline:hover {
        background: #1a1a1a;
        color: white;
    }

    .filter-button {
        background: transparent;
        color: #1a1a1a;
        border: 1px solid #e5e5e5;
        padding: 8px 20px;
        font-size: 12px;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        cursor: pointer;
        transition: all 0.3s ease;
        font-weight: 500;
    }

    .filter-button:hover {
        border-color: #1a1a1a;
        background: #f8f8f8;
    }

    .filter-button.active {
        background: #1a1a1a;
        color: white;
        border-color: #1a1a1a;
    }

    /* Debug styles - بۆ تۆماری کێشەکان */
    .debug-image {
        border: 2px solid red;
    }

    .debug-image::after {
        content: "وێنە نەدۆزرایەوە";
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: rgba(255, 0, 0, 0.8);
        color: white;
        padding: 5px;
        font-size: 12px;
    }
    </style>
    <style>
    /* Mobile Grid Improvements - زیادکردن بۆ نیشاندانی زیاتر لە یەک بەرهەم لە ڕیز */

    /* بۆ مۆبایلی بچووک - 2 بەرهەم لە ڕیز */
    @media (min-width: 480px) and (max-width: 767px) {
        .grid-cols-1.md\:grid-cols-2.lg\:grid-cols-3 {
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 1rem !important;
        }
    }

    /* بۆ مۆبایلی زۆر بچووک - 2 بەرهەم لە ڕیز بە گاپی کەمتر */
    @media (max-width: 479px) {
        .grid-cols-1.md\:grid-cols-2.lg\:grid-cols-3 {
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 0.75rem !important;
        }

        /* کەمکردنەوەی padding لە لایەکان */
        .max-w-7xl.mx-auto.px-6.lg\:px-8 {
            padding-left: 1rem !important;
            padding-right: 1rem !important;
        }
    }

    /* چاککردنی قەبارەی فۆنت و spacing بۆ مۆبایل */
    @media (max-width: 640px) {

        /* کەمکردنەوەی قەبارەی ناونیشان */
        .group h3 {
            font-size: 0.95rem !important;
            margin-bottom: 0.5rem !important;
            line-height: 1.3 !important;
        }

        /* کەمکردنەوەی وەسف */
        .group p {
            font-size: 0.8rem !important;
            margin-bottom: 0.75rem !important;
            line-height: 1.4 !important;
        }

        /* کەمکردنەوەی قەبارەی نرخ */
        .group .text-lg {
            font-size: 0.95rem !important;
        }

        .group .text-sm {
            font-size: 0.75rem !important;
        }

        /* کەمکردنەوەی padding لە کارتەکان */
        .group .pt-6.pb-2 {
            padding-top: 1rem !important;
            padding-bottom: 0.5rem !important;
        }

        /* چاککردنی قەبارەی color swatches */
        .color-swatch {
            width: 20px !important;
            height: 20px !important;
        }

        /* کەمکردنەوەی gap لە نێوان ڕەنگەکان */
        .flex.items-center.gap-2.mb-4 {
            gap: 0.375rem !important;
            margin-bottom: 0.75rem !important;
        }
    }

    /* بۆ تابلێت - 3 بەرهەم لە ڕیز */
    @media (min-width: 768px) and (max-width: 1023px) {
        .grid-cols-1.md\:grid-cols-2.lg\:grid-cols-3 {
            grid-template-columns: repeat(3, 1fr) !important;
            gap: 1.5rem !important;
        }
    }

    /* چاککردنی aspect ratio بۆ وێنەکان لە مۆبایل */
    @media (max-width: 640px) {
        .aspect-\[3\/4\] {
            aspect-ratio: 4/5 !important;
        }
    }

    /* بۆ چاککردنی modal لە مۆبایل */
    @media (max-width: 640px) {
        .fixed.inset-0.bg-black\/60.flex.items-center.justify-center.z-50.p-4 {
            padding: 0.5rem !important;
        }

        .bg-white.max-w-4xl.w-full.max-h-\[90vh\] {
            max-height: 95vh !important;
        }

        .grid.grid-cols-1.lg\:grid-cols-2 {
            grid-template-columns: 1fr !important;
        }

        .p-8.lg\:p-12 {
            padding: 1.5rem !important;
        }
    }
    </style>
</head>

<body class="bg-cream text-primary font-sans">
    <!-- Loading Screen -->
    <div id="loading-screen" class="fixed inset-0 bg-white z-50 flex items-center justify-center">
        <div class="text-center">
            <div class="w-16 h-16 border-4 border-main border-t-transparent rounded-full animate-spin mb-4"></div>
            <p class="text-main font-bold">Sanvito</p>
        </div>
    </div>

    <!-- Header -->
    <header class="bg-white/95 backdrop-blur-sm elegant-shadow sticky top-0 z-40">

        <div class="max-w-7xl mx-auto px-6 lg:px-8 flex items-center justify-between h-20">
            <a href="#" class="text-2xl font-medium tracking-wider text-primary hover:text-secondary transition-colors">
                SANVITO
            </a>
            <nav class="hidden md:flex space-x-12">
                <a href="#featured"
                    class="text-sm tracking-wide text-secondary hover:text-primary transition-colors uppercase">بەرهەمە
                    ناوازەکان</a>
                <span class="mx-2 md:mx-4 lg:mx-6">|</span>
                <a href="#collections"
                    class="text-sm tracking-wide text-secondary hover:text-primary transition-colors uppercase">بەرهەمەکان
                </a>
                <a href="#contact"
                    class="text-sm tracking-wide text-secondary hover:text-primary transition-colors uppercase">پەیوەندی</a>
            </nav>
            <button id="menu-toggle" class="md:hidden p-2 hover:bg-gray-50 transition">
                <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>
        <div id="mobile-menu" class="md:hidden hidden bg-white border-t border-gray-100">
            <a href="#featured"
                class="block px-6 py-4 text-sm text-secondary hover:bg-gray-50 transition mobile-link uppercase">بەرهەمە
                ناوازەکان</a>
            <span class="block text-center text-gray-400">|</span>
            <a href="#collections"
                class="block px-6 py-4 text-sm text-secondary hover:bg-gray-50 transition mobile-link uppercase">بەرهەمەکان
            </a>
            <a href="#contact"
                class="block px-6 py-4 text-sm text-secondary hover:bg-gray-50 transition mobile-link uppercase">پەیوەندی</a>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="relative w-full h-screen bg-black" style="background: #000;">
        <video autoplay loop muted playsinline class="absolute inset-0 w-full h-full object-cover z-0">
            <source src="11.mp4" type="video/mp4" />
        </video>
        <div class="absolute inset-0 gradient-overlay"></div>
        <div class="relative z-10 flex flex-col items-center justify-center h-full text-center px-6">
            <h1
                class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-light text-white tracking-wider mb-8 text-shadow animate-fade-up">
                SANVITO
            </h1>
            <p class="text-lg md:text-xl text-white/90 mb-12 max-w-2xl leading-relaxed font-light animate-fade-up"
                style="animation-delay:0.3s;">
                MORE THAN FASHION </p>
            <button onclick="document.getElementById('featured').scrollIntoView({behavior: 'smooth'})"
                class="classic-button animate-fade-up" style="animation-delay:0.6s;">
                سەیرکردن
            </button>
        </div>
    </section>

    <!-- Main Content -->
    <main class="bg-white">
        <div id="root" class="max-w-7xl mx-auto px-6 lg:px-8"></div>
    </main>


    <section id="contact" class="bg-primary text-white py-20">
        <div class="max-w-4xl mx-auto px-6 text-center">
            <h2 class="text-3xl font-light mb-12 tracking-wide animate-fade-up">پەیوەندی</h2>
            <div class="grid grid-cols-1 md:grid-cols-5 gap-8">
                <div class="animate-fade-up" style="animation-delay:0.1s;">
                    <div class="flex flex-col items-center">
                        <!-- Phone Icon -->
                        <div class="mb-4 p-3 bg-white/10 rounded-full">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z">
                                </path>
                            </svg>
                        </div>
                        <h3 class="font-medium mb-3 tracking-wide">تەلەفۆن</h3>
                        <a href="tel:+9647501234567"
                            class="text-white/80 hover:text-white transition-colors duration-300 hover:underline">
                            +964 750 123 4567
                        </a>
                    </div>
                </div>
                <div class="animate-fade-up" style="animation-delay:0.2s;">
                    <div class="flex flex-col items-center">
                        <!-- Email Icon -->
                        <div class="mb-4 p-3 bg-white/10 rounded-full">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                </path>
                            </svg>
                        </div>
                        <h3 class="font-medium mb-3 tracking-wide">ئیمەیڵ</h3>
                        <a href="mailto:info@sanvito.com"
                            class="text-white/80 hover:text-white transition-colors duration-300 hover:underline">
                            info@sanvito.com
                        </a>
                    </div>
                </div>

                <div class="animate-fade-up" style="animation-delay:0.3s;">
                    <div class="flex flex-col items-center">
                        <!-- Location Icon -->
                        <div class="mb-4 p-3 bg-white/10 rounded-full">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                                </path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        <h3 class="font-medium mb-3 tracking-wide">ناونیشان</h3>
                        <a href="https://maps.google.com/?q=Erbil,Kurdistan" target="_blank" rel="noopener noreferrer"
                            class="text-white/80 hover:text-white transition-colors duration-300 hover:underline">
                            هەولێر، کوردستان
                        </a>
                    </div>
                </div>

                <div class="animate-fade-up" style="animation-delay:0.4s;">
                    <div class="flex flex-col items-center">
                        <!-- Instagram Icon -->
                        <div class="mb-4 p-3 bg-white/10 rounded-full">
                            <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.

zamwa, [6/24/2025 1:46 AM]
281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z" />
                            </svg>
                        </div>
                        <h3 class="font-medium mb-3 tracking-wide">ئینستاگرام</h3>
                        <a href="https://instagram.com/sanvito" target="_blank" rel="noopener noreferrer"
                            class="text-white/80 hover:text-white transition-colors duration-300 hover:underline">
                            @sanvito
                        </a>
                    </div>
                </div>

                <div class="animate-fade-up" style="animation-delay:0.5s;">
                    <div class="flex flex-col items-center">
                        <!-- Telegram Icon -->
                        <div class="mb-4 p-3 bg-white/10 rounded-full">
                            <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z" />
                            </svg>
                        </div>
                        <h3 class="font-medium mb-3 tracking-wide">تێلیگرام</h3>
                        <a href="https://t.me/sanvito" target="_blank" rel="noopener noreferrer"
                            class="text-white/80 hover:text-white transition-colors duration-300 hover:underline">
                            @sanvito
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </section>


    <script type="text/babel">
        // Use real featured products from PHP
    const featuredProducts = window.featuredProducts || [];

    // Enhanced Product Card Component
    function ProductCard({ product, onClick, index }) {
      const [selectedColor, setSelectedColor] = React.useState(product.colors[0]);
      const [imageError, setImageError] = React.useState(false);
      const discount = product.original_price ? Math.round(((product.original_price - product.price) / product.original_price) * 100) : 0;
      
      // Image path fixing function
      const getImagePath = (imagePath) => {
        if (!imagePath) return null;
        // Make sure path starts with correct structure
        if (imagePath.startsWith('http')) return imagePath;
        if (imagePath.startsWith('/')) return imagePath.substring(1);
        if (!imagePath.startsWith('uploads/')) return 'uploads/images/' + imagePath;
        return imagePath;
      };

      const handleImageError = () => {
        console.log('Image failed to load:', selectedColor.image || product.image);
        setImageError(true);
      };

      const currentImage = getImagePath(selectedColor.image || product.image);
      
      return (
        <div className="group cursor-pointer animate-fade-up" style={{ animationDelay: `${index * 0.1}s` }}>
          <div onClick={() => onClick(product)} className="relative overflow-hidden bg-white elegant-hover">
            {product.badge && (
              <div className={`absolute top-4 right-4 z-10 px-3 py-1 text-xs font-medium tracking-wide ${
                product.badge === 'نوێ' ? 'bg-primary text-white' : 'bg-white text-primary border border-primary'
              }`}>
                {product.badge}
              </div>
            )}
            
            {discount > 0 && (
              <div className="absolute top-4 left-4 bg-red-600 text-white px-2 py-1 text-xs font-medium z-10">
                -{discount}%
              </div>
            )}

            <div className="aspect-[3/4] overflow-hidden relative">
              {currentImage && !imageError ? (
                <img 
                  src={currentImage}
                  alt={product.title} 
                  className="w-full h-full object-cover transition-all duration-500 group-hover:scale-105"
                  onError={handleImageError}
                  onLoad={() => console.log('Image loaded successfully:', currentImage)}
                />
              ) : (
                <div className="w-full h-full bg-gray-200 flex items-center justify-center">
                  <div className="text-center text-gray-500">
                    <svg className="w-16 h-16 mx-auto mb-2" fill="currentColor" viewBox="0 0 20 20">
                      <path fillRule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clipRule="evenodd" />
                    </svg>
                    <p className="text-sm">وێنە نییە</p>
                    <p className="text-xs text-gray-400">{currentImage}</p>
                  </div>
                </div>
              )}
            </div>
          </div>

          <div className="pt-6 pb-2">
            <h3 className="text-lg font-medium text-primary mb-2 group-hover:text-secondary transition-colors">
              {product.title}
            </h3>
            <p className="text-sm text-accent mb-4 leading-relaxed">{product.description}</p>
            
            {/* Color Options */}
            <div className="flex items-center gap-2 mb-4">
              {product.colors.map((color, i) => (
                <div
                  key={i}
                  className={`color-swatch ${selectedColor.name === color.name ? 'active' : ''}`}
                  style={{ backgroundColor: color.value }}
                  onClick={(e) => {
                    e.stopPropagation();
                    setSelectedColor(color);
                    setImageError(false); // Reset error state when changing color
                  }}
                  title={color.name}
                />
              ))}
            </div>
            
            <div className="flex items-center justify-between">
              <div>
                <span className="text-lg font-medium text-primary">{Number(product.price).toLocaleString()} $</span>
                {product.original_price && Number(product.original_price) > Number(product.price) && (
                  <span className="text-sm text-accent line-through mr-3">
                    {Number(product.original_price).toLocaleString()} $
                  </span>
                )}
              </div>
              <span className="text-xs text-accent tracking-wide uppercase">{product.category}</span>
            </div>
          </div>
        </div>
      );
    }

    // Enhanced Product Detail Modal
    function ProductDetailModal({ product, onClose }) {
      const [selectedColor, setSelectedColor] = React.useState(product.colors[0]);
      const [selectedSize, setSelectedSize] = React.useState(product.sizes[0]);
      const [imageError, setImageError] = React.useState(false);

      const getImagePath = (imagePath) => {
        if (!imagePath) return null;
        if (imagePath.startsWith('http')) return imagePath;
        if (imagePath.startsWith('/')) return imagePath.substring(1);
        if (!imagePath.startsWith('uploads/')) return 'uploads/images/' + imagePath;
        return imagePath;
      };

      const currentImage = getImagePath(selectedColor.image || product.image);

      return (
        <div className="fixed inset-0 bg-black/60 flex items-center justify-center z-50 p-4 animate-fade-in">
          <div className="bg-white max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <div className="relative">
              <button 
                onClick={onClose}
                className="absolute top-6 right-6 z-20 w-10 h-10 bg-white/90 hover:bg-white flex items-center justify-center transition"
              >
                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </button>
              
              <div className="grid grid-cols-1 lg:grid-cols-2">
                <div className="aspect-square lg:aspect-[4/5]">
                  {currentImage && !imageError ? (
                    <img 
                      src={currentImage}
                      alt={product.title} 
                      className="w-full h-full object-cover transition-all duration-500"
                      onError={() => setImageError(true)}
                    />
                  ) : (
                    <div className="w-full h-full bg-gray-200 flex items-center justify-center">
                      <div className="text-center text-gray-500">
                        <svg className="w-16 h-16 mx-auto mb-2" fill="currentColor" viewBox="0 0 20 20">
                          <path fillRule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clipRule="evenodd" />
                        </svg>
                        <p className="text-sm">وێنە نییە</p>
                      </div>
                    </div>
                  )}
                </div>

                <div className="p-8 lg:p-12">
                  <div className="mb-8">
                    <h2 className="text-3xl font-light text-primary mb-4 tracking-wide">{product.title}</h2>
                    <p className="text-accent leading-relaxed mb-6">{product.description}</p>
                    
                    <div className="flex items-center gap-4 mb-8">
                      <span className="text-2xl font-medium text-primary">
                        {Number(product.price).toLocaleString()} $
                      </span>
                      {product.original_price && Number(product.original_price) > Number(product.price) && (
                        <span className="text-lg text-accent line-through">
                          {Number(product.original_price).toLocaleString()} $
                        </span>
                      )}
                    </div>
                  </div>

                  <div className="space-y-8">
                    <div>
                      <h4 className="text-sm font-medium text-primary mb-4 tracking-wide uppercase">ڕەنگ</h4>
                      <div className="flex gap-3">
                        {product.colors.map((color, i) => (
                          <button
                            key={i}
                            onClick={() => {
                              setSelectedColor(color);
                              setImageError(false);
                            }}
                            className={`w-8 h-8 border-2 transition ${
                              selectedColor.name === color.name 
                                ? 'border-primary scale-110' 
                                : 'border-gray-300 hover:border-gray-400'
                            }`}
                            style={{ backgroundColor: color.value }}
                            title={color.name}
                          />
                        ))}
                      </div>
                      <p className="text-sm text-accent mt-2">{selectedColor.name}</p>
                    </div>

                    <div>
                      <h4 className="text-sm font-medium text-primary mb-4 tracking-wide uppercase">قەبارە</h4>
                      <div className="flex gap-2 flex-wrap">
                        {product.sizes.map(size => (
                          <button 
                            key={size} 
                            onClick={() => setSelectedSize(size)} 
                            
                          >
                            {size}
                          </button>
                        ))}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      );
    }

    // Collection Section Component
    function CollectionSection({ title, products, sectionId }) {
      const [selectedProduct, setSelectedProduct] = React.useState(null);

      // Debug: Log products to see what we're getting
      React.useEffect(() => {
        console.log(`${title} products:`, products);
        products.forEach((product, index) => {
          console.log(`Product ${index}:`, {
            title: product.title,
            image: product.image,
            colors: product.colors
          });
        });
      }, [products, title]);

      return (
        <section id={sectionId} className="py-20">
          <div className="text-center mb-16">
            <h2 className="text-3xl font-light text-primary mb-4 tracking-wide animate-fade-up">
              {title}
            </h2>
            <div className="section-divider w-24 mx-auto"></div>
          </div>
          
          {products.length > 0 ? (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 lg:gap-12">
              {products.map((product, index) => 
                <ProductCard 
                  key={product.id} 
                  product={product} 
                  onClick={setSelectedProduct} 
                  index={index} 
                />
              )}
            </div>
          ) : (
            <div className="text-center py-12">
              <p className="text-gray-500">هیچ بەرهەمێک نەدۆزرایەوە</p>
            </div>
          )}

          {selectedProduct && (
            <ProductDetailModal
              product={selectedProduct}
              onClose={() => setSelectedProduct(null)}
            />
          )}
        </section>
      );
    }

    // Filter Component
    function FilterSection({ categories, selectedCategory, onCategoryChange }) {
      return (
        <div className="text-center mb-16">
          <div className="flex flex-wrap justify-center gap-4">
            {categories.map(category => (
              <button
                key={category}
                onClick={() => onCategoryChange(category)}
                className={`filter-button ${selectedCategory === category ? 'active' : ''}`}
              >
                {category}
              </button>
            ))}
          </div>
        </div>
      );
    }

    // Main App Component
    function App() {
      const [loading, setLoading] = React.useState(true);
      const [selectedCategory, setSelectedCategory] = React.useState('هەموو');
      const [products, setProducts] = React.useState([]);
      // Use featured products from PHP only
      const [featuredProducts, setFeaturedProducts] = React.useState(window.featuredProducts || []);

      // Get unique categories from products
      const categories = ['هەموو', ...new Set(products.map(p => p.category))];
      
      // Filter products based on selected category
      const filteredProducts = selectedCategory === 'هەموو' 
        ? products 
        : products.filter(p => p.category === selectedCategory);

      React.useEffect(() => {
        setTimeout(() => {
          setLoading(false);
          document.getElementById('loading-screen').style.display = 'none';
        }, 1500);

        // Mobile menu functionality
        const menuToggle = document.getElementById('menu-toggle');
        const mobileMenu = document.getElementById('mobile-menu');
        
        const toggleMenu = () => mobileMenu.classList.toggle('hidden');
        menuToggle.addEventListener('click', toggleMenu);

        document.querySelectorAll('.mobile-link').forEach(link => {
          link.addEventListener('click', () => mobileMenu.classList.add('hidden'));
        });

        // Fetch products from API for all products section only
        fetch('products_api.php')
          .then(res => res.json())
          .then(data => {
            setProducts(data);
          });

        // Set featured products from PHP (window.featuredProducts)
        setFeaturedProducts(window.featuredProducts || []);

        return () => menuToggle.removeEventListener('click', toggleMenu);
      }, []);

      if (loading) {
        return null;
      }

      return (
        <div className="space-y-0">
          <CollectionSection 
            title="بەرهەمە ناوازەکان" 
            products={featuredProducts} 
            sectionId="featured"
          />
          
          <div className="bg-light py-2">
            <div className="section-divider"></div>
          </div>
          
          <section id="collections" className="py-20">
            <div className="text-center mb-16">
              <h2 className="text-3xl font-light text-primary mb-4 tracking-wide animate-fade-up">
                هەموو بەرهەمەکان
              </h2>
              <div className="section-divider w-24 mx-auto mb-12"></div>
            </div>
            
            <FilterSection 
              categories={categories}
              selectedCategory={selectedCategory}
              onCategoryChange={setSelectedCategory}
            />
            
            <FilteredProductsSection products={filteredProducts} />
          </section>
        </div>
      );
    }

    // Filtered Products Section Component
    function FilteredProductsSection({ products }) {
      const [selectedProduct, setSelectedProduct] = React.useState(null);

      return (
        <div>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 lg:gap-12">
            {products.map((product, index) => 
              <ProductCard 
                key={product.id} 
                product={product} 
                onClick={setSelectedProduct} 
                index={index} 
              />
            )}
          </div>

          {selectedProduct && (
            <ProductDetailModal
              product={selectedProduct}
              onClose={() => setSelectedProduct(null)}
            />
          )}
        </div>
      );
    }

    ReactDOM.render(<App />, document.getElementById('root'));
  </script>
    <!-- Lazy Loading Script -->
    <script>
    // Lazy Loading Implementation
    document.addEventListener('DOMContentLoaded', function() {
        const lazyImages = document.querySelectorAll('img[data-src]');

        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });

        lazyImages.forEach(img => imageObserver.observe(img));

        // Search Functionality
        const searchInput = document.getElementById('search-input');
        const searchResults = document.getElementById('search-results');

        if (searchInput && searchResults) {
            let searchTimeout;

            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const query = this.value.trim();

                if (query.length < 2) {
                    searchResults.classList.add('hidden');
                    return;
                }

                searchTimeout = setTimeout(() => {
                    fetch(`search.php?q=${encodeURIComponent(query)}`)
                        .then(res => res.json())
                        .then(data => {
                            displaySearchResults(data);
                        });
                }, 300);
            });

            function displaySearchResults(results) {
                if (results.length === 0) {
                    searchResults.innerHTML =
                        '<div class="p-4 text-gray-500 text-center">هیچ ئەنجامێک نەدۆزرایەوە</div>';
                } else {
                    searchResults.innerHTML = results.map(product => `
                    <div class="p-3 hover:bg-gray-50 border-b cursor-pointer" onclick="window.location.href='#product-${product.id}'">
                        <div class="flex items-center space-x-3">
                            <img src="${product.image || 'placeholder.jpg'}" alt="${product.title}" class="w-12 h-12 object-cover rounded">
                            <div class="flex-1">
                                <h4 class="font-medium text-primary">${product.title}</h4>
                                <p class="text-sm text-gray-500">${product.price} $</p>
                            </div>
                        </div>
                    </div>
                `).join('');
                }
                searchResults.classList.remove('hidden');
            }

            // Hide search results when clicking outside
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                    searchResults.classList.add('hidden');
                }
            });
        }
    });
    </script>
</body>

</html>