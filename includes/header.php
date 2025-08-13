<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'VapeStore'; ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo">
                Vape<span>Store</span>
            </div>
            <nav>
                <ul>
                    <li><a href="/index.php">Beranda</a></li>
                    <li><a href="/user/shop.php">Produk</a></li>
                    <li><a href="/tentang.php">Tentang Kami</a></li>
                    <li><a href="/kontak.php">Kontak</a></li>
                    <?php if (isset($_SESSION['user_logged_in'])): ?>
                        <li><a href="/user/profile.php">Profil Saya</a></li>
                        <li><a href="/user/logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="/user/login.php">Login</a></li>
                    <?php endif; ?>
                    <li>
                        <a href="/user/cart.php" class="cart-icon">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="cart-count">
                                <?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?>
                            </span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </header>
