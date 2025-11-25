<?php
// Database connection
$host = 'localhost';
$dbname = 'sistem_usahawan_pahang';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Fetch orders with related data
$query = "SELECT 
    p.*,
    uw.nama as nama_pelanggan,
    us.telefon as telefon_pelanggan,
    us.email as email_pelanggan,
    uw.nama as nama_usahawan
FROM pesanan p
LEFT JOIN users us ON p.id = us.id
LEFT JOIN usahawan uw ON p.usahawan_id = uw.id
ORDER BY p.tarikh_pesanan DESC";

$stmt = $conn->prepare($query);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get products for each order
foreach ($orders as &$order) {
    $produkQuery = "SELECT 
        pp.*,
        pr.nama as nama_produk,
        pr.gambar_url
    FROM pesanan_item pp
    LEFT JOIN produk pr ON pp.produk_id = pr.id
    WHERE pp.pesanan_id = :pesanan_id";
    
    $produkStmt = $conn->prepare($produkQuery);
    $produkStmt->execute(['pesanan_id' => $order['id']]);
    $order['produk'] = $produkStmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get statistics
$statsQuery = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status_pesanan = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status_pesanan = 'completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN status_pesanan = 'cancelled' THEN 1 ELSE 0 END) as cancelled
FROM pesanan";

$statsStmt = $conn->prepare($statsQuery);
$statsStmt->execute();
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Pesanan</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            color: #2c3e50;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }

        .header p {
            opacity: 0.9;
            font-size: 14px;
        }

        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }

        .stat-card h3 {
            color: #7f8c8d;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card .value {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
        }

        .stat-card.pending { border-left: 4px solid #f39c12; }
        .stat-card.completed { border-left: 4px solid #27ae60; }
        .stat-card.cancelled { border-left: 4px solid #e74c3c; }
        .stat-card.total { border-left: 4px solid #3498db; }

        .filters {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filters input,
        .filters select {
            padding: 10px 15px;
            border: 1px solid #dfe6e9;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.3s;
        }

        .filters input:focus,
        .filters select:focus {
            border-color: #667eea;
        }

        .filters button {
            padding: 10px 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: transform 0.2s;
        }

        .filters button:hover {
            transform: scale(1.05);
        }

        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1200px;
        }

        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        thead th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tbody tr {
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.2s;
        }

        tbody tr:hover {
            background: #f8f9fa;
        }

        tbody td {
            padding: 15px;
            font-size: 14px;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .action-btn {
            padding: 6px 12px;
            margin: 0 3px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-view {
            background: #3498db;
            color: white;
        }

        .btn-view:hover {
            background: #2980b9;
        }

        .btn-edit {
            background: #f39c12;
            color: white;
        }

        .btn-edit:hover {
            background: #e67e22;
        }

        .btn-delete {
            background: #e74c3c;
            color: white;
        }

        .btn-delete:hover {
            background: #c0392b;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 12px;
            max-width: 900px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .modal-header h2 {
            color: #2c3e50;
            font-size: 24px;
        }

        .close {
            font-size: 28px;
            font-weight: bold;
            color: #7f8c8d;
            cursor: pointer;
            transition: color 0.2s;
        }

        .close:hover {
            color: #2c3e50;
        }

        .detail-group {
            margin-bottom: 20px;
        }

        .detail-group h3 {
            color: #667eea;
            font-size: 18px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }

        .detail-item {
            display: grid;
            grid-template-columns: 200px 1fr;
            padding: 10px 0;
            border-bottom: 1px solid #f8f9fa;
        }

        .detail-item strong {
            color: #7f8c8d;
            font-size: 14px;
        }

        .detail-item span {
            color: #2c3e50;
            font-size: 14px;
        }

        .products-table {
            width: 100%;
            margin-top: 15px;
            border: 1px solid #dfe6e9;
            border-radius: 8px;
            overflow: hidden;
        }

        .products-table th,
        .products-table td {
            padding: 12px;
            text-align: left;
            font-size: 13px;
        }

        .products-table thead {
            background: #f8f9fa;
        }

        .product-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
            font-size: 16px;
        }

        .usahawan-tag {
            display: inline-block;
            background: #e8f5e9;
            color: #2e7d32;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>👨‍💼 Admin Dashboard - Pengurusan Pesanan</h1>
        <p>Sistem Pengurusan Pesanan Keseluruhan Platform</p>
    </div>

    <div class="container">
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card pending">
                <h3>Pesanan Pending</h3>
                <div class="value"><?php echo $stats['pending'] ?? 0; ?></div>
            </div>
            <div class="stat-card completed">
                <h3>Pesanan Selesai</h3>
                <div class="value"><?php echo $stats['completed'] ?? 0; ?></div>
            </div>
            <div class="stat-card cancelled">
                <h3>Pesanan Dibatalkan</h3>
                <div class="value"><?php echo $stats['cancelled'] ?? 0; ?></div>
            </div>
            <div class="stat-card total">
                <h3>Jumlah Pesanan</h3>
                <div class="value"><?php echo $stats['total'] ?? 0; ?></div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters">
            <input type="text" id="search" placeholder="Cari nama pelanggan, usahawan atau no. pesanan...">
            <select id="filterStatus">
                <option value="">Semua Status</option>
                <option value="pending">Pending</option>
                <option value="completed">Selesai</option>
                <option value="cancelled">Dibatalkan</option>
            </select>
            <input type="date" id="filterDate">
            <button onclick="applyFilters()">🔍 Cari</button>
            <button onclick="resetFilters()">🔄 Reset</button>
        </div>

        <!-- Table -->
        <div class="table-container">
            <table id="ordersTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>No. Pesanan</th>
                        <th>Usahawan</th>
                        <th>Pelanggan</th>
                        <th>No. Telefon</th>
                        <th>Tarikh</th>
                        <th>Jumlah</th>
                        <th>Status Pesanan</th>
                        <th>Status Bayaran</th>
                        <th>Tindakan</th>
                    </tr>
                </thead>
                <tbody id="ordersBody">
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="10" class="no-data">Tiada data pesanan</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <tr data-search="<?php echo strtolower(($order['nama_pelanggan'] ?? '') . ' ' . ($order['nama_usahawan'] ?? '') . ' ' . $order['no_pesanan']); ?>" 
                                data-status="<?php echo $order['status_pesanan']; ?>"
                                data-date="<?php echo date('Y-m-d', strtotime($order['tarikh_pesanan'])); ?>">
                                <td><?php echo htmlspecialchars($order['id']); ?></td>
                                <td><strong><?php echo htmlspecialchars($order['no_pesanan']); ?></strong></td>
                                <td>
                                    <span class="usahawan-tag">
                                        🏪 <?php echo htmlspecialchars($order['nama_usahawan'] ?? 'N/A'); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($order['nama_pelanggan'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($order['no_telefon'] ?? 'N/A'); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($order['tarikh_pesanan'])); ?></td>
                                <td><strong>RM <?php echo number_format($order['jumlah_bayaran'], 2); ?></strong></td>
                                <td>
                                    <span class="status-badge status-<?php echo $order['status_pesanan']; ?>">
                                        <?php 
                                        $statusMap = [
                                            'pending' => 'Pending',
                                            'completed' => 'Selesai',
                                            'cancelled' => 'Dibatalkan'
                                        ];
                                        echo $statusMap[$order['status_pesanan']] ?? $order['status_pesanan'];
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $order['status_bayaran']; ?>">
                                        <?php 
                                        $statusMap = [
                                            'pending' => 'Pending',
                                            'completed' => 'Selesai',
                                            'cancelled' => 'Dibatalkan'
                                        ];
                                        echo $statusMap[$order['status_bayaran']] ?? $order['status_bayaran'];
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="action-btn btn-view" onclick='viewOrder(<?php echo json_encode($order, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>👁️</button>
                                    <button class="action-btn btn-edit" onclick="editOrder(<?php echo $order['id']; ?>)">✏️</button>
                                    <button class="action-btn btn-delete" onclick="deleteOrder(<?php echo $order['id']; ?>)">🗑️</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal for Order Details -->
    <div id="orderModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Butiran Pesanan</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div id="modalBody"></div>
        </div>
    </div>

    <script>
        function viewOrder(order) {
            const modalBody = document.getElementById('modalBody');
            
            // Translate status
            const statusMap = {
                'pending': 'Pending',
                'completed': 'Selesai',
                'cancelled': 'Dibatalkan'
            };

            let productsHTML = '';
            let totalAmount = 0;

            if (order.produk && order.produk.length > 0) {
                productsHTML = order.produk.map(p => {
                    totalAmount += parseFloat(p.subtotal || 0);
                    return `
                        <tr>
                            <td>
                                ${p.gambar_url ? `<img src="${p.gambar_url}" class="product-img" alt="${p.nama_produk}">` : '📦'}
                            </td>
                            <td>${p.nama_produk || 'N/A'}</td>
                            <td>RM ${parseFloat(p.harga || 0).toFixed(2)}</td>
                            <td>${p.kuantiti || 0}</td>
                            <td>RM ${parseFloat(p.subtotal || 0).toFixed(2)}</td>
                        </tr>
                    `;
                }).join('');
            } else {
                productsHTML = '<tr><td colspan="5" class="no-data">Tiada produk</td></tr>';
            }

            modalBody.innerHTML = `
                <div class="detail-group">
                    <h3>Maklumat Usahawan</h3>
                    <div class="detail-item">
                        <strong>Nama Usahawan:</strong>
                        <span><span class="usahawan-tag">🏪 ${order.nama_usahawan || 'N/A'}</span></span>
                    </div>
                    <div class="detail-item">
                        <strong>ID Usahawan:</strong>
                        <span>${order.usahawan_id || 'N/A'}</span>
                    </div>
                </div>

                <div class="detail-group">
                    <h3>Maklumat Pelanggan</h3>
                    <div class="detail-item">
                        <strong>Nama:</strong>
                        <span>${order.nama_pelanggan || 'N/A'}</span>
                    </div>
                    <div class="detail-item">
                        <strong>No. Telefon:</strong>
                        <span>${order.no_telefon || 'N/A'}</span>
                    </div>
                    <div class="detail-item">
                        <strong>Email:</strong>
                        <span>${order.email_pelanggan || 'N/A'}</span>
                    </div>
                    <div class="detail-item">
                        <strong>Alamat:</strong>
                        <span>${order.alamat || 'N/A'}</span>
                    </div>
                </div>

                <div class="detail-group">
                    <h3>Maklumat Pesanan</h3>
                    <div class="detail-item">
                        <strong>No. Pesanan:</strong>
                        <span><strong>${order.no_pesanan}</strong></span>
                    </div>
                    <div class="detail-item">
                        <strong>Tarikh Pesanan:</strong>
                        <span>${formatDate(order.tarikh_pesanan)}</span>
                    </div>
                    <div class="detail-item">
                        <strong>Cara Hantar:</strong>
                        <span>${order.cara_hantar || 'N/A'}</span>
                    </div>
                    <div class="detail-item">
                        <strong>Cara Bayar:</strong>
                        <span>${order.cara_bayar || 'N/A'}</span>
                    </div>
                    <div class="detail-item">
                        <strong>Status Pesanan:</strong>
                        <span><span class="status-badge status-${order.status_pesanan}">${statusMap[order.status_pesanan] || order.status_pesanan}</span></span>
                    </div>
                    <div class="detail-item">
                        <strong>Status Bayaran:</strong>
                        <span><span class="status-badge status-${order.status_bayaran}">${statusMap[order.status_bayaran] || order.status_bayaran}</span></span>
                    </div>
                    ${order.tarikh_diproses ? `
                    <div class="detail-item">
                        <strong>Tarikh Diproses:</strong>
                        <span>${formatDate(order.tarikh_diproses)}</span>
                    </div>
                    ` : ''}
                    ${order.tarikh_dihantar ? `
                    <div class="detail-item">
                        <strong>Tarikh Dihantar:</strong>
                        <span>${formatDate(order.tarikh_dihantar)}</span>
                    </div>
                    ` : ''}
                    ${order.tarikh_selesai ? `
                    <div class="detail-item">
                        <strong>Tarikh Selesai:</strong>
                        <span>${formatDate(order.tarikh_selesai)}</span>
                    </div>
                    ` : ''}
                    ${order.tarikh_dibatalkan ? `
                    <div class="detail-item">
                        <strong>Tarikh Dibatalkan:</strong>
                        <span>${formatDate(order.tarikh_dibatalkan)}</span>
                    </div>
                    ` : ''}
                    ${order.sebab_batal ? `
                    <div class="detail-item">
                        <strong>Sebab Batal:</strong>
                        <span>${order.sebab_batal}</span>
                    </div>
                    ` : ''}
                    ${order.nota ? `
                    <div class="detail-item">
                        <strong>Nota:</strong>
                        <span>${order.nota}</span>
                    </div>
                    ` : ''}
                    ${order.nota_pesanan ? `
                    <div class="detail-item">
                        <strong>Nota Pesanan:</strong>
                        <span>${order.nota_pesanan}</span>
                    </div>
                    ` : ''}
                    ${order.stripe_session_id ? `
                    <div class="detail-item">
                        <strong>Stripe Session ID:</strong>
                        <span style="font-size: 11px; word-break: break-all;">${order.stripe_session_id}</span>
                    </div>
                    ` : ''}
                </div>

                <div class="detail-group">
                    <h3>Produk Dipesan</h3>
                    <table class="products-table">
                        <thead>
                            <tr>
                                <th>Gambar</th>
                                <th>Produk</th>
                                <th>Harga</th>
                                <th>Kuantiti</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${productsHTML}
                            <tr style="background: #f8f9fa; font-weight: bold;">
                                <td colspan="4" style="text-align: right;">Jumlah Bayaran:</td>
                                <td>RM ${parseFloat(order.jumlah_bayaran || 0).toFixed(2)}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            `;

            document.getElementById('orderModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('orderModal').style.display = 'none';
        }

        function editOrder(id) {
            // Redirect to edit page
            window.location.href = 'edit_pesanan.php?id=' + id;
        }

        function deleteOrder(id) {
            if (confirm('⚠️ AMARAN: Adakah anda pasti mahu memadam pesanan ini?\n\nTindakan ini tidak boleh dibatalkan!')) {
                // Send AJAX request to delete
                fetch('delete_pesanan.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: id })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ Pesanan berjaya dipadam');
                        location.reload();
                    } else {
                        alert('❌ Gagal memadam pesanan: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('❌ Error: ' + error);
                });
            }
        }

        function applyFilters() {
            const search = document.getElementById('search').value.toLowerCase();
            const status = document.getElementById('filterStatus').value;
            const date = document.getElementById('filterDate').value;
            const rows = document.querySelectorAll('#ordersBody tr[data-search]');

            rows.forEach(row => {
                const searchText = row.getAttribute('data-search');
                const rowStatus = row.getAttribute('data-status');
                const rowDate = row.getAttribute('data-date');

                let showRow = true;

                if (search && !searchText.includes(search)) {
                    showRow = false;
                }

                if (status && rowStatus !== status) {
                    showRow = false;
                }

                if (date && rowDate !== date) {
                    showRow = false;
                }

                row.style.display = showRow ? '' : 'none';
            });
        }

        function resetFilters() {
            document.getElementById('search').value = '';
            document.getElementById('filterStatus').value = '';
            document.getElementById('filterDate').value = '';
            
            const rows = document.querySelectorAll('#ordersBody tr');
            rows.forEach(row => {
                row.style.display = '';
            });
        }

        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleDateString('ms-MY', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('orderModal');
            if (event.target == modal) {
                closeModal();
            }
        }

        // Real-time search
        document.getElementById('search').addEventListener('keyup', applyFilters);
        document.getElementById('filterStatus').addEventListener('change', applyFilters);
        document.getElementById('filterDate').addEventListener('change', applyFilters);
    </script>
</body>
</html>