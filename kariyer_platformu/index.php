<?php
session_start();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KBÜ Kariyer ve Mentorluk Platformu</title>
    <!-- FontAwesome ve Google Fonts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- CSS Dosyalarımız (Önce ortak, sonra sayfaya özel) -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/index.css">
</head>
<body>

    <!-- Üst Menü -->
    <nav class="navbar-glass">
        <a href="index.php" class="nav-brand">KBÜ <span>Kariyer</span></a>
        
        <div class="nav-links">
            <?php if(isset($_SESSION['user_id'])): ?>
                <!-- Giriş Yapmış Kullanıcı Menüsü -->
                <a href="forum/index.php"><i class="fa-solid fa-comments"></i> Forum</a>
                
                <span class="nav-divider">|</span>
                
                <span class="nav-user">
                    <i class="fa-solid fa-user-circle"></i> <?= htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']) ?>
                </span>
                
                <a href="auth/logout.php" class="nav-logout"><i class="fa-solid fa-right-from-bracket"></i> Çıkış</a>
            <?php else: ?>
                <!-- Ziyaretçi Menüsü -->
                <a href="auth/login.php">Giriş Yap</a>
                <a href="auth/register.php" class="btn-nav-register">Kayıt Ol</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Ana İçerik Alanı -->
    <div class="hero-section">
        
        <?php if(isset($_SESSION['user_id'])): ?>
            
            <!-- KULLANICI GİRİŞ YAPMIŞSA -->
            <h1 class="hero-title">Platforma Hoş Geldiniz</h1>
            <p class="hero-desc">
                Rolünüz: <strong><?= strtoupper($_SESSION['role'] === 'student' ? 'Öğrenci' : ($_SESSION['role'] === 'graduate' ? 'Mezun' : 'Admin')) ?></strong>
            </p>

            <div class="grid-container">
                <!-- Rol Bazlı Kartlar -->
                <?php if($_SESSION['role'] == 'student'): ?>
                    <div class="glass-card action-card">
                        <i class="fa-solid fa-user-tie action-icon"></i>
                        <h3>Mentor Bul</h3>
                        <p>Deneyimli mezunlarımızın profillerini incele ve kariyerine yön vermek için mentorluk talep et.</p>
                        <a href="user/mentors.php" class="btn-primary btn-block">Mentorları İncele</a>
                    </div>
                <?php elseif($_SESSION['role'] == 'graduate'): ?>
                    <div class="glass-card action-card">
                        <i class="fa-solid fa-handshake action-icon"></i>
                        <h3>Mentorluk İstekleri</h3>
                        <p>Öğrencilerden gelen mentorluk taleplerini yönet ve tecrübelerini geleceğin mühendisleriyle paylaş.</p>
                        <a href="mentor/requests.php" class="btn-primary btn-block">İstekleri Görüntüle</a>
                    </div>
                <?php endif; ?>

                <div class="glass-card action-card">
                    <i class="fa-solid fa-layer-group action-icon"></i>
                    <h3>Kariyer Forumu</h3>
                    <p>Sektörel tartışmalara katıl, sorularını sor veya diğer üyelerin karşılaştığı teknik sorunlara çözüm üret.</p>
                    <a href="forum/index.php" class="btn-primary btn-block btn-secondary">Foruma Git</a>
                </div>

                <div class="glass-card action-card">
                    <i class="fa-solid fa-id-card action-icon"></i>
                    <h3>Profilimi Düzenle</h3>
                    <p>Yeteneklerini, özgeçmişini ve iletişim bilgilerini güncelleyerek ağını genişlet.</p>
                    <a href="profile.php" class="btn-primary btn-block btn-secondary">Profili Güncelle</a>
                </div>
            </div>

        <?php else: ?>
            
            <!-- ZİYARETÇİ EKRANI -->
            <div class="hero-pill">
                <i class="fa-solid fa-graduation-cap"></i>
                <span>Mühendislik Network Ağı</span>
            </div>
            
            <h1 class="hero-title">TECRÜBEYİ <span>GELECEKLE</span><br>BİRLEŞTİRİN</h1>
            
            <p class="visitor-desc">
                Mezunların sektörel deneyimi, öğrencilerin vizyonuyla buluşuyor. Karabük Üniversitesi bilgisayar mühendisliği topluluğunun gücünü keşfedin.
            </p>
            
            <div>
                <a href="auth/register.php" class="btn-primary btn-hero">Hemen Katıl <i class="fa-solid fa-arrow-right"></i></a>
            </div>

            <div class="grid-container mt-80">
                <div class="glass-card action-card feature-card">
                    <i class="fa-solid fa-user-shield action-icon feature-icon"></i>
                    <h3>Birebir Mentorluk</h3>
                    <p>Sektörde çalışan mezunlarımızdan doğrudan tavsiye alın.</p>
                </div>
                <div class="glass-card action-card feature-card">
                    <i class="fa-solid fa-comments action-icon feature-icon"></i>
                    <h3>Aktif Forum</h3>
                    <p>Teknik sorular sorun, projelerinizi tartışın ve kod paylaşın.</p>
                </div>
                <div class="glass-card action-card feature-card">
                    <i class="fa-solid fa-briefcase action-icon feature-icon"></i>
                    <h3>Kariyer Fırsatları</h3>
                    <p>Şirket değerlendirmelerini oku ve deneyimleri incele.</p>
                </div>
            </div>

        <?php endif; ?>

    </div>

</body>
</html>