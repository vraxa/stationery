<?php
require_once dirname(__DIR__) . '/includes/auth.php';
sessionStart();

$msConfig  = require dirname(__DIR__) . '/config/microsoft.php';
$appConfig = require dirname(__DIR__) . '/config/app.php';

session_destroy();

// Sign out of Microsoft SSO as well
$params = http_build_query([
    'post_logout_redirect_uri' => rtrim($appConfig['app_url'], '/') . '/login.php',
]);
$logoutUrl = "https://login.microsoftonline.com/{$msConfig['tenant_id']}/oauth2/v2.0/logout?{$params}";

header('Location: ' . $logoutUrl);
exit;
