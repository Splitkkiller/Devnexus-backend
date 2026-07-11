<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
requirePost();
require_once __DIR__ . '/db.php';

$data = requestBody();
$email = strtolower(trim((string) ($data['email'] ?? '')));
$message = 'If an account exists for that email address, a reset link has been sent.';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(['success' => true, 'message' => $message]);
}

$stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($user) {
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expiry = date('Y-m-d H:i:s', time() + 60 * 60);

    $updateStmt = $conn->prepare('UPDATE users SET reset_token_hash = ?, reset_token_expires_at = ? WHERE id = ?');
    $updateStmt->bind_param('ssi', $tokenHash, $expiry, $user['id']);
    $updateStmt->execute();

    // Send the reset URL with a mail provider here. Never return the token in
    // an API response, even while testing, because it can be logged or cached.
}

respond(['success' => true, 'message' => $message]);
