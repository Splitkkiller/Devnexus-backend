<?php
declare(strict_types=1);

if (!isset($databaseConfig)) {
    require_once __DIR__ . '/config.php';
}

$conn = new mysqli(
    $databaseConfig['host'],
    $databaseConfig['user'],
    $databaseConfig['pass'],
    $databaseConfig['name']
);

if ($conn->connect_error) {
    error_log('DevNexus database connection failed: ' . $conn->connect_error);
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Database service is unavailable']);
    exit;
}

$conn->set_charset('utf8mb4');
