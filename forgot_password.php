<?php
// devnexus-api/forgot_password.php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include "db.php";

$data = json_decode(file_get_contents("php://input"), true);
$email = $data['email'] ?? '';

if (empty($email)) {
    echo json_encode(["success" => false, "message" => "Email is required"]);
    exit;
}

// Check if email exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Generate a secure token
    $token = bin2hex(random_bytes(16));
    $token_hash = hash("sha256", $token);
    
    // Set expiry to 1 hour from now
    $expiry = date("Y-m-d H:i:s", time() + 60 * 60);

    // Update DB with token hash and expiry
    $updateStmt = $conn->prepare("UPDATE users SET reset_token_hash = ?, reset_token_expires_at = ? WHERE email = ?");
    $updateStmt->bind_param("sss", $token_hash, $expiry, $email);
    
    if ($updateStmt->execute()) {
        // IN PRODUCTION: Send this link via Email using PHPMailer or SendGrid
        // FOR TESTING: We return it in the JSON so you can see it
        $resetLink = "http://localhost:3000/reset-password?token=" . $token;

        echo json_encode([
            "success" => true, 
            "message" => "Reset link generated (Check console/network tab for link in testing mode)",
            "debug_link" => $resetLink // Remove this line in production!
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Database error"]);
    }
} else {
    // For security, don't reveal if email exists or not, just say sent
    echo json_encode(["success" => true, "message" => "If that email exists, a reset link has been sent."]);
}
?>