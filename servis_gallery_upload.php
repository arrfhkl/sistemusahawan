<?php
include "connection.php";
session_start();

if (!isset($_SESSION['usahawan_id'])) {
    die("Akses tidak dibenarkan.");
}

if (!isset($_POST['service_id'])) {
    die("Servis tidak sah.");
}

$usahawan_id = (int) $_SESSION['usahawan_id'];
$service_id  = (int) $_POST['service_id'];

/* =====================
   CHECK OWNERSHIP
===================== */
$stmt = $conn->prepare("
    SELECT id FROM servis
    WHERE id = ? AND usahawan_id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $service_id, $usahawan_id);
$stmt->execute();

if ($stmt->get_result()->num_rows === 0) {
    die("Anda tidak dibenarkan.");
}

/* =====================
   UPLOAD GAMBAR
===================== */
if (!isset($_FILES['gambar'])) {
    die("Tiada fail dimuat naik.");
}

$upload_dir = "uploads/";

foreach ($_FILES['gambar']['tmp_name'] as $i => $tmp) {

    if ($_FILES['gambar']['error'][$i] !== UPLOAD_ERR_OK) {
        continue;
    }

    $ext = strtolower(pathinfo($_FILES['gambar']['name'][$i], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp'];

    if (!in_array($ext, $allowed)) {
        continue;
    }

    $newName = uniqid("servis_", true) . "." . $ext;
    $target  = $upload_dir . $newName;

    if (move_uploaded_file($tmp, $target)) {

        $stmt2 = $conn->prepare("
            INSERT INTO servis_gallery (service_id, gambar)
            VALUES (?, ?)
        ");
        $stmt2->bind_param("is", $service_id, $newName);
        $stmt2->execute();
    }
}

header("Location: servis_view.php?id=".$service_id);
exit;
