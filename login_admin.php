<?php
session_start();

// ===== Database Connection =====
include "connection.php";

if ($conn->connect_error) {
    die("Sambungan gagal: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $conn->real_escape_string($_POST['password']);

    $sql = "SELECT * FROM admin WHERE username='$username' AND password='$password'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $_SESSION['admin_id'] = $row['id'];
        $_SESSION['admin_username'] = $row['username'];

        echo "<script>
            alert('Log masuk berjaya!');
            window.location.href = 'admin_dashboard.php';
        </script>";
        exit();
    } else {
        echo "<script>alert('Nama pengguna atau kata laluan salah!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
  <meta charset="UTF-8">
  <title>Log Masuk Admin - Sistem Usahawan Pahang</title>
  <link rel="icon" type="image/png" href="assets/img/jatapahang.png">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background: #f2f5f9;
      margin: 0;
      padding: 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: flex-start;
      min-height: 100vh;
    }

    /* ===== Header (Government Style) ===== */
    .header {
      width: 100%;
      background: linear-gradient(90deg, #002147, #003399);
      color: #fff;
      text-align: center;
      padding: 25px 0;
      box-shadow: 0 3px 10px rgba(0,0,0,0.2);
    }

    .header img {
      width: 80px;
      margin-bottom: 10px;
    }

    .header h1 {
      font-size: 1.8rem;
      margin: 0;
      font-weight: 600;
      letter-spacing: 1px;
    }

    .header p {
      font-size: 1rem;
      color: #FFD700;
      margin-top: 5px;
    }

    /* ===== Login Box ===== */
    .login-box {
      background: #fff;
      border: 1px solid #ccc;
      border-radius: 10px;
      padding: 40px 50px;
      width: 100%;
      max-width: 400px;
      margin-top: 60px;
      box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }

    .login-box h2 {
      text-align: center;
      color: #003399;
      margin-bottom: 25px;
      font-size: 1.5rem;
    }

    .login-box input {
      width: 100%;
      padding: 12px;
      margin: 8px 0 18px;
      border: 1px solid #bbb;
      border-radius: 5px;
      font-size: 1rem;
    }

    .login-box button {
      width: 100%;
      background: #003399;
      color: #fff;
      border: none;
      padding: 12px;
      font-size: 1rem;
      border-radius: 5px;
      cursor: pointer;
      transition: 0.3s;
    }

    .login-box button:hover {
      background: #002147;
    }

    .login-box .footer-text {
      text-align: center;
      margin-top: 15px;
      font-size: 0.9rem;
      color: #555;
    }

    .login-box .footer-text a {
      color: #003399;
      text-decoration: none;
      font-weight: 600;
    }

    .login-box .footer-text a:hover {
      text-decoration: underline;
    }

    /* ===== Footer ===== */
    .footer {
      margin-top: auto;
      width: 100%;
      background: #002147;
      color: #fff;
      text-align: center;
      padding: 15px 0;
      font-size: 0.9rem;
    }

    @media (max-width: 480px) {
      .login-box {
        margin-top: 40px;
        padding: 30px;
      }
    }
  </style>
</head>
<body>

  <div class="header">
    <img src="assets/img/jatapahang.png" alt="Jata Negara">
    <h1>SISTEM USAHAWAN PAHANG</h1>
    <p>Portal Rasmi Log Masuk Admin</p>
  </div>

  <div class="login-box">
    <h2>Log Masuk Admin</h2>
    <form method="POST">
      <input type="text" name="username" placeholder="Nama Pengguna" required>
      <input type="password" name="password" placeholder="Kata Laluan" required>
      <button type="submit">Log Masuk</button>
    </form>
    <div class="footer-text">
      <p>Kembali ke <a href="index.php">Laman Utama</a></p>
    </div>
  </div>

  <div class="footer">
    © 2025 Kerajaan Negeri Pahang | Dibangunkan untuk Sistem Usahawan
  </div>

</body>
</html>
