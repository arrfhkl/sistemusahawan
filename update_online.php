<?php
session_start();
include "connection.php";

$user_id = $_SESSION['usahawan_id'];
$conn->query("
REPLACE INTO user_online_status (user_id, last_active)
VALUES ($user_id, NOW())
");
