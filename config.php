<?php
declare(strict_types=1);

$localConfigPath = __DIR__ . '/config.local.php';
$localConfig = file_exists($localConfigPath) ? require $localConfigPath : [];

function setting(string $name, array $localConfig, string $localKey, ?string $default = null): ?string
{
    $environmentValue = getenv($name);
    if (is_string($environmentValue) && $environmentValue !== '') {
        return $environmentValue;
    }

    return $localConfig[$localKey] ?? $default;
}

$secretKey = setting('JWT_SECRET', $localConfig, 'jwt_secret');
if (!is_string($secretKey) || strlen($secretKey) < 32) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Server authentication is not configured']);
    exit;
}

$databaseConfig = [
    'host' => setting('DB_HOST', $localConfig, 'db_host', 'localhost'),
    'name' => setting('DB_NAME', $localConfig, 'db_name', 'devnexus'),
    'user' => setting('DB_USER', $localConfig, 'db_user', 'root'),
    'pass' => setting('DB_PASS', $localConfig, 'db_pass', ''),
];

$origins = setting('CORS_ALLOWED_ORIGINS', $localConfig, 'cors_allowed_origins', 'http://localhost:3000');
$allowedOrigins = array_values(array_filter(array_map('trim', explode(',', $origins))));

// Only the AI assistant endpoint needs this, so a missing key does not take
// down the rest of the API — ai_assistant.php checks for it itself.
$geminiApiKey = setting('GEMINI_API_KEY', $localConfig, 'gemini_api_key');
