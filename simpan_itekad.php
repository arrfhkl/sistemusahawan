<?php
// === Sambungan ke Database ===
include "connection.php";

// Semak sambungan
if ($conn->connect_error) {
    die("Sambungan gagal: " . $conn->connect_error);
}

// === Ambil Data Borang ===
$nama    = $_POST['nama'];
$ic      = $_POST['ic'];
$telefon = $_POST['telefon'];
$kategori= $_POST['kategori'];
$alamat  = $_POST['alamat'];
$jumlah  = $_POST['jumlah'];
$tujuan  = $_POST['tujuan'];

// === Semak IC dalam table usahawan ===
$checkSql = "SELECT id FROM usahawan WHERE ic = ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param("s", $ic);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows == 0) {
    // IC tidak wujud → Hentikan proses
    echo "<script>alert('Anda perlu mendaftar akaun terlebih dahulu'); window.location='daftar.php';</script>";
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

$filename = basename($_FILES["dokumen"]["name"]);
$targetFile = $targetDir . time() . "_" . $filename; // elak duplicate nama

if (move_uploaded_file($_FILES["dokumen"]["tmp_name"], $targetFile)) {
    // === Simpan ke Database ===
    $sql = "INSERT INTO permohonan_itekad (nama, ic, telefon, kategori, alamat, jumlah, tujuan, dokumen) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssiss", $nama, $ic, $telefon, $kategori, $alamat, $jumlah, $tujuan, $targetFile);

    if ($stmt->execute()) {
        echo "<script>alert('Permohonan berjaya dihantar!'); window.location='permohonan-itekad.php';</script>";
    } else {
        echo "Ralat: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "Ralat muat naik fail.";
}

$conn->close();
?>
