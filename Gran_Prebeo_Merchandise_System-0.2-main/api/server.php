<?php
// Vercel PHP adapter (root): routes requests to PHP files and serves static assets.
// Mirrors Gran_Prebeo_Merchandise_System/api/server.php so Root Directory can be repo root.

declare(strict_types=1);

$repoRoot = realpath(__DIR__ . '/..');
if ($repoRoot === false) {
    http_response_code(500);
    echo 'Server misconfigured';
    exit;
}

// Start from repository root
$root = $repoRoot;

// If the app lives inside a subfolder, route into it
$appSubdir = $root . '/Gran_Prebeo_Merchandise_System';
if (is_dir($appSubdir)) {
    $real = realpath($appSubdir);
    if ($real !== false) {
        $root = $real;
    }
}

$original = $_SERVER['HTTP_X_VERCEL_ORIGINAL_PATHNAME']
    ?? $_SERVER['HTTP_X_FORWARDED_URI']
    ?? ($_SERVER['REQUEST_URI'] ?? '/');
$path = parse_url($original, PHP_URL_PATH) ?: '/';
$path = urldecode($path);

// When Vercel rewrites to /api/server.php, treat it as site root
if ($path === '/api/server.php' || $path === 'api/server.php') {
    $path = '/';
}

// Default route → index.php
if ($path === '' || $path === '/') {
    $target = $root . '/index.php';
} else {
    $target = $root . '/' . ltrim($path, '/');
}

// Prevent path traversal
$realTarget = realpath($target);
if ($realTarget === false || strpos($realTarget, $root) !== 0) {
    // Try appending .php (e.g., /dashboard -> /dashboard.php)
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

// Execute PHP script with proper working directory for relative includes
chdir(dirname($realTarget));
require $realTarget;
