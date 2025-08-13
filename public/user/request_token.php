<?php
session_start();
header('Content-Type: application/json');

function return_error($message) {
    http_response_code(400);
    echo json_encode(['error' => $message]);
    exit;
}
function clean_string($string) {
    return mb_convert_encoding($string, 'UTF-8', 'UTF-8');
}

if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) { return_error('Sesi pengguna tidak ditemukan.'); }
if (empty($_SESSION['cart'])) { return_error('Keranjang belanja Anda kosong.'); }

$host = 'localhost';
$dbname = 'vumeemyi_database';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $subtotal = 0; $shipping_cost = 15000; $item_details = [];

    $product_ids = array_keys($_SESSION['cart']);
    if (empty($product_ids)) { return_error('Keranjang tidak berisi produk.'); }

    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    $stmt = $pdo->prepare("SELECT id, name, price FROM products WHERE id IN ($placeholders)");
    $stmt->execute($product_ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($products)) { return_error('Produk di keranjang tidak ditemukan.'); }

    foreach ($products as $product) {
        $quantity = isset($_SESSION['cart'][$product['id']]['quantity']) ? (int)$_SESSION['cart'][$product['id']]['quantity'] : 0;
        $price = isset($product['price']) ? (int)$product['price'] : 0;
        if ($quantity > 0 && $price > 0) {
            $item_details[] = ['id' => (string)$product['id'], 'price' => $price, 'quantity' => $quantity, 'name' => clean_string($product['name'])];
            $subtotal += $price * $quantity;
        }
    }

    if (empty($item_details)) { return_error('Tidak ada item valid di keranjang.'); }
    $item_details[] = ['id' => 'SHIPPING_COST', 'price' => $shipping_cost, 'quantity' => 1, 'name' => 'Biaya Pengiriman'];
    $total = $subtotal + $shipping_cost;

    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT name, email, address FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) { return_error('Data pengguna tidak ditemukan.'); }

    $customer_details = [
        'first_name' => clean_string($user['name']), 'email' => clean_string($user['email']),
        'shipping_address' => [ 'address' => clean_string($user['address']) ]
    ];

    require_once dirname(__FILE__) . '/../../midtrans-php-master/Midtrans.php';
    \Midtrans\Config::$serverKey = 'SB-Mid-server-T1swFng-4kR4g6CLkSWnKzIH';
    \Midtrans\Config::$isProduction = false;
    \Midtrans\Config::$isSanitized = true;
    \Midtrans\Config::$is3ds = true;
    
    $order_id = "VUMEE-" . time();
    $transaction_data = [
        'transaction_details' => ['order_id' => $order_id, 'gross_amount' => (int)$total],
        'customer_details' => $customer_details, 'item_details' => $item_details,
    ];
    
    $snapToken = \Midtrans\Snap::getSnapToken($transaction_data);
    echo json_encode(['token' => $snapToken]);

} catch (Exception $e) {
    error_log("Midtrans Token Creation Error: " . $e->getMessage());
    return_error("Terjadi kesalahan sistem: " . $e->getMessage());
}
?>