<?php
declare(strict_types=1);

$routes = ['index.php' => __DIR__ . '/../index.php'];
$script = $_GET['script'] ?? 'index.php';
if (!is_string($script) || !isset($routes[$script])) {
    http_response_code(404);
    exit('Halaman tidak ditemukan.');
}
require $routes[$script];
