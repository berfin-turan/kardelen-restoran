<?php
// 🔄 Oturumu her şeyden önce en üstte başlatıyoruz
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Veritabanı bağlantısını dahil ediyoruz
include 'baglan.php';

// Güvenlik Duvarı: Giriş yapmamışsa VEYA rolü personel değilse login.php'ye fırlat
if (!isset($_SESSION['kullanici_id']) || $_SESSION['rol'] !== 'personel') {
    header("Location: login.php");
    exit;
}

// 🔄 URL'den hangi sayfada olduğumuzu anlayan yönlendirme değişkeni
$aktif_sayfa = isset($_GET['sayfa']) ? $_GET['sayfa'] : 'mutfak';
$bugun = date('Y-m-d');

// Post-Redirect-Get için session tabanlı bildirim mekanizması kuruyoruz
$mesaj = "";
if (isset($_SESSION['erp_mesaj'])) {
    $mesaj = $_SESSION['erp_mesaj'];
    unset($_SESSION['erp_mesaj']); // Mesajı bir kez gösterdikten sonra siliyoruz
}

// ==========================================
// 🛠️ AKSİYON 1: MUTFAĞA YENİ HAMMADDE EKLEME (NORMAL)
// ==========================================
if (isset($_POST['hammadde_ekle'])) {
    $urun_adi   = mysqli_real_escape_string($conn, $_POST['urun_adi']);
    $adet       = mysqli_real_escape_string($conn, $_POST['adet']);
    $birim      = mysqli_real_escape_string($conn, $_POST['birim']);
    $fiyat      = mysqli_real_escape_string($conn, $_POST['fiyat']);
    $skt_tarihi = mysqli_real_escape_string($conn, $_POST['skt_tarihi']);

    $ekle_query = "INSERT INTO mutfak_stok (urun_adi, adet, birim, fiyat, skt_tarihi, tur) 
                   VALUES ('$urun_adi', '$adet', '$birim', '$fiyat', '$skt_tarihi', 'normal')";
    
    if (mysqli_query($conn, $ekle_query)) {
        $_SESSION['erp_mesaj'] = "<div class='alert alert-success alert-dismissible fade show'>✔️ Hammadde mutfak deposuna başarıyla eklendi. (Müşteriye kapalı)</div>";
    } else {
        $_SESSION['erp_mesaj'] = "<div class='alert alert-danger'>Hata: " . mysqli_error($conn) . "</div>";
    }
    header("Location: personel_panel.php?sayfa=mutfak");
    exit;
}

// ==========================================
// 🛠️ AKSİYON 1.5: RESTORAN MENÜSÜNE YEMEK EKLEME (YENİ)
// ==========================================
if (isset($_POST['ana_menu_ekle'])) {
    $urun_adi   = mysqli_real_escape_string($conn, $_POST['urun_adi']);
    $fiyat      = mysqli_real_escape_string($conn, $_POST['fiyat']);
    $tur        = mysqli_real_escape_string($conn, $_POST['tur']); // 'menu' olarak gelir
    
    // Menü yemekleri genellikle anlık sipariş üzerine hazırlandığı için sembolik yüksek adet giriyoruz ve SKT dert olmuyor.
    $adet = 999;
    $skt_tarihi = date('Y-m-d', strtotime('+1 year'));

    $ekle_query = "INSERT INTO mutfak_stok (urun_adi, adet, birim, fiyat, skt_tarihi, tur) 
                   VALUES ('$urun_adi', '$adet', 'Porsiyon', '$fiyat', '$skt_tarihi', '$tur')";
    
    if (mysqli_query($conn, $ekle_query)) {
        $_SESSION['erp_mesaj'] = "<div class='alert alert-primary alert-dismissible fade show'>🍽️ Yemek doğrudan menü vitrinine eklendi! Müşteriler artık sipariş verebilir.</div>";
    } else {
        $_SESSION['erp_mesaj'] = "<div class='alert alert-danger'>Hata: " . mysqli_error($conn) . "</div>";
    }
    header("Location: personel_panel.php?sayfa=mutfak");
    exit;
}

// ==========================================
// 🛠️ AKSİYON 2: DOĞRUDAN GÜN SONU MENÜSÜ EKLEME (VİTRİN)
// ==========================================
if (isset($_POST['direkt_menu_ekle'])) {
    $urun_adi   = mysqli_real_escape_string($conn, $_POST['urun_adi']);
    $adet       = mysqli_real_escape_string($conn, $_POST['adet']);
    $birim      = mysqli_real_escape_string($conn, $_POST['birim']);
    $fiyat      = mysqli_real_escape_string($conn, $_POST['fiyat']);
    $skt_tarihi = mysqli_real_escape_string($conn, $_POST['skt_tarihi']);

    $ekle_query = "INSERT INTO mutfak_stok (urun_adi, adet, birim, fiyat, skt_tarihi, tur) 
                   VALUES ('$urun_adi', '$adet', '$birim', '$fiyat', '$skt_tarihi', 'gun_sonu_menusu')";
    
    if (mysqli_query($conn, $ekle_query)) {
        $_SESSION['erp_mesaj'] = "<div class='alert alert-warning alert-dismissible fade show'>🚀 Gün Sonu yemeği doğrudan satış vitrinine eklendi! (Müşteriye açık)</div>";
    } else {
        $_SESSION['erp_mesaj'] = "<div class='alert alert-danger'>Hata: " . mysqli_error($conn) . "</div>";
    }
    header("Location: personel_panel.php?sayfa=mutfak");
    exit;
}

// ==========================================
// 🛠️ AKSİYON 3: HAMMADDEYİ GÜN SONU MENÜSÜNE ÇEVİRME (DÖNÜŞTÜRME)
// ==========================================
if (isset($_POST['gun_sonu_yap'])) {
    $stok_id    = mysqli_real_escape_string($conn, $_POST['stok_id']);
    $yeni_isim  = mysqli_real_escape_string($conn, $_POST['yeni_isim']);
    $yeni_fiyat = mysqli_real_escape_string($conn, $_POST['yeni_fiyat']);
    $yeni_adet  = mysqli_real_escape_string($conn, $_POST['yeni_adet']);

    $dönüstür_query = "UPDATE mutfak_stok SET 
                       urun_adi = '$yeni_isim', 
                       fiyat = '$yeni_fiyat', 
                       adet = '$yeni_adet', 
                       tur = 'gun_sonu_menusu' 
                       WHERE id = '$stok_id'";
    
    if (mysqli_query($conn, $dönüstür_query)) {
        $_SESSION['erp_mesaj'] = "<div class='alert alert-warning alert-dismissible fade show'>🚀 Depodaki hammadde başarıyla Gün Sonu Menüsüne dönüştürüldü!</div>";
    } else {
        $_SESSION['erp_mesaj'] = "<div class='alert alert-danger'>Hata: " . mysqli_error($conn) . "</div>";
    }
    header("Location: personel_panel.php?sayfa=mutfak");
    exit;
}

// ==========================================
// 🛠️ AKSİYON 4: STOKTAN MALZEME SİLME (DELETE)
// ==========================================
if (isset($_POST['stok_sil'])) {
    $sil_id = mysqli_real_escape_string($conn, $_POST['sil_id']);
    
    $sil_query = "DELETE FROM mutfak_stok WHERE id = '$sil_id'";
    if (mysqli_query($conn, $sil_query)) {
        $_SESSION['erp_mesaj'] = "<div class='alert alert-danger alert-dismissible fade show'>🗑️ Ürün mutfak envanterinden tamamen silindi.</div>";
    } else {
        $_SESSION['erp_mesaj'] = "<div class='alert alert-danger'>Silme işlemi hatası: " . mysqli_error($conn) . "</div>";
    }
    header("Location: personel_panel.php?sayfa=mutfak");
    exit;
}

// ==========================================
// 📅 AKSİYON 5: REZERVASYON DURUMU GÜNCELLEME
// ==========================================
if (isset($_POST['rezervasyon_guncelle'])) {
    $rez_id = mysqli_real_escape_string($conn, $_POST['rez_id']);
    $yeni_durum = mysqli_real_escape_string($conn, $_POST['yeni_durum']); 

    $guncelle_query = "UPDATE rezervasyonlar SET durum = '$yeni_durum' WHERE id = '$rez_id'";
    if (mysqli_query($conn, $guncelle_query)) {
        if ($yeni_durum == 'Onaylandı') {
            $_SESSION['erp_mesaj'] = "<div class='alert alert-success alert-dismissible fade show'>✔️ Rezervasyon başarıyla onaylandı ve müşteriye bildirildi!</div>";
        } else {
            $_SESSION['erp_mesaj'] = "<div class='alert alert-danger alert-dismissible fade show'>❌ Rezervasyon reddedildi ve müşteriye bildirildi.</div>";
        }
    }
    header("Location: personel_panel.php?sayfa=rezervasyon");
    exit;
}

// ==========================================
// 🎟️ AKSİYON 6: SİPARİŞİ TESLİM ETME 
// ==========================================
if (isset($_POST['siparis_teslim_et'])) {
    $siparis_id = mysqli_real_escape_string($conn, $_POST['siparis_id']);
    $yemek_id = mysqli_real_escape_string($conn, $_POST['yemek_id']);

    $siparis_query = "UPDATE siparisler SET durum = 'Teslim Edildi' WHERE id = '$siparis_id'";
    
    if (mysqli_query($conn, $siparis_query)) {
        
        // 🧠 HATA KORUMASI: Menü ürünleri sınırsız stoğa sahiptir, sadece gün sonu ürünlerinin stoğunu düşür.
        $stok_kontrol = mysqli_query($conn, "SELECT tur, adet FROM mutfak_stok WHERE id = '$yemek_id'");
        $stok_veri = mysqli_fetch_assoc($stok_kontrol);
        
        if ($stok_veri && $stok_veri['tur'] == 'gun_sonu_menusu') {
            $stok_dus_query = "UPDATE mutfak_stok SET adet = adet - 1 WHERE id = '$yemek_id'";
            mysqli_query($conn, $stok_dus_query);

            if ($stok_veri['adet'] <= 1) {
                mysqli_query($conn, "UPDATE mutfak_stok SET adet = 0 WHERE id = '$yemek_id'");
            }
        }
 
        $_SESSION['erp_mesaj'] = "<div class='alert alert-success alert-dismissible fade show'>✔️ Sipariş başarıyla teslim edildi ve adisyon fişi kesildi!</div>";
    } else {
        $_SESSION['erp_mesaj'] = "<div class='alert alert-danger'>Hata: " . mysqli_error($conn) . "</div>";
    }
    header("Location: personel_panel.php?sayfa=siparisler");
    exit;
}

// ==========================================
// 👷 AKSİYON 7: VARDİYAYA ÇALIŞAN EKLEME
// ==========================================
if (isset($_POST['vardiya_ekle'])) {
    $calisan_id    = mysqli_real_escape_string($conn, $_POST['calisan_id']);
    $vardiya_tarihi = mysqli_real_escape_string($conn, $_POST['vardiya_tarihi']);
    $saat_araligi  = mysqli_real_escape_string($conn, $_POST['saat_araligi']);
    $gorev_alani   = mysqli_real_escape_string($conn, $_POST['gorev_alani']);

    $ekle = "INSERT INTO vardiyalar (calisan_id, vardiya_tarihi, saat_araligi, gorev_alani) 
             VALUES ('$calisan_id', '$vardiya_tarihi', '$saat_araligi', '$gorev_alani')";
    
    if (mysqli_query($conn, $ekle)) {
        $_SESSION['erp_mesaj'] = "<div class='alert alert-success alert-dismissible fade show'>✔️ Çalışan vardiyaya başarıyla atandı!</div>";
    } else {
        $_SESSION['erp_mesaj'] = "<div class='alert alert-danger'>Hata: " . mysqli_error($conn) . "</div>";
    }
    header("Location: personel_panel.php?sayfa=vardiya");
    exit;
}

// ==========================================
// 🗑️ AKSİYON 8: VARDİYADAN ÇALIŞAN SİLME
// ==========================================
if (isset($_POST['vardiya_sil'])) {
    $vardiya_id = mysqli_real_escape_string($conn, $_POST['vardiya_id']);
    
    if (mysqli_query($conn, "DELETE FROM vardiyalar WHERE id = '$vardiya_id'")) {
        $_SESSION['erp_mesaj'] = "<div class='alert alert-warning alert-dismissible fade show'>🗑️ Çalışan vardiyadan kaldırıldı.</div>";
    }
    header("Location: personel_panel.php?sayfa=vardiya");
    exit;
}

// ==========================================
// 👤 AKSİYON 9: YENİ PERSONEL EKLEME
// ==========================================
if (isset($_POST['personel_ekle'])) {
    $ad_soyad = mysqli_real_escape_string($conn, $_POST['ad_soyad']);
    $eposta   = mysqli_real_escape_string($conn, $_POST['eposta']);
    $telefon  = mysqli_real_escape_string($conn, $_POST['telefon']);
    $sifre    = $_POST['sifre'];
    $sifre_hash = password_hash($sifre, PASSWORD_DEFAULT);

    // E-posta tekrarı kontrolü
    $kontrol = mysqli_query($conn, "SELECT id FROM kullanicilar WHERE eposta = '$eposta'");
    if (mysqli_num_rows($kontrol) > 0) {
        $_SESSION['erp_mesaj'] = "<div class='alert alert-danger'>Bu e-posta adresiyle zaten bir kayıt var!</div>";
    } else {
        $ekle = "INSERT INTO kullanicilar (ad_soyad, eposta, sifre_hash, telefon, rol) 
                 VALUES ('$ad_soyad', '$eposta', '$sifre_hash', '$telefon', 'personel')";
        if (mysqli_query($conn, $ekle)) {
            $_SESSION['erp_mesaj'] = "<div class='alert alert-success alert-dismissible fade show'>✔️ Yeni personel başarıyla eklendi! Artık giriş yapabilir.</div>";
        } else {
            $_SESSION['erp_mesaj'] = "<div class='alert alert-danger'>Hata: " . mysqli_error($conn) . "</div>";
        }
    }
    header("Location: personel_panel.php?sayfa=personel");
    exit;
}

// ==========================================
// 🗑️ AKSİYON 10: PERSONEL SİLME
// ==========================================
if (isset($_POST['personel_sil'])) {
    $sil_id = mysqli_real_escape_string($conn, $_POST['sil_id']);
    
    // Kendini silemesin
    if ($sil_id == $_SESSION['kullanici_id']) {
        $_SESSION['erp_mesaj'] = "<div class='alert alert-danger'>⚠️ Kendi hesabınızı silemezsiniz!</div>";
    } else {
        mysqli_query($conn, "DELETE FROM vardiyalar WHERE calisan_id = '$sil_id'");
        mysqli_query($conn, "DELETE FROM siparisler WHERE musteri_id = '$sil_id'");
        mysqli_query($conn, "DELETE FROM rezervasyonlar WHERE musteri_id = '$sil_id'");
        mysqli_query($conn, "DELETE FROM kullanicilar WHERE id = '$sil_id' AND rol = 'personel'");
        $_SESSION['erp_mesaj'] = "<div class='alert alert-warning alert-dismissible fade show'>🗑️ Personel hesabı silindi.</div>";
    }
    header("Location: personel_panel.php?sayfa=personel");
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kardelen Restoran - Personel Paneli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f4f6f9; }
        .sidebar { height: 100vh; background-color: #212529; color: white; position: fixed; width: 260px; padding-top: 20px; }
        .sidebar a { color: #cfd4da; text-decoration: none; padding: 12px 20px; display: block; font-weight: 500; }
        .sidebar a:hover, .sidebar a.active { background-color: #343a40; color: white; border-left: 4px solid #0d6efd; }
        .main-content { margin-left: 260px; padding: 30px; }
        .card-custom { border: none; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); background-color: white; }
        .hucre-danger { background-color: #f8d7da !important; color: #842029 !important; }
        .hucre-warning { background-color: #fff3cd !important; color: #664d03 !important; }
        .form-tabs .nav-link { font-size: 13px; color: #6c757d; font-weight: 600; padding: 8px 12px; border: 1px solid #dee2e6; }
        .form-tabs .nav-link.active { background-color: #0d6efd !important; color: white !important; border-color: #0d6efd; }
    </style>
</head>
<body>

<div class="sidebar">
    <h4 class="text-center mb-4 text-primary fw-bold" style="letter-spacing: 1px;">KARDELEN ERP</h4>
    <p class="text-center text-muted small mb-4">🧑‍🍳 Personel: <strong><?php echo htmlspecialchars($_SESSION['ad_soyad'] ?? 'Personel'); ?></strong></p>
    <hr class="bg-secondary">
    
    <a href="personel_panel.php?sayfa=mutfak" class="<?php echo $aktif_sayfa == 'mutfak' ? 'active' : ''; ?>">🧑‍🍳 Mutfak & Envanter</a>
    <a href="personel_panel.php?sayfa=rezervasyon" class="<?php echo $aktif_sayfa == 'rezervasyon' ? 'active' : ''; ?>">📅 Rezervasyon Onayları</a>
    <a href="personel_panel.php?sayfa=siparisler" class="<?php echo $aktif_sayfa == 'siparisler' ? 'active' : ''; ?>">🎟️ Siparişler & Fiş</a>
    <a href="personel_panel.php?sayfa=vardiya" class="<?php echo $aktif_sayfa == 'vardiya' ? 'active' : ''; ?>">📋 Çalışan Vardiyaları</a>
    <a href="personel_panel.php?sayfa=personel" class="<?php echo $aktif_sayfa == 'personel' ? 'active' : ''; ?>">👤 Personel Yönetimi</a>
    
    <hr class="bg-secondary mt-5">
    <a href="cikis.php" class="text-danger">❌ Güvenli Çıkış</a>
</div>

<div class="main-content">
    <div class="container-fluid">
        
        <?php echo $mesaj; ?>

        <?php if ($aktif_sayfa == 'mutfak'): ?>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-dark fw-bold">Mutfak Stok & Envanter Yönetimi</h2>
            <span class="badge bg-dark p-2 fs-6">Sistem Tarihi: <?php echo $bugun; ?></span>
        </div>

        <div class="row g-4">
            <div class="col-xl-4">
                <div class="card card-custom p-4">
                    <ul class="nav nav-pills form-tabs mb-4 justify-content-center" id="formTab" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active me-2" id="hammadde-tab" data-bs-toggle="pill" data-bs-target="#form-hammadde" type="button">📦 Hammadde Ekle</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link me-2" id="menu-tab" data-bs-toggle="pill" data-bs-target="#form-menu" type="button">🔥 Gün Sonu Ekle</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="ana-menu-tab" data-bs-toggle="pill" data-bs-target="#form-ana-menu" type="button">🍽️ Restoran Menüsü Ekle</button>
                        </li>
                    </ul>

                    <div class="tab-content" id="formTabContent">
                        <div class="tab-pane fade show active" id="form-hammadde" role="tabpanel">
                            <h6 class="fw-bold mb-3 text-secondary">📦 Depoya Yeni Malzeme Girişi</h6>
                            <form action="personel_panel.php?sayfa=mutfak" method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Malzeme / Ürün Adı</label>
                                    <input type="text" class="form-control" name="urun_adi" placeholder="Örn: Süt" required>
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col-7">
                                        <label class="form-label">Miktar</label>
                                        <input type="number" class="form-control" name="adet" min="1" required>
                                    </div>
                                    <div class="col-5">
                                        <label class="form-label">Birim</label>
                                        <select class="form-select" name="birim">
                                            <option value="Kg">Kg</option>
                                            <option value="Litre">Litre</option>
                                            <option value="Adet">Adet</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Alış Maliyeti (TL)</label>
                                    <input type="number" class="form-control" name="fiyat" min="0" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Son Kullanma Tarihi</label>
                                    <input type="date" class="form-control" name="skt_tarihi" required>
                                </div>
                                <button type="submit" name="hammadde_ekle" class="btn btn-primary w-100 py-2">Depoya Kaydet</button>
                            </form>
                        </div>

                        <div class="tab-pane fade" id="form-menu" role="tabpanel">
                            <h6 class="fw-bold mb-3 text-danger">🔥 Doğrudan Satış Vitrinine Yemek Ekle</h6>
                            <form action="personel_panel.php?sayfa=mutfak" method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Satılacak Yemek Adı</label>
                                    <input type="text" class="form-control" name="urun_adi" placeholder="Örn: Günün Çorbası" required>
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col-7">
                                        <label class="form-label">Porsiyon Adeti</label>
                                        <input type="number" class="form-control" name="adet" min="1" required>
                                    </div>
                                    <div class="col-5">
                                        <label class="form-label">Birim</label>
                                        <select class="form-select" name="birim">
                                            <option value="Porsiyon">Porsiyon</option>
                                            <option value="Adet">Adet</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Müşteri Satış Fiyatı (TL)</label>
                                    <input type="number" class="form-control" name="fiyat" min="0" required>
                                </div>
                                <input type="hidden" name="skt_tarihi" value="<?php echo date('Y-m-d'); ?>">

                                <button type="submit" name="direkt_menu_ekle" class="btn btn-danger w-100 py-2">Gün Sonuna Koy</button>
                            </form>
                        </div>

                        <div class="tab-pane fade" id="form-ana-menu" role="tabpanel">
                            <h6 class="fw-bold mb-3 text-primary">🍽️ Restoran Menüsüne Yemek Ekle</h6>
                            <form action="personel_panel.php?sayfa=mutfak" method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Yemek Adı</label>
                                    <input type="text" class="form-control" name="urun_adi" placeholder="Örn: Izgara Köfte" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Satış Fiyatı (TL)</label>
                                    <input type="number" class="form-control" name="fiyat" min="0" required>
                                </div>
                                <input type="hidden" name="tur" value="menu">
                                
                                <button type="submit" name="ana_menu_ekle" class="btn btn-dark w-100 py-2">Menüye Kaydet</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="card card-custom p-4">
                    <div class="row g-2 mb-4 bg-light p-3 rounded border">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text bg-white text-muted">🔍</span>
                                <input type="text" id="aramaKutusu" class="form-control" placeholder="Ürün veya yemek adı ara...">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <select id="filtreSecici" class="form-select">
                                <option value="hepsi">📊 Tüm Envanteri Göster</option>
                                <option value="restoran-menusu">🍽️ Restoran Menüsü</option> 
                                <option value="skt-gecti">⚠️ Sadece SKT'si Geçenler</option>
                                <option value="son-3-gun">⏳ Sadece Son 3 Günü Kalanlar</option>
                                <option value="taze">✔️ Sadece Taze / Güvenli Ürünler</option>
                                <option value="satis-vitrini">🚀 Sadece Müşteride Satışta Olanlar</option>
                            </select>
                        </div>
                    </div>

                    <h5 class="fw-bold mb-3 text-secondary">📋 Güncel Mutfak Depo Durumu</h5>
                    <div class="table-responsive">
                        <table class="table align-middle table-hover border text-nowrap" id="depoTablosu">
                            <thead>
                                <tr class="table-dark">
                                    <th>Ürün Adı</th>
                                    <th>Stok</th>
                                    <th>Fiyat</th>
                                    <th>SKT Tarihi</th>
                                    <th>SKT Durumu</th>
                                    <th class="text-center">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stok_cek = mysqli_query($conn, "SELECT * FROM mutfak_stok ORDER BY skt_tarihi ASC");
                                if (mysqli_num_rows($stok_cek) == 0) {
                                    echo "<tr class='tablo-satir'><td colspan='6' class='text-center text-muted py-4'>Envanterde ürün bulunmuyor.</td></tr>";
                                } else {
                                    while ($row = mysqli_fetch_assoc($stok_cek)) {
                                        $td_style_class = ""; $skt_secilen = $row['skt_tarihi'];
                                        $urun_skt_timestamp = strtotime($skt_secilen); $bugun_timestamp = strtotime($bugun);
                                        $gun_farki = ($urun_skt_timestamp - $bugun_timestamp) / 86400;
                                        $skt_durumu_yazisi = ""; $data_durum = "taze";

                                        if ($urun_skt_timestamp < $bugun_timestamp && $row['tur'] != 'menu') {
                                            $td_style_class = "hucre-danger"; 
                                            $skt_durumu_yazisi = "<span class='badge bg-danger'>⚠️ SKT GEÇTİ!</span>"; 
                                            $data_durum = "skt-gecti";
                                        } elseif ($gun_farki >= 0 && $gun_farki <= 3 && $row['tur'] != 'menu') {
                                            $td_style_class = "hucre-warning"; 
                                            $skt_durumu_yazisi = "<span class='badge bg-warning text-dark'>⏳ Son 3 Gün!</span>"; 
                                            $data_durum = "son-3-gun";
                                        } else {
                                            if($row['tur'] == 'gun_sonu_menusu') {
                                                $skt_durumu_yazisi = "<span class='badge bg-info text-dark'>🚀 Gün Sonu</span>"; $data_durum = "gun-sonu";
                                            } elseif($row['tur'] == 'menu') {
                                                $skt_durumu_yazisi = "<span class='badge bg-primary text-white'>🍽️ Restoran Menüsü</span>"; $data_durum = "restoran-menusu";
                                            } else {
                                                $skt_durumu_yazisi = "<span class='badge bg-success'>✔️ Taze / Güvenli</span>"; $data_durum = "taze";
                                            }
                                        }
                                        ?>
                                        <tr class="tablo-satir" data-durum="<?php echo $data_durum; ?>">
                                            <td class="<?php echo $td_style_class; ?> urun-adi-hucre"><strong><?php echo htmlspecialchars($row['urun_adi']); ?></strong></td>
                                            <td class="<?php echo $td_style_class; ?>">
                                                <?php echo ($row['tur'] == 'menu') ? "Limitsiz" : $row['adet'] . " " . $row['birim']; ?>
                                            </td>
                                            <td class="<?php echo $td_style_class; ?>"><?php echo $row['fiyat']; ?> TL</td>
                                            <td class="<?php echo $td_style_class; ?>"><?php echo ($row['tur'] == 'menu') ? "-" : $row['skt_tarihi']; ?></td>
                                            <td class="<?php echo $td_style_class; ?>"><?php echo $skt_durumu_yazisi; ?></td>
                                            <td class="<?php echo $td_style_class; ?> text-center">
                                                <div class="d-flex justify-content-center gap-1">
                                                    <?php if($row['tur'] == 'normal'): ?>
                                                        <button class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#convertModal<?php echo $row['id']; ?>">🎯 Menü Yap</button>
                                                        <span class="badge bg-secondary d-flex align-items-center px-2">📦 Mutfakta</span>
                                                    <?php else: ?>
                                                        <button class="btn btn-sm btn-light border text-dark fw-bold" disabled>🚀 Satışta</button>
                                                    <?php endif; ?>
                                                    <form action="personel_panel.php?sayfa=mutfak" method="POST" onsubmit="return confirm('Bu ürünü envanterden silmek istediğinize emin misiniz?');">
                                                        <input type="hidden" name="sil_id" value="<?php echo $row['id']; ?>">
                                                        <button type="submit" name="stok_sil" class="btn btn-sm btn-danger">Sil</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>

                                        <div class="modal fade" id="convertModal<?php echo $row['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content text-dark text-start">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title fw-bold">🎯 Gün Sonu Menüsüne Dönüştür</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form action="personel_panel.php?sayfa=mutfak" method="POST">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="stok_id" value="<?php echo $row['id']; ?>">
                                                            <div class="mb-3">
                                                                <label class="form-label">Satışa Çıkacak Yemek Adı</label>
                                                                <input type="text" class="form-control" name="yeni_isim" value="<?php echo htmlspecialchars($row['urun_adi']); ?> Yemeği" required>
                                                            </div>
                                                            <div class="row g-2 mb-3">
                                                                <div class="col-6">
                                                                    <label class="form-label">Porsiyon Adeti</label>
                                                                    <input type="number" class="form-control" name="yeni_adet" value="<?php echo $row['adet']; ?>" required>
                                                                </div>
                                                                <div class="col-6">
                                                                    <label class="form-label">İndirimli Fiyat (TL)</label>
                                                                    <input type="number" class="form-control" name="yeni_fiyat" required>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                                                            <button type="submit" name="gun_sonu_yap" class="btn bg-danger text-white">Menüyü Canlıya Al</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <?php
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <?php elseif ($aktif_sayfa == 'rezervasyon'): ?>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-dark fw-bold">📅 Rezervasyon Onaylama Masası</h2>
            <span class="badge bg-primary p-2 fs-6">Aktif Bekleyen Talepler</span>
        </div>

        <div class="card card-custom p-4 table-responsive">
            <h5 class="fw-bold mb-3 text-secondary">Müşterilerden Gelen Rezervasyon İstekleri</h5>
            <table class="table table-striped align-middle border text-nowrap">
                <thead>
                    <tr class="table-dark">
                        <th>Müşteri Adı</th>
                        <th>Telefon</th>
                        <th>Kişi Sayısı</th>
                        <th>Tarih</th>
                        <th>Saat</th>
                        <th>Mevcut Durum</th>
                        <th class="text-center">Karar İşlemi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // 🧠 LEFT JOIN ve COALESCE: Kullanıcı hesabını silse bile sistem çökmez, 'Eski Müşteri' yazar
                    $rez_listele = mysqli_query($conn, "SELECT r.*, COALESCE(k.ad_soyad, 'Eski Müşteri') as ad_soyad, COALESCE(k.telefon, '-') as telefon 
                                                        FROM rezervasyonlar r 
                                                        LEFT JOIN kullanicilar k ON r.musteri_id = k.id 
                                                        ORDER BY r.id DESC");
                    
                    if (mysqli_num_rows($rez_listele) == 0) {
                        echo "<tr><td colspan='7' class='text-center text-muted py-4'>Şu an sistemde hiç rezervasyon talebi bulunmuyor.</td></tr>";
                    } else {
                        while ($rez = mysqli_fetch_assoc($rez_listele)) {
                            $badge_class = "bg-warning text-dark";
                            if ($rez['durum'] == 'Onaylandı') $badge_class = "bg-success";
                            if ($rez['durum'] == 'Reddedildi') $badge_class = "bg-danger";
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($rez['ad_soyad']); ?></strong></td>
                                <td><?php echo htmlspecialchars($rez['telefon']); ?></td>
                                <td><span class="badge bg-dark"><?php echo $rez['kisi_sayisi']; ?> Kişi</span></td>
                                <td><?php echo $rez['rezervasyon_tarihi']; ?></td>
                                <td><?php echo $rez['saat']; ?></td>
                                <td><span class="badge <?php echo $badge_class; ?>"><?php echo $rez['durum']; ?></span></td>
                                <td class="text-center">
                                    <?php if ($rez['durum'] == 'Beklemede'): ?>
                                        <div class="d-flex justify-content-center gap-1">
                                            <form action="personel_panel.php?sayfa=rezervasyon" method="POST">
                                                <input type="hidden" name="rez_id" value="<?php echo $rez['id']; ?>">
                                                <input type="hidden" name="yeni_durum" value="Onaylandı">
                                                <button type="submit" name="rezervasyon_guncelle" class="btn btn-sm btn-success">Onayla</button>
                                            </form>
                                            <form action="personel_panel.php?sayfa=rezervasyon" method="POST">
                                                <input type="hidden" name="rez_id" value="<?php echo $rez['id']; ?>">
                                                <input type="hidden" name="yeni_durum" value="Reddedildi">
                                                <button type="submit" name="rezervasyon_guncelle" class="btn btn-sm btn-danger">Reddet</button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small">Karar Verildi</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <?php elseif ($aktif_sayfa == 'siparisler'): ?>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-dark fw-bold">🎟️ Tüm Siparişler ve Adisyon Masası</h2>
            <span class="badge bg-danger p-2 fs-6">Aktif Bekleyen Siparişler</span>
        </div>

        <div class="card card-custom p-4 table-responsive">
            <h5 class="fw-bold mb-3 text-secondary">Müşteri Sipariş Listesi</h5>
            <table class="table table-striped align-middle border text-nowrap">
                <thead>
                    <tr class="table-dark">
                        <th>Sipariş ID</th>
                        <th>Fiş Kodu</th>
                        <th>Müşteri Adı</th>
                        <th>Telefon</th>
                        <th>Alınan Yemek</th>
                        <th>Birim Fiyat</th>
                        <th>Sipariş Tarihi</th>
                        <th>Durum</th>
                        <th class="text-center">Teslimat Kararı</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // 🧠 LEFT JOIN ile silinen müşteri koruması ve siparis_tarihi çekme
                    $sip_listele = mysqli_query($conn, "SELECT s.*, COALESCE(k.ad_soyad, 'Eski Müşteri') as ad_soyad, COALESCE(k.telefon, '-') as telefon, m.urun_adi, m.fiyat 
                                                        FROM siparisler s
                                                        LEFT JOIN kullanicilar k ON s.musteri_id = k.id
                                                        JOIN mutfak_stok m ON s.yemek_id = m.id
                                                        ORDER BY s.id DESC");
                    
                    if (mysqli_num_rows($sip_listele) == 0) {
                        echo "<tr><td colspan='9' class='text-center text-muted py-4'>Şu an teslim edilmeyi bekleyen hiçbir sipariş yok.</td></tr>";
                    } else {
                        while ($sip = mysqli_fetch_assoc($sip_listele)) {
                            $sip_badge = "bg-warning text-dark";
                            if ($sip['durum'] == 'Teslim Edildi') $sip_badge = "bg-success";
                            if ($sip['durum'] == 'İptal Edildi') $sip_badge = "bg-danger";
                            ?>
                            <tr>
                                <td>#<?php echo $sip['id']; ?></td>
                                <td><code class="text-danger fw-bold"><?php echo htmlspecialchars($sip['fis_kodu']); ?></code></td>
                                <td><strong><?php echo htmlspecialchars($sip['ad_soyad']); ?></strong></td>
                                <td><?php echo htmlspecialchars($sip['telefon']); ?></td>
                                <td><span class="badge bg-dark"><?php echo htmlspecialchars($sip['urun_adi']); ?></span></td>
                                <td class="fw-bold text-primary"><?php echo $sip['fiyat']; ?> TL</td>
                                <td><?php echo $sip['siparis_tarihi'] ?? '-'; ?></td>
                                <td><span class="badge <?php echo $sip_badge; ?>"><?php echo $sip['durum']; ?></span></td>
                                <td class="text-center">
                                    <?php if ($sip['durum'] !== 'Teslim Edildi'): ?>
                                        <form action="personel_panel.php?sayfa=siparisler" method="POST" onsubmit="return confirm('#<?php echo $sip['id']; ?> nolu siparişi müşteriye teslim etmek istiyor musunuz?');">
                                            <input type="hidden" name="siparis_id" value="<?php echo $sip['id']; ?>">
                                            <input type="hidden" name="yemek_id" value="<?php echo $sip['yemek_id']; ?>">
                                            <button type="submit" name="siparis_teslim_et" class="btn btn-sm btn-success fw-bold px-3">📦 Teslim Et</button>
                                        </form>
                                    <?php else: ?>
                                        <div class="d-flex justify-content-center gap-1">
                                            <span class="badge bg-light text-success border d-flex align-items-center px-2">✔️ Teslim Edildi</span>
                                            <button class="btn btn-sm btn-dark fw-bold" data-bs-toggle="modal" data-bs-target="#fisModal<?php echo $sip['id']; ?>">🖨️ Fiş Kes</button>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <div class="modal fade" id="fisModal<?php echo $sip['id']; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-sm modal-dialog-centered">
                                    <div class="modal-content" style="border: 2px dashed #ccc; background-color: #fff;">
                                        <div class="modal-body text-dark font-monospace p-4" id="yazdirilacakAlan<?php echo $sip['id']; ?>" style="font-size: 13px;">
                                            <div class="text-center mb-3">
                                                <h5 class="fw-bold mb-0">KARDELEN RESTORAN</h5>
                                                <small class="text-muted">Müşteri Adisyon Fişi</small>
                                                <div class="my-2">----------------------------------</div>
                                            </div>
                                            <div class="mb-1"><strong>Tarih:</strong> <?php echo $sip['siparis_tarihi'] ?? date('Y-m-d H:i'); ?></div>
                                            <div class="mb-1"><strong>Sipariş No:</strong> #<?php echo $sip['id']; ?></div>
                                            <div class="mb-3"><strong>Fiş Kodu:</strong> <span class="text-danger fw-bold"><?php echo htmlspecialchars($sip['fis_kodu']); ?></span></div>
                                            <div class="my-2">----------------------------------</div>
                                            <div class="mb-1"><strong>Müşteri:</strong> <?php echo htmlspecialchars($sip['ad_soyad']); ?></div>
                                            <div class="mb-3"><strong>Telefon:</strong> <?php echo htmlspecialchars($sip['telefon']); ?></div>
                                            <div class="my-2">----------------------------------</div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span><?php echo htmlspecialchars($sip['urun_adi']); ?></span>
                                                <span>1 Adet</span>
                                            </div>
                                            <div class="my-2">----------------------------------</div>
                                            <div class="d-flex justify-content-between fw-bold fs-6 mt-2">
                                                <span>TOPLAM TUTAR:</span>
                                                <span><?php echo $sip['fiyat']; ?> TL</span>
                                            </div>
                                            <div class="text-center mt-4">
                                                <small class="fw-bold">Afiyet Olsun! - KARDELEN ERP</small>
                                            </div>
                                        </div>
                                        <div class="modal-footer justify-content-between bg-light border-top-0">
                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Kapat</button>
                                            <button type="button" class="btn btn-primary btn-sm fw-bold" onclick="fisYazdir(<?php echo $sip['id']; ?>)">🖨️ Fişi Yazdır</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <?php elseif ($aktif_sayfa == 'vardiya'): ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-dark fw-bold">📋 Çalışan Vardiya Yönetimi</h2>
            <span class="badge bg-info text-dark p-2 fs-6">Tarih: <?php echo $bugun; ?></span>
        </div>

        <div class="row g-4">
            <div class="col-xl-4">
                <div class="card card-custom p-4">
                    <h5 class="fw-bold mb-3 text-secondary">👷 Vardiyaya Çalışan Ata</h5>
                    <form action="personel_panel.php?sayfa=vardiya" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Çalışan Seç</label>
                            <select class="form-select" name="calisan_id" required>
                                <option value="" disabled selected>-- Çalışan Seçin --</option>
                                <?php
                                $personeller = mysqli_query($conn, "SELECT id, ad_soyad FROM kullanicilar WHERE rol = 'personel' ORDER BY ad_soyad ASC");
                                while ($p = mysqli_fetch_assoc($personeller)) {
                                    echo "<option value='{$p['id']}'>{$p['ad_soyad']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Vardiya Tarihi</label>
                            <input type="date" class="form-control" name="vardiya_tarihi" required value="<?php echo $bugun; ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Saat Aralığı</label>
                            <select class="form-select" name="saat_araligi" required>
                                <option value="08:00-16:00">☀️ Sabah (08:00 - 16:00)</option>
                                <option value="16:00-00:00">🌙 Akşam (16:00 - 00:00)</option>
                                <option value="00:00-08:00">🌃 Gece (00:00 - 08:00)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Görev Alanı</label>
                            <select class="form-select" name="gorev_alani" required>
                                <option value="Mutfak">🍳 Mutfak</option>
                                <option value="Salon">🪑 Salon / Garsonluk</option>
                                <option value="Kasa">💰 Kasa</option>
                                <option value="Temizlik">🧹 Temizlik</option>
                                <option value="Depo">📦 Depo</option>
                            </select>
                        </div>
                        <button type="submit" name="vardiya_ekle" class="btn btn-primary w-100 py-2">Vardiyaya Ata</button>
                    </form>
                </div>
            </div>

            <div class="col-xl-8">
                <?php
                // Benzersiz vardiya gruplarını çek (tarih + saat aralığı)
                $gruplar = mysqli_query($conn, "SELECT DISTINCT vardiya_tarihi, saat_araligi FROM vardiyalar ORDER BY vardiya_tarihi DESC, saat_araligi ASC");
                // Not: Çalışan isimlerini göstermek için aşağıda kullanicilar tablosuyla JOIN yapılıyor.
                
                if (mysqli_num_rows($gruplar) == 0) {
                    echo "<div class='card card-custom p-4 text-center text-muted'><h5>Henüz tanımlanmış vardiya bulunmuyor.</h5><p>Sol panelden çalışan atayarak başlayın.</p></div>";
                } else {
                    while ($grup = mysqli_fetch_assoc($gruplar)) {
                        $g_tarih = $grup['vardiya_tarihi'];
                        $g_saat  = $grup['saat_araligi'];
                        
                        // Vardiya ikonu
                        $ikon = '☀️';
                        if (strpos($g_saat, '16:00') === 0) $ikon = '🌙';
                        if (strpos($g_saat, '00:00') === 0) $ikon = '🌃';
                        
                        // Tarih bugün mü kontrol
                        $tarih_badge = ($g_tarih == $bugun) ? 'bg-success' : 'bg-secondary';
                        ?>
                        <div class="card card-custom p-4 mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="fw-bold mb-0"><?php echo $ikon; ?> Vardiya: <?php echo $g_saat; ?></h5>
                                <span class="badge <?php echo $tarih_badge; ?> p-2"><?php echo $g_tarih; ?></span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle border mb-0">
                                    <thead>
                                        <tr class="table-dark">
                                            <th>Çalışan</th>
                                            <th>Görev Alanı</th>
                                            <th class="text-center">İşlem</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $calisanlar = mysqli_query($conn, "SELECT v.*, COALESCE(k.ad_soyad, 'Bilinmeyen') as ad_soyad FROM vardiyalar v LEFT JOIN kullanicilar k ON v.calisan_id = k.id WHERE v.vardiya_tarihi = '$g_tarih' AND v.saat_araligi = '$g_saat' ORDER BY v.id ASC");
                                        while ($c = mysqli_fetch_assoc($calisanlar)) {
                                            $gorev_icon = '👤';
                                            if ($c['gorev_alani'] == 'Mutfak') $gorev_icon = '🍳';
                                            elseif ($c['gorev_alani'] == 'Salon') $gorev_icon = '🪑';
                                            elseif ($c['gorev_alani'] == 'Kasa') $gorev_icon = '💰';
                                            elseif ($c['gorev_alani'] == 'Temizlik') $gorev_icon = '🧹';
                                            elseif ($c['gorev_alani'] == 'Depo') $gorev_icon = '📦';
                                            ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($c['ad_soyad']); ?></strong></td>
                                                <td><span class="badge bg-light text-dark border"><?php echo $gorev_icon . ' ' . htmlspecialchars($c['gorev_alani']); ?></span></td>
                                                <td class="text-center">
                                                    <form action="personel_panel.php?sayfa=vardiya" method="POST" style="display:inline" onsubmit="return confirm('Bu çalışanı vardiyadan kaldırmak istediğinize emin misiniz?');">
                                                        <input type="hidden" name="vardiya_id" value="<?php echo $c['id']; ?>">
                                                        <button type="submit" name="vardiya_sil" class="btn btn-sm btn-outline-danger">🗑️ Kaldır</button>
                                                    </form>
                                                </td>
                                            </tr>
                                            <?php
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
        </div>

        <?php elseif ($aktif_sayfa == 'personel'): ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-dark fw-bold">👤 Personel Yönetimi</h2>
            <span class="badge bg-dark p-2 fs-6">Yetkili: <?php echo htmlspecialchars($_SESSION['ad_soyad']); ?></span>
        </div>

        <div class="row g-4">
            <div class="col-xl-4">
                <div class="card card-custom p-4">
                    <h5 class="fw-bold mb-3 text-secondary">➕ Yeni Personel Ekle</h5>
                    <form action="personel_panel.php?sayfa=personel" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Ad Soyad</label>
                            <input type="text" class="form-control" name="ad_soyad" placeholder="Örn: Mehmet Yıldız" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">E-posta</label>
                            <input type="email" class="form-control" name="eposta" placeholder="personel@kardelen.com" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Telefon</label>
                            <input type="text" class="form-control" name="telefon" placeholder="05XXXXXXXXX" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Şifre</label>
                            <input type="password" class="form-control" name="sifre" placeholder="Giriş şifresi" required minlength="4">
                        </div>
                        <button type="submit" name="personel_ekle" class="btn btn-primary w-100 py-2">Personel Olarak Kaydet</button>
                    </form>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="card card-custom p-4">
                    <h5 class="fw-bold mb-3 text-secondary">📋 Kayıtlı Personel Listesi</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle border">
                            <thead>
                                <tr class="table-dark">
                                    <th>ID</th>
                                    <th>Ad Soyad</th>
                                    <th>E-posta</th>
                                    <th>Telefon</th>
                                    <th class="text-center">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $personel_listele = mysqli_query($conn, "SELECT * FROM kullanicilar WHERE rol = 'personel' ORDER BY id ASC");
                                if (mysqli_num_rows($personel_listele) == 0) {
                                    echo "<tr><td colspan='5' class='text-center text-muted py-4'>Henüz personel kaydı yok.</td></tr>";
                                } else {
                                    while ($per = mysqli_fetch_assoc($personel_listele)) {
                                        $kendisi = ($per['id'] == $_SESSION['kullanici_id']);
                                        ?>
                                        <tr>
                                            <td>#<?php echo $per['id']; ?></td>
                                            <td><strong><?php echo htmlspecialchars($per['ad_soyad']); ?></strong>
                                                <?php if ($kendisi): ?><span class="badge bg-success ms-1">Siz</span><?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($per['eposta']); ?></td>
                                            <td><?php echo htmlspecialchars($per['telefon']); ?></td>
                                            <td class="text-center">
                                                <?php if ($kendisi): ?>
                                                    <span class="badge bg-light text-muted border">Yetkili</span>
                                                <?php else: ?>
                                                    <form action="personel_panel.php?sayfa=personel" method="POST" style="display:inline" onsubmit="return confirm('Bu personeli silmek istediğinize emin misiniz?');">
                                                        <input type="hidden" name="sil_id" value="<?php echo $per['id']; ?>">
                                                        <button type="submit" name="personel_sil" class="btn btn-sm btn-outline-danger">🗑️ Sil</button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <?php endif; ?> 
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Bildirim Mesajını Otomatik Kapatma Algoritması
    const bildirimKutusu = document.querySelector('.alert');
    if (bildirimKutusu) {
        setTimeout(function() {
            bildirimKutusu.style.transition = "opacity 0.5s ease";
            bildirimKutusu.style.opacity = "0";
            setTimeout(function() {
                bildirimKutusu.remove();
            }, 500); 
        }, 3000);
    }

    // Canlı Filtreleme Motoru
    const aramaKutusu = document.getElementById("aramaKutusu");
    const filtreSecici = document.getElementById("filtreSecici");
    const tabloSatirlari = document.querySelectorAll(".tablo-satir");

    function tabloyuFiltrele() {
        if(!aramaKutusu || !filtreSecici) return;

        const aramaMetni = aramaKutusu.value.toLowerCase().trim();
        const secilenFiltre = filtreSecici.value;

        tabloSatirlari.forEach(satir => {
            const urunAdiHucre = satir.querySelector(".urun-adi-hucre");
            if (!urunAdiHucre) return;

            const urunAdi = urunAdiHucre.textContent.toLowerCase();
            const urunDurumu = satir.getAttribute("data-durum");

            const aramaUyumlu = urunAdi.includes(aramaMetni);
            const filtreUyumlu = (secilenFiltre === "hepsi") || (urunDurumu === secilenFiltre);

            if (aramaUyumlu && filtreUyumlu) {
                satir.style.display = "";
            } else {
                satir.style.display = "none";
            }
        });
    }

    if(aramaKutusu && filtreSecici) {
        aramaKutusu.addEventListener("input", tabloyuFiltrele);
        filtreSecici.addEventListener("change", tabloyuFiltrele);
    }
});

// 🖨️ Sihirli Fiş Yazdırma Motoru
function fisYazdir(siparisId) {
    var yazdirilacakIcerik = document.getElementById('yazdirilacakAlan' + siparisId).innerHTML;
    var orijinalSayfa = document.body.innerHTML;

    document.body.innerHTML = "<html><head><title>Adisyon Basimi</title></head><body style='background:white; color:black; padding:20px;'>" + yazdirilacakIcerik + "</body></html>";
    window.print();
    document.body.innerHTML = orijinalSayfa;
    window.location.reload();
}
</script>
</body>
</html>