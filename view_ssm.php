<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    die("Akses tidak dibenarkan");
}

$file = basename($_GET['file'] ?? '');

$path = __DIR__ . "/uploads/ssm/" . $file;

if (!$file || !file_exists($path)) {
    die("Fail SSM tidak ditemui");
}

$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

if ($ext === 'pdf') {
    header("Content-Type: application/pdf");
} else {
    header("Content-Type: image/$ext");
}

readfile($path);
exit;
