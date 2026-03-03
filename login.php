<?php
require 'vendor/autoload.php';
use Firebase\JWT\JWT;

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include "db.php";

$secretKey = "YOUR_SECRET_KEY"; // Must match your other scripts
$data = json_decode(file_get_contents("php://input"), true);

if (!empty($data['email']) && !empty($data['password'])) {
    $email = $data['email'];
    $password = $data['password'];
    $rememberMe = $data['rememberMe'] ?? false;

    $stmt = $conn->prepare("SELECT id, name, email, password, xp, quizzesTaken, quizStreak FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            // Logic for "Remember Me"
            $duration = $rememberMe ? (30 * 24 * 60 * 60) : 7200; // 30 days vs 2 hours
            $issuedAt = time();
            $expirationTime = $issuedAt + $duration;

            $payload = [
                "iat" => $issuedAt,
                "exp" => $expirationTime,
                "user_id" => $user['id']
            ];

            $jwt = JWT::encode($payload, $secretKey, 'HS256');

            echo json_encode([
                "success" => true,
                "token" => $jwt,
                "user" => [
                    "id" => $user['id'],
                    "name" => $user['name'],
                    "email" => $user['email'],
                    "stats" => [
                        "xp" => (int)$user['xp'],
                        "quizzesTaken" => (int)$user['quizzesTaken'],
                        "quizStreak" => (int)$user['quizStreak']
                    ]
                ]
            ]);
        } else {
            echo json_encode(["success" => false, "message" => "Invalid password"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "User not found"]);
    }
}
?>

