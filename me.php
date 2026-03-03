<?php
require 'vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

include "db.php";

// Get token from header
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';
$token = str_replace('Bearer ', '', $authHeader);

if (!$token) {
    echo json_encode(["loggedIn" => false]);
    exit;
}

include "config.php";

try {
    $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));

    $stmt = $conn->prepare("
        SELECT name, email, joined_date, xp, level, coins, quizzesTaken, avgScore, masteredTopics, loginStreak, quizStreak, lastLoginDate
        FROM users WHERE id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    echo json_encode([
        "loggedIn" => true,
        "user" => [
            "name" => $user["name"],
            "email" => $user["email"],
            "joined_date" => date("F Y", strtotime($user["joined_date"])),
            "stats" => [
                "xp" => (int)$user["xp"],
                "level" => (int)$user["level"],
                "coins" => (int)$user["coins"],
                "quizzesTaken" => (int)$user["quizzesTaken"],
                "avgScore" => (float)$user["avgScore"],
                "masteredTopics" => json_decode($user["masteredTopics"]),
                "loginStreak" => (int)$user["loginStreak"],
                "quizStreak" => (int)$user["quizStreak"],
                "lastLoginDate" => $user["lastLoginDate"]
            ]
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(["loggedIn" => false, "message" => $e->getMessage()]);
}
?>




