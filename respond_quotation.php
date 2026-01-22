<?php
session_start();
include "connection.php";

if (!isset($_SESSION['usahawan_id'])) exit("NO_SESSION");

$user_id = (int)$_SESSION['usahawan_id'];
$quotation_id = (int)($_POST['quotation_id'] ?? 0);
$action = $_POST['action']; // accept | reject

if (!in_array($action, ['accept','reject'])) exit("INVALID");

/* Validate buyer */
$stmt = $conn->prepare("
  SELECT *
  FROM quotation
  WHERE id = ?
");
$stmt->bind_param("i", $quotation_id);
$stmt->execute();
$q = $stmt->get_result()->fetch_assoc();

if (!$q || $q['buyer_id'] != $user_id) exit("FORBIDDEN");
if ($q['status'] !== 'sent') exit("INVALID_STATUS");

$newStatus = ($action === 'accept') ? 'accepted' : 'rejected';

$stmt = $conn->prepare("
  UPDATE quotation
  SET status = ?,
      responded_at = NOW()
  WHERE id = ?
");
$stmt->bind_param("si", $newStatus, $quotation_id);
$stmt->execute();

echo "OK";

// Untuk masuk pesanan servis
if ($action === 'accept') {

  // ⛔ Safety: pastikan servis_order belum wujud
  $check = $conn->prepare("
    SELECT id
    FROM servis_order
    WHERE quotation_id = ?
    LIMIT 1
  ");
  $check->bind_param("i", $quotation_id);
  $check->execute();
  $exists = $check->get_result()->fetch_assoc();

  if (!$exists) {

    $stmt = $conn->prepare("
      INSERT INTO servis_order (
        quotation_id,
        chat_id,
        servis_id,
        seller_id,
        buyer_id,
        title,
        description,
        price,
        duration,
        status,
        payment_status,
        created_at
      ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'unpaid', NOW()
      )
    ");

    $stmt->bind_param(
      "iiiiissds",
      $quotation_id,
      $q['chat_id'],
      $q['servis_id'],
      $q['seller_id'],
      $q['buyer_id'],
      $q['title'],
      $q['description'],
      $q['price'],
      $q['duration']
    );

    $stmt->execute();
  }
}

