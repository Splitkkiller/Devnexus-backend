<?php
require 'vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

include "db.php";
$secretKey = "YOUR_SECRET_KEY"; // Must match login.php

$data = json_decode(file_get_contents("php://input"), true);
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';

if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    $jwt = $matches[1];
    try {
        $decoded = JWT::decode($jwt, new Key($secretKey, 'HS256'));
        $userId = $decoded->user_id;

        // Get values from frontend request
        $xp = $data['xp'] ?? 0;
        $quizzesTaken = $data['quizzesTaken'] ?? 0;
        $quizStreak = $data['quizStreak'] ?? 0;

        // Update the database
        // We use "+" in the SQL to ensure we are incrementing correctly
        $stmt = $conn->prepare("
            UPDATE users 
            SET xp = xp + ?, 
                quizzesTaken = quizzesTaken + 1, 
                quizStreak = ? 
            WHERE id = ?
        ");
        
        $stmt->bind_param("iii", $xp, $quizStreak, $userId);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Stats updated"]);
        } else {
            echo json_encode(["success" => false, "message" => "Database error"]);
        }

    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => "Unauthorized"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "No token provided"]);
}
?>