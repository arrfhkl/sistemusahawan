<?php
session_start();
require 'stripe-php/init.php';
require 'connection.php';

\Stripe\Stripe::setApiKey('sk_test_51SSyVKGbRah2rwXWBgdH4IanXzobmPMY6sEGinsOQQn1p6jenf74YK0L18K82P84OVxFEzECHwqbvbpfuVUSmHO100XSiafaMV');

// Check login
if (!isset($_SESSION['usahawan_id'])) {
    die("User not logged in.");
}

$usahawan_id = $_SESSION['usahawan_id'];

// Validate POST data
if (empty($_POST['selected_items'])) {
    die("Tiada item dipilih.");
}

// Get form data from checkout.php
$nama_pelanggan = $_POST['nama_pelanggan'] ?? '';
$no_telefon = $_POST['no_telefon'] ?? '';
$alamat = $_POST['alamat'] ?? '';
$nota = $_POST['nota'] ?? '';
$cara_hantar = $_POST['cara_hantar'] ?? 'delivery';
$cara_bayar = $_POST['cara_bayar'] ?? 'online';

// Validate required fields
if (empty($nama_pelanggan) || empty($no_telefon) || empty($alamat)) {
    die("Sila lengkapkan semua maklumat yang diperlukan.");
}

// Get selected cart items
$ids = array_map('intval', $_POST['selected_items']);
$id_list = implode(",", $ids);

// Load cart items with product details
$sql = "SELECT c.id as cart_id, c.kuantiti, c.produk_id, c.usahawan_id,
               p.nama as nama_produk, p.harga, p.gambar_url 
        FROM cart c 
        JOIN produk p ON c.produk_id = p.id 
        WHERE c.id IN ($id_list) AND c.usahawan_id = ?";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usahawan_id);
$stmt->execute();
$result = $stmt->get_result();

$cart_items = [];
$line_items = [];
$total_amount = 0;

while ($row = $result->fetch_assoc()) {
    $subtotal = $row['harga'] * $row['kuantiti'];
    $total_amount += $subtotal;
    
    // Save complete cart item data
    $cart_items[] = [
        'cart_id' => $row['cart_id'],
        'produk_id' => $row['produk_id'],
        'nama_produk' => $row['nama_produk'],
        'gambar_url' => $row['gambar_url'],
        'harga' => $row['harga'],
        'kuantiti' => $row['kuantiti'],
        'subtotal' => $subtotal
    ];

    // Prepare Stripe line items
    $line_items[] = [
        'price_data' => [
            'currency' => 'myr',
            'product_data' => ['name' => $row['nama_produk']],
            'unit_amount' => intval($row['harga'] * 100)
        ],
        'quantity' => intval($row['kuantiti'])
    ];
}

if (empty($line_items)) {
    die("Cart kosong.");
}

\Stripe\ApiRequestor::setHttpClient(
    new \Stripe\HttpClient\CurlClient([CURLOPT_SSL_VERIFYPEER => false])
);

// Store ALL order data in session
$_SESSION['pending_order'] = [
    'usahawan_id' => $usahawan_id,
    'cart_items' => $cart_items,
    'total_amount' => $total_amount,
    // Customer details
    'nama_pelanggan' => $nama_pelanggan,
    'no_telefon' => $no_telefon,
    'alamat' => $alamat,
    'nota' => $nota,
    // Delivery & Payment
    'cara_hantar' => $cara_hantar,
    'cara_bayar' => $cara_bayar,
    'tarikh_pesanan' => date('Y-m-d H:i:s')
];

$session = \Stripe\Checkout\Session::create([
    'payment_method_types' => [
        'card',
        'fpx',
        'grabpay'
    ],
    'line_items' => $line_items,
    'mode' => 'payment',

    'success_url' => 'http://localhost/sups/success.php?session_id={CHECKOUT_SESSION_ID}',
    'cancel_url'  => 'http://localhost/sups/cart.php',

    'billing_address_collection' => 'required',
    'phone_number_collection' => [
        'enabled' => true,
    ],
]);


// Redirect to Stripe payment page
header("Location: " . $session->url);
exit;
?>