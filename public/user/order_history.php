<?php
session_start();

// Redirect jika belum login
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
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

    // Get order data
    $stmt = $pdo->prepare("
        SELECT 
            up.id, up.name_product, up.total_harga, up.payment_status, up.created_at, 
            up.resi_code, up.status_shipping, p.image
        FROM user_payment up
        JOIN products p ON up.name_product = p.name
        WHERE up.user = ? 
        ORDER BY up.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pesanan - Vumee</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .order-history {
            margin-top: 2rem;
        }
        .order-history table {
            border-spacing: 30px;
        }
        
        .order-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 0.5rem;
        }
        
        .order-status {
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
        
        .order-details {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
        }

        /* Gunakan styling yang sama seperti halaman lain */
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
            background-color: #f9f9f9;
            color: var(--dark);
        }
        
        /* Header Styles */
        header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 1rem 5%;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            display: flex;
            align-items: center;
        }
        
        .logo span {
            color: var(--accent);
        }
        
        nav ul {
            display: flex;
            list-style: none;
        }
        
        nav ul li {
            margin-left: 2rem;
        }
        
        nav ul li a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        nav ul li a:hover {
            color: var(--accent);
        }
        
        .cart-icon {
            position: relative;
        }
        
        .cart-count {
            position: absolute;
            top: -10px;
            right: -10px;
            background-color: var(--accent);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
        }
        
        .mobile-menu {
            display: none;
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        /* Profil Section */
        .profile-section {
            padding: 3rem 5%;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        
        .profile-container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
        }
        
        .profile-sidebar {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            padding: 1.5rem;
        }
        
        .profile-nav ul {
            list-style: none;
        }
        
        .profile-nav li {
            margin-bottom: 0.5rem;
        }
        
        .profile-nav a {
            display: block;
            padding: 0.8rem 1rem;
            color: var(--dark);
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .profile-nav a:hover,
        .profile-nav a.active {
            background-color: rgba(74, 0, 224, 0.1);
            color: var(--primary);
        }
        
        .profile-content {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            padding: 1.5rem;
            width: 100%;
        }
        
        .profile-content h2 {
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(74, 0, 224, 0.1);
            outline: none;
        }
        
        .error-message {
            color: #dc3545;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        
        .success-message {
            color: #28a745;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .btn {
            display: inline-block;
            background-color: var(--primary);
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background-color: var(--secondary);
            transform: translateY(-2px);
        }
        
        .btn-outline {
            background-color: transparent;
            color: var(--primary);
            border: 1px solid var(--primary);
        }
        
        .btn-outline:hover {
            background-color: rgba(74, 0, 224, 0.1);
        }

        /* Footer */
        footer {
            background-color: var(--dark);
            color: white;
            padding: 5rem 5% 2rem;
            margin-top: 3rem;
        }
        
        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .footer-col h3 {
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 0.5rem;
        }
        
        .footer-col h3::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 2px;
            background-color: var(--accent);
        }
        
        .footer-col ul {
            list-style: none;
        }
        
        .footer-col ul li {
            margin-bottom: 0.8rem;
        }
        
        .footer-col ul li a {
            color: #bbb;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .footer-col ul li a:hover {
            color: white;
            padding-left: 5px;
        }
        
        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: rgba(255,255,255,0.1);
            border-radius: 50%;
            color: white;
            transition: all 0.3s ease;
        }
        
        .social-links a:hover {
            background-color: var(--accent);
            transform: translateY(-3px);
        }
        
        .copyright {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            color: #bbb;
            font-size: 0.9rem;
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .header-container {
                flex-wrap: wrap;
            }
            
            .mobile-menu {
                display: block;
            }
            
            .profile-container {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
        
            .profile-sidebar,
            .profile-content {
                padding: 1rem;
                border-radius: 0;
                box-shadow: none;
            }
        
            .profile-sidebar {
                width: 100%;
            }
            
            .profile-nav a {
                padding: 0.75rem 1rem;
                font-size: 1rem;
            }
            
            .profile-nav {
                background: --primary; /* Biar kelihatan */
                display: block !important;
            }
        
            .form-group {
                margin-bottom: 1.2rem;
            }
        
            .btn,
            .btn-outline {
                width: 100%;
                text-align: center;
                margin-top: 0.5rem;
            }
        
            .form-group:last-child {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .order-history table {
                width: 100%;
                border-collapse: collapse;
                overflow-x: auto;
                display: block;
                font-size: 0.85rem;
            }
            
            .order-history table thead {
                display: none; /* Sembunyikan header untuk tampil mobile card-style */
            }
            
            .order-history table tbody tr {
                display: block;
                margin-bottom: 1rem;
                border: 1px solid #ddd;
                border-radius: 8px;
                background: white;
                padding: 1rem;
            }
            
            .order-history table tbody td {
                display: flex;
                justify-content: space-between;
                padding: 0.5rem 0;
                border-bottom: 1px solid #eee;
            }
            
            .order-history table tbody td:last-child {
                border-bottom: none;
            }
            
            .order-history table tbody td::before {
                content: attr(data-label);
                font-weight: bold;
                color: var(--primary);
            }
            
            nav {
                width: 100%;
            }
        
            nav {
                display: none;
                flex-direction: column;
                background-color: --primary;
                color: white;
                width: 100%;
                padding: 1rem;
                box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            }
        
            nav.show {
                display: flex;
            }
        
            nav ul {
                flex-direction: column;
                width: 100%;
            }
        
            nav ul li {
                margin: 1rem 0;
            }
        
            nav ul li a {
                color: white;
            }
        }
    </style>
</head>
<body>
   <header>
        <div class="header-container">
            <div class="logo">
                Vumee<span>VapeStore</span>
            </div>
            <nav>
                <ul>
                    <li><a href="../../index.php">Beranda</a></li>
                    <li><a href="shop.php">Produk</a></li>
                    <li><a href="about.php">Tentang Kami</a></li>
                    <li><a href="contact.php">Kontak</a></li>
                    <?php if (isset($_SESSION['user_logged_in'])): ?>
                        <li><a href="profile.php">Profil Saya</a></li>
                        <li><a href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Login</a></li>
                    <?php endif; ?>
                    <li>
                        <a href="cart.php" class="cart-icon">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="cart-count">
                                <?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?>
                            </span>
                        </a>
                    </li>
                </ul>
            </nav>
            <div class="mobile-menu">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </header>
    
    <section class="profile-section">
        <div class="profile-container">
            <div class="profile-sidebar">
                <nav class="profile-nav">
                    <ul>
                        <li><a href="profile.php" >Edit Profil</a></li>
                        <li><a href="change_password.php">Ganti Password</a></li>
                        <li><a href="order_history.php" class="active">Riwayat Pesanan</a></li>
                        <li><a href="wishlist.php">Daftar Keinginan</a></li>
                    </ul>
                </nav>
            </div>
            
            <div class="profile-content">
                <h2>Riwayat Pesanan</h2>
                
                <div class="order-history">
                    <?php if (empty($payments)): ?>
                        <p>Anda belum memiliki pesanan.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th>Gambar</th>
                                    <th>Resi Kode</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($payment['name_product']); ?></td>
                                        <td>
                                            <img src="../../uploads/<?php echo htmlspecialchars($payment['image']); ?>" alt="<?php echo htmlspecialchars($payment['name_product']); ?>" style="width: 50px; height: 50px;">
                                        </td>
                                        <td><?php echo htmlspecialchars($payment['resi_code']); ?></td> <!-- Display resi_code -->
                                        <td>Rp <?php echo number_format($payment['total_harga'], 0, ',', '.'); ?></td>
                                        <td>
                                            <span class="status-<?php echo strtolower($payment['status_shipping']); ?>">
                                                <?php echo ucfirst($payment['status_shipping']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d M Y', strtotime($payment['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer>
        <div class="footer-grid">
            <div class="footer-col">
                <h3>Vumee - VapeStore</h3>
                <p>Temukan koleksi lengkap vape, liquid, dan aksesoris dengan harga terbaik. Garansi resmi dan pengiriman ke seluruh Indonesia.</p>
                <div class="social-links">
                    <!--<a href="#"><i class="fab fa-facebook-f"></i></a>-->
                    <a href="https://www.instagram.com/vumee.store/"><i class="fab fa-instagram"></i></a>
                    <!--<a href="#"><i class="fab fa-twitter"></i></a>-->
                    <!--<a href="#"><i class="fab fa-youtube"></i></a>-->
                </div>
            </div>
            <div class="footer-col">
                <h3>Informasi</h3>
                <ul>
                    <li><a href="about.php">Tentang Kami</a></li>
                    <!--<li><a href="#">Kebijakan Privasi</a></li>-->
                    <!--<li><a href="#">Syarat & Ketentuan</a></li>-->
                    <!--<li><a href="#">Blog</a></li>-->
                    <!--<li><a href="#">Pusat Bantuan</a></li>-->
                </ul>
            </div>
            <div class="footer-col">
                <h3>Akun Saya</h3>
                <ul>
                    <li><a href="profile.php">Akun Saya</a></li>
                    <li><a href="order_history.php">Riwayat Pesanan</a></li>
                    <li><a href="wishlist.php">Daftar Keinginan</a></li>
                    <li><a href="payment_success.php">Cek Resi</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h3>Kontak Kami</h3>
                <ul>
                    <li><i class="fas fa-map-marker-alt"></i> Jl. Raya Cagar Alam No.6, RT.05/RW.01, Pancoran Mas, Kec. Pancoran Mas, Kota Depok, Jawa Barat 16436</li>
                    <li><i class="fas fa-phone"></i> +62 812-9967-9441</li>
                    <li><i class="fas fa-envelope"></i> vumee@gmail.com</li>
                    <li><i class="fas fa-clock"></i> Senin-Minggu, 09:00-21:00 WIB</li>
                </ul>
            </div>
        </div>
        <div class="copyright">
            <p>&copy; 2025 Vumee. All Rights Reserved.</p>
        </div>
    </footer>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenu = document.querySelector('.mobile-menu');
            const nav = document.querySelector('nav');
            if (mobileMenu) {
                mobileMenu.addEventListener('click', function() {
                    nav.style.display = nav.style.display === 'block' ? 'none' : 'block';
                });
            }
        });
    </script>
</body>
</html>
