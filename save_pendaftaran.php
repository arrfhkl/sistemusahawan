<?php
// === Database Connection ===
include "connection.php";

// Check connection
if ($conn->connect_error) {
    die("Sambungan ke pangkalan data gagal: " . $conn->connect_error);
}

// === Get form data safely ===
$nama       = isset($_POST['nama']) ? trim($_POST['nama']) : '';
$ic         = isset($_POST['ic']) ? trim($_POST['ic']) : '';
$perniagaan = isset($_POST['perniagaan']) ? trim($_POST['perniagaan']) : '';
$jenis      = isset($_POST['jenis']) ? trim($_POST['jenis']) : '';
$alamat     = isset($_POST['alamat']) ? trim($_POST['alamat']) : '';
$telefon    = isset($_POST['telefon']) ? trim($_POST['telefon']) : '';
$email      = isset($_POST['email']) ? trim($_POST['email']) : '';
$password   = isset($_POST['password']) ? trim($_POST['password']) : '';

// Simple validation
if (empty($nama) || empty($ic) || empty($perniagaan) || empty($jenis) || empty($telefon)) {
    die("Sila isi semua maklumat yang diperlukan. <a href='pendaftaran.php'>Kembali</a>");
}

// === Prepare and bind ===
// Simpan ke DB tanpa avatar
$sql = "INSERT INTO usahawan (nama, ic, perniagaan, jenis, alamat, telefon, email, password) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssssss", $nama, $ic, $perniagaan, $jenis, $alamat, $telefon, $email, $password);

// Execute and check
if ($stmt->execute()) {
    echo "<script>
            alert('Pendaftaran Berjaya! Maklumat anda telah disimpan.');
            window.location = 'login.php';
          </script>";
} else {
    echo "<script>
            alert('Ralat: " . addslashes($stmt->error) . "');
            window.location = 'daftar.php';
          </script>";
}


$stmt->close();
$conn->close();
?>
