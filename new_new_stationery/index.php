<?php
require_once __DIR__ . "/includes/auth.php";
requireLogin();

$user = getCurrentUser();
$appConfig = require __DIR__ . "/config/app.php";
$currency = $appConfig["currency_symbol"];
$admin = isAdmin();
?>
<!DOCTYPE html>
<html lang="en" data-currency="<?= htmlspecialchars($currency) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($appConfig["app_name"]) ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- Top bar -->
<nav class="topbar">
  <span class="topbar-brand"><img src="assets/logo_white.png"></span>
  <div class="topbar-user">
    <span><?= htmlspecialchars($user["name"]) ?></span>
    <?php if ($admin): ?>
      <a href="admin.php" class="admin-link">Admin</a>
    <?php endif; ?>
    <a href="api/logout.php">Sign out</a>
  </div>
</nav>

<!-- Main layout -->
<div class="page-layout">

  <!-- Left: search + items -->
  <div>
    <div class="toolbar">
      <div class="search-row">
        <input id="search-input" type="search" placeholder="Search stationery…" autocomplete="off">
      </div>
      <div id="category-chips" class="category-chips">
        <button class="chip active" data-cat="All">All</button>
      </div>
    </div>

    <div id="items-list" class="items-list">
      <p class="no-results">Loading items…</p>
    </div>
  </div>

  <!-- Right: cart -->
  <aside class="cart-panel">
    <div class="cart-header">
      <h2>Cart</h2>
      <span id="cart-badge" class="cart-badge" style="display:none">0</span>
    </div>

    <div id="cart-items" class="cart-items">
      <p class="cart-empty">Your cart is empty.<br>Add items from the list.</p>
    </div>

    <div class="cart-footer">
      <div class="cart-total">
        <span>Total</span>
        <span id="cart-total-val"><?= htmlspecialchars($currency) ?>0.00</span>
      </div>

      <label for="dept-select" class="sr-only">Department</label>
      <select id="dept-select" class="dept-select">
        <option value="">— Select department —</option>
      </select>

      <label for="cart-notes" class="sr-only">Additional notes</label>
      <textarea id="cart-notes" class="cart-notes" placeholder="Additional notes (optional)…"></textarea>

      <button id="btn-order" class="btn btn-primary btn-full" disabled>Place Order</button>
    </div>
  </aside>

</div>

<div id="toast-container"></div>

<script src="assets/js/app.js"></script>
</body>
</html>
