<?php
session_start();
include "connection.php";

$conn->query("SET time_zone = '+08:00'");

/* ===============================
   AUTH CHECK
================================ */
if (!isset($_SESSION['usahawan_id'])) {
  die("Login dahulu");
}

$user_id = (int)$_SESSION['usahawan_id'];

/* ===============================
   GET & VALIDATE INPUT
================================ */
$quotation_id = (int)($_POST['quotation_id'] ?? 0);
$chat_id      = (int)($_POST['chat_id'] ?? 0);

if ($quotation_id <= 0 || $chat_id <= 0) {
  die("Permintaan tidak sah");
}

/* ===============================
   LOAD QUOTATION (SECURITY CHECK)
================================ */
$stmt = $conn->prepare("
  SELECT *
  FROM quotation
  WHERE id = ?
    AND chat_id = ?
    AND seller_id = ?
    AND status = 'requested'
  LIMIT 1
");
$stmt->bind_param("iii", $quotation_id, $chat_id, $user_id);
$stmt->execute();
$quotation = $stmt->get_result()->fetch_assoc();

if (!$quotation) {
  die("Quotation tidak sah atau telah diproses");
}

/* ===============================
   COLLECT FORM DATA
================================ */
$company_name    = trim($_POST['company_name'] ?? '');
$company_address = trim($_POST['company_address'] ?? '');
$company_phone   = trim($_POST['company_phone'] ?? '');
$company_email   = trim($_POST['company_email'] ?? '');
$quotation_date  = $_POST['quotation_date'] ?? '';
$quotation_no    = trim($_POST['quotation_no'] ?? '');

$customer_info = trim($_POST['customer_info'] ?? '');
$seller_info   = trim($_POST['seller_info'] ?? '');

$item_names  = $_POST['item_name'] ?? [];
$item_descs  = $_POST['item_desc'] ?? [];
$item_qtys   = $_POST['item_qty'] ?? [];
$item_totals = $_POST['item_total'] ?? [];

/* ===============================
   BASIC VALIDATION
================================ */
if ($company_name === '' || $quotation_date === '' || $quotation_no === '') {
  die("Maklumat quotation tidak lengkap");
}

if (count($item_names) === 0) {
  die("Item quotation diperlukan");
}

/* ===============================
   BUILD ITEMS ARRAY + CALCULATE
================================ */
$items = [];
$subtotal = 0;

for ($i = 0; $i < count($item_names); $i++) {

  $name  = trim($item_names[$i]);
  $desc  = trim($item_descs[$i] ?? '');
  $qty   = (int)($item_qtys[$i] ?? 1);
  $total = (float)($item_totals[$i] ?? 0);

  if ($name === '' || $total <= 0) {
    continue;
  }

  $items[] = [
    "name"  => $name,
    "desc"  => $desc,
    "qty"   => $qty,
    "total" => $total
  ];

  $subtotal += $total;
}

if (count($items) === 0) {
  die("Item quotation tidak sah");
}

$tax = round($subtotal * 0.06, 2);
$grand_total = round($subtotal + $tax, 2);

/* ===============================
   UPDATE QUOTATION
================================ */
$stmt = $conn->prepare("
  UPDATE quotation
  SET
    title = ?,
    description = ?,
    price = ?,
    status = 'sent',
    sent_at = NOW()
  WHERE id = ?
");
$description_json = json_encode([
  "company" => [
    "name"    => $company_name,
    "address" => $company_address,
    "phone"   => $company_phone,
    "email"   => $company_email
  ],
  "quotation" => [
    "date" => $quotation_date,
    "no"   => $quotation_no
  ],
  "party" => [
    "customer" => $customer_info,
    "seller"   => $seller_info
  ],
  "items" => $items,
  "subtotal" => $subtotal,
  "tax" => $tax,
  "total" => $grand_total
], JSON_UNESCAPED_UNICODE);

$title = "Quotation #" . $quotation_no;

$stmt->bind_param(
  "ssdi",
  $title,
  $description_json,
  $grand_total,
  $quotation_id
);

if (!$stmt->execute()) {
  die("Gagal simpan quotation");
}

/* ==============================
   SYSTEM MESSAGE (UNDELETEABLE)
================================ */

$systemMessage = sprintf(
  '📄 <strong>Quotation telah dihantar</strong><br>
   <span style="color:#666;font-size:13px">
     Penjual telah menghantar quotation rasmi untuk servis ini.
   </span><br><br>
   <a href="view_quotation.php?quotation_id=%d&chat_id=%d"
      style="font-weight:600">
     Lihat quotation
   </a>',
  $quotation_id,
  $chat_id
);

$systemMessage = sprintf(
  '📄 <a href="view_quotation.php?quotation_id=%d&chat_id=%d">
   Lihat Quotation
   </a>',
  $quotation_id,
  $chat_id
);

$msg = $conn->prepare("
  INSERT INTO chat_messages
    (chat_id, sender_id, servis_id, message, created_at, is_deleted, is_read)
  VALUES (?, 0, ?, ?, NOW(), 0, 0)
");
$msg->bind_param(
  "iis",
  $chat_id,
  $quotation['servis_id'],
  $systemMessage
);
$msg->execute();

/* ===============================
   REDIRECT BACK TO CHAT
================================ */
header("Location: chat_room.php?chat_id=".$chat_id);
exit;
