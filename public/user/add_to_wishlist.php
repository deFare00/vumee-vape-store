<?php
session_start();

if (!isset($_SESSION['user_logged_in'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'];
    $user_id = $_SESSION['user_id'];

    // Koneksi ke database
$host = 'localhost';
$dbname = 'vumeemyi_database';
$username = 'root';
$password = '';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Tambahkan produk ke wishlist
        $stmt = $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)
                                ON DUPLICATE KEY UPDATE created_at = CURRENT_TIMESTAMP");
        $stmt->execute([$user_id, $product_id]);

        // Redirect kembali ke halaman produk dengan pesan sukses
        header('Location: product.php?id=' . $product_id . '&success=added_to_wishlist');
        exit;
    } catch (PDOException $e) {
        die("Koneksi database gagal: " . $e->getMessage());
    }
}
?>
