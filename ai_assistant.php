<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
requirePost();

// Require a logged-in user so this endpoint (and your Gemini quota) isn't
// open to anyone who finds the URL — it's not just the API key we're
// protecting, it's the usage/cost behind it.
authenticatedUserId();

if (!isset($geminiApiKey) || !is_string($geminiApiKey) || $geminiApiKey === '') {
    respond(['success' => false, 'message' => 'AI service is not configured on the server'], 500);
}

$data = requestBody();
$question = trim((string) ($data['question'] ?? ''));
$contextDocName = trim((string) ($data['contextDocName'] ?? ''));

if ($question === '') {
    respond(['success' => false, 'message' => 'Enter a question'], 422);
}
if (mb_strlen($question) > 2000) {
    respond(['success' => false, 'message' => 'Question is too long (2000 character limit)'], 422);
}

$prompt = "You are an expert senior frontend engineer and HTML documentation assistant.\n"
    . "Answer the user's question clearly, concisely, and professionally.\n"
    . "Use Markdown for formatting. Code blocks should be formatted with the language (e.g. ```html).\n\n"
    . "User Question: {$question}";

if ($contextDocName !== '') {
    $prompt = "You are an expert senior frontend engineer assisting a user who is currently looking at documentation for the <{$contextDocName}> tag.\n"
        . "Answer their question specifically in the context of this tag if relevant, or general HTML knowledge if not.\n"
        . "Keep it concise and helpful.\n\n"
        . "User Question: {$question}";
}

$model = 'gemini-2.5-flash';
$url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($geminiApiKey);

$payload = json_encode([
    'contents' => [
        ['parts' => [['text' => $prompt]]],
    ],
]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_TIMEOUT => 30,
]);

$responseBody = curl_exec($ch);
$curlError = curl_error($ch);
$httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($responseBody === false) {
    error_log('DevNexus Gemini request failed: ' . $curlError);
    respond(['success' => false, 'message' => 'Failed to reach the AI service'], 502);
}

$decoded = json_decode($responseBody, true);

if ($httpStatus !== 200 || !is_array($decoded)) {
    $errorMessage = is_array($decoded) ? ($decoded['error']['message'] ?? null) : null;
    error_log("DevNexus Gemini API error ({$httpStatus}): {$responseBody}");
    respond([
        'success' => false,
        'message' => $errorMessage ?? 'The AI service returned an error',
    ], 502);
}

$text = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? null;

if (!is_string($text) || $text === '') {
    respond(['success' => false, 'message' => 'No response generated.'], 502);
}

respond(['success' => true, 'text' => $text]);
