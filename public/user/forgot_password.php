<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../../includes/PHPMailer/PHPMailer-master/src/Exception.php';
require '../../includes/PHPMailer/PHPMailer-master/src/PHPMailer.php';
require '../../includes/PHPMailer/PHPMailer-master/src/SMTP.php';

$host = 'localhost';
$dbname = 'vumeemyi_database';
$username = 'root';
$password = '';
$message = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $token = bin2hex(random_bytes(50));
            $expiry = date("Y-m-d H:i:s", strtotime('+1 hour'));

            $stmt = $pdo->prepare("UPDATE users SET reset_token = :token, reset_token_expiry = :expiry WHERE email = :email");
            $stmt->bindParam(':token', $token);
            $stmt->bindParam(':expiry', $expiry);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            // Konfigurasi PHPMailer
            $mail = new PHPMailer(true);
            try {
                //Server settings

                // OPSI PENTING UNTUK HOSTING CPANEL (KURANG AMAN)
                $mail->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                );

                $mail->SMTPDebug = 2; // AKTIFKAN DEBUG. Ganti menjadi 0 setelah selesai.

                $mail->isSMTP();
                $mail->Host       = 'smtp.googlemail.com'; // Ganti dengan host SMTP Anda
                $mail->SMTPAuth   = true;
                $mail->Username   = 'defarhannugraha1@gmail.com'; // Ganti dengan email SMTP Anda
                $mail->Password   = 'kfgwxmvwxtdrqnda'; // Ganti dengan password SMTP Anda
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                //Recipients
                $mail->setFrom('no-reply@vumee.my.id', 'Vumee VapeStore'); // Sesuaikan dengan email cPanel Anda
                $mail->addAddress($email);
                $mail->addReplyTo('no-reply@vumee.my.id', 'Vumee VapeStore'); // Sesuaikan dengan email cPanel Anda

                //Content
                $mail->isHTML(true);
                $mail->Subject = 'Reset Password Akun Vumee Anda';
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/public/user/reset_password.php?token=$token";
                $mail->Body    = "
                <p>Halo,</p>
                <p>Kami menerima permintaan untuk mereset password akun Anda. Silakan klik tautan di bawah ini untuk melanjutkan:</p>
                <p><a href='$reset_link'>$reset_link</a></p>
                <p>Tautan ini akan kedaluwarsa dalam 1 jam.</p>
                <p>Jika Anda tidak merasa melakukan permintaan ini, abaikan email ini.</p>
                <p>Terima kasih,</p>
                <p>Tim Vumee</p>
                ";

                $mail->send();
                $message = 'Tautan reset password telah dikirim ke email Anda.';
            } catch (Exception $e) {
                $error = "Gagal mengirim email. Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            $error = "Tidak ada pengguna yang terdaftar dengan email tersebut.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Vumee</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
            background-color: #f9f9f9;
            color: var(--dark);
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 2rem 5%;
        }

        .forgot-password-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 500px;
            padding: 2.5rem;
        }

        .header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: var(--primary);
        }

        .header p {
            color: #666;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(74, 0, 224, 0.1);
            outline: none;
        }
        
        .message, .error-message {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 5px;
            text-align: center;
        }

        .message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .btn {
            display: inline-block;
            background-color: var(--primary);
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn:hover {
            background-color: var(--secondary);
            transform: translateY(-2px);
        }

        .back-to-login {
            text-align: center;
            margin-top: 1.5rem;
        }

        .back-to-login a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }

        .back-to-login a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="forgot-password-container">
            <div class="header">
                <h1>Lupa Password</h1>
                <p>Masukkan alamat email Anda dan kami akan mengirimkan tautan untuk mengatur ulang kata sandi Anda</p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="message"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form action="forgot_password.php" method="POST">
                <div class="form-group">
                    <label for="email">Alamat Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <button type="submit" class="btn">Kirim Tautan Reset</button>
            </form>

            <div class="back-to-login">
                <p><a href="login.php">Kembali ke halaman Login</a></p>
            </div>
        </div>
    </div>
</body>
</html>