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

// Ambil data produk dari database berdasarkan item di keranjang
$cart_items = [];
$subtotal = 0;
$total = 0;
$shipping_cost = 15000; // Biaya pengiriman default
if (!empty($_SESSION['cart'])) {
    $product_ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    
    $query = "SELECT id, name, price, image FROM products WHERE id IN ($placeholders)";
    $stmt = $pdo->prepare($query);
    $stmt->execute($product_ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Susun data keranjang
    foreach ($products as $product) {
        $cart_items[] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'image' => $product['image'],
            'quantity' => $_SESSION['cart'][$product['id']]['quantity']
        ];
        
        $subtotal += $product['price'] * $_SESSION['cart'][$product['id']]['quantity'];
    }
    
    $total = $subtotal + $shipping_cost;
}

// Transaction Details
$transaction_details = array(
    'order_id' => 'order_' . time(),
    'gross_amount' => $total, // Total amount including shipping
);

// Customer Details
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT address, email, first_name, last_name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Ambil alamat pengguna untuk ditampilkan sebagai placeholder
$user_address = $user ? $user['address'] : '';
$customer_details = array(
    'first_name'    => $user ? $user['first_name'] : 'Unknown',
    'last_name'     => $user ? $user['last_name'] : 'User',
    'email'         => $user ? $user['email'] : 'user@example.com',
    'phone'         => "081122334455",  // Replace with actual phone number
    'billing_address'  => array(
        'address'   => $user_address,
        'city'      => 'Jakarta',  // Replace with dynamic city if needed
        'postal_code' => '16602',
        'country_code' => 'IDN'
    ),
    'shipping_address' => array(
        'address'   => $user_address,
        'city'      => 'Jakarta',
        'postal_code' => '16602',
        'country_code' => 'IDN'
    )
);

// Payment Methods to be enabled
$enable_payments = array('credit_card', 'bca_va', 'gopay');

// Prepare the full transaction array
$transaction = array(
    'enabled_payments' => $enable_payments,
    'transaction_details' => $transaction_details,
    'customer_details' => $customer_details,
    'item_details' => $cart_items,
);

$snap_token = '';
try {
    require_once dirname(__FILE__) . '/../../midtrans-php-master/Midtrans.php';
    \Midtrans\Config::$serverKey = 'SB-Mid-server-T1swFng-4kR4g6CLkSWnKzIH';
    \Midtrans\Config::$clientKey = 'SB-Mid-client-dmtofN76DYy2vU_A';
    \Midtrans\Config::$isProduction = false; // Set to true for production

    $snap_token = \Midtrans\Snap::getSnapToken($transaction);
} catch (\Exception $e) {
    echo $e->getMessage();
    exit;
}

// Cek apakah sudah ada transaksi dengan order_id yang sama
$stmt = $pdo->prepare("SELECT * FROM user_payment WHERE order_id = ?");
$stmt->execute([$transaction_details['order_id']]);
$existing_transaction = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existing_transaction) {
    echo "Transaksi sudah ada, tidak bisa melakukan transaksi lagi.";
    exit;
}

// Save only transaction details to the user_payment table
if ($snap_token) {
    try {
        // Insert each product into user_payment table
        foreach ($cart_items as $item) {
            $stmt = $pdo->prepare("INSERT INTO user_payment (name_product, user, jumlah, total_harga, payment_status, order_id) 
                                   VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $item['name'], // Product name
                $user_id, // User ID
                $item['quantity'], // Quantity
                $total, // Total price (you may want to adjust if needed)
                'pending', // Payment status
                $transaction_details['order_id'] // Order ID to link to the payment
            ]);
        }

        // Store the snap_token in the session to use it on the frontend
        $_SESSION['snap_token'] = $snap_token;

        echo "snapToken = " . $snap_token;
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
} else {
    echo "Error: Snap Token not generated.";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - VapeStore</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f9f9f9;
            color: #333;
        }

        .container {
            width: 80%;
            margin: 0 auto;
        }

        .summary {
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .summary h3 {
            text-align: center;
        }

        .summary .total {
            font-weight: bold;
        }

        .summary button {
            display: block;
            width: 100%;
            background-color: #4a00e0;
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
        }

        .summary button:hover {
            background-color: #8e2de2;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="summary">
            <h3>Ringkasan Belanja</h3>
            <p>Subtotal: Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></p>
            <p>Biaya Pengiriman: Rp <?php echo number_format($shipping_cost, 0, ',', '.'); ?></p>
            <p class="total">Total: Rp <?php echo number_format($total, 0, ',', '.'); ?></p>
            <button id="pay-button">Selesaikan Pembayaran</button>
        </div>
    </div>

    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="<?php echo \Midtrans\Config::$clientKey; ?>"></script>
    <script type="text/javascript">
        document.getElementById('pay-button').onclick = function() {
            snap.pay('<?php echo $snap_token ?>', {
                onSuccess: function(result) {
                    alert("Pembayaran Berhasil! " + JSON.stringify(result));
                    <?php unset($_SESSION['snap_token']); ?>
                    // Redirect to success page after successful payment
                    window.location.href = "payment_success.php";
                },
                onPending: function(result) {
                    alert("Pembayaran Pending: " + JSON.stringify(result));
                },
                onError: function(result) {
                    alert("Pembayaran Gagal: " + JSON.stringify(result));
                }
            });
        };
    </script>
</body>
</html>
