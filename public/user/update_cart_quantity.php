<?php
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['cart'])) {
    die(json_encode(['success' => false, 'message' => 'Keranjang tidak ditemukan']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'], $_POST['quantity'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    
    if ($quantity < 1) {
        die(json_encode(['success' => false, 'message' => 'Jumlah tidak valid']));
    }
    
    // Cek apakah produk ada di keranjang
    if (!isset($_SESSION['cart'][$product_id])) {
        die(json_encode(['success' => false, 'message' => 'Produk tidak ditemukan di keranjang']));
    }
    
    // Update quantity
    $_SESSION['cart'][$product_id]['quantity'] = $quantity;
    
    die(json_encode(['success' => true]));
}

die(json_encode(['success' => false, 'message' => 'Permintaan tidak valid']));
?>
