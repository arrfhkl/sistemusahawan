<?php
session_start();
header('Content-Type: application/json');

// Database
$conn = new mysqli("localhost", "root", "", "sistem_usahawan_pahang");

// Response array
$response = ['success' => false, 'message' => ''];

// Check connection
if ($conn->connect_error) {
    $response['message'] = "Connection failed: " . $conn->connect_error;
    echo json_encode($response);
    exit;
}

// Check login
if (!isset($_SESSION['usahawan_id'])) {
    $response['message'] = "Sila login terlebih dahulu. Session tidak wujud.";
    echo json_encode($response);
    exit;
}

$user_id = intval($_SESSION['usahawan_id']);

// Validate POST
if (empty($_POST['produk_id'])) {
    $response['message'] = "Produk ID tidak wujud dalam POST";
    echo json_encode($response);
    exit;
}

if (empty($_POST['nama'])) {
    $response['message'] = "Nama produk tidak wujud dalam POST";
    echo json_encode($response);
    exit;
}

// Get data
$produk_id = $conn->real_escape_string($_POST['produk_id']);
$nama = $conn->real_escape_string($_POST['nama']);
$harga = isset($_POST['harga']) ? floatval($_POST['harga']) : 0;
$gambar = isset($_POST['gambar_url']) ? $conn->real_escape_string($_POST['gambar_url']) : '';

// Simple insert (no check for existing)
$sql = "INSERT INTO cart (usahawan_id, produk_id, nama_produk, harga, gambar_url, kuantiti) 
        VALUES ($user_id, '$produk_id', '$nama', $harga, '$gambar', 1)";

if ($conn->query($sql) === TRUE) {
    $response['success'] = true;
    $response['message'] = "Berjaya! User ID: $user_id, Produk: $nama";
} else {
    $response['message'] = "Error SQL: " . $conn->error;
}

$conn->close();
echo json_encode($response);
?>