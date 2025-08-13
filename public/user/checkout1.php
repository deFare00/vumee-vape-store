<?php
session_start();

// Cek apakah pengguna sudah login
if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
    header('Location: login.php');
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

// ====================================================================
// BAGIAN INI HANYA UNTUK MENAMPILKAN DATA DI HALAMAN HTML
// ====================================================================

// Inisialisasi variabel untuk tampilan
$cart_items_display = [];
$subtotal = 0;
$shipping_cost = 15000; // Biaya pengiriman default

// Ambil data keranjang dari session untuk ditampilkan
if (!empty($_SESSION['cart'])) {
    $product_ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    
    $query = "SELECT id, name, price, image FROM products WHERE id IN ($placeholders)";
    $stmt = $pdo->prepare($query);
    $stmt->execute($product_ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($products as $product) {
        $quantity = $_SESSION['cart'][$product['id']]['quantity'];
        $cart_items_display[] = [
            'name' => $product['name'],
            'price' => $product['price'],
            'quantity' => $quantity
        ];
        $subtotal += $product['price'] * $quantity;
    }
}

$total = $subtotal + $shipping_cost;
$user_id = $_SESSION['user_id'];

// Ambil alamat pengguna untuk ditampilkan sebagai placeholder
$stmt = $pdo->prepare("SELECT address FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$user_address = $user ? $user['address'] : '';

// *** TIDAK ADA LAGI KODE PEMBUATAN TOKEN MIDTRANS DI SINI ***
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - VapeStore</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary: #4a00e0; --secondary: #8e2de2; --accent: #ff5722;
            --dark: #121212; --light: #f5f5f5;
        }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f9f9f9; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        header { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; padding: 1rem 5%; position: sticky; top: 0; z-index: 100; }
        .header-container { display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 1.8rem; font-weight: 700; }
        nav ul { display: flex; list-style: none; }
        nav ul li { margin-left: 2rem; }
        nav ul li a { color: white; text-decoration: none; font-weight: 500; }
        .checkout-container { padding: 2rem 5%; max-width: 1200px; margin: 2rem auto; background-color: white; border-radius: 10px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05); }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--primary); }
        .form-group textarea { width: 100%; padding: 1rem; font-size: 1rem; border: 1px solid #ddd; border-radius: 5px; background-color: #f5f5f5; }
        .summary { background-color: #fdfdfd; padding: 1.5rem; border-radius: 10px; border: 1px solid #eee; margin-top: 1rem; }
        .summary h3 { margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid #eee; }
        .summary p { margin-bottom: 0.5rem; display: flex; justify-content: space-between; }
        #pay-button { display: block; width: 100%; background-color: var(--accent); color: white; border: none; padding: 1rem; border-radius: 5px; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: background-color 0.3s ease; margin-top: 2rem; }
        #pay-button:hover { background-color: #e64a19; }
        #pay-button:disabled { background-color: #ccc; cursor: not-allowed; }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo">Vape<span>Store</span></div>
            <nav>
                <ul>
                    <li><a href="../../index.php">Beranda</a></li>
                    <li><a href="shop.php">Produk</a></li>
                    <li><a href="profile.php">Profil Saya</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="checkout-container">
        <h1>Proses Checkout</h1>

        <form id="payment-form">
            <div class="form-group">
                <label for="address">Alamat Pengiriman</label>
                <textarea name="address" id="address" rows="4" required placeholder="Masukkan alamat pengiriman Anda"><?php echo htmlspecialchars($user_address); ?></textarea>
            </div>
            <div class="form-group">
                <label for="notes">Catatan (Opsional)</label>
                <textarea name="notes" id="notes" rows="4" placeholder="Masukkan catatan untuk penjual"></textarea>
            </div>

            <div class="summary">
                <h3>Ringkasan Belanja</h3>
                <p><span>Subtotal:</span> <span>Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></span></p>
                <p><span>Biaya Pengiriman:</span> <span>Rp <?php echo number_format($shipping_cost, 0, ',', '.'); ?></span></p>
                <hr>
                <p><strong>Total:</strong> <strong>Rp <?php echo number_format($total, 0, ',', '.'); ?></strong></p>
            </div>

            <button type="button" id="pay-button">Selesaikan Pembayaran</button>
        </form>
    </div>

    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="SB-Mid-client-dmtofN76DYy2vU_A"></script>
    <script type="text/javascript">
        document.getElementById('pay-button').onclick = function() {
            const payButton = this;
            // Nonaktifkan tombol untuk mencegah klik ganda
            payButton.disabled = true;
            payButton.innerHTML = 'Memproses...';

            // Panggil file backend untuk membuat token
            fetch('request_token.php', {
                method: 'POST',
            })
            .then(response => response.json())
            .then(data => {
                // Jika backend mengembalikan error, tampilkan
                if (data.error) {
                    throw new Error(data.error);
                }

                // Jika token diterima, buka popup pembayaran Midtrans
                if (data.token) {
                    snap.pay(data.token, {
                        onSuccess: function(result) {
                            alert("Pembayaran Berhasil!");
                            // Arahkan ke halaman sukses atau tampilkan pesan
                            window.location.href = "payment_success.php?order_id=" + result.order_id;
                        },
                        onPending: function(result) {
                            alert("Pembayaran Anda Tertunda.");
                        },
                        onError: function(result) {
                            alert("Pembayaran Gagal.");
                            payButton.disabled = false;
                            payButton.innerHTML = 'Selesaikan Pembayaran';
                        },
                        onClose: function() {
                            console.log('Popup ditutup oleh pengguna.');
                            payButton.disabled = false;
                            payButton.innerHTML = 'Selesaikan Pembayaran';
                        }
                    });
                }
            })
            .catch(error => {
                alert('Gagal membuat transaksi: ' + error.message);
                // Aktifkan kembali tombol jika gagal
                payButton.disabled = false;
                payButton.innerHTML = 'Selesaikan Pembayaran';
            });
        };
    </script>
</body>
</html>