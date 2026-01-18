<?php
function optimizeImage($source, $destination, $quality = 80) {
    $info = getimagesize($source);
    
    if (!$info) return false;
    
    switch ($info[2]) {
        case IMAGETYPE_JPEG:
            $image = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $image = imagecreatefrompng($source);
            break;
        case IMAGETYPE_WEBP:
            $image = imagecreatefromwebp($source);
            break;
        default:
            return false;
    }
    
    // WebP format بۆ نوێترین browsers
    if (function_exists('imagewebp')) {
        $webpDestination = str_replace(['.jpg', '.jpeg', '.png'], '.webp', $destination);
        imagewebp($image, $webpDestination, $quality);
    }
    
    // JPEG compression
    imagejpeg($image, $destination, $quality);
    imagedestroy($image);
    
    return true;
}

// Lazy Loading Image Function
function getLazyImage($src, $alt = '', $class = '') {
    $webpSrc = str_replace(['.jpg', '.jpeg', '.png'], '.webp', $src);
    
    return sprintf(
        '<picture>
            <source srcset="%s" type="image/webp">
            <img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" 
                 data-src="%s" 
                 alt="%s" 
                 class="lazy %s"
                 loading="lazy">
        </picture>',
        $webpSrc,
        $src,
        htmlspecialchars($alt),
        $class
    );
}
?>