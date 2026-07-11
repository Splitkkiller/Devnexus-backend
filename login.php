<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
requirePost();
require_once __DIR__ . '/db.php';

$data = requestBody();
$email = strtolower(trim((string) ($data['email'] ?? '')));
$password = (string) ($data['password'] ?? '');
$rememberMe = (bool) ($data['rememberMe'] ?? false);

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    respond(['success' => false, 'message' => 'Invalid email or password'], 422);
}

$stmt = $conn->prepare('SELECT id, name, email, password, joined_date, xp, level, coins, quizzesTaken, avgScore, masteredTopics, loginStreak, quizStreak, lastLoginDate FROM users WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || !password_verify($password, $user['password'])) {
    respond(['success' => false, 'message' => 'Invalid email or password'], 401);
}

respond([
    'success' => true,
    'token' => createToken((int) $user['id'], $rememberMe),
    'user' => userPayload($user),
]);
