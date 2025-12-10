<?php
// Vercel PHP adapter: routes requests to PHP files and serves static assets.
// This enables deploying this PHP app to Vercel using vercel-php runtime.

declare(strict_types=1);

$root = realpath(__DIR__ . '/..');
if ($root === false) {
    http_response_code(500);
    echo 'Server misconfigured';
    exit;
}

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH) ?: '/';
$path = urldecode($path);

// Default route
if ($path === '' || $path === '/') {
    $target = $root . '/index.php';
} else {
    $target = $root . '/' . ltrim($path, '/');
}

// Prevent path traversal
$realTarget = realpath($target);
if ($realTarget === false || strpos($realTarget, $root) !== 0) {
    // If the request omits .php (e.g., /dashboard), try appending .php
    $guess = realpath($target . '.php');
    if ($guess === false || strpos($guess, $root) !== 0) {
        http_response_code(404);
        echo 'Not Found';
        exit;
    }
    $realTarget = $guess;
}

// Serve static assets directly when not a PHP script
if (!preg_match('/\.php$/i', $realTarget)) {
    if (is_file($realTarget)) {
        $ext = strtolower(pathinfo($realTarget, PATHINFO_EXTENSION));
        $mimes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'webp' => 'image/webp',
            'json' => 'application/json',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'map' => 'application/json',
            'txt' => 'text/plain',
            'html' => 'text/html',
        ];
        $mime = $mimes[$ext] ?? 'application/octet-stream';
        header('Content-Type: ' . $mime);
        readfile($realTarget);
        exit;
    }

    // If non-existent static, try PHP fallback by adding .php
    $phpFallback = $realTarget . '.php';
    if (is_file($phpFallback)) {
        $realTarget = $phpFallback;
    } else {
        http_response_code(404);
        echo 'Not Found';
        exit;
    }
}

// Execute PHP script; set CWD so relative includes work
chdir(dirname($realTarget));
require $realTarget;
