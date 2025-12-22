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
$ssm_no = isset($_POST['ssm_no']) ? trim($_POST['ssm_no']) : '';

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

if ($jenis_pendaftaran === 'Usahawan' && empty($ssm_no)) {
    die("No Pendaftaran SSM wajib diisi. <a href='daftar.php'>Kembali</a>");
}


// === Hash password for security (RECOMMENDED) ===
// Uncomment line below to use password hashing
// $hashed_password = password_hash($password, PASSWORD_DEFAULT);

$ssm_file_name = null;
if ($jenis_pendaftaran === 'Usahawan') {

    if (!isset($_FILES['ssm_file']) || $_FILES['ssm_file']['error'] !== 0) {
        die("Fail sijil SSM wajib dimuat naik. <a href='daftar.php'>Kembali</a>");
    }

    $allowed_ext = ['pdf','jpg','jpeg','png'];
    $file_ext = strtolower(pathinfo($_FILES['ssm_file']['name'], PATHINFO_EXTENSION));

    if (!in_array($file_ext, $allowed_ext)) {
        die("Format fail SSM tidak dibenarkan. <a href='daftar.php'>Kembali</a>");
    }

    // Nama fail unik
    $ssm_file_name = 'ssm_' . time() . '_' . rand(1000,9999) . '.' . $file_ext;

    // Folder simpanan
    $upload_path = 'uploads/ssm/' . $ssm_file_name;

    $max_size = 2 * 1024 * 1024; // 2MB
    if ($_FILES['ssm_file']['size'] > $max_size) {
        die("Saiz fail SSM terlalu besar. Maksimum 2MB. <a href='daftar.php'>Kembali</a>");
    }


    // Pastikan folder wujud
    if (!is_dir('uploads/ssm')) {
        mkdir('uploads/ssm', 0777, true);
    }

    if (!move_uploaded_file($_FILES['ssm_file']['tmp_name'], $upload_path)){
        die("Gagal memuat naik fail SSM. <a href='daftar.php'>Kembali</a>");
    }
}


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
    $sql = "INSERT INTO pending_usahawan (nama, ic, perniagaan, jenis, alamat, telefon, email, password, ssm_no, ssm_file, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssss", $nama, $ic, $perniagaan, $jenis, $alamat, $telefon, $email, $password,  $ssm_no, 
    $ssm_file_name);
    
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