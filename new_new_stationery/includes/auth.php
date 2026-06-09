<?php
// Auth helpers — included by every protected page/endpoint

function sessionStart(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

// Cached absolute base URL (with trailing slash) read from config/app.php
function appUrl(): string {
    static $url = null;
    if ($url === null) {
        $config = require dirname(__DIR__) . '/config/app.php';
        $url    = rtrim($config['app_url'], '/') . '/';
    }
    return $url;
}

function redirect(string $path): never {
    session_write_close(); // flush session before browser follows the redirect
    header('Location: ' . appUrl() . ltrim($path, '/'));
    exit;
}

function requireLogin(): void {
    sessionStart();
    if (empty($_SESSION['user'])) {
        redirect('login.php');
    }
    ensureValidToken();
}

function requireLoginApi(): void {
    sessionStart();
    if (empty($_SESSION['user'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthenticated']);
        exit;
    }
    ensureValidToken();
}

function requireAdminApi(): void {
    requireLoginApi();
    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }
}

function getCurrentUser(): array {
    return $_SESSION['user'] ?? [];
}

function isAdmin(): bool {
    $config = require dirname(__DIR__) . '/config/app.php';
    $user   = getCurrentUser();
    if (empty($user['email'])) {
        return false;
    }
    foreach ($config['admins'] as $admin) {
        if (strtolower($admin) === strtolower($user['email'])) {
            return true;
        }
    }
    return false;
}

function ensureValidToken(): void {
    if (empty($_SESSION['access_token']) || empty($_SESSION['token_expiry'])) {
        return;
    }
    if ($_SESSION['access_token'] === 'DEV_MODE') {
        return;
    }
    if (time() >= $_SESSION['token_expiry'] - 300) {
        refreshToken();
    }
}

function refreshToken(): bool {
    if (empty($_SESSION['refresh_token'])) {
        return false;
    }
    require_once dirname(__DIR__) . '/includes/graph.php';
    $msConfig = require dirname(__DIR__) . '/config/microsoft.php';

    $data = tokenRequest($msConfig['tenant_id'], [
        'client_id'     => $msConfig['client_id'],
        'grant_type'    => 'refresh_token',
        'refresh_token' => $_SESSION['refresh_token'],
        'scope'         => $msConfig['scopes'],
    ]);

    if (empty($data['access_token'])) {
        return false;
    }

    $_SESSION['access_token']  = $data['access_token'];
    $_SESSION['token_expiry']  = time() + (int) ($data['expires_in'] ?? 3600);
    if (!empty($data['refresh_token'])) {
        $_SESSION['refresh_token'] = $data['refresh_token'];
    }
    return true;
}
