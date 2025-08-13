<?php
session_start();
require_once dirname(__FILE__) . '/../../../midtrans-php-master/Midtrans.php';

// Midtrans Config
\Midtrans\Config::$serverKey = 'SB-Mid-server-T1swFng-4kR4g6CLkSWnKzIH';
\Midtrans\Config::$isProduction = false;

// Cek session cart
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Cart is empty']);
    exit;
}

// Hitung total belanja + shipping
$shipping_cost = 15000;
$subtotal = 0;
$item_details = [];

foreach ($_SESSION['cart'] as $product_id => $item) {
    $item_details[] = [
        'id' => $product_id,
        'price' => (int)$item['price'],
        'quantity' => (int)$item['quantity'],
        'name' => $item['name']
    ];
    $subtotal += $item['price'] * $item['quantity'];
}

$total = $subtotal + $shipping_cost;

$transaction_details = [
    'order_id' => 'ORDER-' . time(),
    'gross_amount' => (int)$total
];

$customer_details = [
    'first_name' => $_SESSION['user_name'] ?? 'Guest',
    'email' => $_SESSION['user_email'] ?? 'noemail@example.com'
];

$transaction = [
    'transaction_details' => $transaction_details,
    'customer_details' => $customer_details,
    'item_details' => $item_details
];

try {
    $snapToken = \Midtrans\Snap::getSnapToken($transaction);
    echo json_encode(['success' => true, 'snap_token' => $snapToken]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
