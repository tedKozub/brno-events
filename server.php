<?php

// Prevent PHP session from sending legacy no-store headers that break bfcache
ini_set('session.cache_limiter', '');

// Enable transparent gzip/deflate compression for fast document and asset delivery
if (!headers_sent() && extension_loaded('zlib')) {
    ini_set('zlib.output_compression', 'On');
    ini_set('zlib.output_compression_level', '6');
}

$publicPath = __DIR__.'/public';
$realPublicPath = realpath($publicPath);
$realStoragePath = realpath(__DIR__.'/storage/app/public');

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? ''
);

$filePath = $publicPath . $uri;
$realFilePath = realpath($filePath);

// Strictly enforce path containment: file must resolve inside public/ or storage/app/public/
$isContained = $realFilePath !== false && is_file($realFilePath) && (
    ($realPublicPath && str_starts_with($realFilePath, $realPublicPath . DIRECTORY_SEPARATOR)) ||
    ($realStoragePath && str_starts_with($realFilePath, $realStoragePath . DIRECTORY_SEPARATOR))
);

// If the requested path is an existing safe static file, serve it with high-efficiency caching
if ($uri !== '/' && $isContained) {
    $ext = strtolower(pathinfo($realFilePath, PATHINFO_EXTENSION));
    $mimeTypes = [
        'css' => 'text/css; charset=utf-8',
        'js' => 'application/javascript; charset=utf-8',
        'json' => 'application/json; charset=utf-8',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'txt' => 'text/plain; charset=utf-8',
        'xml' => 'application/xml; charset=utf-8',
    ];

    if (isset($mimeTypes[$ext])) {
        // Immutable 1-year caching for fingerprinted assets, uploads, and icons
        if (
            str_starts_with($uri, '/build/') ||
            str_starts_with($uri, '/storage/') ||
            str_starts_with($uri, '/vendor/') ||
            str_starts_with($uri, '/icons/') ||
            str_starts_with($uri, '/favicon')
        ) {
            header('Cache-Control: public, max-age=31536000, immutable');
        } else {
            header('Cache-Control: public, max-age=86400');
        }

        header('Content-Type: ' . $mimeTypes[$ext]);
        header('Content-Length: ' . filesize($realFilePath));
        header('Access-Control-Allow-Origin: *');

        // ETag and Last-Modified conditional validation
        $mtime = filemtime($realFilePath);
        $etag = sprintf('"%x-%x"', $mtime, filesize($realFilePath));
        header('ETag: ' . $etag);
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');

        if (
            (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) ||
            (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && @strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $mtime)
        ) {
            http_response_code(304);
            exit;
        }

        readfile($realFilePath);
        exit;
    }

    return false;
}

$formattedDateTime = date('D M j H:i:s Y');
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$remoteAddress = ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1') . ':' . ($_SERVER['REMOTE_PORT'] ?? '80');

file_put_contents('php://stdout', "[$formattedDateTime] $remoteAddress [$requestMethod] URI: $uri\n");

require_once $publicPath . '/index.php';
