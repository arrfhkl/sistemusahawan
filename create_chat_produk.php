<?php
session_start();
include "connection.php";
echo "STEP 1 OK<br>";


/* ===============================
   1. LOGIN CHECK
================================ */
if (!isset($_SESSION['usahawan_id'])) {
  die("Sila login");
}
echo "STEP 2 OK<br>";


$user_id   = (int)$_SESSION['usahawan_id'];
$produk_id = (int)($_POST['produk_id'] ?? 0);

if ($produk_id <= 0) {
  die("Produk tidak sah");
}
echo "STEP 3 OK<br>";

/* ===============================
   2. GET OWNER PRODUK
================================ */
$stmt = $conn->prepare("
  SELECT usahawan_id
  FROM produk
  WHERE id = ?
  LIMIT 1
");
$stmt->bind_param("i", $produk_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
  die("Produk tidak dijumpai");
}

$seller_id = (int)$res->fetch_assoc()['usahawan_id'];

echo "STEP 4 OK<br>";

/* ===============================
   3. BLOCK SELF CHAT
================================ */
if ($seller_id === $user_id) {
  die("Anda tidak boleh chat produk sendiri");
}

/* ===============================
   4. CHECK EXISTING CHAT
================================ */
$stmt = $conn->prepare("
  SELECT id
  FROM chat_rooms
  WHERE produk_id = ?
    AND (
      (user_a = ? AND user_b = ?)
      OR
      (user_a = ? AND user_b = ?)
    )
  LIMIT 1
");
$stmt->bind_param(
  "iiiii",
  $produk_id,
  $user_id, $seller_id,
  $seller_id, $user_id
);
$stmt->execute();
$res = $stmt->get_result();

echo "STEP 5 OK<br>";

/* ===============================
   5. REUSE OR CREATE CHAT
================================ */
if ($res->num_rows > 0) {
  $chat_id = (int)$res->fetch_assoc()['id'];
} else {

  $stmt = $conn->prepare( "
    INSERT INTO chat_rooms
      (produk_id, user_a, user_b, created_at)
    VALUES (?, ?, ?, NOW())
  ");
  $stmt->bind_param("iii", $produk_id, $user_id, $seller_id);
  $stmt->execute();

  $chat_id = $stmt->insert_id;
}

echo "STEP 6 OK<br>";

/* ===============================
   6. REDIRECT TO CHAT ROOM
================================ */
header("Location: chat_room.php?chat_id=" . $chat_id . "&produk_id=" . $produk_id);
exit;
