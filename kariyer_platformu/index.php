<?php
session_start();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KBÜ Kariyer ve Mentorluk Platformu</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/index.css">
</head>
<body>

    <nav class="navbar-glass">
        <a href="index.php" class="nav-brand">KBÜ <span>Kariyer</span></a>
        
        <div class="nav-links">
            <a href="auth/login.php">Giriş Yap</a>
            <a href="auth/register.php" class="btn-nav-register">Kayıt Ol</a>
        </div>
    </nav>

    <div class="hero-section">
        
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

    </div>

</body>
</html>
