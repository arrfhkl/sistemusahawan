<?php
session_start();
include "connection.php";

/* ===============================
   AUTH CHECK
================================ */
if (!isset($_SESSION['usahawan_id'])) {
  die("Login dahulu");
}

$user_id = (int)$_SESSION['usahawan_id'];
$quotation_id = (int)($_GET['quotation_id'] ?? 0);
$chat_id = (int)($_GET['chat_id'] ?? 0);

if ($quotation_id <= 0 || $chat_id <= 0) {
  die("Permintaan tidak sah");
}

/* ===============================
   LOAD QUOTATION (BUYER / SELLER)
================================ */
$stmt = $conn->prepare("
  SELECT q.*, s.nama AS servis_nama
  FROM quotation q
  JOIN servis s ON s.id = q.servis_id
  WHERE q.id = ?
    AND q.chat_id = ?
    AND (q.buyer_id = ? OR q.seller_id = ?)
  LIMIT 1
");
$stmt->bind_param(
  "iiii",
  $quotation_id,
  $chat_id,
  $user_id,
  $user_id
);
$stmt->execute();

$quotation = $stmt->get_result()->fetch_assoc();

if (!$quotation) {
  die("Quotation tidak dijumpai atau tiada akses");
}

/* ===============================
   ROLE CHECK
================================ */
$isBuyer  = ($quotation['buyer_id'] == $user_id);
$isSeller = ($quotation['seller_id'] == $user_id);

/* ===============================
   DECODE QUOTATION DATA
================================ */
$data = json_decode($quotation['description'], true);
?>

<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($quotation['title']) ?></title>

<style>
/* ==================================================
   DESIGN SYSTEM (Professional SaaS)
================================================== */
:root {
  --bg-page: #f5f7fb;
  --bg-card: #ffffff;
  --bg-muted: #f8fafc;
  --bg-dark: #0f172a;

  --border: #e5e7eb;
  --border-soft: #eef2f7;

  --text-primary: #0f172a;
  --text-secondary: #475569;
  --text-muted: #64748b;

  --accent: #0ea5e9;        /* Trust blue */
  --success: #16a34a;
  --danger: #dc2626;

  --radius-lg: 16px;
  --radius-md: 12px;
  --radius-sm: 8px;
}

/* ==================================================
   PAGE
================================================== */
body {
  margin: 0;
  background: var(--bg-page);
  font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
  color: var(--text-primary);
  line-height: 1.5;
}

.quotation-box {
  max-width: 720px;
  margin: 24px auto;
  padding: 0 16px 32px;
}

/* ==================================================
   HEADER
================================================== */
.quotation-header {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 20px 24px;
  margin-bottom: 16px;
}

.quotation-header h2 {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
}

.quotation-header .meta {
  margin-top: 6px;
  font-size: 13px;
  color: var(--text-muted);
}

/* ==================================================
   STATUS BADGE
================================================== */
.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  margin-top: 12px;
  padding: 6px 14px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
}

.status-badge.sent {
  background: #fef3c7;
  color: #92400e;
}

.status-badge.accepted {
  background: #dcfce7;
  color: #166534;
}

.status-badge.rejected {
  background: #fee2e2;
  color: #991b1b;
}

/* ==================================================
   CARD SECTIONS
================================================== */
.card {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  margin-bottom: 16px;
  overflow: hidden;
}

.card-header {
  padding: 14px 20px;
  background: var(--bg-muted);
  border-bottom: 1px solid var(--border-soft);
  font-size: 13px;
  font-weight: 600;
  color: var(--text-secondary);
}

.card-body {
  padding: 20px;
}

/* ==================================================
   COMPANY DETAILS
================================================== */
.company-row {
  display: grid;
  grid-template-columns: 140px 1fr;
  gap: 12px;
  margin-bottom: 12px;
}

.company-label {
  font-size: 12px;
  color: var(--text-muted);
}

.company-value {
  font-weight: 500;
}

/* ==================================================
   ITEM TABLE
================================================== */
table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}

table thead th {
  text-align: left;
  padding: 10px 12px;
  font-size: 12px;
  color: var(--text-muted);
  border-bottom: 1px solid var(--border);
}

table tbody td {
  padding: 12px;
  border-bottom: 1px solid var(--border-soft);
}

table tbody tr:last-child td {
  border-bottom: none;
}

/* ==================================================
   TOTAL SUMMARY
================================================== */
.total-box {
  background: var(--bg-dark);
  color: #e5e7eb;
  border-radius: var(--radius-lg);
  padding: 20px;
}

.total-row {
  display: flex;
  justify-content: space-between;
  font-size: 14px;
  margin-bottom: 6px;
  color: #cbd5f5;
}

.total-row.grand {
  margin-top: 10px;
  font-size: 18px;
  font-weight: 600;
  color: #38bdf8;
}

/* ==================================================
   ACTION BUTTONS
================================================== */
.actions {
  display: flex;
  gap: 12px;
  margin-top: 20px;
}

.actions button {
  flex: 1;
  padding: 14px;
  border-radius: var(--radius-md);
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s ease;
}

/* Accept */
.actions .accept {
  background: var(--success);
  color: #fff;
  border: none;
}

.actions .accept:hover {
  filter: brightness(0.95);
}

/* Reject */
.actions .reject {
  background: #fff;
  color: var(--danger);
  border: 1.5px solid #fecaca;
}

.actions .reject:hover {
  background: #fff5f5;
}

/* ==================================================
   FINAL STATUS MESSAGE
================================================== */
.status {
  margin-top: 20px;
  padding: 14px;
  background: var(--bg-muted);
  border-radius: var(--radius-md);
  text-align: center;
  font-weight: 600;
  color: var(--text-secondary);
}

/* ===============================
   OUTER BORDER (KESAN KEMAS)
================================ */
.quotation-box {
  border: 1px solid #e5e7eb;
  border-radius: 20px;
  background: #f8fafc;
}

/* ===============================
   INNER CARD BORDER CONSISTENCY
================================ */
.quotation-header,
.card,
.total-box {
  border: 1px solid #e5e7eb;
}

/* ===============================
   SECTION SEPARATOR (HALUS)
================================ */
.card + .card {
  margin-top: 18px;
}

/* ===============================
   MICRO SHADOW (OPTIONAL TAPI CANTIK)
================================ */
.quotation-header,
.card,
.total-box {
  box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}



</style>
</head>

<body>

<div class="quotation-box">

  <h2><?= htmlspecialchars($quotation['title']) ?></h2>

  <div class="meta">
    Servis: <strong><?= htmlspecialchars($quotation['servis_nama']) ?></strong><br>
    Status: <strong><?= ucfirst($quotation['status']) ?></strong>
  </div>

  <hr>

  <h4>Butiran Syarikat</h4>
  <p>
    <?= htmlspecialchars($data['company']['name'] ?? '-') ?><br>
    <?= htmlspecialchars($data['company']['address'] ?? '-') ?><br>
    <?= htmlspecialchars($data['company']['phone'] ?? '-') ?><br>
    <?= htmlspecialchars($data['company']['email'] ?? '-') ?>
  </p>

  <hr>

  <h4>Item Quotation</h4>

  <table>
    <tr>
      <th>Item</th>
      <th>Qty</th>
      <th>Jumlah (RM)</th>
    </tr>

    <?php foreach ($data['items'] as $item): ?>
      <tr>
        <td><?= htmlspecialchars($item['name']) ?></td>
        <td><?= (int)$item['qty'] ?></td>
        <td><?= number_format($item['total'], 2) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>

  <div class="total">
    Subtotal: RM <?= number_format($data['subtotal'], 2) ?><br>
    Cukai (6%): RM <?= number_format($data['tax'], 2) ?><br>
    <strong>Jumlah: RM <?= number_format($data['total'], 2) ?></strong>
  </div>

  <?php if ($quotation['status'] !== 'sent'): ?>
    <div class="status">
      Quotation telah <?= htmlspecialchars($quotation['status']) ?>
    </div>
  <?php endif; ?>

  <?php if ($isBuyer && $quotation['status'] === 'sent'): ?>
    <div class="actions">
      <form method="post" action="respond_quotation.php">
        <input type="hidden" name="quotation_id" value="<?= $quotation_id ?>">
        <input type="hidden" name="chat_id" value="<?= $chat_id ?>">

        <button class="accept" name="action" value="accept">
          ✅ TERIMA
        </button>

        <button class="reject" name="action" value="reject">
          ❌ TOLAK
        </button>
      </form>
    </div>
  <?php endif; ?>

</div>

</body>
</html>
