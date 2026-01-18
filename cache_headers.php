<?php
// Cache Headers بۆ Performance
function setCacheHeaders($type = 'default') {
    switch($type) {
        case 'images':
            header('Cache-Control: public, max-age=31536000'); // 1 year
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
            break;
        case 'css_js':
            header('Cache-Control: public, max-age=2592000'); // 30 days
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 2592000) . ' GMT');
            break;
        case 'html':
            header('Cache-Control: public, max-age=3600'); // 1 hour
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');
            break;
        default:
            header('Cache-Control: public, max-age=86400'); // 1 day
    }
    
    header('Vary: Accept-Encoding');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
}

// HTML Compression
function compressHTML($html) {
    return preg_replace('/\s+/', ' ', trim($html));
}
?>