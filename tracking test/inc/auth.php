<?php
// inc/auth.php
session_start();
include __DIR__ . "/../inc/db.php";


function is_logged_in() {
return !empty($_SESSION['admin_id']);
}


function require_login() {
if (!is_logged_in()) {
header('Location: admin/login.php');
exit;
}
}