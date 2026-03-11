<?php
session_start();
require 'stripe-php/init.php';
require 'connection.php';

\Stripe\Stripe::setApiKey('sk_test_51SSyVKGbRah2rwXWBgdH4IanXzobmPMY6sEGinsOQQn1p6jenf74YK0L18K82P84OVxFEzECHwqbvbpfuVUSmHO100XSiafaMV');

/* ── Auth check ── */
if (!isset($_SESSION['usahawan_id'])) {
    echo "<script>alert('Sila log masuk.'); window.location='login.php';</script>";
    exit;
}

$user_id = (int)$_SESSION['usahawan_id'];

/* ── Get booking_id from URL ── */
if (!isset($_GET['booking_id'])) {
    die("ID tempahan tidak sah.");
}
$booking_id = (int)$_GET['booking_id'];

/* ── Get logged-in user's nama & telefon ── */
$uStmt = $conn->prepare("SELECT nama, telefon FROM usahawan WHERE id = ?");
$uStmt->bind_param("i", $user_id);
$uStmt->execute();
$currentUser = $uStmt->get_result()->fetch_assoc();
if (!$currentUser) die("Pengguna tidak dijumpai.");

/* ── Load booking — match by nama_pelanggan & telefon (how bookings are stored) ── */
$stmt = $conn->prepare("
    SELECT sb.*, s.nama AS nama_servis, u.nama AS nama_usahawan
    FROM servis_booking sb
    JOIN servis s ON sb.service_id = s.id
    JOIN usahawan u ON sb.usahawan_id = u.id
    WHERE sb.id = ?
      AND sb.nama_pelanggan = ?
      AND sb.telefon = ?
      AND sb.status = 'approved'
");
$stmt->bind_param("iss", $booking_id, $currentUser['nama'], $currentUser['telefon']);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    die("Tempahan tidak dijumpai, akses tidak dibenarkan, atau status bukan 'approved'. Booking ID: $booking_id | User: {$currentUser['nama']} | Telefon: {$currentUser['telefon']}");
}

/* ── Load quotation items ── */
$qi = $conn->prepare("SELECT * FROM quotation_items WHERE booking_id = ? ORDER BY id ASC");
$qi->bind_param("i", $booking_id);
$qi->execute();
$q_items = $qi->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($q_items)) {
    die("Tiada item sebut harga dijumpai untuk tempahan ini.");
}

/* ── Build Stripe line items ── */
$line_items = [];
foreach ($q_items as $item) {
    $item_label = $item['item_name'];
    if (!empty($item['item_desc'])) {
        $item_label .= ' — ' . $item['item_desc'];
    }
    $line_items[] = [
        'price_data' => [
            'currency'     => 'myr',
            'product_data' => ['name' => $item_label],
            'unit_amount'  => intval(round($item['unit_price'] * 100)),
        ],
        'quantity' => intval($item['qty']),
    ];
}

\Stripe\ApiRequestor::setHttpClient(
    new \Stripe\HttpClient\CurlClient([CURLOPT_SSL_VERIFYPEER => false])
);

/* ── Store in session for success page ── */
$_SESSION['pending_servis_payment'] = [
    'booking_id'     => $booking_id,
    'usahawan_id'    => $booking['usahawan_id'],
    'nama_servis'    => $booking['nama_servis'],
    'nama_pelanggan' => $booking['nama_pelanggan'],
    'telefon'        => $booking['telefon'],
    'alamat'         => $booking['alamat'],
    'total_amount'   => (float)$booking['harga_sebut'],
];

/* ── Create Stripe Checkout Session ── */
$session = \Stripe\Checkout\Session::create([
    'payment_method_types'       => ['card', 'fpx', 'grabpay'],
    'line_items'                 => $line_items,
    'mode'                       => 'payment',
    'success_url'                => 'http://localhost/sups/servis_payment_success.php?session_id={CHECKOUT_SESSION_ID}&booking_id=' . $booking_id,
    'cancel_url'                 => 'http://localhost/sups/customer_booking.php?cancelled=1',
    'billing_address_collection' => 'required',
    'phone_number_collection'    => ['enabled' => true],
]);

/* ── Redirect to Stripe ── */
header("Location: " . $session->url);
exit;
?>