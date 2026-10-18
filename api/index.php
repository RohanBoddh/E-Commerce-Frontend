<?php
// Vercel Serverless Entrypoint for E-Commerce Frontend

$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$requestUri = trim($requestUri, '/');

if ($requestUri === '' || $requestUri === 'index.php') {
    $target = __DIR__ . '/../index.php';
} else {
    $target = __DIR__ . '/../' . $requestUri;
}

if (!file_exists($target)) {
    if (file_exists($target . '.php')) {
        $target = $target . '.php';
    } else {
        http_response_code(404);
        echo "<!DOCTYPE html><html><head><title>404 Not Found</title></head><body style='font-family:sans-serif;text-align:center;padding:50px;'><h1>404 Not Found</h1><p>The requested page was not found.</p><p><a href='/'>Return to Store</a></p></body></html>";
        exit;
    }
}

chdir(dirname($target));
require $target;
