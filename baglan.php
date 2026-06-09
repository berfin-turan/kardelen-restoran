<?php
// Oturumları (Session) başlatıyoruz. Giriş/çıkış işlemlerinin çalışması için şarttır.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "restoran_db";

// Bağlantıyı kurma (W3Schools tarzı)
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Karakter setini Türkçe uyumlu yapma (Veritabanında ş, ç, ğ gibi harflerin bozulmaması için)
mysqli_set_charset($conn, "utf8mb4");

// Kontrol etme
if (!$conn) {
  die("Bağlantı hatası: " . mysqli_connect_error());
}
?>