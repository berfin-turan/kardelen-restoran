<?php
// 1. Veritabanı bağlantımızı dahil ediyoruz
include 'baglan.php';

/*// Eğer kullanıcı zaten giriş yapmışsa, tekrar giriş sayfasını görmesin, paneline gitsin
if (isset($_SESSION['kullanici_id'])) {
    if ($_SESSION['rol'] == 'personel') {
        header("Location: personel_panel.php");
    } else {
        header("Location: index.php");
    }
    exit;
}*/

$mesaj = "";

// 2. Giriş butonuna basıldıysa işlemleri başlat
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $eposta = mysqli_real_escape_string($conn, $_POST['eposta']);
    $sifre  = $_POST['sifre'];

    // 3. Giriş yapılmak istenen e-posta veritabanında var mı kontrol et
    $query = "SELECT * FROM kullanicilar WHERE eposta = '$eposta'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $kullanici = mysqli_fetch_assoc($result);
        
        // 4. Hocanın Kritik Kuralı: password_verify ile hash kontrolü
        // Formdan gelen düz şifre (123456) ile veritabanındaki hash'i karşılaştırır
        if (password_verify($sifre, $kullanici['sifre_hash'])) {
            
            // 5. Giriş Başarılı! Bilgileri Session (Oturum) içine kaydediyoruz
            $_SESSION['kullanici_id'] = $kullanici['id'];
            $_SESSION['ad_soyad']     = $kullanici['ad_soyad'];
            $_SESSION['rol']          = $kullanici['rol'];

            // 6. Rol Kontrolü ve Yönlendirme
            if ($kullanici['rol'] == 'personel') {
                $mesaj = "<div class='alert alert-success'>Giriş başarılı! Personel paneline yönlendiriliyorsunuz...</div>";
                header("Refresh:2; url=personel_panel.php");
            } else {
                $mesaj = "<div class='alert alert-success'>Giriş başarılı! Ana sayfaya yönlendiriliyorsunuz...</div>";
                header("Refresh:2; url=index.php");
            }

        } else {
            $mesaj = "<div class='alert alert-danger'>Hatalı şifre girdiniz!</div>";
        }
    } else {
        $mesaj = "<div class='alert alert-danger'>Bu e-posta adresiyle kayıtlı kullanıcı bulunamadı!</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kardelen Restoran - Giriş Yap</title>
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
        .login-box { max-width: 380px; width: 100%; }
        .login-box h3 { font-family: 'Playfair Display', serif; font-weight: 700; color: #2c2c2c; }
        .btn-giris { background: #2d6a4f; border: none; color: white; padding: 12px; font-weight: 600; border-radius: 10px; }
        .btn-giris:hover { background: #1b4332; color: white; }
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
            <h3 class="mb-1">Hoş Geldiniz</h3>
            <p class="text-muted mb-4">Kardelen Restoran hesabınıza giriş yapın</p>

            <?php echo $mesaj; ?>

            <form action="login.php" method="POST">
                <div class="mb-3">
                    <label for="eposta" class="form-label fw-semibold">E-posta Adresi</label>
                    <input type="email" class="form-control" id="eposta" name="eposta" required placeholder="name@example.com">
                </div>
                <div class="mb-3">
                    <label for="sifre" class="form-label fw-semibold">Şifre</label>
                    <input type="password" class="form-control" id="sifre" name="sifre" required placeholder="******">
                </div>
                <button type="submit" class="btn btn-giris w-100">Giriş Yap</button>
            </form>
            
            <div class="text-center mt-4">
                <p class="text-muted">Henüz hesabınız yok mu? <a href="kayit.php" class="fw-bold text-decoration-none" style="color:#2d6a4f">Kayıt Ol</a></p>
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