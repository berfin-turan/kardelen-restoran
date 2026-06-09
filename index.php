<?php
// 🔄 Oturumu her şeyden önce en üstte başlatıyoruz
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Veritabanı bağlantısını dahil ediyoruz
include 'baglan.php';

// Güvenlik Duvarı: Giriş yapmamış kullanıcıyı login.php'ye fırlat
if (!isset($_SESSION['kullanici_id'])) {
    header("Location: login.php");
    exit;
}

$musteri_id = $_SESSION['kullanici_id'];

// Post-Redirect-Get (PRG) Pattern
$mesaj = "";
if (isset($_SESSION['erp_mesaj'])) {
    $mesaj = $_SESSION['erp_mesaj'];
    unset($_SESSION['erp_mesaj']); 
}

$bugun = date('Y-m-d');

// ==========================================
// 🛠️ AKSİYON 1: REZERVASYON TALEBİ OLUŞTURMA
// ==========================================
if (isset($_POST['rezervasyon_yap'])) {
    $kisi_sayisi = mysqli_real_escape_string($conn, $_POST['kisi_sayisi']);
    $tarih       = mysqli_real_escape_string($conn, $_POST['rezervasyon_tarihi']);
    $saat        = mysqli_real_escape_string($conn, $_POST['saat']);

    $rez_query = "INSERT INTO rezervasyonlar (musteri_id, kisi_sayisi, rezervasyon_tarihi, saat, durum) 
                  VALUES ('$musteri_id', '$kisi_sayisi', '$tarih', '$saat', 'Beklemede')";
    
    if (mysqli_query($conn, $rez_query)) {
        $_SESSION['erp_mesaj'] = "<div class='alert alert-success alert-dismissible fade show'>🎉 Rezervasyon talebiniz alındı! Takibini 'Rezervasyonlarım' sekmesinden yapabilirsiniz.</div>";
    } else {
        $_SESSION['erp_mesaj'] = "<div class='alert alert-danger'>Hata: " . mysqli_error($conn) . "</div>";
    }
    header("Location: index.php");
    exit;
}

// ==========================================
// 🛠️ AKSİYON 2: GÜN SONU VEYA MENÜ YEMEĞİ AYIRTMA 
// ==========================================
if (isset($_POST['yemegi_kurtar']) || isset($_POST['menu_siparis_ver'])) {
    $yemek_id = mysqli_real_escape_string($conn, $_POST['yemek_id']);
    
    $stok_kontrol = mysqli_query($conn, "SELECT urun_adi, adet FROM mutfak_stok WHERE id = '$yemek_id'");
    $yemek = mysqli_fetch_assoc($stok_kontrol);

    if ($yemek && $yemek['adet'] > 0) {
        $fis_kodu = "FIS-" . rand(1000, 9999);

        $siparis_query = "INSERT INTO siparisler (musteri_id, yemek_id, fis_kodu, durum, siparis_tarihi) 
                          VALUES ('$musteri_id', '$yemek_id', '$fis_kodu', 'Beklemede', NOW())";
        if (mysqli_query($conn, $siparis_query)) {
            $_SESSION['erp_mesaj'] = "<div class='alert alert-success alert-dismissible fade show'>
                                        🎉 <strong>Sipariş Başarıyla Alındı!</strong><br>
                                        Ürün: {$yemek['urun_adi']}<br>
                                        <strong>Dijital Fiş Kodunuz: <span class='text-danger'>{$fis_kodu}</span></strong><br>
                                        <small>Bu kodu 'Siparişlerim' sekmesinde görebilirsiniz.</small>
                                      </div>";
        }
    } else {
        $_SESSION['erp_mesaj'] = "<div class='alert alert-danger'>Üzgünüz, bu yemek tükendi!</div>";
    }
    header("Location: index.php");
    exit;
}

// ==========================================
// 🛠️ AKSİYON 3: YORUM VEYA YILDIZ BIRAKMA
// ==========================================
if (isset($_POST['yorum_yap'])) {
    $siparis_id = mysqli_real_escape_string($conn, $_POST['siparis_id']);
    $yildiz     = mysqli_real_escape_string($conn, $_POST['yildiz']);
    $yorum      = mysqli_real_escape_string($conn, $_POST['yorum']);

    $yorum_query = "UPDATE siparisler SET yildiz = '$yildiz', yorum = '$yorum' 
                    WHERE id = '$siparis_id' AND musteri_id = '$musteri_id'";
    
    if (mysqli_query($conn, $yorum_query)) {
        $_SESSION['erp_mesaj'] = "<div class='alert alert-success alert-dismissible fade show'>Değerlendirmeniz için teşekkür ederiz! ⭐</div>";
    }
    header("Location: index.php");
    exit;
}

// ==========================================
// 🛠️ AKSİYON 4: YORUMU SİLME
// ==========================================
if (isset($_POST['yorum_sil'])) {
    $siparis_id = mysqli_real_escape_string($conn, $_POST['siparis_id']);
    $sil_query = "UPDATE siparisler SET yildiz = NULL, yorum = NULL WHERE id = '$siparis_id' AND musteri_id = '$musteri_id'";
    mysqli_query($conn, $sil_query);
    header("Location: index.php");
    exit;
}

// ==========================================
// ❌ AKSİYON 5: HESAP SİLME (GÜVENLİ CASCADE)
// ==========================================
if (isset($_POST['hesap_sil'])) {
    // Manuel Cascade Temizliği (Hata almamak için)
    mysqli_query($conn, "DELETE FROM siparisler WHERE musteri_id = '$musteri_id'");
    mysqli_query($conn, "DELETE FROM rezervasyonlar WHERE musteri_id = '$musteri_id'");
    mysqli_query($conn, "DELETE FROM kullanicilar WHERE id = '$musteri_id'");
    
    session_destroy();
    header("Location: login.php?mesaj=hesapsilindi");
    exit;
}
// ==========================================
// 🗑️ AKSİYON: MÜŞTERİNİN KENDİ SİPARİŞİNİ SİLMESİ
// ==========================================
if (isset($_POST['siparis_iptal'])) {
    $siparis_id = mysqli_real_escape_string($conn, $_POST['siparis_id']);
    
    // Güvenlik: Sadece kendi siparişi ise silsin
    mysqli_query($conn, "DELETE FROM siparisler WHERE id = '$siparis_id' AND musteri_id = '$musteri_id'");
    
    $_SESSION['erp_mesaj'] = "<div class='alert alert-warning'>🗑️ Siparişiniz iptal edildi.</div>";
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kardelen Restoran - Müşteri Paneli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #fcfaf7; }
        .hero-title { font-family: 'Playfair Display', serif; font-weight: 700; color: #4a3728; }
        .navbar { background-color: #3e2723 !important; }
        .btn-custom { background-color: #8d6e63; color: white; border: none; }
        .btn-custom:hover { background-color: #5d4037; color: white; }
        .restaurant-card { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); background-color: white; }
        .nav-tabs .nav-link { color: #8d6e63; font-weight: 600; border: none; }
        .nav-tabs .nav-link.active { color: white !important; background-color: #8d6e63 !important; border-radius: 20px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand font-monospace" href="index.php" style="letter-spacing: 2px;">✨ KARDELEN RESTORAN</a>
        <div class="d-flex align-items-center ms-auto">
            <span class="navbar-text text-white me-3 d-none d-sm-inline">👋 <strong><?php echo htmlspecialchars($_SESSION['ad_soyad']); ?></strong></span>
            <form action="index.php" method="POST" class="me-2" onsubmit="return confirm('⚠️ DİKKAT! Hesabınızı sildiğinizde tüm geçmişiniz kalıcı olarak silinecektir. Onaylıyor musunuz?');">
                <button type="submit" name="hesap_sil" class="btn btn-danger btn-sm fw-bold">Hesabımı Sil</button>
            </form>
            <a class="btn btn-outline-light btn-sm" href="cikis.php">Çıkış</a>
        </div>
    </div>
</nav>

<div class="bg-white shadow-sm py-2 mb-4">
    <div class="container">
        <ul class="nav nav-tabs border-0 justify-content-center" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active me-2" id="menu-tab" data-bs-toggle="tab" data-bs-target="#menu" type="button" role="tab">🍽️ Restoran Menüsü</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link me-2" id="home-tab" data-bs-toggle="tab" data-bs-target="#home" type="button" role="tab">🍕 Gün Sonu Menüsü</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link me-2" id="rezervasyon-tab" data-bs-toggle="tab" data-bs-target="#rezervasyon" type="button" role="tab">📅 Rezervasyon Yap</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link me-2" id="durum-tab" data-bs-toggle="tab" data-bs-target="#durum" type="button" role="tab">📋 Rezervasyonlarım</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="siparis-tab" data-bs-toggle="tab" data-bs-target="#siparis" type="button" role="tab">⭐ Siparişlerim & Yorumlar</button>
            </li>
        </ul>
    </div>
</div>

<div class="container my-4">
    <?php echo $mesaj; ?>
    <div class="tab-content" id="myTabContent">
        
        <div class="tab-pane fade show active" id="menu" role="tabpanel">
            <h3 class="hero-title mb-4 text-center">Menümüz</h3>
            <div class="row g-3">
                <?php
                // Sadece personelin "Menü" olarak eklediği ürünleri çekiyoruz
                $genel_menu = mysqli_query($conn, "SELECT * FROM mutfak_stok WHERE tur = 'menu'");
                if (mysqli_num_rows($genel_menu) == 0) {
                    echo "<div class='col-12'><div class='alert alert-light text-center'>Menü şu an güncelleniyor.</div></div>";
                } else {
                    while ($m = mysqli_fetch_assoc($genel_menu)) {
                        $yemek_id = $m['id'];
                        // 🧠 Algoritma: Bu yemeğe ait siparişlerdeki yıldızların ortalamasını al
                        $puan_sorgu = mysqli_query($conn, "SELECT AVG(yildiz) as ortalama, COUNT(yildiz) as oy_sayisi FROM siparisler WHERE yemek_id = '$yemek_id' AND yildiz IS NOT NULL");
                        $puan_veri = mysqli_fetch_assoc($puan_sorgu);
                        $ortalama = $puan_veri['ortalama'] ? number_format($puan_veri['ortalama'], 1) : "0.0";
                        $oy_sayisi = $puan_veri['oy_sayisi'];
                        ?>
                        <div class='col-md-3'>
                            <div class='card restaurant-card p-3 h-100 d-flex flex-column justify-content-between'>
                                <div>
                                    <h5 class='text-dark fw-bold'><?php echo htmlspecialchars($m['urun_adi']); ?></h5>
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="text-warning fs-5 me-1">⭐</span>
                                        <strong><?php echo $ortalama; ?></strong>
                                        <span class="text-muted small ms-1">(<?php echo $oy_sayisi; ?>)</span>
                                    </div>
                                    <p class='text-success fw-bold fs-4'><?php echo $m['fiyat']; ?> TL</p>
                                </div>
                                <form action='index.php' method='POST' class="mt-auto">
                                    <input type='hidden' name='yemek_id' value='<?php echo $m['id']; ?>'>
                                    <button type='submit' name='menu_siparis_ver' class='btn btn-dark w-100'>Sipariş Ver</button>
                                </form>
                            </div>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
        </div>

        <div class="tab-pane fade" id="home" role="tabpanel">
            <div class="text-center mb-5">
                <h1 class="hero-title display-5">Günün Lezzetlerini Kurtarın</h1>
                <p class="text-muted">Mutfakta kalan taze yemekleri ayırtarak israfın önüne geçin.</p>
            </div>
            
            <div class="row g-3">
                <?php
                $yemekler_query = "SELECT * FROM mutfak_stok WHERE tur = 'gun_sonu_menusu' AND adet > 0";
                $yemekler_result = mysqli_query($conn, $yemekler_query);

                if (mysqli_num_rows($yemekler_result) == 0) {
                    echo "<div class='col-12'><div class='alert alert-info text-center'>Şu an aktif gün sonu menüsü bulunmuyor.</div></div>";
                } else {
                    while ($row = mysqli_fetch_assoc($yemekler_result)) {
                        ?>
                        <div class="col-md-4">
                            <div class="card restaurant-card p-3 h-100 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h5 class="card-title text-dark mb-0"><?php echo htmlspecialchars($row['urun_adi']); ?></h5>
                                        <span class="badge bg-danger">Kalan: <?php echo $row['adet']; ?></span>
                                    </div>
                                    
                                    <div class="mt-2 p-2 bg-light rounded" style="max-height: 120px; overflow-y: auto;">
                                        <h6 class="small fw-bold text-secondary mb-1" style="font-size: 12px;">💬 Yorumlar:</h6>
                                        <?php
                                        $o_yemek_id = $row['id'];
                                        // 🧠 LEFT JOIN ve COALESCE sayesinde kullanıcı hesabı silinmiş olsa bile yorum "Eski Müşteri" olarak kalır, çökmeyi engeller.
                                        $yorumlar_cek = mysqli_query($conn, "SELECT s.yorum, s.yildiz, COALESCE(k.ad_soyad, 'Eski Müşteri') as ad_soyad 
                                                                             FROM siparisler s 
                                                                             LEFT JOIN kullanicilar k ON s.musteri_id = k.id 
                                                                             WHERE s.yemek_id = '$o_yemek_id' AND s.yorum IS NOT NULL AND s.yorum != ''");
                                        
                                        if (mysqli_num_rows($yorumlar_cek) == 0) {
                                            echo "<small class='text-muted d-block italic' style='font-size: 11px;'>Henüz yorum yapılmamış.</small>";
                                        } else {
                                            while ($dyorum = mysqli_fetch_assoc($yorumlar_cek)) {
                                                ?>
                                                <div class="border-bottom pb-1 mb-1" style="font-size: 11px;">
                                                    <span class="text-warning"><?php echo str_repeat("⭐", $dyorum['yildiz']); ?></span>
                                                    <strong class="text-dark"><?php echo htmlspecialchars($dyorum['ad_soyad']); ?>:</strong>
                                                    <span class="text-secondary">"<?php echo htmlspecialchars($dyorum['yorum']); ?>"</span>
                                                </div>
                                                <?php
                                            }
                                        }
                                        ?>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <span class="fw-bold fs-4 text-success"><?php echo $row['fiyat']; ?> TL</span>
                                    <form action="index.php" method="POST">
                                        <input type="hidden" name="yemek_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" name="yemegi_kurtar" class="btn btn-custom btn-sm">Kurtar (Ayırt)</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
        </div>

        <div class="tab-pane fade" id="rezervasyon" role="tabpanel">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <h3 class="mb-4 text-center text-dark hero-title">Masanızı Ayırtın</h3>
                    <div class="card restaurant-card p-4">
                        <form action="index.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label">Kişi Sayısı</label>
                                <select class="form-select" name="kisi_sayisi" required>
                                    <option value="2" selected>2 Kişilik</option>
                                    <option value="3">3 Kişilik</option>
                                    <option value="4">4 Kişilik</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tarih</label>
                                <input type="date" class="form-control" name="rezervasyon_tarihi" required min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Saat</label>
                                <input type="time" class="form-control" name="saat" required>
                            </div>
                            <button type="submit" name="rezervasyon_yap" class="btn btn-custom w-100 py-2">Talebi Gönder</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
 
        <div class="tab-pane fade" id="durum" role="tabpanel">
            <h3 class="mb-4 text-dark hero-title">Rezervasyon Durumlarım</h3>
            <div class="card restaurant-card p-3 table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr class="table-dark"><th>Tarih</th><th>Saat</th><th>Kişi Sayısı</th><th>Durum</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        $rez_listele = mysqli_query($conn, "SELECT * FROM rezervasyonlar WHERE musteri_id = '$musteri_id' ORDER BY id DESC");
                        while ($rez = mysqli_fetch_assoc($rez_listele)) {
                            $badge_class = "bg-warning text-dark";
                            if ($rez['durum'] == 'Onaylandı') $badge_class = "bg-success";
                            if ($rez['durum'] == 'Reddedildi') $badge_class = "bg-danger";
                            echo "<tr>
                                    <td><strong>{$rez['rezervasyon_tarihi']}</strong></td>
                                    <td>{$rez['saat']}</td>
                                    <td>{$rez['kisi_sayisi']} Kişi</td>
                                    <td><span class='badge {$badge_class} p-2'>{$rez['durum']}</span></td>
                                  </tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tab-pane fade" id="siparis" role="tabpanel">
            <h3 class="mb-4 text-dark hero-title">⭐ Sipariş Geçmişim & Puanlama</h3>
            <div class="row">
                <?php
                $siparis_listele = mysqli_query($conn, "SELECT s.*, m.urun_adi 
                                                        FROM siparisler s 
                                                        JOIN mutfak_stok m ON s.yemek_id = m.id 
                                                        WHERE s.musteri_id = '$musteri_id' ORDER BY s.id DESC");
                
                if (mysqli_num_rows($siparis_listele) == 0) {
                    echo "<div class='col-12'><div class='alert alert-light text-center py-4'>Henüz bir yemek siparişiniz bulunmuyor.</div></div>";
                } else {
                    while ($sip = mysqli_fetch_assoc($siparis_listele)) {
                        ?>
                        <div class="col-md-6 mb-3">
                            <div class="card restaurant-card p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h5 class="mb-0 text-dark text-capitalize"><?php echo htmlspecialchars($sip['urun_adi']); ?></h5>
                                    
                                    <?php if ($sip['durum'] !== 'Teslim Edildi'): ?>
                                        <span class="badge bg-warning text-dark p-2">Dükkanda Teslim Bekliyor</span>
                                    <?php else: ?>
                                        <span class="badge bg-success p-2 text-white">Teslim Edildi</span>
                                    <?php endif; ?>
                                </div>
                                
                                <p class="text-muted small mb-1">Sipariş Tarihi: <strong><?php echo $sip['siparis_tarihi'] ?? '-'; ?></strong></p>
                                <p class="text-muted small mb-2">Dijital Fiş Kodu: <strong class="text-danger"><?php echo htmlspecialchars($sip['fis_kodu']); ?></strong></p>

                                <?php if ($sip['durum'] !== 'Teslim Edildi'): ?>
                                    <form action="index.php" method="POST" onsubmit="return confirm('Siparişi iptal etmek istediğinize emin misiniz?');">
                                        <input type="hidden" name="siparis_id" value="<?php echo $sip['id']; ?>">
                                        <button type="submit" name="siparis_iptal" class="btn btn-outline-danger btn-sm w-100 mb-2">🗑️ Siparişi İptal Et</button>
                                    </form>
                                <?php endif; ?>

                                <?php if ($sip['durum'] == 'Teslim Edildi' && is_null($sip['yildiz'])): ?>
                                    <form action="index.php" method="POST" class="mt-2 bg-light p-2 rounded">
                                        <input type="hidden" name="siparis_id" value="<?php echo $sip['id']; ?>">
                                        <div class="row g-2">
                                            <div class="col-4">
                                                <select class="form-select form-select-sm" name="yildiz" required>
                                                    <option value="5">⭐⭐⭐⭐⭐</option>
                                                    <option value="4">⭐⭐⭐⭐</option>
                                                    <option value="3">⭐⭐⭐</option>
                                                    <option value="2">⭐⭐</option>
                                                    <option value="1">⭐</option>
                                                </select>
                                            </div>
                                            <div class="col-8">
                                                <input type="text" class="form-control form-control-sm" name="yorum" placeholder="Yorum yapın..." required>
                                            </div>
                                            <div class="col-12">
                                                <button type="submit" name="yorum_yap" class="btn btn-dark btn-sm w-100 mt-1">Gönder</button>
                                            </div>
                                        </div>
                                    </form>
                                <?php elseif (!is_null($sip['yildiz'])): ?>
                                    <div class="bg-light p-2 rounded small mt-2 d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="text-warning"><?php echo str_repeat("⭐", $sip['yildiz']); ?></span>
                                            <p class="mb-0 text-secondary"><em>"<?php echo htmlspecialchars($sip['yorum']); ?>"</em></p>
                                        </div>
                                        <form action="index.php" method="POST">
                                            <input type="hidden" name="siparis_id" value="<?php echo $sip['id']; ?>">
                                            <button type="submit" name="yorum_sil" class="btn btn-sm btn-outline-danger border-0">🗑️ Sil</button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const bildirimKutusu = document.querySelector('.alert');
    if (bildirimKutusu) {
        setTimeout(() => { bildirimKutusu.style.opacity = "0"; setTimeout(() => bildirimKutusu.remove(), 500); }, 3000);
    }
});
</script>
</body>
</html>