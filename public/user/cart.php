<?php

session_start();

// Cek apakah pengguna sudah login
if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
    header('Location: login.php');  // Arahkan ke halaman login jika belum login
    exit;
}

// Koneksi ke database
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

// Inisialisasi variabel
$cart_items = [];
$subtotal = 0;
$total = 0;
$shipping_cost = 15000; // Biaya pengiriman default

// Simulasi data keranjang (dalam implementasi nyata, ini harus dari session/database)
// Anda perlu mengganti ini dengan sistem keranjang yang sesuai dengan database Anda
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle update quantity
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $product_id => $quantity) {
        if (isset($_SESSION['cart'][$product_id])) {
            if ($quantity > 0) {
                $_SESSION['cart'][$product_id]['quantity'] = $quantity;
            } else {
                unset($_SESSION['cart'][$product_id]);
            }
        }
    }
}

// Handle remove item
if (isset($_GET['remove']) && isset($_SESSION['cart'][$_GET['remove']])) {
    unset($_SESSION['cart'][$_GET['remove']]);
    header('Location: cart.php');
    exit;
}

// Ambil data produk dari database berdasarkan item di keranjang
if (!empty($_SESSION['cart'])) {
    $product_ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    
    $query = "SELECT id, name, price, image FROM products WHERE id IN ($placeholders)";
    $stmt = $pdo->prepare($query);
    $stmt->execute($product_ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Susun data keranjang
    foreach ($products as $product) {
        $cart_items[] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'image' => $product['image'],
            'quantity' => $_SESSION['cart'][$product['id']]['quantity']
        ];
        
        $subtotal += $product['price'] * $_SESSION['cart'][$product['id']]['quantity'];
    }
    
    $total = $subtotal + $shipping_cost;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - VapeStore</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
        
        /* Breadcrumb */
        .breadcrumb {
            padding: 1rem 5%;
            background-color: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .breadcrumb a {
            color: var(--primary);
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        /* Cart Page */
        .cart-container {
            padding: 2rem 5%;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .cart-header {
            margin-bottom: 2rem;
        }
        
        .cart-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .cart-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        
        .cart-items {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            padding: 1.5rem;
        }
        
        .empty-cart {
            text-align: center;
            padding: 3rem 0;
        }
        
        .empty-cart i {
            font-size: 5rem;
            color: #ccc;
            margin-bottom: 1rem;
        }
        
        .empty-cart h3 {
            margin-bottom: 1rem;
        }
        
        .empty-cart p {
            color: #666;
            margin-bottom: 2rem;
        }
        
        .btn {
            display: inline-block;
            background-color: var(--primary);
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background-color: var(--secondary);
            transform: translateY(-2px);
        }
        
        .btn-outline {
            background-color: white;
            color: var(--primary);
            border: 1px solid var(--primary);
        }
        
        .btn-outline:hover {
            background-color: #f5f5f5;
        }
        
        .cart-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .cart-table th {
            text-align: left;
            padding: 1rem;
            border-bottom: 1px solid #eee;
            font-weight: 600;
        }
        
        .cart-table td {
            padding: 1rem;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        
        .product-cell {
            display: flex;
            align-items: center;
        }
        
        .product-image {
            width: 80px;
            height: 80px;
            margin-right: 1rem;
            border-radius: 5px;
            overflow: hidden;
            background-color: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .product-image img {
            max-width: 80%;
            max-height: 80%;
        }
        
        .product-info h4 {
            margin-bottom: 0.3rem;
        }

        .remove-btn {
            color: #dc3545;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            padding: 0.3rem 0.8rem;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .remove-btn:hover {
            background-color: #f8d7da;
            color: #721c24;
        }
        .remove-btn i {
            margin-right: 5px;
        }
        .product-actions {
            text-align: center;
        }
        
        .product-actions a {
            color: var(--accent);
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .product-actions a:hover {
            text-decoration: underline;
        }
        
        .quantity-selector {
            display: flex;
            align-items: center;
        }
        
        .quantity-input {
            width: 50px;
            text-align: center;
            padding: 0.3rem;
            margin: 0 0.5rem;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .quantity-btn {
            background-color: #f5f5f5;
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .quantity-btn:hover {
            background-color: #eee;
        }
        
        /* Cart Summary */
        .cart-summary {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            padding: 1.5rem;
            position: sticky;
            top: 100px;
        }
        
        .cart-summary h3 {
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        
        .summary-row.total {
            font-weight: 600;
            font-size: 1.1rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }
        
        .coupon-form {
            margin: 1.5rem 0;
        }
        
        .coupon-form input {
            width: 100%;
            padding: 0.8rem;
            margin-bottom: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .checkout-btn {
            display: block;
            width: 100%;
            background-color: var(--accent);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
            margin-top: 1rem;
        }
        
        .checkout-btn:hover {
            background-color: #e64a19;
            transform: translateY(-2px);
        }

        /* Footer */
        footer {
            background-color: var(--dark);
            color: white;
            padding: 5rem 5% 2rem;
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
            .header-container nav {
                display: none;
            }
            
            .mobile-menu {
                display: block;
            }
            
            .cart-content {
                grid-template-columns: 1fr;
            }
            
            .product-cell {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .product-image {
                margin-bottom: 0.5rem;
            }
        }
        
    </style>
</head>
<body>
    <!-- Header -->
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
                    <li><a href="contact.php" class="active">Kontak</a></li>
                    <?php if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in']): ?>
                        <li><a href="profile.php">Profil Saya</a></li>
                        <li><a href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Login</a></li>
                    <?php endif; ?>
                    <li>
                        <a href="cart.php" class="cart-icon">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="cart-count"><?php echo $cart_count; ?></span>
                        </a>
                    </li>
                </ul>
            </nav>
            <div class="mobile-menu">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </header>

    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="../index.php">Beranda</a> &gt; 
        <span>Keranjang Belanja</span>
    </div>

    <!-- Cart Page -->
    <div class="cart-container">
        <div class="cart-header">
            <h1>Keranjang Belanja</h1>
            <p>Lihat dan kelola produk di keranjang belanja Anda</p>
        </div>
        
        <div class="cart-content">
            <div class="cart-items">
                <?php if (empty($cart_items)): ?>
                    <div class="empty-cart">
                        <i class="fas fa-shopping-cart"></i>
                        <h3>Keranjang Belanja Kosong</h3>
                        <p>Tambahkan beberapa produk ke keranjang Anda dan mulailah berbelanja</p>
                        <a href="shop.php" class="btn">Belanja Sekarang</a>
                    </div>
                <?php else: ?>
                    <form method="post">
                        <table class="cart-table">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th>Harga</th>
                                    <th>Jumlah</th>
                                    <th>Subtotal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart_items as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="product-cell">
                                                <div class="product-image">
                                                    <img src="../../uploads/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                                </div>
                                                <div class="product-info">
                                                    <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                                </div>
                                            </div>
                                        </td>
                                        <td>Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                                        <td>
                                            <div class="quantity-selector">
                                                <input type="number" class="quantity-input" name="quantity[<?php echo $item['id']; ?>]" 
                                                    value="<?php echo $item['quantity']; ?>" min="1" 
                                                    data-product-id="<?php echo $item['id']; ?>">
                                            </div>
                                        </td>
                                        <td>Rp <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?></td>
                                        <td class="product-actions">
                                            <a href="remove_from_cart.php?id=<?php echo $item['id']; ?>" class="remove-btn" 
                                            onclick="return confirm('Yakin ingin menghapus produk ini dari keranjang?')">
                                                <i class="fas fa-trash"></i> Hapus
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <div style="display: flex; justify-content: space-between; margin-top: 2rem;">
                            <a href="shop.php" class="btn btn-outline">Lanjut Belanja</a>
                            <button type="submit" name="update_cart" class="btn">Perbarui Keranjang</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($cart_items)): ?>
                <div class="cart-summary">
                    <h3>Ringkasan Belanja</h3>
                    
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Biaya Pengiriman</span>
                        <span>Rp <?php echo number_format($shipping_cost, 0, ',', '.'); ?></span>
                    </div>
                    
                    <div class="summary-row total">
                        <span>Total</span>
                        <span>Rp <?php echo number_format($total, 0, ',', '.'); ?></span>
                    </div>
                    
                    <!-- <div class="coupon-form">
                        <input type="text" placeholder="Masukkan kode kupon">
                        <button type="button" class="btn">Gunakan Kupon</button>
                    </div> -->
                    
                    <div class="checkout-btn-container">
                        <a href="checkout.php" class="checkout-btn">Proses Ke Checkout</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

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

    <!-- Font Awesome for icons -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
        // Konfirmasi penghapusan
        document.querySelectorAll('.remove-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('Yakin ingin menghapus produk ini dari keranjang?')) {
                    e.preventDefault();
                }
            });
        });

        // Update jumlah secara real-time
        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('change', function() {
                const productId = this.getAttribute('data-product-id');
                const newQuantity = this.value;
                
                // Kirim permintaan AJAX untuk update quantity
                fetch('update_cart_quantity.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `product_id=${productId}&quantity=${newQuantity}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload(); // Segarkan halaman untuk update total
                    } else {
                        alert(data.message || 'Gagal mengupdate jumlah');
                    }
                });
            });
        });
    });

    </script>
</body>
</html>
