<?php
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/graph.php';
sessionStart();

function fail(string $message): void {
    http_response_code(400);
    echo htmlspecialchars($message);
    exit;
}

// Validate state to prevent CSRF
if (empty($_GET['state']) || empty($_SESSION['oauth_state'])) {
    fail('Invalid OAuth state.');
}
if (!hash_equals($_SESSION['oauth_state'], $_GET['state'])) {
    fail('OAuth state mismatch.');
}
unset($_SESSION['oauth_state']);

if (!empty($_GET['error'])) {
    fail('Login failed: ' . htmlspecialchars($_GET['error_description'] ?? $_GET['error']));
}

if (empty($_GET['code'])) {
    fail('No authorisation code received.');
}

$msConfig = require dirname(__DIR__) . '/config/microsoft.php';

// Exchange authorisation code for tokens (PKCE: include the verifier stored at login)
$codeVerifier = $_SESSION['pkce_code_verifier'] ?? '';
unset($_SESSION['pkce_code_verifier']);

// Public client — no client_secret; PKCE code_verifier is the proof of identity
$tokens = tokenRequest($msConfig['tenant_id'], [
    'client_id'     => $msConfig['client_id'],
    'code'          => $_GET['code'],
    'redirect_uri'  => $msConfig['redirect_uri'],
    'grant_type'    => 'authorization_code',
    'scope'         => $msConfig['scopes'],
    'code_verifier' => $codeVerifier,
]);

if (empty($tokens['access_token'])) {
    fail('Token exchange failed: ' . ($tokens['error_description'] ?? 'Could not contact Microsoft login servers.'));
}

// Fetch user profile from Graph
$user = graphGetUser($tokens['access_token']);
if (empty($user['email'])) {
    fail('Could not retrieve user profile from Microsoft.');
}

// Store in session
$_SESSION['user']          = $user;
$_SESSION['access_token']  = $tokens['access_token'];
$_SESSION['token_expiry']  = time() + (int) ($tokens['expires_in'] ?? 3600);
$_SESSION['refresh_token'] = $tokens['refresh_token'] ?? '';

redirect('index.php');
