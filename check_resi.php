<?php
session_start();

// Cek apakah ada input resi
if (isset($_POST['resi_code'])) {
    // Ambil kode resi dari form input
    $resi_code = $_POST['resi_code'];

    // Koneksi ke database
    $host = 'localhost';
    $dbname = 'vumeemyi_database';
    $username = 'root';
    $password = '';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Query untuk memeriksa status pengiriman berdasarkan kode resi
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE resi_code = ?");
        $stmt->execute([$resi_code]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            $status = $order['status']; // Status pengiriman
        } else {
            $status = "Kode resi tidak ditemukan atau salah.";
        }
    } catch (PDOException $e) {
        die("Koneksi database gagal: " . $e->getMessage());
    }
} else {
    // Jika tidak ada resi yang dikirim
    $status = "Silakan masukkan kode resi untuk mengecek status.";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Pengiriman - VapeStore</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            color: #333;
            padding: 2rem;
        }

        .status-message {
            background-color: #4caf50;
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }

        .error-message {
            background-color: #e64a19;
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }

        .back-to-home {
            text-align: center;
            margin-top: 2rem;
        }

        .back-to-home a {
            background-color: #4a00e0;
            color: white;
            padding: 1rem 2rem;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
        }

        .back-to-home a:hover {
            background-color: #8e2de2;
        }
    </style>
</head>
<body>
    <div class="status-message">
        <h2>Status Pengiriman</h2>
        <p>Kode Resi: <?php echo htmlspecialchars($resi_code); ?></p>
        <p>Status: <?php echo htmlspecialchars($status); ?></p>
    </div>

    <div class="back-to-home">
        <a href="index.php">Kembali ke Beranda</a>
    </div>
</body>
</html>
