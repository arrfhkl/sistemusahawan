<?php
session_start();
include "connection.php";

echo "<h2>🔍 Debug Checkout System</h2>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} pre{background:#f5f5f5;padding:10px;border-radius:5px;}</style>";

// 1. Check if tables exist
echo "<h3>1. Checking Tables...</h3>";
$tables_to_check = ['pesanan', 'pesanan_item', 'cart', 'produk', 'usahawan'];

foreach($tables_to_check as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if($result->num_rows > 0) {
        echo "<p class='success'>✓ Table '$table' exists</p>";
        
        // Show table structure
        $structure = $conn->query("DESCRIBE $table");
        echo "<details><summary>View structure</summary><pre>";
        while($row = $structure->fetch_assoc()) {
            echo $row['Field'] . " | " . $row['Type'] . " | " . $row['Null'] . " | " . $row['Key'] . "\n";
        }
        echo "</pre></details>";
    } else {
        echo "<p class='error'>✗ Table '$table' NOT FOUND!</p>";
    }
}

// 2. Check session
echo "<h3>2. Checking Session...</h3>";
if(isset($_SESSION['usahawan_id'])) {
    echo "<p class='success'>✓ User logged in: ID = " . $_SESSION['usahawan_id'] . "</p>";
} else {
    echo "<p class='error'>✗ No user logged in!</p>";
}

// 3. Check cart data (if user logged in)
if(isset($_SESSION['usahawan_id'])) {
    $usahawan_id = $_SESSION['usahawan_id'];
    
    echo "<h3>3. Checking Cart Data...</h3>";
    $sql = "SELECT c.*, p.nama, p.harga FROM cart c 
            LEFT JOIN produk p ON c.produk_id = p.id 
            WHERE c.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usahawan_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        echo "<p class='success'>✓ Found " . $result->num_rows . " items in cart</p>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Cart ID</th><th>Product Name</th><th>Price</th><th>Quantity</th></tr>";
        while($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['nama'] . "</td>";
            echo "<td>RM " . $row['harga'] . "</td>";
            echo "<td>" . $row['kuantiti'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>✗ Cart is empty!</p>";
    }
}

// 4. Test INSERT query
echo "<h3>4. Testing INSERT Query...</h3>";
echo "<p class='info'>Testing if we can insert into pesanan table...</p>";

$test_sql = "INSERT INTO pesanan 
    (usahawan_id, no_pesanan, nama_pelanggan, no_telefon, alamat, nota, cara_hantar, cara_bayar, jumlah_bayaran, status_pesanan, status_bayaran) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt_test = $conn->prepare($test_sql);

if($stmt_test) {
    echo "<p class='success'>✓ Prepare statement successful</p>";
    
    // Test with dummy data
    if(isset($_SESSION['usahawan_id'])) {
        $test_id = $_SESSION['usahawan_id'];
        $test_order = 'TEST' . time();
        $test_nama = 'Test User';
        $test_tel = '0123456789';
        $test_alamat = 'Test Address';
        $test_nota = 'Test Note';
        $test_hantar = 'delivery';
        $test_bayar = 'cod';
        $test_total = 100.00;
        $test_status_pesanan = 'pending';
        $test_status_bayaran = 'pending';
        
        $stmt_test->bind_param("isssssssdss", 
            $test_id, $test_order, $test_nama, $test_tel, $test_alamat, 
            $test_nota, $test_hantar, $test_bayar, $test_total, 
            $test_status_pesanan, $test_status_bayaran
        );
        
        if($stmt_test->execute()) {
            $inserted_id = $conn->insert_id;
            echo "<p class='success'>✓ Test INSERT successful! ID: $inserted_id</p>";
            
            // Clean up test data
            $conn->query("DELETE FROM pesanan WHERE id = $inserted_id");
            echo "<p class='info'>Test data cleaned up.</p>";
        } else {
            echo "<p class='error'>✗ Test INSERT failed: " . $stmt_test->error . "</p>";
        }
    }
} else {
    echo "<p class='error'>✗ Prepare statement failed: " . $conn->error . "</p>";
}

// 5. Check POST data (if coming from checkout)
echo "<h3>5. Checking POST Data...</h3>";
if(!empty($_POST)) {
    echo "<p class='success'>✓ POST data received</p>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
} else {
    echo "<p class='info'>No POST data (this is normal if accessing directly)</p>";
}

// 6. Show SQL Error Log
echo "<h3>6. Recent Errors...</h3>";
echo "<p class='info'>Connection errors: " . $conn->error . "</p>";

echo "<hr>";
echo "<h3>📝 Recommendations:</h3>";
echo "<ul>";
echo "<li>If tables don't exist → Run the SQL query from 'SQL Query - Orders Table' artifact</li>";
echo "<li>If cart is empty → Add some products to cart first</li>";
echo "<li>If not logged in → Login first</li>";
echo "<li>Check error_log file in your server for more details</li>";
echo "</ul>";

echo "<hr>";
echo "<p><a href='cart.php'>← Back to Cart</a> | <a href='index.php'>Home</a></p>";
?>