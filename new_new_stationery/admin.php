<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

if (!isAdmin()) {
    http_response_code(403);
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><link rel="stylesheet" href="assets/css/style.css"></head>'
       . '<body><div style="text-align:center;padding:80px 20px;">'
       . '<h2>Access Denied</h2><p>You do not have admin privileges.</p>'
       . '<a href="index.php">Return to ordering</a></div></body></html>';
    exit;
}

$user      = getCurrentUser();
$appConfig = require __DIR__ . '/config/app.php';
$currency  = $appConfig['currency_symbol'];
?>
<!DOCTYPE html>
<html lang="en" data-currency="<?= htmlspecialchars($currency) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin — <?= htmlspecialchars($appConfig['app_name']) ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- Top bar -->
<nav class="topbar">
  <a href="index.php" class="topbar-brand"><?= htmlspecialchars($appConfig['app_name']) ?></a>
  <div class="topbar-user">
    <span><?= htmlspecialchars($user['name']) ?> &mdash; Admin</span>
    <a href="index.php">Order page</a>
    <a href="api/logout.php">Sign out</a>
  </div>
</nav>

<div class="admin-layout">

  <div class="tabs">
    <button class="tab-btn active" data-tab="tab-items">Items</button>
    <button class="tab-btn"        data-tab="tab-depts">Departments</button>
  </div>

  <!-- ── Items tab ──────────────────────────────────────────────── -->
  <div id="tab-items" class="tab-panel active">

    <div class="card">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
        <h3 style="margin:0;">Stationery Items</h3>
        <button class="btn btn-primary btn-sm" onclick="openItemModal(null)">+ Add Item</button>
      </div>
      <div style="overflow-x:auto;">
        <table class="data-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Category</th>
              <th>Unit</th>
              <th>Price</th>
              <th>Description</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="items-tbody">
            <tr><td colspan="6" class="no-results">Loading…</td></tr>
          </tbody>
        </table>
      </div>
    </div>

  </div>

  <!-- ── Departments tab ────────────────────────────────────────── -->
  <div id="tab-depts" class="tab-panel">

    <div class="card">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
        <h3 style="margin:0;">Departments</h3>
        <button class="btn btn-primary btn-sm" onclick="openDeptModal(null)">+ Add Department</button>
      </div>
      <div style="overflow-x:auto;">
        <table class="data-table">
          <thead>
            <tr>
              <th>Department</th>
              <th>Head Name</th>
              <th>Email</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="depts-tbody">
            <tr><td colspan="4" class="no-results">Loading…</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card" style="background:#FFF9F0;border-left:4px solid #FFB900;">
      <h3 style="color:#605E5C;margin-bottom:4px;">Admin Users</h3>
      <p style="font-size:13px;color:#797775;">Admin users are configured in <code>config/app.php</code> under the <code>admins</code> array. Edit that file directly to add or remove admins.</p>
      <p style="font-size:13px;color:#797775;margin-top:8px;">Current admins:</p>
      <ul style="font-size:13px;margin-left:20px;margin-top:4px;">
        <?php foreach ($appConfig['admins'] as $a): ?>
          <li><?= htmlspecialchars($a) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>

  </div>

</div><!-- /admin-layout -->

<!-- ── Item modal ──────────────────────────────────────────────── -->
<div id="item-modal" class="modal-backdrop">
  <div class="modal">
    <h3 id="item-modal-title">Add Item</h3>
    <form id="item-form" novalidate>
      <input type="hidden" id="item-id">
      <div class="form-grid">
        <div class="form-group" style="grid-column:1/-1;">
          <label for="item-name">Name *</label>
          <input id="item-name" type="text" required maxlength="200" placeholder="e.g. A4 Paper (500 sheets)">
        </div>
        <div class="form-group">
          <label for="item-category">Category *</label>
          <input id="item-category" type="text" required maxlength="100" list="category-list" placeholder="e.g. Paper">
          <datalist id="category-list"></datalist>
        </div>
        <div class="form-group">
          <label for="item-unit">Unit *</label>
          <input id="item-unit" type="text" required maxlength="50" placeholder="e.g. ream, box, each">
        </div>
        <div class="form-group">
          <label for="item-price">Price (<?= htmlspecialchars($currency) ?>) *</label>
          <input id="item-price" type="number" required min="0" step="0.01" placeholder="0.00">
        </div>
        <div class="form-group" style="grid-column:1/-1;">
          <label for="item-description">Description</label>
          <input id="item-description" type="text" maxlength="500" placeholder="Short description">
        </div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-outline" onclick="closeItemModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>

<!-- ── Department modal ────────────────────────────────────────── -->
<div id="dept-modal" class="modal-backdrop">
  <div class="modal">
    <h3 id="dept-modal-title">Add Department</h3>
    <form id="dept-form" novalidate>
      <input type="hidden" id="dept-id">
      <div class="form-grid">
        <div class="form-group" style="grid-column:1/-1;">
          <label for="dept-name">Department Name *</label>
          <input id="dept-name" type="text" required maxlength="200" placeholder="e.g. Finance Department">
        </div>
        <div class="form-group">
          <label for="dept-head">Head Name</label>
          <input id="dept-head" type="text" maxlength="200" placeholder="e.g. Jane Smith">
        </div>
        <div class="form-group">
          <label for="dept-email">Head Email *</label>
          <input id="dept-email" type="email" required placeholder="e.g. finance@company.com">
        </div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-outline" onclick="closeDeptModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>

<div id="toast-container"></div>

<script src="assets/js/admin.js"></script>
</body>
</html>
