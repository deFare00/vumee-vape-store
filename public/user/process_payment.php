<?php
// process_payment.php

// Pastikan hanya menerima POST request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data yang dikirimkan oleh JavaScript
    $data = json_decode(file_get_contents('php://input'), true);

    // Cek apakah data valid
    if (isset($data['user_id'], $data['cart_items'], $data['total_amount'], $data['payment_status'])) {
        $host = 'localhost';
$dbname = 'vumeemyi_database';
$username = 'root';
$password = '';

        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Generate resi_code untuk transaksi
            $resi_code = 'RESI-' . strtoupper(bin2hex(random_bytes(5))); // Generate random resi code

            // Proses setiap item dalam keranjang
            foreach ($data['cart_items'] as $item) {
                // Insert transaksi pembayaran ke database
                $stmt = $pdo->prepare("INSERT INTO user_payment (user, name_product, jumlah, total_harga, payment_status, resi_code)
                                       VALUES (?, ?, ?, ?, ?, ?)");

                $stmt->execute([
                    $data['user_id'],       // ID pengguna
                    $item['name'],          // Nama produk
                    $item['quantity'],      // Jumlah produk
                    $item['price'] * $item['quantity'], // Total harga untuk produk
                    $data['payment_status'], // Status pembayaran
                    $resi_code              // Kode resi yang dihasilkan
                ]);
            }

            // Jika berhasil, kirimkan respon sukses dan kembalikan kode resi
            echo json_encode(['success' => true, 'resi_code' => $resi_code]);

        } catch (PDOException $e) {
            // Jika gagal, kirimkan pesan error
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
        // Jika data tidak lengkap
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
