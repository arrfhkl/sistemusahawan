<?php
// === Sambungan ke Database ===
include "connection.php";

// Semak sambungan
if ($conn->connect_error) {
    die("Sambungan gagal: " . $conn->connect_error);
}

// === Ambil Data Borang ===
$nama     = trim($_POST['nama']);
$ic       = trim($_POST['ic']);
$telefon  = trim($_POST['telefon']);
$kategori = trim($_POST['kategori']);
$alamat   = trim($_POST['alamat']);
$jumlah   = floatval($_POST['jumlah']);
$tujuan   = trim($_POST['tujuan']);

// === Validasi Input ===
if (empty($nama) || empty($ic) || empty($telefon) || empty($kategori) || empty($alamat) || empty($jumlah) || empty($tujuan)) {
    echo "<script>alert('Sila isi semua maklumat yang diperlukan!'); window.history.back();</script>";
    exit();
}

// === Semak IC dalam table usahawan ===
$checkSql = "SELECT id FROM usahawan WHERE ic = ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param("s", $ic);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows == 0) {
    // IC tidak wujud → Hentikan proses
    echo "<script>alert('Anda perlu mendaftar akaun terlebih dahulu!'); window.location='daftar.php';</script>";
    $checkStmt->close();
    $conn->close();
    exit();
}
$checkStmt->close();

// === Uruskan Fail Upload ===
$targetDir = "uploads/";
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true); // buat folder kalau tak wujud
}

// Semak jika fail diupload
if (!isset($_FILES["dokumen"]) || $_FILES["dokumen"]["error"] == UPLOAD_ERR_NO_FILE) {
    echo "<script>alert('Sila muat naik dokumen yang diperlukan!'); window.history.back();</script>";
    exit();
}

// Semak error upload
if ($_FILES["dokumen"]["error"] !== UPLOAD_ERR_OK) {
    echo "<script>alert('Ralat semasa muat naik fail. Sila cuba lagi.'); window.history.back();</script>";
    exit();
}

// Validasi jenis fail
$allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png', 
                 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
$fileType = $_FILES["dokumen"]["type"];
$fileExtension = strtolower(pathinfo($_FILES["dokumen"]["name"], PATHINFO_EXTENSION));
$allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx'];

if (!in_array($fileType, $allowedTypes) && !in_array($fileExtension, $allowedExtensions)) {
    echo "<script>alert('Jenis fail tidak dibenarkan! Sila muat naik PDF, Gambar, Word atau Excel sahaja.'); window.history.back();</script>";
    exit();
}

// Validasi saiz fail (maksimum 5MB)
$maxFileSize = 5 * 1024 * 1024; // 5MB
if ($_FILES["dokumen"]["size"] > $maxFileSize) {
    echo "<script>alert('Saiz fail terlalu besar! Maksimum 5MB sahaja.'); window.history.back();</script>";
    exit();
}

$originalFilename = basename($_FILES["dokumen"]["name"]);
$safeFilename = preg_replace("/[^a-zA-Z0-9._-]/", "_", $originalFilename); // Sanitize filename
$uniqueFilename = time() . "_" . uniqid() . "_" . $safeFilename; // Nama fail unik
$targetFile = $targetDir . $uniqueFilename;

if (move_uploaded_file($_FILES["dokumen"]["tmp_name"], $targetFile)) {
    // === Simpan ke Database ===
    $sql = "INSERT INTO permohonan_agro (nama, ic, telefon, kategori, alamat, jumlah, tujuan, dokumen, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Sedang Diproses')";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssdss", $nama, $ic, $telefon, $kategori, $alamat, $jumlah, $tujuan, $uniqueFilename);

    if ($stmt->execute()) {
        echo "<script>
                alert('Permohonan Agropreneur berjaya dihantar! Sila tunggu maklumbalas daripada pihak kami.');
                window.location='permohonan-agro.php';
              </script>";
    } else {
        // Jika gagal simpan, delete fail yang diupload
        if (file_exists($targetFile)) {
            unlink($targetFile);
        }
        echo "<script>alert('Ralat menyimpan data: " . addslashes($stmt->error) . "'); window.history.back();</script>";
    }

    $stmt->close();
} else {
    echo "<script>alert('Ralat muat naik fail. Sila cuba lagi.'); window.history.back();</script>";
}

$conn->close();
?>