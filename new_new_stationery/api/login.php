<?php
require_once dirname(__DIR__) . '/includes/auth.php';
sessionStart();

// If already logged in, skip straight to the app
if (!empty($_SESSION['user'])) {
    redirect('index.php');
}

$msConfig = require dirname(__DIR__) . '/config/microsoft.php';

$state        = bin2hex(random_bytes(16));
$codeVerifier = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
$codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

$_SESSION['oauth_state']        = $state;
$_SESSION['pkce_code_verifier'] = $codeVerifier;

$params = http_build_query([
    'client_id'             => $msConfig['client_id'],
    'response_type'         => 'code',
    'redirect_uri'          => $msConfig['redirect_uri'],
    'scope'                 => $msConfig['scopes'],
    'state'                 => $state,
    'response_mode'         => 'query',
    'prompt'                => 'select_account',
    'code_challenge'        => $codeChallenge,
    'code_challenge_method' => 'S256',
]);

$authUrl = "https://login.microsoftonline.com/{$msConfig['tenant_id']}/oauth2/v2.0/authorize?{$params}";

session_write_close();
header('Location: ' . $authUrl);
exit;
