<?php
session_start();
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
// Ambil ID produk dari URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 1;

// Query untuk mendapatkan detail produk
$product_query = "SELECT * FROM products WHERE id = :id";
$stmt = $pdo->prepare($product_query);
$stmt->bindParam(':id', $product_id, PDO::PARAM_INT);
$stmt->execute();
$product = $stmt->fetch(PDO::FETCH_ASSOC);
// Jika produk tidak ditemukan
if (!$product) {
    die("Produk tidak ditemukan");
}
// Query untuk mendapatkan produk terkait (dari kategori yang sama)
$related_query = "SELECT * FROM products WHERE category = :category AND id != :id LIMIT 4";
$stmt = $pdo->prepare($related_query);
$stmt->bindParam(':category', $product['category'], PDO::PARAM_STR);
$stmt->bindParam(':id', $product_id, PDO::PARAM_INT);
$stmt->execute();
$related_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Inisialisasi cart count
$cart_count = 0;
// Cek apakah ada cart di session
if (isset($_SESSION['cart'])) {
    $cart_count = count($_SESSION['cart']); // Menghitung jumlah item di cart
}

// Cek apakah ada pesan sukses
$success_message = '';
if (isset($_GET['success']) && $_GET['success'] === 'added_to_wishlist') {
    $success_message = 'Produk berhasil ditambahkan ke wishlist!';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produk Vape - Vumee</title>
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
        
        /* Product Page */
        .product-page {
            padding: 3rem 5%;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
        }
        
        .product-gallery {
            display: grid;
            grid-template-columns: 100px 1fr;
            gap: 1rem;
        }
        
        .thumbnail-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .thumbnail {
            width: 100px;
            height: 100px;
            background-color: #f5f5f5;
            border-radius: 5px;
            overflow: hidden;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        .thumbnail:hover, .thumbnail.active {
            border-color: var(--primary);
        }
        
        .thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .main-image {
            height: 500px;
            background-color: #f5f5f5;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .main-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .product-info {
            padding: 1rem;
        }
        
        .product-title {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        
        .product-brand {
            color: var(--secondary);
            font-weight: 600;
            margin-bottom: 1rem;
            display: block;
        }
        
        .product-rating {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .stars {
            color: #ffc107;
            margin-right: 0.5rem;
        }
        
        .review-count {
            color: #666;
            font-size: 0.9rem;
        }
        
        .product-price {
            font-size: 1rem;
            font-weight: 700;
            color: var(--primary);
            margin: 1.5rem 0;
        }
        
        .product-description {
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .product-meta {
            margin-bottom: 2rem;
        }
        
        .meta-item {
            display: flex;
            margin-bottom: 0.5rem;
        }
        
        .meta-label {
            font-weight: 600;
            width: 120px;
        }
        
        .product-actions {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .quantity-selector {
            display: flex;
            align-items: center;
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .quantity-btn {
            background-color: #f5f5f5;
            border: none;
            width: 40px;
            height: 40px;
            font-size: 1.2rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .quantity-input {
            width: 60px;
            height: 40px;
            text-align: center;
            border: none;
            border-left: 1px solid #ddd;
            border-right: 1px solid #ddd;
            font-size: 1rem;
        }
        
        .add-to-cart {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 0 2rem;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            height: 100%;
            text-decoration: none;
        }
        
        .add-to-cart:hover {
            background-color: var(--secondary);
        }
        
        .wishlist-btn {
            background-color: white;
            color: var(--dark);
            border: 1px solid #ddd;
            padding: 0 1.5rem;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            height: 58%;
        }
        
        .wishlist-btn:hover {
            background-color: #f5f5f5;
        }
        
        .product-tabs {
            margin-top: 3rem;
        }
        
        .tabs-header {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 1.5rem;
        }
        
        .tab-btn {
            padding: 0.8rem 1.5rem;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 600;
            position: relative;
        }
        
        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 3px;
            background-color: var(--primary);
        }
        
        .tab-content {
            display: none;
            padding: 1rem;
            line-height: 1.6;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .specs-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .specs-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .specs-table th, .specs-table td {
            padding: 0.8rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .specs-table th {
            width: 200px;
        }
        
        /* Related Products */
        .related-products {
            padding: 3rem 5%;
            background-color: white;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .section-title h2 {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 2rem;
        }
        
        .product-card {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
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
        }
        
        .product-info h3 {
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }
        
        .product-info p {
            color: #666;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        .product-price {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
        }
        
        .price {
            font-weight: 700;
            color: var(--primary);
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
        @media (max-width: 1024px) {
            .product-page {
                grid-template-columns: 1fr;
            }
            
            .product-gallery {
                grid-template-columns: 80px 1fr;
            }
            
            .thumbnail {
                width: 80px;
                height: 80px;
            }
            
            .main-image {
                height: 400px;
            }
        }
        
        @media (max-width: 768px) {
            .header-container {
                flex-wrap: wrap;
            }
            
            .mobile-menu {
                display: block;
            }
            
            .product-gallery {
                grid-template-columns: 1fr;
                grid-template-rows: auto auto;
            }
            
            .thumbnail-list {
                flex-direction: row;
                order: 2;
                overflow-x: auto;
                padding-bottom: 1rem;
            }
            
            .main-image {
                height: 300px;
                order: 1;
            }
            
            .product-actions {
                flex-direction: column;
            }
            
            .quantity-selector {
                width: 100%;
            }
            
            .add-to-cart, .wishlist-btn {
                width: 100%;
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
                <li><a href="contact.php">Kontak</a></li>
                <?php if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in']): ?>
                    <li><a href="profile.php">Profil Saya</a></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                <?php endif; ?>
                <li>
                    <a href="cart.php" class="cart-icon">
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

    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="../../index.php">Beranda</a> &gt; 
        <a href="shop.php">Produk</a> &gt; 
        <span><?php echo htmlspecialchars($product['name']); ?></span>
    </div>

    <!-- Product Page -->
    <div class="product-page">
        <div class="product-gallery">
            <div class="thumbnail-list">
                <!-- Thumbnail utama -->
                <div class="thumbnail active">
                    <img src="../../uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                </div>
                <!-- Thumbnail tambahan bisa ditambahkan di sini -->
                <!-- <div class="thumbnail">
                    <img src="../../uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?> angle">
                </div> -->
            </div>
            <div class="main-image">
                <img src="../../uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" id="main-image">
            </div>
        </div>
        
        <div class="product-info">
            <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
            <span class="product-brand"><?php echo htmlspecialchars($product['category']); ?></span>
            
            <!--<div class="product-rating">-->
            <!--    <div class="stars">-->
            <!--        <i class="fas fa-star"></i>-->
            <!--        <i class="fas fa-star"></i>-->
            <!--        <i class="fas fa-star"></i>-->
            <!--        <i class="fas fa-star"></i>-->
            <!--        <i class="fas fa-star-half-alt"></i>-->
            <!--    </div>-->
            <!--    <span class="review-count">(42 Ulasan)</span>-->
            <!--</div>-->
            
            <div class="product-price">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></div>
            
            <div class="product-meta">
                <div class="meta-item">
                    <span class="meta-label">Ketersediaan:</span>
                    <span class="meta-value"><?php echo ($product['stock'] > 0) ? 'Tersedia (Stok: ' . $product['stock'] . ')' : 'Stok Habis'; ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Kategori:</span>
                    <span class="meta-value"><?php echo htmlspecialchars($product['category']); ?></span>
                </div>
            </div>
            
            <div class="product-actions">
                <form action="add_to_cart.php" method="post" class="product-actions">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    <div class="quantity-selector">
                        <button type="button" class="quantity-btn minus">-</button>
                        <input type="number" class="quantity-input" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>">
                        <button type="button" class="quantity-btn plus">+</button>
                    </div>
                    <button type="submit" class="add-to-cart" <?php echo $product['stock'] == 0 ? 'disabled' : ''; ?>>
                        <?php echo $product['stock'] == 0 ? 'Stok Habis' : 'Tambah ke Keranjang'; ?>
                    </button>
                </form>

                <form action="add_to_wishlist.php" method="post" class="wishlist-form">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    <button type="submit" class="wishlist-btn">+ Wishlist</button>
                </form>
            </div>

            
            <div class="product-tabs">
                <div class="tabs-header">
                    <button class="tab-btn active" data-tab="description">Deskripsi</button>
                    <button class="tab-btn" data-tab="specs">Spesifikasi</button>
                    <!--<button class="tab-btn" data-tab="reviews">Ulasan</button>-->
                </div>
                
                <div class="tab-content active" id="description">
                    <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                </div>
                
                <div class="tab-content" id="specs">
                    <table class="specs-table">
                        <tr>
                            <th>Kategori</th>
                            <td><?php echo htmlspecialchars($product['category']); ?></td>
                        </tr>
                        <tr>
                            <th>Harga</th>
                            <td>Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></td>
                        </tr>
                        <tr>
                            <th>Stok</th>
                            <td><?php echo $product['stock']; ?> unit</td>
                        </tr>
                        <tr>
                            <th>Tanggal Update</th>
                            <td><?php echo date('d F Y', strtotime($product['updated_at'])); ?></td>
                        </tr>
                    </table>
                </div>
                
                <!--<div class="tab-content" id="reviews">-->
                    <!-- Ulasan produk -->
                <!--    <div class="review">-->
                <!--        <div class="review-header">-->
                <!--            <div class="review-author">Budi Santoso</div>-->
                <!--            <div class="review-rating">-->
                <!--                <i class="fas fa-star"></i>-->
                <!--                <i class="fas fa-star"></i>-->
                <!--                <i class="fas fa-star"></i>-->
                <!--                <i class="fas fa-star"></i>-->
                <!--                <i class="fas fa-star"></i>-->
                <!--            </div>-->
                <!--            <div class="review-date">12 Oktober 2023</div>-->
                <!--        </div>-->
                <!--        <div class="review-content">-->
                <!--            <p>Produk sangat bagus, performa sesuai deskripsi. Pengiriman cepat dan packing aman. Recomended seller!</p>-->
                <!--        </div>-->
                <!--    </div>-->
                    
                <!--    <button class="btn" style="margin-top: 1rem;">Tulis Ulasan</button>-->
                <!--</div>-->
            </div>
        </div>
    </div>
    
    <!-- Related Products -->
    <?php if (!empty($related_products)): ?>
    <section class="related-products">
        <div class="section-title">
            <h2>Produk Terkait</h2>
            <p>Anda mungkin juga menyukai produk-produk berikut</p>
        </div>
        <div class="product-grid">
            <?php foreach ($related_products as $related): ?>
            <div class="product-card">
                <div class="product-image">
                    <img src="../../uploads/<?php echo htmlspecialchars($related['image']); ?>" alt="<?php echo htmlspecialchars($related['name']); ?>">
                </div>
                <div class="product-info">
                    <h3><?php echo htmlspecialchars($related['name']); ?></h3>
                    <p><?php echo htmlspecialchars($related['category']); ?></p>
                    <div class="product-price">
                        <span class="price">Rp <?php echo number_format($related['price'], 0, ',', '.'); ?></span>
                        <a href="product.php?id=<?php echo $related['id']; ?>" class="add-to-cart">Detail</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

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
            // Mobile menu toggle
            const mobileMenu = document.querySelector('.mobile-menu');
            const nav = document.querySelector('nav');
            
            mobileMenu.addEventListener('click', function() {
                nav.style.display = nav.style.display === 'block' ? 'none' : 'block';
            });
            
            // Thumbnail image switcher
            const thumbnails = document.querySelectorAll('.thumbnail');
            const mainImage = document.getElementById('main-image');
            
            thumbnails.forEach(thumbnail => {
                thumbnail.addEventListener('click', function() {
                    // Remove active class from all thumbnails
                    thumbnails.forEach(t => t.classList.remove('active'));
                    
                    // Add active class to clicked thumbnail
                    this.classList.add('active');
                    
                    // Change main image (in a real app, this would load a different image)
                    mainImage.src = this.querySelector('img').src;
                });
            });
            
            // Quantity selector
            const minusBtn = document.querySelector('.quantity-btn.minus');
            const plusBtn = document.querySelector('.quantity-btn.plus');
            const quantityInput = document.querySelector('.quantity-input');
            const maxStock = <?php echo $product['stock']; ?>;
            
            minusBtn.addEventListener('click', function() {
                let value = parseInt(quantityInput.value);
                if (value > 1) {
                    quantityInput.value = value - 1;
                }
            });
            
            plusBtn.addEventListener('click', function() {
                let value = parseInt(quantityInput.value);
                if (value < maxStock) {
                    quantityInput.value = value + 1;
                }
            });
            
            // Tab switcher
            const tabBtns = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    
                    // Remove active class from all buttons and contents
                    tabBtns.forEach(b => b.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));
                    
                    // Add active class to clicked button and corresponding content
                    this.classList.add('active');
                    document.getElementById(tabId).classList.add('active');
                });
            });
            
            // Add to cart functionality
            const addToCartBtn = document.querySelector('.add-to-cart');
            const cartCount = document.querySelector('.cart-count');
            let count = parseInt(cartCount.textContent);
            
            addToCartBtn.addEventListener('click', function() {
                const quantity = parseInt(quantityInput.value);
                
                // Kirim data ke server menggunakan AJAX
                fetch('add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        product_id: <?php echo $product['id']; ?>,
                        quantity: quantity
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update cart count
                        count += quantity;
                        cartCount.textContent = count;
                        
                        // Show added animation
                        this.textContent = '✓ Ditambahkan (' + quantity + ')';
                        this.style.backgroundColor = '#4CAF50';
                        
                        setTimeout(() => {
                            this.textContent = 'Tambah ke Keranjang';
                            this.style.backgroundColor = 'var(--primary)';
                        }, 1500);
                    } else {
                        alert('Gagal menambahkan ke keranjang: ' + data.message);
                    }
                })
                
            });
        });
    </script>
</body>
</html>
