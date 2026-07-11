<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
requirePost();

// JWTs are stateless. The browser removes its token; token revocation, if
// needed later, should be implemented with a server-side deny-list.
respond(['success' => true, 'message' => 'Logout successful']);
