<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $message = $_POST['message'];

    // Validasi input
    if (empty($name) || empty($email) || empty($message)) {
        die("Semua field harus diisi.");
    }

    // Kirim email (atau simpan ke database)
    $to = "info@vapestore.id"; // Ganti dengan email tujuan
    $subject = "Pesan dari Kontak Website";
    $body = "Nama: $name\nEmail: $email\nPesan:\n$message";
    $headers = "From: $email";

    if (mail($to, $subject, $body, $headers)) {
        echo "Pesan Anda telah dikirim!";
    } else {
        echo "Gagal mengirim pesan.";
    }
} else {
    header('Location: contact.php');
    exit;
}
