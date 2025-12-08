<?php
include "connection.php";

// Fungsi tutup sambungan (jika belum wujud)
if (!function_exists('closeDBConnection')) {
    function closeDBConnection($c) {
        if ($c instanceof mysqli) {
            $c->close();
        }
    }
}

// ===== Pastikan ID servis wujud =====
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>
        alert('ID servis tidak sah!');
        window.location='profil_usahawan.php';
    </script>";
    exit();
}

$id = intval($_GET['id']);

// ===== Dapatkan maklumat servis untuk padam gambar =====
$sql = "SELECT gambar_servis_url, usahawan_id FROM servis WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$servis = $result->fetch_assoc();
$stmt->close();

if (!$servis) {
    echo "<script>
        alert('Servis tidak dijumpai!');
        window.location='profil_usahawan.php';
    </script>";
    exit();
}

// ===== Padam gambar dari folder jika ada =====
if (!empty($servis['gambar_servis_url'])) {
    $filePath = "uploads/" . $servis['gambar_servis_url'];
    if (file_exists($filePath)) {
        unlink($filePath); // Padam fail gambar
    }
}

// ===== Padam servis dari database =====
$sql = "DELETE FROM servis WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo "<script>
        alert('✅ Servis berjaya dipadam!');
        window.location='profil_usahawan.php?id=" . $servis['usahawan_id'] . "';
    </script>";
} else {
    echo "<script>
        alert('❌ Ralat: Gagal memadam servis!');
        window.location='profil_usahawan.php?id=" . $servis['usahawan_id'] . "';
    </script>";
}

$stmt->close();
closeDBConnection($conn);
?>
