<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../user/login.php');
    exit;
}

$host = 'localhost';
$dbname = 'vumeemyi_database';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// Ambil data pengiriman
$stmt = $pdo->prepare("SELECT up.id, up.resi_code, up.payment_status, up.status_shipping, up.created_at, u.name as customer_name
                       FROM user_payment up
                       JOIN users u ON up.user = u.id
                       ORDER BY up.created_at DESC");
$stmt->execute();
$shipments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Update status pengiriman
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $resi_code = $_POST['resi_code'];  // Using resi_code to identify the shipment
    $new_status = $_POST['shipping_status'];  // New status chosen by the admin

    // Prepare update statement to change status_shipping based on resi_code
    $update_stmt = $pdo->prepare("UPDATE user_payment SET status_shipping = ? WHERE resi_code = ?");
    if ($update_stmt->execute([$new_status, $resi_code])) {
        $_SESSION['success_message'] = "Shipping status updated successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to update shipping status.";
    }

    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengiriman - Vumee Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #4a00e0;
            --secondary: #8e2de2;
            --accent: #ff5722;
            --dark: #121212;
            --light: #f5f5f5;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
        }
        
        /* Header Styles */
        .admin-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .admin-header h1 {
            font-size: 1.5rem;
        }
        
        .admin-nav ul {
            display: flex;
            list-style: none;
        }
        
        .admin-nav li {
            margin-left: 1.5rem;
        }
        
        .admin-nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .admin-nav a:hover {
            color: var(--accent);
        }
        
        /* Main Layout */
        .admin-container {
            display: flex;
            min-height: calc(100vh - 60px);
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background-color: white;
            padding: 1.5rem;
            box-shadow: 2px 0 5px rgba(0,0,0,0.05);
        }
        
        .sidebar-nav ul {
            list-style: none;
        }
        
        .sidebar-nav li {
            margin-bottom: 0.5rem;
        }
        
        .sidebar-nav a {
            display: block;
            padding: 0.8rem 1rem;
            color: var(--dark);
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background-color: rgba(74, 0, 224, 0.1);
            color: var(--primary);
        }
        
        .sidebar-nav i {
            width: 20px;
            margin-right: 10px;
            text-align: center;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            padding: 2rem;
        }
        
        .shipments-container {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
        }
        
        th, td {
            padding: 0.8rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background-color: #f9f9f9;
            font-weight: 500;
        }
        
        /* Status Badges */
        .status {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            display: inline-block;
        }
        
        .status-order-placed {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .status-in-transit {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }
        
        /* Update Form Styles */
        .update-form {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        
        .status-select {
            padding: 0.5rem;
            border-radius: 4px;
            border: 1px solid #ddd;
            min-width: 120px;
        }
        
        .update-btn {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.8rem;
        }
        
        .update-btn:hover {
            background-color: var(--secondary);
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .admin-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
            }
            
            .update-form {
                flex-direction: column;
                align-items: flex-start;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
        }
        
        /* Alert Messages */
        .alert {
            padding: 0.75rem 1.25rem;
            margin-bottom: 1rem;
            border-radius: 4px;
            display: none;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <h1>Dashboard Admin Vumee</h1>
        <nav class="admin-nav">
            <ul>
                <li><a href="index.php"><i class="fas fa-user"></i> <?php echo $_SESSION['user_name']; ?></a></li>
                <li><a href="../user/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </header>

    <div class="admin-container">
        <aside class="sidebar">
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="manage-products.php"><i class="fas fa-box"></i> Kelola Produk</a></li>
                    <li><a href="manage-orders.php"><i class="fas fa-shopping-cart"></i> Kelola Pesanan</a></li>
                    <li><a href="manage-users.php"><i class="fas fa-users"></i> Kelola Pengguna</a></li>
                    <li><a href="manage-shipments.php" class="active"><i class="fas fa-truck"></i> Kelola Pengiriman Resi</a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <div class="shipments-container">
                <h2><i class="fas fa-truck"></i> Kelola Status Pengiriman</h2>
                
                <?php if (isset($success_message)): ?>
                <div class="alert alert-success" id="success-alert">
                    <?php echo $success_message; ?>
                </div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                <div class="alert alert-error" id="error-alert">
                    <?php echo $error_message; ?>
                </div>
                <?php endif; ?>
                
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pelanggan</th>
                            <th>Kode Resi</th>
                            <!--<th>Status</th>-->
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($shipments as $shipment): ?>
                    <tr>
                        <td>#<?php echo $shipment['id']; ?></td>
                        <td><?php echo htmlspecialchars($shipment['customer_name']); ?></td>
                        <td><?php echo htmlspecialchars($shipment['resi_code']); ?></td>
                        <!--<td>-->
                        <!--    <span class="status status-<?php echo strtolower(str_replace(' ', '-', $shipment['payment_status'])); ?>">-->
                        <!--        <?php echo $shipment['payment_status']; ?>-->
                        <!--    </span>-->
                        <!--</td>-->
                        <td><?php echo date('d M Y', strtotime($shipment['created_at'])); ?></td>
                        <td>
                            <form method="POST" action="">
                                <input type="hidden" name="resi_code" value="<?php echo $shipment['resi_code']; ?>"> <!-- Use resi_code -->
                                <select class="status-select" name="shipping_status">
                                    <option value="Order Placed" <?php echo ($shipment['status_shipping'] == 'Order Placed') ? 'selected' : ''; ?>>Order Placed</option>
                                    <option value="In Transit" <?php echo ($shipment['status_shipping'] == 'In Transit') ? 'selected' : ''; ?>>In Transit</option>
                                    <option value="Shipped" <?php echo ($shipment['status_shipping'] == 'Shipped') ? 'selected' : ''; ?>>Shipped</option>
                                </select>
                                <button class="update-btn" type="submit" name="update_status">
                                    <i class="fas fa-sync-alt"></i> Update
                                </button>
                            </form>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        // Show alert messages temporarily
        document.addEventListener('DOMContentLoaded', function() {
            const successAlert = document.getElementById('success-alert');
            const errorAlert = document.getElementById('error-alert');
            
            if (successAlert) {
                successAlert.style.display = 'block';
                setTimeout(() => {
                    successAlert.style.display = 'none';
                }, 3000);
            }
            
            if (errorAlert) {
                errorAlert.style.display = 'block';
                setTimeout(() => {
                    errorAlert.style.display = 'none';
                }, 3000);
            }
        });
    </script>
</body>
</html>
