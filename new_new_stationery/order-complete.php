<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$lastOrder = $_SESSION['last_order'] ?? null;
if (!$lastOrder) {
    redirect('index.php');
}

$appConfig = require __DIR__ . '/config/app.php';
$items     = $lastOrder['items'];
$dept      = $lastOrder['department'];
$user      = $lastOrder['user'];
$total     = $lastOrder['total'];
$currency  = $lastOrder['currency'];
$notes     = $lastOrder['notes'] ?? '';
$orderedAt = $lastOrder['ordered_at'];

$rowsHtml = '';
foreach ($items as $row) {
    $sku = $row['sku'] ?? '';
    $rowsHtml .= '<tr>'
        . '<td>' . htmlspecialchars($sku)           . '</td>'
        . '<td>' . htmlspecialchars($row['name'])    . '</td>'
        . '<td style="text-align:center;">' . (int) $row['quantity'] . '</td>'
        . '<td>' . htmlspecialchars($row['unit'])    . '</td>'
        . '<td style="text-align:right;">' . htmlspecialchars($currency) . number_format($row['price'],   2) . '</td>'
        . '<td style="text-align:right;">' . htmlspecialchars($currency) . number_format($row['subtotal'], 2) . '</td>'
        . '</tr>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Order Submitted — <?= htmlspecialchars($appConfig['app_name']) ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="topbar">
  <span class="topbar-brand"><img src="assets/logo_white.png"></span>
  <div class="topbar-user">
    <span><?= htmlspecialchars($user['name']) ?></span>
  </div>
</nav>

<div class="order-complete-layout">
  <div class="order-complete-card">

    <div class="order-success-header">
      <div class="order-success-icon">&#10003;</div>
      <h1 class="order-success-title">Order Submitted</h1>
      <p class="order-success-sub">
        Sent to <strong><?= htmlspecialchars($dept['head_name']) ?></strong>
        (<?= htmlspecialchars($dept['name']) ?>)
        at <a href="mailto:<?= htmlspecialchars($dept['email']) ?>"><?= htmlspecialchars($dept['email']) ?></a>
      </p>
    </div>

    <div class="order-complete-body">

      <div style="overflow-x:auto;">
        <table class="data-table order-summary-table">
          <thead>
            <tr>
              <th>SKU</th>
              <th>Item</th>
              <th style="text-align:center;">Qty</th>
              <th>Unit</th>
              <th style="text-align:right;">Unit Price</th>
              <th style="text-align:right;">Subtotal</th>
            </tr>
          </thead>
          <tbody>
            <?= $rowsHtml ?>
          </tbody>
          <tfoot>
            <tr class="order-total-row">
              <td colspan="5" style="text-align:right;font-weight:600;">Total</td>
              <td style="text-align:right;font-weight:700;color:var(--primary);">
                <?= htmlspecialchars($currency) ?><?= number_format($total, 2) ?>
              </td>
            </tr>
          </tfoot>
        </table>
      </div>

      <?php if ($notes !== ''): ?>
      <div class="order-notes-block">
        <strong>Notes:</strong><br>
        <?= nl2br(htmlspecialchars($notes)) ?>
      </div>
      <?php endif; ?>

      <div class="order-meta">
        <strong>Submitted by:</strong> <?= htmlspecialchars($user['name']) ?>
        (<?= htmlspecialchars($user['email']) ?>)<br>
        <strong>Date:</strong> <?= htmlspecialchars($orderedAt) ?>
      </div>

      <div class="order-actions">
        <a href="index.php" class="btn btn-primary">Place another order</a>
        <a href="api/logout.php" class="btn btn-outline">Sign out</a>
      </div>

    </div>
  </div>
</div>
</body>
</html>
