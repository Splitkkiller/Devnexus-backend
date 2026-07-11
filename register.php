<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
requirePost();
require_once __DIR__ . '/db.php';

$data = requestBody();
$name = trim((string) ($data['name'] ?? ''));
$email = strtolower(trim((string) ($data['email'] ?? '')));
$password = (string) ($data['password'] ?? '');

if (strlen($name) < 2 || strlen($name) > 100) {
    respond(['success' => false, 'message' => 'Name must be between 2 and 100 characters'], 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(['success' => false, 'message' => 'Enter a valid email address'], 422);
}
if (strlen($password) < 8) {
    respond(['success' => false, 'message' => 'Password must be at least 8 characters'], 422);
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$xp = 0;
$level = 1;
$coins = 0;
$quizzesTaken = 0;
$avgScore = 0.0;
$masteredTopics = '[]';
$loginStreak = 1;
$quizStreak = 0;
$now = date('Y-m-d H:i:s');

$stmt = $conn->prepare('INSERT INTO users (name, email, password, joined_date, xp, level, coins, quizzesTaken, avgScore, masteredTopics, loginStreak, quizStreak, lastLoginDate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
$stmt->bind_param('ssssiiiidsiis', $name, $email, $passwordHash, $now, $xp, $level, $coins, $quizzesTaken, $avgScore, $masteredTopics, $loginStreak, $quizStreak, $now);

if (!$stmt->execute()) {
    if ($conn->errno === 1062) {
        respond(['success' => false, 'message' => 'An account already exists for this email address'], 409);
    }
    error_log('DevNexus registration failed: ' . $conn->error);
    respond(['success' => false, 'message' => 'Unable to create account'], 500);
}

$user = [
    'id' => $conn->insert_id,
    'name' => $name,
    'email' => $email,
    'joined_date' => $now,
    'xp' => $xp,
    'level' => $level,
    'coins' => $coins,
    'quizzesTaken' => $quizzesTaken,
    'avgScore' => $avgScore,
    'masteredTopics' => $masteredTopics,
    'loginStreak' => $loginStreak,
    'quizStreak' => $quizStreak,
    'lastLoginDate' => $now,
];

respond([
    'success' => true,
    'token' => createToken((int) $user['id'], false),
    'user' => userPayload($user),
], 201);
