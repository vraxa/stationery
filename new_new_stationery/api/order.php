<?php
require_once dirname(__DIR__) . "/includes/auth.php";
require_once dirname(__DIR__) . "/includes/graph.php";
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed."]);
    exit();
}

requireLoginApi();

$input = json_decode(file_get_contents("php://input"), true) ?? [];
$cart = $input["cart"] ?? [];
$deptId = trim($input["department_id"] ?? "");
$orderNotes = trim(substr($input["notes"] ?? "", 0, 1000));

if (empty($cart)) {
    http_response_code(422);
    echo json_encode(["error" => "Cart is empty."]);
    exit();
}
if ($deptId === "") {
    http_response_code(422);
    echo json_encode(["error" => "Please select a department."]);
    exit();
}

// Load department
$departments =
    json_decode(
        file_get_contents(dirname(__DIR__) . "/data/departments.json"),
        true,
    ) ?? [];
$department = null;
foreach ($departments as $d) {
    if ($d["id"] === $deptId) {
        $department = $d;
        break;
    }
}
if ($department === null) {
    http_response_code(422);
    echo json_encode(["error" => "Selected department not found."]);
    exit();
}

// Validate cart items against live data (prevent price tampering)
$allItems =
    json_decode(
        file_get_contents(dirname(__DIR__) . "/data/items.json"),
        true,
    ) ?? [];
$itemIndex = [];
foreach ($allItems as $item) {
    $itemIndex[$item["id"]] = $item;
}

$validatedCart = [];
foreach ($cart as $entry) {
    $id = (string) ($entry["id"] ?? "");
    $qty = (int) ($entry["quantity"] ?? 0);
    if ($qty <= 0) {
        continue;
    }
    if (!isset($itemIndex[$id])) {
        continue;
    }
    $liveItem = $itemIndex[$id];
    $validatedCart[] = [
        "sku"      => $liveItem["sku"] ?? "",
        "name"     => $liveItem["name"],
        "quantity" => $qty,
        "price"    => $liveItem["price"],
        "unit"     => $liveItem["unit"],
        "subtotal" => round($qty * $liveItem["price"], 2),
    ];
}

if (empty($validatedCart)) {
    http_response_code(422);
    echo json_encode(["error" => "No valid items in cart."]);
    exit();
}

$appConfig = require dirname(__DIR__) . "/config/app.php";
$user = getCurrentUser();
$token = $_SESSION["access_token"];
$currency = $appConfig["currency_symbol"];
$total = array_sum(array_column($validatedCart, "subtotal"));
$orderDate = date("D, d M Y H:i:s") . " UTC";

$_SESSION["last_order"] = [
    "items"      => $validatedCart,
    "department" => $department,
    "user"       => $user,
    "total"      => $total,
    "currency"   => $currency,
    "notes"      => $orderNotes,
    "ordered_at" => date("d M Y, H:i") . " UTC",
];

// Build HTML email
$rowsHtml = "";
foreach ($validatedCart as $row) {
    $rowsHtml .= sprintf(
        '<tr><td style="padding:8px 12px;border-bottom:1px solid #EDEBE9;">%s</td>' .
            '<td style="padding:8px 12px;border-bottom:1px solid #EDEBE9;text-align:center;">%d</td>' .
            '<td style="padding:8px 12px;border-bottom:1px solid #EDEBE9;text-align:center;">%s</td>' .
            '<td style="padding:8px 12px;border-bottom:1px solid #EDEBE9;text-align:right;">%s%s</td>' .
            '<td style="padding:8px 12px;border-bottom:1px solid #EDEBE9;text-align:right;">%s%s</td></tr>',
        htmlspecialchars($row["name"]),
        $row["quantity"],
        htmlspecialchars($row["unit"]),
        $currency,
        number_format($row["price"], 2),
        $currency,
        number_format($row["subtotal"], 2),
    );
}

$notesSection = "";
if ($orderNotes !== "") {
    $notesSection =
        '<p style="margin-top:16px;"><strong>Additional Notes:</strong><br>' .
        nl2br(htmlspecialchars($orderNotes)) .
        "</p>";
}

$userDept =
    $user["department"] !== ""
        ? " (" . htmlspecialchars($user["department"]) . ")"
        : "";
$userTitle =
    $user["job_title"] !== "" ? htmlspecialchars($user["job_title"]) : "";

$htmlBody = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family:'Segoe UI',Calibri,Arial,sans-serif;color:#323130;margin:0;padding:0;background:#F3F2F1;">
  <div style="max-width:640px;margin:24px auto;background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.1);">
    <div style="background:#009b9b;padding:24px 32px;">
      <h1 style="margin:0;color:#fff;font-size:22px;font-weight:600;">Stationery Order Request</h1>
      <p style="margin:4px 0 0;color:#ccf0f0;font-size:14px;">{$appConfig["system_name"]}</p>
    </div>
    <div style="padding:24px 32px;">
      <p style="margin-top:0;">Dear <strong>{$department["head_name"]}</strong>,</p>
      <p><strong>{$user["name"]}</strong>{$userDept} has submitted a stationery order request for the <strong>{$department["name"]}</strong>.</p>

      <h2 style="font-size:16px;font-weight:600;border-bottom:2px solid #009b9b;padding-bottom:8px;margin-top:24px;">Order Summary</h2>
      <table style="width:100%;border-collapse:collapse;font-size:14px;">
        <thead>
          <tr style="background:#F3F2F1;">
            <th style="padding:10px 12px;text-align:left;font-weight:600;">Item</th>
            <th style="padding:10px 12px;text-align:center;font-weight:600;">Qty</th>
            <th style="padding:10px 12px;text-align:center;font-weight:600;">Unit</th>
            <th style="padding:10px 12px;text-align:right;font-weight:600;">Unit Price</th>
            <th style="padding:10px 12px;text-align:right;font-weight:600;">Subtotal</th>
          </tr>
        </thead>
        <tbody>{$rowsHtml}</tbody>
        <tfoot>
          <tr style="background:#F3F2F1;">
            <td colspan="4" style="padding:10px 12px;font-weight:600;text-align:right;">Total</td>
            <td style="padding:10px 12px;font-weight:700;text-align:right;color:#009b9b;">{$currency}{$total}</td>
          </tr>
        </tfoot>
      </table>

      {$notesSection}

      <div style="margin-top:24px;padding:16px;background:#F3F2F1;border-radius:6px;font-size:13px;color:#605E5C;">
        <strong>Submitted by:</strong> {$user["name"]} &lt;{$user["email"]}&gt;{$userTitle}<br>
        <strong>Date:</strong> {$orderDate}
      </div>
    </div>
    <div style="padding:16px 32px;background:#F3F2F1;font-size:12px;color:#797775;text-align:center;">
      This email was sent automatically via {$appConfig["system_name"]}. Please do not reply to this email.
    </div>
  </div>
</body>
</html>
HTML;

$subject = sprintf(
    "%s — %s — %s — %s",
    $appConfig["order_subject_prefix"],
    $user["name"],
    $department["name"],
    date("d M Y"),
);

// Dev mode: skip Graph API and simulate success
if ($token === "DEV_MODE") {
    echo json_encode([
        "success" => true,
        "message" =>
            "Thank you for your order, once approved by the budget holder you will be notified to collect from the Management Suite.",
    ]);
    exit();
}

$sent = graphSendMail(
    $token,
    $department["email"],
    $department["head_name"],
    $subject,
    $htmlBody,
);

if (!$sent) {
    http_response_code(502);
    echo json_encode([
        "error" => "Failed to send email. Please try again or contact IT.",
    ]);
    exit();
}

echo json_encode([
    "success" => true,
    "message" => "Order sent to {$department["head_name"]} at {$department["email"]}.",
]);
