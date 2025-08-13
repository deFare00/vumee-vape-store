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

// Ambil statistik
$stats = [];
$queries = [
    'total_products' => "SELECT COUNT(*) FROM products",
    'total_users' => "SELECT COUNT(*) FROM users",
    'total_orders' => "SELECT COUNT(*) FROM orders",
    'revenue' => "SELECT SUM(total_amount) FROM orders WHERE status = 'completed'"
];

foreach ($queries as $key => $query) {
    $stmt = $pdo->query($query);
    $stats[$key] = $stmt->fetchColumn();
}

// Ambil 4 data pembayaran terbaru berdasarkan created_at
$stmt = $pdo->prepare("SELECT up.id, u.name AS customer_name, up.name_product, up.jumlah, up.total_harga, up.payment_status, up.created_at
                       FROM user_payment up
                       JOIN users u ON up.user = u.id
                       ORDER BY up.created_at DESC LIMIT 4");
$stmt->execute();
$recent_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil total pembayaran dari tabel user_payment
$stats['total_revenue'] = $pdo->query("SELECT SUM(total_harga) FROM user_payment")->fetchColumn();
$stats['total_orders'] = $pdo->query("SELECT SUM(jumlah) FROM user_payment")->fetchColumn();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Vumee</title>
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
        
        .welcome-message {
            margin-bottom: 2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .stat-card h3 {
            font-size: 1rem;
            color: #666;
            margin-bottom: 0.5rem;
        }
        
        .stat-card p {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .recent-orders {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .recent-orders h2 {
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
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
        
        .status {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .admin-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
            }
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .main-content {
                padding: 1rem;
            }
            
            /* Sembunyikan thead */
            .recent-orders table thead {
                position: absolute;
                top: -9999px;
                left: -9999px;
                height: 0;
                overflow: hidden;
            }
        
            /* Tampilkan tabel sebagai block */
            .recent-orders table,
            .recent-orders thead,
            .recent-orders tbody,
            .recent-orders th,
            .recent-orders td,
            .recent-orders tr {
                display: block;
                width: 100%;
            }
        
            /* Beri jarak antara thead dan tbody (secara visual) */
            .recent-orders table tbody tr:first-child {
                margin-top: 1.5rem; /* atau padding-top jika dibutuhkan */
            }
        
            /* Setiap row */
            .recent-orders table tbody tr {
                margin-bottom: 1.2rem;
                background: #fff;
                padding: 1rem;
                box-shadow: 0 1px 4px rgba(0,0,0,0.05);
                border-radius: 5px;
            }
        
            /* Data label */
            .recent-orders table td {
                position: relative;
                padding-left: 50%;
                padding-top: 0.5rem;
                padding-bottom: 0.5rem;
                margin-bottom: 0.5rem;
                border: none;
                border-bottom: 1px solid #eee;
                text-align: left;
            }
        
            .recent-orders table td::before {
                content: attr(data-label);
                position: absolute;
                left: 1rem;
                top: 50%;
                transform: translateY(-50%);
                font-weight: bold;
                color: #555;
                width: 45%;
                white-space: nowrap;
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
                <li><a href="index.php"><i class="fas fa-user"></i> <?php echo $_SESSION['user_name']; ?></a></li>
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
                    <li><a href="index.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="manage-products.php"><i class="fas fa-box"></i> Kelola Produk</a></li>
                    <li><a href="manage-orders.php"><i class="fas fa-shopping-cart"></i> Kelola Pesanan</a></li>
                    <li><a href="manage-users.php"><i class="fas fa-users"></i> Kelola Pengguna</a></li>
                    <li><a href="manage-shipments.php"><i class="fas fa-truck"></i> Kelola Pengiriman Resi</a></li>
                    <!-- <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Laporan</a></li> -->
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="welcome-message">
                <h2>Selamat Datang, <?php echo $_SESSION['user_name']; ?></h2>
                <p>Berikut adalah ringkasan aktivitas toko Anda hari ini.</p>
            </div>
            
            <!-- Statistik -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Produk</h3>
                    <p><?php echo $stats['total_products']; ?></p>
                </div>
                
                <div class="stat-card">
                    <h3>Total Pengguna</h3>
                    <p><?php echo $stats['total_users']; ?></p>
                </div>
                
                <div class="stat-card">
                    <h3>Total Pesanan</h3>
                    <p><?php echo $stats['total_orders']; ?></p>
                </div>
                
                <div class="stat-card">
                    <h3>Total Pendapatan</h3>
                    <p>Rp <?php echo number_format($stats['total_revenue'] ?? 0, 0, ',', '.'); ?></p>
                </div>
            </div>
            
            <!-- Pesanan Terbaru -->
            <div class="recent-orders">
                <h2>Pesanan Terbaru</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID Pembayaran</th>
                            <th>Pelanggan</th>
                            <th>Produk</th>
                            <th>Jumlah</th>
                            <th>Total Pembayaran</th>
                            <!--<th>Status Pembayaran</th>-->
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_payments as $payment): ?>
                        <tr>
                            <td data-label="ID">#<?php echo $payment['id']; ?></td>
                            <td data-label="Pelanggan"><?php echo htmlspecialchars($payment['customer_name']); ?></td>
                            <td data-label="Produk"><?php echo htmlspecialchars($payment['name_product']); ?></td>
                            <td data-label="Jumlah"><?php echo $payment['jumlah']; ?></td>
                            <td data-label="Total">Rp <?php echo number_format($payment['total_harga'], 0, ',', '.'); ?></td>
                            <!--<td data-label="Status Pembayaran">-->
                            <!--    <span class="status status-<?php echo strtolower($payment['payment_status']); ?>">-->
                            <!--        <?php echo ucfirst($payment['payment_status']); ?>-->
                            <!--    </span>-->
                            <!--</td>-->
                            <td data-label="Tanggal"><?php echo date('d M Y', strtotime($payment['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Font Awesome for icons -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>
