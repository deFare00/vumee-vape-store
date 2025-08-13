<?php
session_start();

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'], $_POST['quantity'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];

    // Validasi stok tersedia
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        $_SESSION['error'] = "Produk tidak ditemukan";
        header('Location: product.php?id='.$product_id);
        exit;
    }

    if ($quantity <= 0 || $quantity > $product['stock']) {
        $_SESSION['error'] = "Jumlah tidak valid atau stok tidak mencukupi";
        header('Location: product.php?id='.$product_id);
        exit;
    }

    // Inisialisasi keranjang jika belum ada
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Tambahkan/update produk di keranjang
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][$product_id] = [
            'quantity' => $quantity,
            'name' => $product['name'],
            'price' => $product['price'],
            'image' => $product['image']
        ];
    }
    

    // Update stok di database (optional)
    $new_stock = $product['stock'] - $quantity;
    $stmt = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?");
    $stmt->execute([$new_stock, $product_id]);

    // Redirect ke halaman keranjang
    $_SESSION['success'] = "Produk berhasil ditambahkan ke keranjang";
    header('Location: cart.php');
    exit;
} else {
    $_SESSION['error'] = "Permintaan tidak valid";
    header('Location: shop.php');
    exit;
}
