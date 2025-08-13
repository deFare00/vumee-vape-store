<?php
session_start();

// Koneksi database untuk mengembalikan stok
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

if (isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];
    
    if (isset($_SESSION['cart'][$product_id])) {
        // Dapatkan quantity yang akan dihapus untuk mengembalikan stok
        $quantity_to_remove = $_SESSION['cart'][$product_id]['quantity'];
        
        // Hapus produk dari keranjang
        unset($_SESSION['cart'][$product_id]);
        
        // Kembalikan stok ke database (optional)
        $stmt = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
        $stmt->execute([$quantity_to_remove, $product_id]);
        
        $_SESSION['success'] = "Produk berhasil dihapus dari keranjang";
    } else {
        $_SESSION['error'] = "Produk tidak ditemukan di keranjang";
    }
}

header('Location: cart.php');
exit;
?>
