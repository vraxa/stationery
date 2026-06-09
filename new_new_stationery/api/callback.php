<?php
// SPA redirect URI flow — token exchange must happen in the browser (cross-origin).
// This page validates state server-side, then hands off to JS to finish the exchange.
require_once dirname(__DIR__) . '/includes/auth.php';
sessionStart();

$appConfig = require dirname(__DIR__) . '/config/app.php';
$msConfig  = require dirname(__DIR__) . '/config/microsoft.php';
$appUrl    = rtrim($appConfig['app_url'], '/') . '/';

function failPage(string $msg, string $loginUrl): never {
    http_response_code(400);
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Login error</title>'
       . '<style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;background:#F3F2F1;text-align:center}</style>'
       . '</head><body><div><h2>Login failed</h2><p>' . htmlspecialchars($msg) . '</p>'
       . '<a href="' . htmlspecialchars($loginUrl) . '">Try again</a></div></body></html>';
    exit;
}

// ── Server-side checks ────────────────────────────────────────────
if (empty($_GET['state']) || empty($_SESSION['oauth_state'])) {
    failPage('Invalid OAuth state.', $appUrl . 'login.php');
}
if (!hash_equals($_SESSION['oauth_state'], $_GET['state'])) {
    failPage('OAuth state mismatch.', $appUrl . 'login.php');
}
unset($_SESSION['oauth_state']);

if (!empty($_GET['error'])) {
    failPage($_GET['error_description'] ?? $_GET['error'], $appUrl . 'login.php');
}
if (empty($_GET['code'])) {
    failPage('No authorisation code received.', $appUrl . 'login.php');
}

// Pull the PKCE verifier and mark session as awaiting token storage
$codeVerifier = $_SESSION['pkce_code_verifier'] ?? '';
unset($_SESSION['pkce_code_verifier']);
$_SESSION['awaiting_tokens'] = true;

// Flush session now so the JS follow-up request to store-tokens.php can read it
session_write_close();

// ── Embed values safely for JavaScript ───────────────────────────
$jsCode         = json_encode($_GET['code']);
$jsVerifier     = json_encode($codeVerifier);
$jsRedirectUri  = json_encode($msConfig['redirect_uri']);
$jsScopes       = json_encode($msConfig['scopes']);
$jsClientId     = json_encode($msConfig['client_id']);
$jsTenantId     = json_encode($msConfig['tenant_id']);
$jsStoreUrl     = json_encode($appUrl . 'api/store-tokens.php');
$jsIndexUrl     = json_encode($appUrl . 'index.php');
$jsLoginUrl     = json_encode($appUrl . 'login.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Signing in…</title>
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
           display: flex; align-items: center; justify-content: center;
           height: 100vh; margin: 0; background: #F3F2F1; color: #323130; text-align: center; }
  </style>
</head>
<body>
<p>Signing in, please wait…</p>
<script>
(async () => {
  const code        = <?= $jsCode ?>;
  const verifier    = <?= $jsVerifier ?>;
  const redirectUri = <?= $jsRedirectUri ?>;
  const scopes      = <?= $jsScopes ?>;
  const clientId    = <?= $jsClientId ?>;
  const tenantId    = <?= $jsTenantId ?>;
  const storeUrl    = <?= $jsStoreUrl ?>;
  const indexUrl    = <?= $jsIndexUrl ?>;
  const loginUrl    = <?= $jsLoginUrl ?>;

  function showError(msg) {
    document.body.innerHTML =
      '<div><h2>Login failed</h2><p>' + msg + '</p><a href="' + loginUrl + '">Try again</a></div>';
  }

  try {
    // Cross-origin token exchange — required for SPA redirect URI type
    const tokenRes = await fetch(
      'https://login.microsoftonline.com/' + tenantId + '/oauth2/v2.0/token',
      {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          client_id:     clientId,
          code:          code,
          redirect_uri:  redirectUri,
          grant_type:    'authorization_code',
          code_verifier: verifier,
          scope:         scopes,
        }),
      }
    );

    const tokens = await tokenRes.json();
    if (!tokens.access_token) {
      throw new Error(tokens.error_description || tokens.error || 'Token exchange failed');
    }

    // Hand tokens to PHP session handler (same-origin)
    const storeRes = await fetch(storeUrl, {
      method:      'POST',
      credentials: 'same-origin',
      headers:     { 'Content-Type': 'application/json' },
      body:        JSON.stringify(tokens),
    });

    const result = await storeRes.json();
    if (!result.success) throw new Error(result.error || 'Session storage failed');

    window.location.href = indexUrl;
  } catch (err) {
    showError(err.message);
  }
})();
</script>
</body>
</html>
