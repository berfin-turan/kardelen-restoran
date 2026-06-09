<?php
// 1. Veritabanı bağlantı dosyamızı dahil ediyoruz
include 'baglan.php';

$mesaj = ""; // Ekranda kullanıcıya göstermek istediğimiz uyarılar için değişken

// 2. Eğer form gönderildiyse (Kaydet butonuna basıldıysa) backend işlemlerini başlat
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Formdan gelen verileri değişkenlere atıyoruz ve temizliyoruz (Güvenlik için)
    $ad_soyad = mysqli_real_escape_string($conn, $_POST['ad_soyad']);
    $eposta   = mysqli_real_escape_string($conn, $_POST['eposta']);
    $telefon  = mysqli_real_escape_string($conn, $_POST['telefon']);
    $sifre    = $_POST['sifre']; // Şifreyi hashleyeceğimiz için direkt alabiliriz

    // 3. E-posta adresinin veritabanında daha önce kayıtlı olup olmadığını kontrol et
    $kontrol_query = "SELECT * FROM kullanicilar WHERE eposta = '$eposta'";
    $kontrol_result = mysqli_query($conn, $kontrol_query);

    if (mysqli_num_rows($kontrol_result) > 0) {
        $mesaj = "<div class='alert alert-danger'>Bu e-posta adresiyle daha önce kayıt olunmuş!</div>";
    } else {
        // 4. Hocanın Kritik Kuralı: Şifreyi password_hash() ile güvenli hale getiriyoruz
        // Veritabanına şifrenin kendisini (123456) değil, kırılması imkansız olan hash halini kaydedeceğiz.
        $sifre_hash = password_hash($sifre, PASSWORD_DEFAULT);

        // Varsayılan olarak kayıt olan herkes 'musteri' rolünde başlar
        $rol = "musteri"; 

        // 5. Veritabanına Ekleme (CREATE) İşlemi
        $ekle_query = "INSERT INTO kullanicilar (ad_soyad, eposta, sifre_hash, telefon, rol) 
                       VALUES ('$ad_soyad', '$eposta', '$sifre_hash', '$telefon', '$rol')";
        
        if (mysqli_query($conn, $ekle_query)) {
            $mesaj = "<div class='alert alert-success'>Kayıt başarıyla tamamlandı! Giriş yapabilirsiniz.</div>";
            // İstersen 2 saniye sonra giriş sayfasına yönlendirebilirsin
            header("Refresh:2; url=login.php");
        } else {
            $mesaj = "<div class='alert alert-danger'>Kayıt sırasında bir hata oluştu: " . mysqli_error($conn) . "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kardelen Restoran - Kayıt Ol</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif; min-height: 100vh;
            background: url('arkaplan.jpg') no-repeat center center fixed;
            background-size: cover;
        }
        .wrapper {
            min-height: 100vh; display: flex; align-items: center;
            background: rgba(0,0,0,0.25);
            padding: 40px 60px;
        }
        .split-left {
            width: 380px; 
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(12px);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.2);
        }
        .split-right {
            flex: 1;
            display: flex; align-items: center; justify-content: center;
        }
        .brand-overlay { text-align: center; color: white; }
        .brand-overlay h1 {
            font-family: 'Playfair Display', serif; font-size: 4.5rem; font-weight: 800;
            text-shadow: 2px 4px 16px rgba(0,0,0,0.6); letter-spacing: 4px;
        }
        .brand-overlay p {
            font-size: 1.3rem; font-weight: 300; margin-top: 12px;
            text-shadow: 1px 2px 8px rgba(0,0,0,0.5); letter-spacing: 4px;
        }
        .login-box { max-width: 400px; width: 100%; }
        .login-box h3 { font-family: 'Playfair Display', serif; font-weight: 700; color: #2c2c2c; }
        .btn-kayit { background: #2d6a4f; border: none; color: white; padding: 12px; font-weight: 600; border-radius: 10px; }
        .btn-kayit:hover { background: #1b4332; color: white; }
        .form-control { border-radius: 10px; padding: 12px; }
        .form-control:focus { box-shadow: 0 0 0 3px rgba(45,106,79,0.2); border-color: #2d6a4f; }
        @media (max-width: 768px) {
            .split-left { width: 100%; min-height: auto; padding: 40px 20px; }
            .split-right { display: none; }
        }
    </style>
</head>
<body>

<div class="wrapper">
    <div class="split-left">
        <div class="login-box">
            <h3 class="mb-1">Hesap Oluştur</h3>
            <p class="text-muted mb-4">Kardelen Restoran'a üye olun</p>

            <?php echo $mesaj; ?>

            <form action="kayit.php" method="POST">
                <div class="mb-3">
                    <label for="ad_soyad" class="form-label fw-semibold">Ad Soyad</label>
                    <input type="text" class="form-control" id="ad_soyad" name="ad_soyad" required placeholder="Örn: Berfin Turan">
                </div>
                <div class="mb-3">
                    <label for="eposta" class="form-label fw-semibold">E-posta Adresi</label>
                    <input type="email" class="form-control" id="eposta" name="eposta" required placeholder="name@example.com">
                </div>
                <div class="mb-3">
                    <label for="telefon" class="form-label fw-semibold">Telefon Numarası</label>
                    <input type="text" class="form-control" id="telefon" name="telefon" required placeholder="05XXXXXXXXX">
                </div>
                <div class="mb-3">
                    <label for="sifre" class="form-label fw-semibold">Şifre</label>
                    <input type="password" class="form-control" id="sifre" name="sifre" required placeholder="******">
                </div>
                <button type="submit" class="btn btn-kayit w-100">Kayıt Ol</button>
            </form>
            
            <div class="text-center mt-4">
                <p class="text-muted">Zaten hesabınız var mı? <a href="login.php" class="fw-bold text-decoration-none" style="color:#2d6a4f">Giriş Yap</a></p>
            </div>
        </div>
    </div>

    <div class="split-right">
        <div class="brand-overlay">
            <h1>Kardelen</h1>
            <p>R&nbsp;E&nbsp;S&nbsp;T&nbsp;O&nbsp;R&nbsp;A&nbsp;N</p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>