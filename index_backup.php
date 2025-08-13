<?php
session_start(); // Pastikan session dimulai

// Koneksi ke database menggunakan PDO
$host = 'localhost';
$dbname = 'vumeemyi_database';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Ambil 4 produk dengan stok terbanyak
    $stmt = $pdo->prepare("SELECT * FROM products ORDER BY stock DESC LIMIT 4");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// Inisialisasi cart count
$cart_count = 0;
// Cek apakah ada cart di session
if (isset($_SESSION['cart'])) {
    $cart_count = count($_SESSION['cart']); // Menghitung jumlah item di cart
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <title>VUMEE - Order Online</title>
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
        
        /* Hero Section */
        .hero {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('https://images.unsplash.com/photo-1524653736724-8490ee06859d?q=80&w=1470&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D');
            background-size: cover;
            background-position: center;
            height: 80vh;
            display: flex;
            align-items: center;
            padding: 0 5%;
            color: white;
        }
        
        .hero-content {
            max-width: 600px;
        }
        
        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .hero p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .btn {
            display: inline-block;
            background-color: var(--accent);
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        /* Features Section */
        .features {
            padding: 5rem 5%;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            background-color: white;
        }
        
        .feature-card {
            text-align: center;
            padding: 2rem;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .feature-icon {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
        }
        
        .feature-card h3 {
            margin-bottom: 1rem;
        }
        
        /* Products Section */
        .products {
            padding: 5rem 5%;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .section-title h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .section-title p {
            color: #666;
            max-width: 700px;
            margin: 0 auto;
        }
        
        .product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 2rem;
    justify-content: center; /* ini penting untuk grid alignment */
    max-width: 1200px;
    margin: 0 auto;
}

        
        .product-card {
    background-color: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    height: 100%; /* buat semua card seragam */
}
        
        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
        }
        
        .product-image {
            height: 200px;
            background-color: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        .product-image img {
            max-width: 80%;
            max-height: 80%;
            object-fit: contain;
        }
        
        .product-label {
            position: absolute;
            top: 10px;
            left: 10px;
            background-color: var(--accent);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .product-info {
    padding: 1.5rem;
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
        
        .product-info h3 {
            margin-bottom: 0.5rem;
        }
        
        .product-info p {
    color: #666;
    margin-bottom: 1rem;
    font-size: 0.9rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap; /* tampilkan satu baris saja */
}
        
        .product-price {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
        }
        .product-price a.view-details {
    background-color: var(--primary);
    color: white;
    padding: 0.4rem 0.9rem;
    border-radius: 20px;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 600;
    transition: all 0.3s ease;
    margin-left: auto;
}

.product-price a.view-details:hover {
    background-color: var(--secondary); /* darker accent */
    transform: translateY(-2px);
    box-shadow: 0 5px 10px rgba(0,0,0,0.15);
}

        
        .price {
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--primary);
        }
        
        .add-to-cart {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .add-to-cart:hover {
            background-color: var(--secondary);
        }
        
        /* Newsletter Section */
        .newsletter {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 5rem;
            text-align: center;
        }
        
        .newsletter h2 {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        
        .newsletter p {
            margin-bottom: 2rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .newsletter-form {
            display: flex;
            max-width: 500px;
            margin: 0 auto;
        }
        
        .newsletter-form input {
            flex: 1;
            padding: 1rem;
            border: none;
            border-radius: 5px 0 0 5px;
            font-size: 1rem;
        }
        
        .newsletter-form button {
            background-color: var(--accent);
            color: white;
            border: none;
            padding: 0 1.5rem;
            border-radius: 0 5px 5px 0;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .newsletter-form button:hover {
            background-color: #e64a19;
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
            
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .hero p {
                font-size: 1rem;
            }
            
            .newsletter {
                padding: 3rem 1rem;
            }
            
            .newsletter-form {
                flex-direction: column;
            }
            
            .newsletter-form input {
                border-radius: 5px;
                margin-bottom: 1rem;
            }
            
            .newsletter-form button {
                border-radius: 5px;
                padding: 1rem;
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
                <li><a href="index.php">Beranda</a></li>
                <li><a href="public/user/shop.php">Produk</a></li>
                <li><a href="public/user/about.php">Tentang Kami</a></li>
                <li><a href="public/user/contact.php">Kontak</a></li>
                <?php if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in']): ?>
                    <li><a href="public/user/profile.php">Profil Saya</a></li>
                    <li><a href="public/user/logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="public/user/login.php">Login</a></li>
                <?php endif; ?>
                <li>
                    <a href="public/user/cart.php" class="cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count">
                            <?php 
                            if (isset($_SESSION['cart'])) {
                                echo count($_SESSION['cart']);
                            } else {
                                echo '0';
                            }
                            ?>
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

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Vumee Vape Store Cagar Alam</h1>
            <p>Temukan koleksi lengkap vape, liquid, dan aksesoris dengan harga terbaik.</p>
            <a href="public/user/shop.php" class="btn">Belanja Sekarang</a>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="feature-card">
            <div class="feature-icon">
                <i class="fas fa-truck"></i>
            </div>
            <h3>Gratis Ongkir</h3>
            <p>Gratis ongkos kirim untuk pembelian minimal Rp 500.000 ke seluruh Indonesia.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h3>Garansi Resmi</h3>
            <p>Produk kami bergaransi resmi dari distributor dengan layanan after sales terbaik.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">
                <i class="fas fa-headset"></i>
            </div>
            <h3>Customer Service 24/7</h3>
            <p>Tim customer service kami siap membantu Anda kapan pun melalui WhatsApp, email, atau live chat.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">
                <i class="fas fa-undo"></i>
            </div>
            <h3>Pengembalian Mudah</h3>
            <p>Tidak cocok? Kami menyediakan kebijakan pengembalian yang mudah dalam 14 hari.</p>
        </div>
    </section>

    <!-- Products Section -->
    <section class="products">
        <div class="section-title">
            <h2>Produk Terbaru</h2>
            <p>Temukan koleksi terbaru perangkat vape dan liquid.</p>
        </div>
        <div class="product-grid">
    <?php foreach ($products as $product): ?>
        <div class="product-card">
            <div class="product-image">
                <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
            </div>
            <div class="product-info">
                <h3><?= htmlspecialchars($product['name']) ?></h3>
                <p><?= htmlspecialchars($product['description']) ?></p>
                <div class="product-price">
                    <span class="price">Rp <?= number_format($product['price'], 0, ',', '.') ?></span>
                    <a href="public/user/product.php?id=<?php echo $product['id']; ?>" class="view-details">Detail</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

    </section>

    <!-- Newsletter Section -->
    <section class="newsletter">
        <h2>Cek Resi</h2>
        <p>Masukkan kode resi yang Anda dapatkan setelah melakukan pembayaran.</p>
        <form class="newsletter-form" action="public/user/status_shipping.php" method="GET">
            <input type="text" name="resi_code" placeholder="RESI-xxx" required>
            <button type="submit">Submit</button>
        </form>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-grid">
            <div class="footer-col">
                <h3>VapeStore</h3>
                <p>Toko vape online terpercaya dengan koleksi lengkap perangkat vape, liquid, dan aksesoris.</p>
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
                    <li><a href="public/user/about.php">Tentang Kami</a></li>
                    <li><a href="#">Kebijakan Privasi</a></li>
                    <li><a href="#">Syarat & Ketentuan</a></li>
                    <li><a href="#">Blog</a></li>
                    <li><a href="#">Pusat Bantuan</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h3>Akun Saya</h3>
                <ul>
                    <li><a href="public/user/profile.php">Akun Saya</a></li>
                    <li><a href="public/user/order_history.php">Riwayat Pesanan</a></li>
                    <li><a href="public/user/wishlist.php">Daftar Keinginan</a></li>
                    <li><a href="public/user/payment_success.php">Cek Resi</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h3>Kontak Kami</h3>
                <ul>
                    <li><i class="fas fa-map-marker-alt"></i> Jl. Raya Cagar Alam No.6, RT.05/RW.01, Pancoran MAS, Kec. Pancoran Mas, Kota Depok, Jawa Barat 16436</li>
                    <li><i class="fas fa-phone"></i> +62 812-9967-9441</li>
                    <li><i class="fas fa-envelope"></i> vumeestore@gmail.com</li>
                    <li><i class="fas fa-clock"></i> Senin-Minggu, 09:00-21:00 WIB</li>
                </ul>
            </div>
        </div>
        <div class="copyright">
            <p>&copy; 2024 Vumee Vape Store. All Rights Reserved.</p>
        </div>
    </footer>

    <!-- Font Awesome for icons -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    
    <script>
        // Simple JavaScript for interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu toggle
            const mobileMenu = document.querySelector('.mobile-menu');
            const nav = document.querySelector('nav');
            
            mobileMenu.addEventListener('click', function() {
                nav.style.display = nav.style.display === 'block' ? 'none' : 'block';
            });
            
            // Add to cart functionality
            const addToCartButtons = document.querySelectorAll('.add-to-cart');
            const cartCount = document.querySelector('.cart-count');
            let count = parseInt(cartCount.textContent);
            
            addToCartButtons.forEach(button => {
                button.addEventListener('click', function() {
                    count++;
                    cartCount.textContent = count;
                    
                    // Show added animation
                    this.textContent = '✓ Ditambahkan';
                    this.style.backgroundColor = '#4CAF50';
                    
                    setTimeout(() => {
                        this.textContent = '+ Keranjang';
                        this.style.backgroundColor = 'var(--primary)';
                    }, 1000);
                });
            });
        });
    </script>
</body>
</html>
