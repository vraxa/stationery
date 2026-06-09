<?php
// Receives the token payload from callback.php's browser-side exchange and stores it in the session.
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/graph.php';
sessionStart();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit;
}

// Must be part of the legitimate OAuth flow
if (empty($_SESSION['awaiting_tokens'])) {
    http_response_code(403); echo json_encode(['error' => 'Forbidden']); exit;
}
unset($_SESSION['awaiting_tokens']);

$tokens = json_decode(file_get_contents('php://input'), true) ?? [];
if (empty($tokens['access_token'])) {
    http_response_code(422); echo json_encode(['error' => 'No access token provided']); exit;
}

$user = graphGetUser($tokens['access_token']);
if (empty($user['email'])) {
    http_response_code(502); echo json_encode(['error' => 'Could not retrieve user profile from Microsoft']); exit;
}

$_SESSION['user']          = $user;
$_SESSION['access_token']  = $tokens['access_token'];
$_SESSION['token_expiry']  = time() + (int) ($tokens['expires_in'] ?? 3600);
$_SESSION['refresh_token'] = $tokens['refresh_token'] ?? '';

echo json_encode(['success' => true]);
