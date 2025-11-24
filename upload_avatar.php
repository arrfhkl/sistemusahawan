<?php
// DB config
$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "sistem_usahawan_pahang";

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) die("DB Error: " . $conn->connect_error);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) $_POST['id'];

    if (!empty($_FILES['avatar']['tmp_name'])) {
        $uploadDir = "uploads/"; // make sure this folder is writable
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $filename = time() . "_" . basename($_FILES['avatar']['name']);
        $targetFile = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetFile)) {
            // store relative path in DB
            $stmt = $conn->prepare("UPDATE usahawan SET avatar=? WHERE id=?");
            $stmt->bind_param("si", $targetFile, $id);

            if ($stmt->execute()) {
                header("Location: profil_usahawan.php?id=" . $id);
                exit;
            } else {
                echo "Database update failed: " . $stmt->error;
            }
        } else {
            echo "Upload failed!";
        }
    }
}
