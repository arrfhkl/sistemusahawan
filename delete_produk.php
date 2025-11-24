<?php
include "connection.php";

if (!function_exists('closeDBConnection')) {
    function closeDBConnection($c) {
        if ($c instanceof mysqli) {
            $c->close();
        }
    }
}

// Pastikan ada ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('ID produk tidak sah!'); window.location='profil_usahawan.php';</script>";
    exit();
}

$id = intval($_GET['id']);

// Dapatkan maklumat produk untuk delete gambar
$sql = "SELECT gambar_url FROM produk WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$produk = $result->fetch_assoc();
$stmt->close();

if (!$produk) {
    echo "<script>alert('Produk tidak dijumpai!'); window.location='profil_usahawan.php';</script>";
    exit();
}

// Delete gambar dari folder jika ada
if (!empty($produk['gambar_url'])) {
    $filePath = "uploads/" . $produk['gambar_url'];
    if (file_exists($filePath)) {
        unlink($filePath); // Delete file
    }
}

// Delete dari database
$sql = "DELETE FROM produk WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo "<script>alert('✅ Produk berjaya dipadam!'); window.location='profil_usahawan.php';</script>";
} else {
    echo "<script>alert('❌ Ralat: Gagal memadam produk!'); window.location='profil_usahawan.php';</script>";
}

$stmt->close();
closeDBConnection($conn);
?>