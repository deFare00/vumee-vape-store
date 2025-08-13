<?php
session_start(); 

$host = 'localhost';
$dbname = 'vumeemyi_database';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT * FROM products ORDER BY stock DESC LIMIT 4");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}


$cart_count = 0;

if (isset($_SESSION['cart'])) {
    $cart_count = count($_SESSION['cart']); 
}

$chat_user_name = "User"; 
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true && isset($_SESSION['user_id'])) {
    if (isset($_SESSION['user_name'])) {
        $chat_user_name = htmlspecialchars($_SESSION['user_name']);
    } else {
        try {
            $stmt_user = $pdo->prepare("SELECT name FROM users WHERE id = ?");
            $stmt_user->execute([$_SESSION['user_id']]);
            $user = $stmt_user->fetch(PDO::FETCH_ASSOC);
            if ($user && !empty($user['name'])) {
                $chat_user_name = htmlspecialchars($user['name']);
                $_SESSION['user_name'] = $user['name']; 
            }
        } catch (PDOException $e) {
           
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <title>Vumee - Toko Vape Online Terpercaya</title>
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
        
        @media (max-width: 768px) {
            .header-container {
                flex-wrap: wrap;
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

        #chat-button {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #004aad;
            color: white;
            border-radius: 30px;
            padding: 10px 15px;
            display: flex;
            align-items: center;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            z-index: 1000;
            font-weight: 600;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            transition: background-color 0.3s ease;
        }
        #chat-button:hover {
            background-color: #003080;
        }
        #chat-button img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
        }
        #chat-button span {
            font-size: 1rem;
        }

        #chat-popup {
            position: fixed;
            bottom: 80px;
            right: 20px;
            width: 320px;
            height: 450px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.3);
            display: none; /* Diubah oleh JS */
            flex-direction: column;
            z-index: 1001;
            transition: opacity 0.3s ease, transform 0.3s ease;
            opacity: 0;
            transform: translateY(20px);
        }
        #chat-popup.show {
            display: flex;
            opacity: 1;
            transform: translateY(0);
        }
        #chat-popup header {
            background-color: #004aad;
            color: white;
            padding: 10px 15px;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        #chat-popup header .title {
            display: flex;
            align-items: center;
            font-weight: 700;
            font-size: 1.1rem;
        }
        #chat-popup header .title img {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
        }
        #chat-popup header .close-btn {
            cursor: pointer;
            font-size: 1.2rem;
            font-weight: 700;
        }

        #chat-popup .chat-input {
            border-top: 1px solid #ddd;
            padding: 10px 15px;
            display: flex;
            align-items: center;
        }
        #chat-popup .chat-input input[type="text"] {
            flex: 1;
            padding: 8px 10px;
            border: 1px solid #ccc;
            border-radius: 20px;
            font-size: 0.9rem;
            outline: none;
        }
        #chat-popup .chat-input button {
            background-color: #004aad;
            color: white;
            border: none;
            padding: 8px 15px;
            margin-left: 10px;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 600;
        }

       
        #chat-popup .chat-content {
            display: flex;
            flex-direction: column;
            flex: 1;
            overflow: hidden;
            color: #333;
            font-size: 0.9rem;
        }

        #chat-greeting {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            flex-shrink: 0;
        }
        #chat-greeting p {
             margin: 0;
        }

        #chat-interaction-area {
            padding: 15px;
            overflow-y: auto;
            flex-grow: 1;
        }

        #chat-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        #chat-menu li {
            padding: 8px 10px;
            cursor: pointer;
            color: #004aad;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        #chat-menu li:hover {
            background-color: #f0f4f8;
        }

        #chat-answer-container {
            margin-top: 15px;
        }
        
        #chat-answer-container {
            display: flex;
            flex-direction: column;
            margin-top: 15px; 
        }

        .chat-bubble {
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 5px;
            max-width: 80%; 
            word-wrap: break-word; 
        }

        .user-message-bubble {
            background-color: #dcf8c6; 
            align-self: flex-end; 
            margin-left: auto; 
        }

        .bot-message-bubble {
            background-color: #e0f7fa;
            align-self: flex-start; 
            margin-right: auto; 
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
                            <span class="cart-count"><?= $cart_count ?></span>
                        </a>
                    </li>
                </ul>
            </nav>
            <div class="mobile-menu">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </header>

    <section class="hero">
        <div class="hero-content">
            <h1>Vape Premium Kualitas Terbaik</h1>
            <p>Temukan koleksi lengkap vape, liquid, dan aksesoris dengan harga terbaik. Garansi resmi dan pengiriman ke seluruh Indonesia.</p>
            <a href="public/user/shop.php" class="btn">Belanja Sekarang</a>
        </div>
    </section>

    <section class="features">
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-truck"></i></div>
            <h3>Pengiriman Terpercaya</h3>
            <p>Pengiriman ke seluruh Indonesia.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
            <h3>Garansi Resmi</h3>
            <p>Produk kami bergaransi resmi dari distributor dengan layanan after sales terbaik.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-headset"></i></div>
            <h3>Customer Service Responsive</h3>
            <p>Tim customer service kami siap membantu Anda kapan pun melalui WhatsApp atau FnQ chat.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-undo"></i></div>
            <h3>Pengembalian Mudah</h3>
            <p>Produk rusak? Kami menyediakan kebijakan pengembalian yang mudah dalam 14 hari. Dengan menghubungi WA</p>
        </div>
    </section>

    <section class="products">
        <div class="section-title">
            <h2>Produk Terbaru</h2>
            <p>Temukan koleksi terbaru perangkat vape dan liquid dari brand-brand ternama dunia.</p>
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

    <section class="newsletter">
        <h2>Cek Resi</h2>
        <p>Masukkan kode resi yang Anda dapatkan setelah melakukan pembayaran.</p>
        <form class="newsletter-form" action="public/user/status_shipping.php" method="GET">
            <input type="text" name="resi_code" placeholder="RESI-xxx" required>
            <button type="submit">Submit</button>
        </form>
    </section>

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
                    <li><a href="public/user/about.php">Tentang Kami</a></li>
                    <!--<li><a href="#">Kebijakan Privasi</a></li>-->
                    <!--<li><a href="#">Syarat & Ketentuan</a></li>-->
                    <!--<li><a href="#">Blog</a></li>-->
                    <!--<li><a href="#">Pusat Bantuan</a></li>-->
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

    <div id="chat-button" aria-label="Open Customer Service Chat" role="button" tabindex="0">
        <img src="https://i.ibb.co/2kRZxqZ/joni-avatar.png" alt="Chat Avatar">
        <span>Chat 24/7</span>
    </div>

    <div id="chat-popup" role="dialog" aria-modal="true" aria-labelledby="chat-popup-title">
        <header>
            <div class="title">
                <img src="https://i.ibb.co/2kRZxqZ/joni-avatar.png" alt="Chat Avatar">
                CHAT 24/7
            </div>
            <div class="close-btn" aria-label="Close Chat" role="button" tabindex="0">&times;</div>
        </header>

        <div class="chat-content">
            <div id="chat-greeting">
                <p>Hai kak <?= $chat_user_name ?>, apa yang bisa AGUS bantu? Silahkan pilih menu di bawah ya :)</p>
            </div>
            <div id="chat-interaction-area">
                <ul id="chat-menu">
                    <li data-answer="Produk vape yang kami jual adalah legal dan telah memenuhi peraturan yang berlaku di Indonesia. Namun, pastikan Anda memeriksa regulasi lokal di daerah Anda sebelum membeli.">1. Apakah produk vape ini legal di Indonesia?</li>
                    <li data-answer="Langkah-langkah pemesanan: Pilih produk yang Anda inginkan, Tambahkan ke keranjang, Lanjut ke checkout, Masukkan data pengiriman dan pilih metode pembayaran, Selesaikan pesanan.">2. Bagaimana cara memesan produk?</li>
                    <li data-answer="Kami menerima: Transfer Bank (BCA, Mandiri, dll), E-wallet (OVO, GoPay, Dana, ShopeePay), Kartu Kredit/Debit, Pembayaran di tempat (COD – wilayah tertentu).">3. Metode pembayaran apa saja yang tersedia?</li>
                    <li data-answer="Pengiriman Jabodetabek: 1–2 hari kerja, Luar Jabodetabek: 2–5 hari kerja, Estimasi tergantung jasa ekspedisi dan lokasi Anda.">4. Berapa lama waktu pengiriman?</li>
                    <li data-answer="Ya. Kami hanya menjual produk original dari brand terpercaya.">5. Apakah produk yang dijual original?</li>
                </ul>
                <div id="chat-answer-container"></div>
            </div>
        </div>
        
        <div class="chat-input">
            <input type="text" placeholder="Ketik pesan..." aria-label="Ketik pesan">
            <button type="button" aria-label="Kirim pesan">Kirim</button>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            
            const mobileMenu = document.querySelector('.mobile-menu');
            const nav = document.querySelector('nav');
            if (mobileMenu) {
                mobileMenu.addEventListener('click', function() {
                    nav.style.display = nav.style.display === 'block' ? 'none' : 'block';
                });
            }

            
            const chatButton = document.getElementById('chat-button');
            const chatPopup = document.getElementById('chat-popup');
            const closeBtn = chatPopup.querySelector('.close-btn');
            const chatMenu = document.getElementById('chat-menu');
            const answerContainer = document.getElementById('chat-answer-container');
            const chatInput = chatPopup.querySelector('.chat-input input[type="text"]'); 
            const sendButton = chatPopup.querySelector('.chat-input button'); 

            function toggleChat() {
                chatPopup.classList.toggle('show');
            }

            chatButton.addEventListener('click', toggleChat);
            closeBtn.addEventListener('click', toggleChat);
            
            chatMenu.addEventListener('click', function(e) {
                if (e.target && e.target.tagName === 'LI') {
                    const questionText = e.target.textContent;
                    const answerText = e.target.getAttribute('data-answer');

                    answerContainer.innerHTML = '';

       
                    const userMessageDiv = document.createElement('div');
                    userMessageDiv.classList.add('chat-bubble', 'user-message-bubble');
                    userMessageDiv.innerHTML = "<strong>Anda:</strong> " + questionText; 
                    answerContainer.appendChild(userMessageDiv);

                   
                    const botResponseDiv = document.createElement('div');
                    botResponseDiv.classList.add('chat-bubble', 'bot-message-bubble');
                    botResponseDiv.innerHTML = answerText;
                    answerContainer.appendChild(botResponseDiv);

                    answerContainer.scrollTop = answerContainer.scrollHeight; 
                }
            });

           
            sendButton.addEventListener('click', function() {
                const userMessage = chatInput.value.trim();
                if (userMessage !== '') {
                   
                    answerContainer.innerHTML = '';

                    
                    const userMessageDiv = document.createElement('div');
                    userMessageDiv.classList.add('chat-bubble', 'user-message-bubble');
                    userMessageDiv.innerHTML = "<strong>Anda:</strong> " + userMessage;
                    const botResponseDiv = document.createElement('div');
                    botResponseDiv.innerHTML = "Silahkan pilih menu di atas atau hubungi kami via WhatsApp: <a href='https://wa.me/6281234567890' target='_blank'>Klik di sini</a>.";
                    botResponseDiv.classList.add('chat-bubble', 'bot-message-bubble');
                    
                    answerContainer.appendChild(userMessageDiv); 
                    answerContainer.appendChild(botResponseDiv); 
                    chatInput.value = ''; 
                    answerContainer.scrollTop = answerContainer.scrollHeight;
                }
            });
        });

    </script>
</body>
</html>