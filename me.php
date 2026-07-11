<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/db.php';

$userId = authenticatedUserId();
$stmt = $conn->prepare('SELECT id, name, email, joined_date, xp, level, coins, quizzesTaken, avgScore, masteredTopics, loginStreak, quizStreak, lastLoginDate FROM users WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    respond(['loggedIn' => false, 'message' => 'Account not found'], 401);
}

respond(['loggedIn' => true, 'user' => userPayload($user)]);
