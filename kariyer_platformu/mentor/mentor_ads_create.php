<?php
require_once "../includes/db.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$graduate_id = $_SESSION['user_id'];
$message = "";

// Onay kontrolü
$check_sql = "SELECT role, is_verified FROM users WHERE id = $graduate_id";
$check_res = mysqli_query($conn, $check_sql);
$user_status = mysqli_fetch_assoc($check_res);
$is_approved = ($user_status && $user_status['role'] === 'graduate' && $user_status['is_verified'] == 1);

// Form işleme (Sadece onaylıysa)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($is_approved) {
        $title = mysqli_real_escape_string($conn, htmlspecialchars($_POST['title']));
        $expertise = mysqli_real_escape_string($conn, htmlspecialchars($_POST['expertise']));

        $sql = "INSERT INTO mentor_ads (graduate_id, title, expertise) VALUES ($graduate_id, '$title', '$expertise')";

        if (mysqli_query($conn, $sql)) {
            header("Location: mentor_my_ads.php");
            exit;
        } else {
            $message = "Hata: " . mysqli_error($conn);
        }
    } else {
        $message = "Yetkisiz işlem: Hesabınız onaylı olmadığı için ilan yayınlayamazsınız.";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Yeni İlan Oluştur | KBÜ Mentorluk</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/app.css">
</head>
<body>
    <div class="app-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">Yeni Mentorlük İlanı</h1>
            </div>

            <div style="max-width: 600px;">
                <?php if (!$is_approved): ?>
                    <!-- Onaylı Değilse Form Yerine Bu Mesaj Çıkar -->
                    <div style="background: #fee2e2; color: #b91c1c; padding: 20px; border-radius: 12px; border: 1px solid #fecaca; text-align: center;">
                        <i class="fa-solid fa-lock" style="font-size: 40px; margin-bottom: 15px; display: block;"></i>
                        <h3 style="margin-bottom: 10px;">Erişim Kısıtlı</h3>
                        <p>Henüz yönetici onayından geçmediğiniz için yeni bir mentorluk ilanı oluşturamazsınız.</p>
                        <a href="mentor_my_ads.php" class="btn-primary" style="display:inline-block; margin-top:15px; background: #b91c1c;">İlanlarıma Dön</a>
                    </div>
                <?php else: ?>
                    <!-- Onaylıysa Formu Göster -->
                    <?php if ($message): ?>
                        <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
                            <?= $message ?>
                        </div>
                    <?php endif; ?>

                    <div class="glass-card">
                        <form action="" method="POST">
                            <div class="form-group">
                                <label for="title">İlan Başlığı</label>
                                <input type="text" class="form-control" name="title" id="title" placeholder="Örn: PHP ile Web Geliştirme Temelleri" required>
                            </div>

                            <div class="form-group">
                                <label for="expertise">Uzmanlık Alanı / Deneyim</label>
                                <textarea class="form-control" name="expertise" id="expertise" rows="5" placeholder="Hangi konularda mentorluk vereceksiniz?" required></textarea>
                            </div>

                            <button type="submit" class="btn-primary"><i class="fa-solid fa-plus"></i> İlanı Yayınla</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
