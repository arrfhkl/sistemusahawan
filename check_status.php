<?php
include "connection.php";

$user_id = (int)$_GET['user_id'];
$res = $conn->query("
SELECT last_active FROM user_online_status WHERE user_id=$user_id
");

if($res->num_rows==0){
 echo "offline"; exit;
}
$last = strtotime($res->fetch_assoc()['last_active']);
echo (time()-$last < 60) ? "online":"offline";
