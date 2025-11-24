<?php
require_once __DIR__.'/../inc/db.php';


$tn = $_GET['tn'] ?? '';
if (!$tn) {
header('Content-Type: application/json', true, 400);
echo json_encode(['error' => 'missing tn']);
exit;
}


$stmt = $pdo->prepare('SELECT * FROM parcels WHERE tracking_no = ?');
$stmt->execute([$tn]);
$parcel = $stmt->fetch();


$stmt = $pdo->prepare('SELECT * FROM parcel_logs WHERE tracking_no = ? ORDER BY log_time DESC');
$stmt->execute([$tn]);
$logs = $stmt->fetchAll();

header('Content-Type: application/json');
echo json_encode([
	'parcel' => $parcel,
	'logs' => $logs
]);