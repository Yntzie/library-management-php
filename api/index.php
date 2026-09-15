<?php

$basePath = dirname(__DIR__);
$publicPath = $basePath . '/public';
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$requestPath = trim($requestPath, '/');

if ($requestPath === '' || $requestPath === 'index.php') {
    require $publicPath . '/index.php';
    return;
}

$target = realpath($publicPath . '/' . $requestPath);
$publicRealPath = realpath($publicPath);

if (
    $target !== false
    && $publicRealPath !== false
    && str_starts_with($target, $publicRealPath)
    && pathinfo($target, PATHINFO_EXTENSION) === 'php'
) {
    require $target;
    return;
}

http_response_code(404);
echo 'Not found';

