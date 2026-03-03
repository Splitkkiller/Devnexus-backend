<?php
// devnexus-api/reset_password.php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include "db.php";

$data = json_decode(file_get_contents("php://input"), true);
$token = $data['token'] ?? '';
$newPassword = $data['password'] ?? '';

if (empty($token) || empty($newPassword)) {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
    exit;
}

$token_hash = hash("sha256", $token);

// Find user with this token AND check if token is not expired
$sql = "SELECT id FROM users WHERE reset_token_hash = ? AND reset_token_expires_at > NOW()";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $token_hash);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $userId = $user['id'];

    // Hash new password
    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

    // Update password and clear the token fields
    $updateStmt = $conn->prepare("UPDATE users SET password = ?, reset_token_hash = NULL, reset_token_expires_at = NULL WHERE id = ?");
    $updateStmt->bind_param("si", $passwordHash, $userId);

    if ($updateStmt->execute()) {
        echo json_encode(["success" => true, "message" => "Password updated successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to update password"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Token is invalid or expired"]);
}
?>