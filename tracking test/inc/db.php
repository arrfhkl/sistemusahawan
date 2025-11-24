<?php
// inc/db.php
$DB_HOST = 'localhost';
$DB_NAME = 'parcel_tracking';
$DB_USER = 'root';
$DB_PASS = '';


try {
$dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4";
$pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
} catch (Exception $e) {
die('Database connection failed: ' . $e->getMessage());
}