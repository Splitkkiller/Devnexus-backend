<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function respond(array $body, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($body);
    exit;
}

function configureCors(): void
{
    global $allowedOrigins;

    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin !== '' && !in_array($origin, $allowedOrigins, true)) {
        respond(['success' => false, 'message' => 'Origin is not allowed'], 403);
    }

    if ($origin !== '') {
        header("Access-Control-Allow-Origin: {$origin}");
        header('Vary: Origin');
        header('Access-Control-Allow-Credentials: true');
    }

    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

function requirePost(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(['success' => false, 'message' => 'Method not allowed'], 405);
    }
}

function requestBody(): array
{
    $rawBody = file_get_contents('php://input');
    if ($rawBody === false || $rawBody === '') {
        return [];
    }

    $body = json_decode($rawBody, true);
    if (!is_array($body) || json_last_error() !== JSON_ERROR_NONE) {
        respond(['success' => false, 'message' => 'Invalid JSON request body'], 400);
    }

    return $body;
}

function createToken(int $userId, bool $rememberMe): string
{
    global $secretKey;

    $issuedAt = time();
    $expiresAt = $issuedAt + ($rememberMe ? 30 * 24 * 60 * 60 : 2 * 60 * 60);

    return JWT::encode([
        'iat' => $issuedAt,
        'exp' => $expiresAt,
        'user_id' => $userId,
    ], $secretKey, 'HS256');
}

function authenticatedUserId(): int
{
    global $secretKey;

    $authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/^Bearer\s+(\S+)$/i', $authorization, $matches)) {
        respond(['success' => false, 'message' => 'Authentication required'], 401);
    }

    try {
        $decoded = JWT::decode($matches[1], new Key($secretKey, 'HS256'));
        $userId = (int) ($decoded->user_id ?? 0);
        if ($userId <= 0) {
            throw new UnexpectedValueException('Invalid token subject');
        }
        return $userId;
    } catch (Throwable $exception) {
        respond(['success' => false, 'message' => 'Invalid or expired session'], 401);
    }
}

function userPayload(array $user): array
{
    $masteredTopics = json_decode($user['masteredTopics'] ?? '[]', true);

    return [
        'id' => (int) $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'joined_date' => date('F Y', strtotime($user['joined_date'])),
        'stats' => [
            'xp' => (int) $user['xp'],
            'level' => (int) $user['level'],
            'coins' => (int) $user['coins'],
            'quizzesTaken' => (int) $user['quizzesTaken'],
            'avgScore' => (float) $user['avgScore'],
            'masteredTopics' => is_array($masteredTopics) ? $masteredTopics : [],
            'loginStreak' => (int) $user['loginStreak'],
            'quizStreak' => (int) $user['quizStreak'],
            'lastLoginDate' => $user['lastLoginDate'],
        ],
    ];
}

configureCors();
