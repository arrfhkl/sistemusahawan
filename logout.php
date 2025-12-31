<?php
session_start();

$_SESSION['toast_logout'] = true;

session_destroy();
session_start(); // restart untuk simpan flag

$_SESSION['toast_logout'] = true;

header("Location: index.php");
exit();
