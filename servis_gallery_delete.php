<?php
include "connection.php";
session_start();

if (!isset($_SESSION['usahawan_id'])) {
    die("Akses tidak dibenarkan.");
}

if (!isset($_GET['id'], $_GET['servis'])) {
    die("Data tidak sah.");
}

$usahawan_id = (int) $_SESSION['usahawan_id'];
$gallery_id  = (int) $_GET['id'];
$service_id  = (int) $_GET['servis'];

/* =====================
   AMBIL GAMBAR + CHECK OWNER
===================== */
$stmt = $conn->prepare("
    SELECT g.gambar, s.gambar_servis_url
    FROM servis_gallery g
    JOIN servis s ON g.service_id = s.id
    WHERE g.id = ? AND s.id = ? AND s.usahawan_id = ?
    LIMIT 1
");
$stmt->bind_param("iii", $gallery_id, $service_id, $usahawan_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    die("Tidak dibenarkan.");
}

/* =====================
   SEKAT PADAM COVER
===================== */
if ($data['gambar'] === $data['gambar_servis_url']) {
    die("Tidak boleh padam gambar cover.");
}

/* =====================
   PADAM FAIL
===================== */
$file = "uploads/" . $data['gambar'];
if (file_exists($file)) {
    unlink($file);
}

/* =====================
   PADAM DB
===================== */
$stmt2 = $conn->prepare("
    DELETE FROM servis_gallery
    WHERE id = ?
");
$stmt2->bind_param("i", $gallery_id);
$stmt2->execute();

header("Location: servis_view.php?id=".$service_id);
exit;
