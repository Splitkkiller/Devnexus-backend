<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
requirePost();
require_once __DIR__ . '/db.php';

$data = requestBody();
$token = (string) ($data['token'] ?? '');
$newPassword = (string) ($data['password'] ?? '');

if ($token === '' || strlen($newPassword) < 8) {
    respond(['success' => false, 'message' => 'Use a valid reset link and a password of at least 8 characters'], 422);
}

$tokenHash = hash('sha256', $token);
$stmt = $conn->prepare('SELECT id FROM users WHERE reset_token_hash = ? AND reset_token_expires_at > NOW() LIMIT 1');
$stmt->bind_param('s', $tokenHash);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    respond(['success' => false, 'message' => 'This reset link is invalid or expired'], 400);
}

$passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
$updateStmt = $conn->prepare('UPDATE users SET password = ?, reset_token_hash = NULL, reset_token_expires_at = NULL WHERE id = ?');
$updateStmt->bind_param('si', $passwordHash, $user['id']);

if (!$updateStmt->execute()) {
    error_log('DevNexus password reset failed: ' . $conn->error);
    respond(['success' => false, 'message' => 'Unable to update password'], 500);
}

respond(['success' => true, 'message' => 'Password updated successfully']);
