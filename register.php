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

include "db.php";

$data = json_decode(file_get_contents("php://input"), true);

$name = $data["name"] ?? "";
$email = $data["email"] ?? "";
$password = $data["password"] ?? "";

if (!$name || !$email || !$password) {
    echo json_encode(["success" => false, "message" => "All fields are required"]);
    exit;
}

// Hash password
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// Default stats
$xp = 0;
$level = 1;
$coins = 0;
$quizzesTaken = 0;
$avgScore = 0;
$masteredTopicsArr = []; 
$masteredTopicsJson = json_encode($masteredTopicsArr);
$loginStreak = 1;
$quizStreak = 0;
$now = date("Y-m-d H:i:s");

// Insert into DB
$stmt = $conn->prepare("
    INSERT INTO users 
    (name, email, password, joined_date, xp, level, coins, quizzesTaken, avgScore, masteredTopics, loginStreak, quizStreak, lastLoginDate)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "ssssiiiiisiis",
    $name,
    $email,
    $passwordHash,
    $now, // Using the variable instead of NOW() to keep sync with response
    $xp,
    $level,
    $coins,
    $quizzesTaken,
    $avgScore,
    $masteredTopicsJson,
    $loginStreak,
    $quizStreak,
    $now
);

if ($stmt->execute()) {
    // Format date for the frontend response (e.g., "January 2026")
    $joinedDateFormatted = date("F Y", strtotime($now));

    echo json_encode([
        "success" => true,
        "user" => [
            "name" => $name,
            "email" => $email,
            "joined_date" => $joinedDateFormatted,
            "stats" => [
                "xp" => $xp,
                "level" => $level,
                "coins" => $coins,
                "quizzesTaken" => $quizzesTaken,
                "avgScore" => $avgScore,
                "masteredTopics" => $masteredTopicsArr,
                "loginStreak" => $loginStreak,
                "quizStreak" => $quizStreak,
                "lastLoginDate" => $now
            ]
        ]
    ]);
} else {
    // Check if error is due to duplicate email
    if ($conn->errno === 1062) {
        echo json_encode(["success" => false, "message" => "Email already exists"]);
    } else {
        echo json_encode(["success" => false, "message" => "Registration failed: " . $conn->error]);
    }
}
?>




