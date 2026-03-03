<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Since JWT is stateless, the server doesn't "destroy" it.
// The client (React/Vue) simply removes the token from LocalStorage/Cookies.

echo json_encode([
    "success" => true,
    "message" => "Logout successful"
]);
?>