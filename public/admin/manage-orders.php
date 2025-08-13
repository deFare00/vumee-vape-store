<?php
session_start();

// Redirect jika bukan admin
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../user/login.php');
    exit;
}

// Koneksi database
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

// Handle update status pesanan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $order_id]);
    
    header('Location: manage-orders.php?success=updated');
    exit;
}

// Menangani penghapusan pembayaran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_payment'])) {
    $payment_id = (int)$_POST['payment_id'];
    
    // Menyiapkan dan mengeksekusi query untuk menghapus pembayaran
    $stmt = $pdo->prepare("DELETE FROM user_payment WHERE id = ?");
    $stmt->execute([$payment_id]);

    // Redirect kembali ke halaman manage-orders dengan pesan sukses
    header('Location: manage-orders.php?success=deleted');
    exit;
}

$stmt = $pdo->query("SELECT up.id, u.name AS customer_name, up.name_product, up.jumlah, up.total_harga, up.payment_status, up.created_at
                      FROM user_payment up 
                      JOIN users u ON up.user = u.id
                      ORDER BY up.created_at DESC");
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Status yang tersedia
$statuses = ['pending', 'processing', 'shipped', 'completed', 'cancelled'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pesanan - Admin Vumee</title>
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
            color: var(--dark);
        }
        
        /* Admin Header */
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
        
        /* Sidebar */
        .admin-container {
            display: flex;
            min-height: calc(100vh - 60px);
        }
        
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
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .page-header h2 {
            font-size: 1.8rem;
            color: var(--primary);
        }
        
        .btn {
            display: inline-block;
            padding: 0.6rem 1.2rem;
            background-color: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background-color: var(--secondary);
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background-color: #dc3545;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }
        
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .orders-table th, 
        .orders-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .orders-table th {
            background-color: #f9f9f9;
            font-weight: 500;
        }
        
        .status-badge {
            padding: 0.3rem 0.6rem;
            border-radius: 5px;
            font-size: 0.8rem;
            color: white;
        }
        
        .status-pending {
            background-color: #ffc107;
        }
        
        .status-processing {
            background-color: #17a2b8;
        }
        
        .status-shipped {
            background-color: #007bff;
        }
        
        .status-completed {
            background-color: #28a745;
        }
        
        .status-cancelled {
            background-color: #dc3545;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .alert {
            padding: 0.8rem 1rem;
            margin-bottom: 1.5rem;
            border-radius: 5px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .admin-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
            }
            
            .orders-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <header class="admin-header">
        <h1>Dashboard Admin Vumee</h1>
        <nav class="admin-nav">
            <ul>
                <!--<li><a href="#"><i class="fas fa-bell"></i></a></li>-->
                <li><a href="#"><i class="fas fa-user"></i> <?php echo $_SESSION['user_name']; ?></a></li>
                <li><a href="../user/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </header>

    <!-- Admin Container -->
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="manage-products.php"><i class="fas fa-box"></i> Kelola Produk</a></li>
                    <li><a href="manage-orders.php" class="active"><i class="fas fa-shopping-cart"></i> Kelola Pesanan</a></li>
                    <li><a href="manage-users.php"><i class="fas fa-users"></i> Kelola Pengguna</a></li>
                    <li><a href="manage-shipments.php"><i class="fas fa-truck"></i> Kelola Pengiriman Resi</a></li>
                    <!-- <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Laporan</a></li> -->
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h2>Kelola Pesanan</h2>
            </div>
            
            <?php if (isset($_GET['success']) && $_GET['success'] === 'updated'): ?>
                <div class="alert alert-success">
                    Status pesanan berhasil diperbarui!
                </div>
            <?php endif; ?>
            
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>ID Pesanan</th>
                        <th>Pelanggan</th>
                        <th>Nama Produk</th>
                        <th>ID Produk</th>
                        <th>Total</th>
                        <!--<th>Status</th> <!-- Kolom baru untuk menampilkan produk pembayaran -->
                        <th>Tanggal Pembayaran</th> <!-- Kolom baru untuk menampilkan Tanggal Pembayaran -->
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td>#<?php echo $payment['id']; ?></td>
                        <td><?php echo htmlspecialchars($payment['customer_name']); ?></td>
                        <td><?php echo htmlspecialchars($payment['name_product']); ?></td>
                        <td><?php echo $payment['jumlah']; ?></td>
                        <td>Rp <?php echo number_format($payment['total_harga'], 0, ',', '.'); ?></td>
                        <!--<td>-->
                            <!--<span class="status-badge status-<?php echo strtolower($payment['payment_status']); ?>">-->
                        <!--        <?php echo ucfirst($payment['payment_status']); ?>-->
                        <!--    </span>-->
                        <!--</td>-->
                        <td><?php echo date('d M Y', strtotime($payment['created_at'])); ?></td>
                        <td>
                            <div class="action-buttons">
                                <!-- Form untuk menghapus pembayaran -->
                                <form method="POST" action="manage-orders.php" style="display:inline;">
                                    <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                    <button type="submit" name="delete_payment" class="btn btn-danger">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </main>
    </div>

    <!-- Font Awesome for icons -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>
