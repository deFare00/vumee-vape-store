<?php
session_start();

if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
    header('Location: login.php');
    exit;
}


$host = 'localhost';
$dbname = 'vumeemyi_database';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

$subtotal = 0;
$shipping_cost = 15000;
if (!empty($_SESSION['cart'])) {
    $product_ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    $stmt = $pdo->prepare("SELECT id, name, price FROM products WHERE id IN ($placeholders)");
    $stmt->execute($product_ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($products as $product) {
        $quantity = $_SESSION['cart'][$product['id']]['quantity'];
        $subtotal += $product['price'] * $quantity;
    }
}
$total = $subtotal + $shipping_cost;
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT address FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$user_address = $user ? $user['address'] : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Vumee</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root { --primary: #4a00e0; --secondary: #8e2de2; --accent: #ff5722; --dark: #121212; --light: #f5f5f5; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f9f9f9; color: var(--dark); margin: 0; padding: 0; }
        * { box-sizing: border-box; }
        header { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; padding: 1rem 5%; position: sticky; top: 0; z-index: 100; }
        .header-container { display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; }
        .logo { font-size: 1.8rem; font-weight: 700; }
        nav ul { display: flex; list-style: none; margin: 0; padding: 0; }
        nav ul li { margin-left: 2rem; }
        nav ul li a { color: white; text-decoration: none; font-weight: 500; }
        .checkout-container { padding: 2rem 5%; max-width: 800px; margin: 2rem auto; background-color: white; border-radius: 10px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05); }
        h1 { margin-bottom: 2rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--primary); }
        .form-group textarea { width: 100%; padding: 1rem; font-size: 1rem; border: 1px solid #ddd; border-radius: 5px; background-color: #f5f5f5; }
        .summary { background-color: #fdfdfd; padding: 1.5rem; border-radius: 10px; border: 1px solid #eee; margin-top: 1rem; }
        .summary h3 { margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid #eee; }
        .summary p { margin-bottom: 0.5rem; display: flex; justify-content: space-between; }
        #pay-button { display: block; width: 100%; background-color: var(--accent); color: white; border: none; padding: 1rem; border-radius: 5px; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: background-color 0.3s ease; margin-top: 2rem; }
        #pay-button:hover { background-color: #e64a19; }
        #pay-button:disabled { background-color: #ccc; cursor: not-allowed; }
        
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
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo">
                Vumee
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
        </div>
    </header>

    <div class="checkout-container">
        <h1>Proses Checkout</h1>
        <form id="payment-form">
            <div class="form-group">
                <label for="address">Alamat Pengiriman</label>
                <textarea name="address" id="address" rows="4" required readonly><?php echo htmlspecialchars($user_address); ?></textarea>
            </div>
            <div class="form-group">
                <label for="notes">Catatan (Opsional)</label>
                <textarea name="notes" id="notes" rows="4" placeholder="Masukkan catatan untuk penjual"></textarea>
            </div>
            <div class="summary">
                <h3>Ringkasan Belanja</h3>
                <p><span>Subtotal:</span> <span>Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></span></p>
                <p><span>Biaya Pengiriman:</span> <span>Rp <?php echo number_format($shipping_cost, 0, ',', '.'); ?></span></p>
                <hr style="border: 0; border-top: 1px solid #eee; margin: 1rem 0;">
                <p><strong>Total:</strong> <strong>Rp <?php echo number_format($total, 0, ',', '.'); ?></strong></p>
            </div>
            <button type="button" id="pay-button">Bayar Sekarang</button>
        </form>
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
    
    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="SB-Mid-client-dmtofN76DYy2vU_A"></script>
    <script type="text/javascript">
        document.getElementById('pay-button').onclick = function() {
            const payButton = this;
            payButton.disabled = true;
            payButton.innerHTML = 'Memproses...';

            fetch('request_token.php', {
                method: 'POST',
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) { throw new Error(data.error); }
                if (data.token) {
                    snap.pay(data.token, {
                        onSuccess: function(result) {
                            payButton.innerHTML = 'Pembayaran Sukses, Menyimpan Pesanan...';
                            
                            fetch('simpan_pesanan.php', {
                                method: 'POST',
                                headers: {'Content-Type': 'application/json'},
                                body: JSON.stringify({ transaction_status: result.transaction_status })
                            })
                            .then(response => response.json())
                            .then(server_data => {
                                if (server_data.success) {
                                    alert("Pesanan berhasil disimpan! Nomor Resi Anda: " + server_data.resi_code);

window.location.href = "payment_success.php?order_id=" + result.order_id;
                                } else {
                                    alert("Gagal Menyimpan Pesanan: " + server_data.message);
                                }
                            })
                            .catch(error => {
                                alert("Terjadi kesalahan fatal saat menyimpan pesanan Anda. Silakan hubungi customer service.");
                            });
                        },
                        onPending: function(result) {
                            alert("Pembayaran Anda Tertunda.");
                            payButton.disabled = false; payButton.innerHTML = 'Bayar Sekarang';
                        },
                        onError: function(result) {
                            alert("Pembayaran Gagal.");
                            payButton.disabled = false; payButton.innerHTML = 'Bayar Sekarang';
                        },
                        onClose: function() {
                            payButton.disabled = false; payButton.innerHTML = 'Bayar Sekarang';
                        }
                    });
                }
            })
            .catch(error => {
                alert('Gagal membuat transaksi: ' + error.message);
                payButton.disabled = false; payButton.innerHTML = 'Bayar Sekarang';
            });
        };
    </script>
</body>
</html>