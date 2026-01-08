<?php
session_start();
include "connection.php";

$id = (int)$_POST['message_id'];
$user = $_SESSION['usahawan_id'];

$conn->query("
UPDATE chat_messages
SET is_deleted=1
WHERE id=$id AND sender_id=$user
");
