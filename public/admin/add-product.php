<?php
session_start();

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../user/login.php');
    exit;
}

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

// Ambil kategori unik
// Ambil semua nilai dari ENUM category
$enumResult = $pdo->query("SHOW COLUMNS FROM products LIKE 'category'")->fetch(PDO::FETCH_ASSOC);

$enumValues = [];
if ($enumResult) {
    preg_match("/^enum\((.*)\)$/", $enumResult['Type'], $matches);
    if (!empty($matches[1])) {
        $enum = explode(",", $matches[1]);
        foreach ($enum as $value) {
            $enumValues[] = trim($value, " '");
        }
    }
}
$categories = $enumValues;


// Proses form tambah produk
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $description = $_POST['description'];
    
    // Upload gambar
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "../uploads/";
        $target_file = $target_dir . basename($_FILES["image"]["name"]);
        move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
        $image = basename($_FILES["image"]["name"]);
    }
    
    // Insert ke database
    $stmt = $pdo->prepare("INSERT INTO products (name, category, price, stock, description, image) 
                          VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $category, $price, $stock, $description, $image]);
    
    header('Location: manage-products.php?success=added');
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Produk - Admin Vumee</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #4a00e0;
            --secondary: #8e2de2;
            --accent: #ff5722;
            --dark: #121212;
            --light: #f5f5f5;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: var(--dark);
        }
        
        /* Admin Header */
        .admin-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .admin-header h1 {
            font-size: 1.5rem;
        }
        
        .admin-nav ul {
            display: flex;
            list-style: none;
        }
        
        .admin-nav li {
            margin-left: 1.5rem;
        }
        
        .admin-nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .admin-nav a:hover {
            color: var(--accent);
        }
        
        /* Sidebar */
        .admin-container {
            display: flex;
            min-height: calc(100vh - 60px);
        }
        
        .sidebar {
            width: 250px;
            background-color: white;
            padding: 1.5rem;
            box-shadow: 2px 0 5px rgba(0,0,0,0.05);
        }
        
        .sidebar-nav ul {
            list-style: none;
        }
        
        .sidebar-nav li {
            margin-bottom: 0.5rem;
        }
        
        .sidebar-nav a {
            display: block;
            padding: 0.8rem 1rem;
            color: var(--dark);
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background-color: rgba(74, 0, 224, 0.1);
            color: var(--primary);
        }
        
        .sidebar-nav i {
            width: 20px;
            margin-right: 10px;
            text-align: center;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            padding: 2rem;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .page-header h2 {
            font-size: 1.8rem;
            color: var(--primary);
        }
        
        /* Form Styles */
        .form-container {
            background-color: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            max-width: 800px;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #555;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(74, 0, 224, 0.1);
            outline: none;
        }
        
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        /* File Input Styling */
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }
        
        .file-input-wrapper input[type="file"] {
            font-size: 100px;
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .file-input-button {
            display: inline-block;
            padding: 0.8rem 1rem;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 5px;
            color: #555;
            width: 100%;
            text-align: center;
        }
        
        .file-input-button:hover {
            background-color: #e9ecef;
        }
        
        .file-name {
            margin-top: 0.5rem;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        /* Button Styles */
        .btn {
            display: inline-block;
            padding: 0.8rem 1.5rem;
            background-color: var(--primary);
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background-color: var(--secondary);
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background-color: #6c757d;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        /* Preview Image */
        .image-preview {
            margin-top: 1rem;
            max-width: 200px;
            max-height: 200px;
            display: none;
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .admin-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
            }
            
            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <header class="admin-header">
        <h1>Dashboard Admin Vumee</h1>
        <nav class="admin-nav">
            <ul>
                <!--<li><a href="#"><i class="fas fa-bell"></i></a></li>-->
                <li><a href="#"><i class="fas fa-user"></i> <?php echo $_SESSION['user_name']; ?></a></li>
                <li><a href="../user/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </header>

    <!-- Admin Container -->
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="manage-products.php"><i class="fas fa-box"></i> Kelola Produk</a></li>
                    <li><a href="manage-orders.php"><i class="fas fa-shopping-cart"></i> Kelola Pesanan</a></li>
                    <li><a href="manage-users.php"><i class="fas fa-users"></i> Kelola Pengguna</a></li>
                    <li><a href="manage-shipments.php"><i class="fas fa-truck"></i> Kelola Pengiriman Resi</a></li>
                    <!-- <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Laporan</a></li> -->

                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h2>Tambah Produk Baru</h2>
                <a href="manage-products.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
            
            <div class="form-container">
                <form method="POST" enctype="multipart/form-data" id="product-form">
                    <div class="form-group">
                        <label for="name">Nama Produk</label>
                        <input type="text" id="name" name="name" required placeholder="Masukkan nama produk">
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Kategori</label>
                        <select id="category" name="category" required>
                            <option value="">Pilih Kategori</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>">
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Harga</label>
                        <input type="number" id="price" name="price" required placeholder="Masukkan harga" min="0" step="1000">
                    </div>
                    
                    <div class="form-group">
                        <label for="stock">Stok</label>
                        <input type="number" id="stock" name="stock" required placeholder="Masukkan jumlah stok" min="0">
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Deskripsi Produk</label>
                        <textarea id="description" name="description" required placeholder="Masukkan deskripsi produk"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="image">Gambar Produk</label>
                        <div class="file-input-wrapper">
                            <button type="button" class="file-input-button">
                                <i class="fas fa-upload"></i> Pilih Gambar
                            </button>
                            <input type="file" id="image" name="image" accept="image/*" required>
                        </div>
                        <p class="file-name" id="file-name">Belum ada file dipilih</p>
                        <img id="image-preview" class="image-preview" src="#" alt="Preview Gambar">
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn">
                            <i class="fas fa-save"></i> Simpan Produk
                        </button>
                        <button type="reset" class="btn btn-secondary">
                            <i class="fas fa-undo"></i> Reset Form
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <!-- Font Awesome for icons -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    
    <script>
        // Preview image sebelum upload
        document.getElementById('image').addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : "Belum ada file dipilih";
            document.getElementById('file-name').textContent = fileName;
            
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                const preview = document.getElementById('image-preview');
                
                reader.onload = function(event) {
                    preview.src = event.target.result;
                    preview.style.display = 'block';
                }
                
                reader.readAsDataURL(e.target.files[0]);
            }
        });
    </script>
</body>
</html>
