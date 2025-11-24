<?php 

$conn = new mysqli("localhost", "pelangai_pelangai", "Amizar77@1973", "pelangai_pelangai");

    if($conn->connect_error){
        die("Connection failed: " . $conn->connect_error);
    }
?>