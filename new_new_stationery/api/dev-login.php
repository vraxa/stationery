<?php
require_once dirname(__DIR__) . '/includes/auth.php';
sessionStart();

$appConfig = require dirname(__DIR__) . '/config/app.php';

if (empty($appConfig['dev_mode'])) {
    http_response_code(403);
    echo 'Dev mode is disabled.';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('login.php');
}

$name  = trim($_POST['name']  ?? '');
$email = trim($_POST['email'] ?? '');

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect('login.php?dev_error=1');
}

$_SESSION['user'] = [
    'name'       => $name,
    'email'      => $email,
    'job_title'  => 'Developer',
    'department' => 'Dev',
];

// Sentinel value — tells order.php to simulate instead of calling Graph
$_SESSION['access_token']  = 'DEV_MODE';
$_SESSION['token_expiry']  = PHP_INT_MAX;
$_SESSION['refresh_token'] = '';

redirect('index.php');
