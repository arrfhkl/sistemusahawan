<?php
// === Database Connection ===
include "connection.php";

// Check connection
if ($conn->connect_error) {
    die("Sambungan ke pangkalan data gagal: " . $conn->connect_error);
}

// === Get form data safely ===
$jenis_pendaftaran = isset($_POST['jenis_pendaftaran']) ? trim($_POST['jenis_pendaftaran']) : '';
$nama              = isset($_POST['nama']) ? trim($_POST['nama']) : '';
$ic                = isset($_POST['ic']) ? trim($_POST['ic']) : '';
$alamat            = isset($_POST['alamat']) ? trim($_POST['alamat']) : '';
$telefon           = isset($_POST['telefon']) ? trim($_POST['telefon']) : '';
$email             = isset($_POST['email']) ? trim($_POST['email']) : '';
$password          = isset($_POST['password']) ? trim($_POST['password']) : '';

// Set default values for Pengguna
if ($jenis_pendaftaran === 'Pengguna') {
    $perniagaan = 'Pengguna';
    $jenis      = 'Pengguna';
} else {
    // Get values from form for Usahawan
    $perniagaan = isset($_POST['perniagaan']) ? trim($_POST['perniagaan']) : '';
    $jenis      = isset($_POST['jenis']) ? trim($_POST['jenis']) : '';
}

// Simple validation
if (empty($nama) || empty($ic) || empty($telefon) || empty($jenis_pendaftaran)) {
    die("Sila isi semua maklumat yang diperlukan. <a href='daftar.php'>Kembali</a>");
}

// Validate Usahawan specific fields
if ($jenis_pendaftaran === 'Usahawan' && (empty($perniagaan) || empty($jenis))) {
    die("Sila isi maklumat perniagaan untuk pendaftaran Usahawan. <a href='daftar.php'>Kembali</a>");
}

// === Hash password for security (RECOMMENDED) ===
// Uncomment line below to use password hashing
// $hashed_password = password_hash($password, PASSWORD_DEFAULT);

// === Process based on registration type ===
if ($jenis_pendaftaran === 'Pengguna') {
    // Insert directly into usahawan table for Pengguna
    $sql = "INSERT INTO usahawan (nama, ic, perniagaan, jenis, alamat, telefon, email, password, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'aktif')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssss", $nama, $ic, $perniagaan, $jenis, $alamat, $telefon, $email, $password);
    
    if ($stmt->execute()) {
        echo "<script>
                alert('Pendaftaran Pengguna Berjaya! Anda boleh log masuk sekarang.');
                window.location = 'login.php';
              </script>";
    } else {
        echo "<script>
                alert('Ralat: " . addslashes($stmt->error) . "');
                window.location = 'daftar.php';
              </script>";
    }
    
} else {
    // Insert into pending_usahawan table for Usahawan (pending approval)
    $sql = "INSERT INTO pending_usahawan (nama, ic, perniagaan, jenis, alamat, telefon, email, password, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssss", $nama, $ic, $perniagaan, $jenis, $alamat, $telefon, $email, $password);
    
    if ($stmt->execute()) {
        echo "<script>
                alert('Pendaftaran Usahawan Berjaya! Akaun anda sedang menunggu kelulusan daripada admin.');
                window.location = 'login.php';
              </script>";
    } else {
        echo "<script>
                alert('Ralat: " . addslashes($stmt->error) . "');
                window.location = 'daftar.php';
              </script>";
    }
}

$stmt->close();
$conn->close();
?>