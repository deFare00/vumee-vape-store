<?php
session_start();

// Redirect jika bukan admin
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

// Definisikan kategori yang valid
$valid_categories = ['Liquid', 'Pod', 'Atomizer', 'Accessories'];

// Handle tambah kategori
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $category_name = trim($_POST['category_name']);
    
    // Validasi kategori
    $valid_categories = ['Liquid', 'Pod', 'Atomizer', 'Accessories'];
    
    if (in_array($category_name, $valid_categories)) {
        // Update kategori produk yang ada
        $stmt = $pdo->prepare("UPDATE products SET category = ? WHERE category = ?");
        $stmt->execute([$category_name, $category_name]);
        
        header('Location: manage-categories.php?success=added');
        exit;
    } else {
        echo "Kategori tidak valid.";
    }
}


// Handle hapus kategori
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $category_name = trim($_POST['category_name']);
    
    // Validasi kategori
    $valid_categories = ['Liquid', 'Pod', 'Atomizer', 'Accessories'];
    
    if (in_array($category_name, $valid_categories)) {
        // Update kategori produk yang ada
        $stmt = $pdo->prepare("UPDATE products SET category = ? WHERE category = ?");
        $stmt->execute([$category_name, $category_name]);
        
        header('Location: manage-categories.php?success=added');
        exit;
    } else {
        echo "Kategori tidak valid.";
    }
}


// Ambil kategori unik dari produk
$stmt = $pdo->query("SELECT DISTINCT category FROM products ORDER BY category ASC");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kategori - Admin VapeStore</title>
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

        
        .btn {
            display: inline-block;
            padding: 0.6rem 1.2rem;
            background-color: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background-color: var(--secondary);
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background-color: #dc3545;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }
        
        .categories-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .categories-table th, 
        .categories-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .categories-table th {
            background-color: #f9f9f9;
            font-weight: 500;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .alert {
            padding: 0.8rem 1rem;
            margin-bottom: 1.5rem;
            border-radius: 5px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .admin-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
            }
            
            .categories-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <header class="admin-header">
        <h1>Dashboard Admin VapeStore</h1>
        <nav class="admin-nav">
            <ul>
                <li><a href="#"><i class="fas fa-bell"></i></a></li>
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
                    <li><a href="manage-categories.php" class="active"><i class="fas fa-tags"></i> Kelola Kategori</a></li>
                    <!-- <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Laporan</a></li> -->
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h2>Kelola Kategori</h2>
                <form method="post" action="manage-categories.php" style="display: inline;">
                    <input type="text" name="category_name" placeholder="Nama Kategori" required>
                    <button type="submit" name="add_category" class="btn">+ Tambah Kategori</button>
                </form>
            </div>
            
            <?php if (isset($_GET['success']) && $_GET['success'] === 'added'): ?>
                <div class="alert alert-success">
                    Kategori berhasil ditambahkan!
                </div>
            <?php elseif (isset($_GET['success']) && $_GET['success'] === 'deleted'): ?>
                <div class="alert alert-success">
                    Kategori berhasil dihapus!
                </div>
            <?php endif; ?>
            
            <table class="categories-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Kategori</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($valid_categories as $category): ?>
                    <tr>
                        <td>#<?php echo htmlspecialchars($category); ?></td>
                        <td><?php echo htmlspecialchars($category); ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="edit-category.php?category=<?php echo urlencode($category); ?>" class="btn btn-edit">
                                    <i class="fas fa-edit">Edit</i>
                                </a>
                                <a href="manage-categories.php?delete=<?php echo urlencode($category); ?>" 
                                class="btn btn-danger" 
                                onclick="return confirm('Yakin ingin menghapus kategori ini?')">
                                    <i class="fas fa-trash">Delete</i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </main>
    </div>

    <!-- Font Awesome for icons -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>
