<?php
require_once __DIR__ . '/includes/auth.php';
sessionStart();

if (!empty($_SESSION['user'])) {
    redirect('index.php');
}

$appConfig = require __DIR__ . '/config/app.php';
$devMode   = !empty($appConfig['dev_mode']);
$devError  = !empty($_GET['dev_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sign In — <?= htmlspecialchars($appConfig['app_name']) ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    .login-divider {
      display: flex;
      align-items: center;
      gap: 12px;
      margin: 24px 0;
      color: #a0a0a0;
      font-size: 12px;
    }
    .login-divider::before,
    .login-divider::after {
      content: '';
      flex: 1;
      height: 1px;
      background: #e0e0e0;
    }
    .dev-panel {
      background: #fffbeb;
      border: 1px dashed #f59e0b;
      border-radius: 6px;
      padding: 16px;
      text-align: left;
    }
    .dev-panel-title {
      font-size: 11px;
      font-weight: 700;
      letter-spacing: .5px;
      text-transform: uppercase;
      color: #92400e;
      margin-bottom: 10px;
    }
    .dev-panel input {
      width: 100%;
      padding: 8px 10px;
      border: 1px solid #fcd34d;
      border-radius: 5px;
      font-size: 13px;
      font-family: inherit;
      background: #fff;
      margin-bottom: 8px;
      outline: none;
    }
    .dev-panel input:focus { border-color: #f59e0b; }
    .dev-panel button {
      width: 100%;
      padding: 8px;
      background: #f59e0b;
      color: #fff;
      border: none;
      border-radius: 5px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      font-family: inherit;
    }
    .dev-panel button:hover { background: #d97706; }
    .dev-error {
      font-size: 12px;
      color: #b45309;
      margin-top: 6px;
    }
  </style>
</head>
<body>
<div class="login-page">
  <div class="login-card">
    <h1><?= htmlspecialchars($appConfig['app_name']) ?></h1>
    <p><?= htmlspecialchars($appConfig['company_name']) ?></p>

    <a href="api/login.php" class="ms-login-btn">
      <svg xmlns="http://www.w3.org/2000/svg" width="21" height="21" viewBox="0 0 21 21" aria-hidden="true">
        <rect x="1"  y="1"  width="9" height="9" fill="#F25022"/>
        <rect x="11" y="1"  width="9" height="9" fill="#7FBA00"/>
        <rect x="1"  y="11" width="9" height="9" fill="#00A4EF"/>
        <rect x="11" y="11" width="9" height="9" fill="#FFB900"/>
      </svg>
      Sign in with Microsoft
    </a>

    <?php if ($devMode): ?>
    <div class="login-divider">DEV MODE</div>

    <div class="dev-panel">
      <div class="dev-panel-title">⚠ Developer Login — disable in production</div>
      <form method="post" action="api/dev-login.php">
        <input type="text"  name="name"  placeholder="Display name" required value="Dev User">
        <input type="email" name="email" placeholder="Email address" required>
        <button type="submit">Sign in as dev user</button>
      </form>
      <?php if ($devError): ?>
        <p class="dev-error">Please enter a valid name and email.</p>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
