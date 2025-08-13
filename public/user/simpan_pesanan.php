<?php
session_start();
header('Content-Type: application/json');

function return_json_error($message) {
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in'] || empty($_SESSION['cart'])) {
    return_json_error('Sesi tidak valid atau keranjang kosong untuk disimpan.');
}

$request_body = file_get_contents('php://input');
$request_data = json_decode($request_body, true);
$transaction_status = $request_data['transaction_status'] ?? 'unknown';

$host = 'localhost';
$dbname = 'vumeemyi_database';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->beginTransaction();
    
    $resi_code = 'RESI-' . strtoupper(bin2hex(random_bytes(5)));
    $user_id = $_SESSION['user_id'];
    $cart_items = $_SESSION['cart'];


    $stmt = $pdo->prepare(
        "INSERT INTO user_payment (user, name_product, jumlah, total_harga, payment_status, resi_code)
         VALUES (?, ?, ?, ?, ?, ?)"
    );

    foreach ($cart_items as $product_id => $item) {
        $product_stmt = $pdo->prepare("SELECT name, price FROM products WHERE id = ?");
        $product_stmt->execute([$product_id]);
        $product_data = $product_stmt->fetch(PDO::FETCH_ASSOC);

        if ($product_data) {
            $product_name = $product_data['name'];
            $total_price_per_item = $product_data['price'] * $item['quantity'];

            
            $stmt->execute([
                $user_id,
                $product_name,
                $item['quantity'],
                $total_price_per_item,
                $transaction_status, 
                $resi_code
            ]);
        }
    }
    
    $pdo->commit();
    unset($_SESSION['cart']);

    echo json_encode(['success' => true, 'resi_code' => $resi_code]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Gagal simpan pesanan: " . $e->getMessage());
    return_json_error('Gagal menyimpan data pesanan ke database.');
}
?>