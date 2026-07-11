<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
requirePost();
authenticatedUserId();

// Do not accept XP, scores, or streaks calculated by the browser. The current
// quiz data and answer keys are client-side, so any browser can forge a result.
// Replace this endpoint with a server-validated quiz-submission flow before
// awarding persistent progress.
respond([
    'success' => false,
    'message' => 'Progress updates are unavailable until quiz results are validated on the server.',
], 501);
