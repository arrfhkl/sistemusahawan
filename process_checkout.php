<?php
session_start();
include "connection.php";

// Semak login
if (!isset($_SESSION['usahawan_id'])) {
    echo "<script>alert('Sila log masuk terlebih dahulu.'); window.location.href='login.php';</script>";
    exit;
}

$usahawan_id = $_SESSION['usahawan_id'];

// Ambil data dari form
$nama_pelanggan = trim($_POST['nama_pelanggan']);
$no_telefon = trim($_POST['no_telefon']);
$alamat = trim($_POST['alamat']);
$nota = isset($_POST['nota']) ? trim($_POST['nota']) : '';
$cara_hantar = $_POST['cara_hantar'];
$cara_bayar = $_POST['cara_bayar'];

// Validasi input
if (empty($nama_pelanggan) || empty($no_telefon) || empty($alamat)) {
    echo "<script>alert('Sila lengkapkan semua maklumat yang diperlukan.'); window.history.back();</script>";
    exit;
}

// Semak jika tiada item dipilih
if (empty($_POST['selected_items'])) {
    echo "<script>alert('Tiada item dipilih.'); window.location.href='cart.php';</script>";
    exit;
}

// Dapatkan senarai item terpilih
$ids = array_map('intval', $_POST['selected_items']);
$id_list = implode(",", $ids);

// Ambil maklumat produk dari cart dengan validation
$sql = "SELECT c.id as cart_id, c.kuantiti, c.produk_id, p.nama, p.harga, p.gambar_url 
        FROM cart c 
        JOIN produk p ON c.produk_id = p.id 
        WHERE c.id IN ($id_list) AND c.usahawan_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usahawan_id);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
$total = 0;
while($row = $result->fetch_assoc()) {
    $subtotal = $row['harga'] * $row['kuantiti'];
    $row['subtotal'] = $subtotal;
    $total += $subtotal;
    $items[] = $row;
}

if (empty($items)) {
    echo "<script>alert('Item tidak dijumpai.'); window.location.href='cart.php';</script>";
    exit;
}

// Jana nombor pesanan unik
$no_pesanan = 'ORD' . date('Ymd') . strtoupper(substr(uniqid(), -6));

// Mulakan transaksi
$conn->begin_transaction();

try {
    // Masukkan data pesanan
    $sql_order = "INSERT INTO pesanan 
                  (usahawan_id, no_pesanan, nama_pelanggan, no_telefon, alamat, nota, cara_hantar, cara_bayar, jumlah_bayaran, status_pesanan, status_bayaran) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)";
    $stmt_order = $conn->prepare($sql_order);
    
    // Tentukan status bayaran
    $status_bayaran = ($cara_bayar == 'cod') ? 'pending' : 'pending';
    
    $stmt_order->bind_param("isssssssds", 
        $usahawan_id, 
        $no_pesanan, 
        $nama_pelanggan, 
        $no_telefon, 
        $alamat, 
        $nota, 
        $cara_hantar, 
        $cara_bayar, 
        $total,
        $status_bayaran
    );
    
    if (!$stmt_order->execute()) {
        throw new Exception("Gagal menyimpan pesanan: " . $stmt_order->error);
    }
    
    $pesanan_id = $conn->insert_id;

    // Masukkan item pesanan
    $sql_item = "INSERT INTO pesanan_item 
                 (pesanan_id, produk_id, nama_produk, gambar_url, harga, kuantiti, subtotal) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt_item = $conn->prepare($sql_item);

    foreach($items as $item) {
        $stmt_item->bind_param("iissdid",
            $pesanan_id,
            $item['produk_id'],
            $item['nama'],
            $item['gambar_url'],
            $item['harga'],
            $item['kuantiti'],
            $item['subtotal']
        );
        
        if (!$stmt_item->execute()) {
            throw new Exception("Gagal menyimpan item pesanan: " . $stmt_item->error);
        }
    }

    // PENTING: Padam item dari cart selepas order berjaya
    $sql_delete = "DELETE FROM cart WHERE id IN ($id_list) AND usahawan_id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $usahawan_id);
    
    if (!$stmt_delete->execute()) {
        throw new Exception("Gagal memadamkan item dari cart: " . $stmt_delete->error);
    }

    // Commit transaksi jika semua berjaya
    $conn->commit();

    // Redirect ke halaman order confirmation
    echo "<script>
        alert('Pesanan berjaya dibuat! Nombor Pesanan: " . $no_pesanan . "');
        window.location.href='pesanan_detail.php?order_id=" . $pesanan_id . "';
    </script>";
    exit;

} catch (Exception $e) {
    // Rollback jika ada error
    $conn->rollback();
    
    // Log error untuk debugging
    error_log("Checkout Error: " . $e->getMessage());
    
    // Show detailed error for debugging (REMOVE IN PRODUCTION!)
    $error_msg = $e->getMessage();
    $error_msg = str_replace("'", "\\'", $error_msg); // Escape quotes for JavaScript
    
    echo "<script>
        alert('Ralat: " . $error_msg . "\\n\\nSila semak debug_checkout.php untuk maklumat lanjut.');
        window.location.href='cart.php';
    </script>";
    exit;
}
?>