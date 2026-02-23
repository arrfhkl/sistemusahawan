<?php
include 'connection.php';
include 'header.php';
$conn = new mysqli("localhost", "root", "", "sistem_usahawan_pahang");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/* ===============================
   LOGIN CHECK
================================ */
if (!isset($_SESSION['usahawan_id'])) {
    echo "<script>
        alert('Sila login dahulu');
        window.location='login.php';
    </script>";
    exit;
}

$usahawan_id = (int) $_SESSION['usahawan_id'];

/* ===============================
   GET BOOKING DATA
================================ */
$stmt = $conn->prepare("
    SELECT sb.*, s.nama AS nama_servis
    FROM servis_booking sb
    JOIN servis s ON sb.service_id = s.id
    WHERE sb.usahawan_id = ?
    ORDER BY sb.tarikh DESC
");

$stmt->bind_param("i", $usahawan_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Senarai Tempahan Servis</title>
    <style>
        body { font-family: Arial; background:#f4f4f4; }
        .container {
            max-width:1000px;
            margin:40px auto;
            background:white;
            padding:25px;
            border-radius:10px;
            box-shadow:0 0 15px rgba(0,0,0,.1);
        }
        table {
            width:100%;
            border-collapse: collapse;
        }
        th, td {
            padding:12px;
            border-bottom:1px solid #ddd;
            text-align:left;
        }
        th {
            background:#007bff;
            color:white;
        }
        .status {
            padding:5px 10px;
            border-radius:20px;
            font-size:13px;
            font-weight:bold;
        }
        .pending { background:#ffc107; color:#000; }
        .approved { background:#28a745; color:white; }
        .rejected { background:#dc3545; color:white; }
        .completed { background:#6c757d; color:white; }

        .btn-lihat {
            background:#007bff;
            color:white;
            padding:6px 12px;
            border-radius:5px;
            text-decoration:none;
            font-size:13px;
        }

        .btn-lihat:hover {
            background:#0056b3;
        }

        .btn-chat {
            background:#28a745;
            color:white;
            padding:6px 12px;
            border-radius:5px;
            text-decoration:none;
            font-size:13px;
        }

        .btn-chat:hover {
            background:#1e7e34;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Senarai Tempahan Servis</h2>

    <br>

    <?php if ($result->num_rows > 0): ?>
        <table>
            <tr>
                <th>Nama Servis</th>
                <th>Nama Pelanggan</th>
                <th>Telefon</th>
                <th>Tarikh</th>
                <th>Status</th>
                <th>Butiran</th>
                <th>Chat</th>
            </tr>

            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['nama_servis']) ?></td>
                    <td><?= htmlspecialchars($row['nama_pelanggan']) ?></td>
                    <td><?= htmlspecialchars($row['telefon']) ?></td>
                    <td><?= $row['tarikh'] ?></td>
                    <td>
                        <span class="status <?= $row['status'] ?>">
                            <?= strtoupper($row['status']) ?>
                        </span>
                    </td>
                    <td>
                    <a href="booking_detail.php?id=<?= $row['id'] ?>" class="btn-lihat">
                        Lihat
                    </a>
                    </td>
                    <td>
                        <a href="chat_room.php?booking_id=<?= $row['id'] ?>" class="btn-chat">
                            Chat
                        </a>
                    </td>
                </tr>

            <?php endwhile; ?>

        </table>
    <?php else: ?>
        <p>Tiada tempahan lagi.</p>
    <?php endif; ?>

</div>

</body>
</html>

<? include 'footer.php'; ?>

<?php
$stmt->close();
$conn->close();
?>