<?php
require_once "../includes/db.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
$graduate_id = $_SESSION['user_id'];

// Kullanıcı durumunu kontrol et
$check_sql = "SELECT role, is_verified FROM users WHERE id = $graduate_id";
$check_res = mysqli_query($conn, $check_sql);
$user_status = mysqli_fetch_assoc($check_res);

$is_approved = ($user_status && $user_status['role'] === 'graduate' && $user_status['is_verified'] == 1);

// İlanları çek
$sql = "SELECT ads_id, title, expertise, created_at FROM mentor_ads WHERE graduate_id = $graduate_id ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);
$my_ads = mysqli_fetch_all($result, MYSQLI_ASSOC);
$total_ads = count($my_ads);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>İlanlarım | KBÜ Mentorluk</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/app.css">
    <link rel="stylesheet" href="../assets/css/mentor.css">
</head>
<body>
    <div class="app-container">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <!-- Onaylı Değilse Uyarı Mesajı -->
            <?php if (!$is_approved): ?>
                <div style="background: #fffbeb; border-left: 4px solid #f59e0b; color: #92400e; padding: 15px; margin-bottom: 20px; border-radius: 8px; display: flex; align-items: center; gap: 15px;">
                    <i class="fa-solid fa-circle-exclamation" style="font-size: 20px;"></i>
                    <div>
                        <strong>Hesabınız Henüz Onaylanmadı!</strong><br>
                        İlan oluşturabilmek için hesabınızın yönetici tarafından doğrulanması gerekmektedir. Lütfen bekleyiniz.
                    </div>
                </div>
            <?php endif; ?>

            <div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1 class="page-title">Mentorlük İlanlarım</h1>
                    <p class="page-subtitle">Toplam <strong><?= $total_ads ?></strong> aktif ilanınız bulunuyor.</p>
                </div>
                <!-- Sadece onaylıysa buton görünür -->
                <?php if ($is_approved): ?>
                    <a href="mentor_ads_create.php" class="btn-sm btn-action"><i class="fa-solid fa-plus"></i> Yeni İlan Oluştur</a>
                <?php endif; ?>
            </div>

            <div class="mentor-grid">
                <?php if ($total_ads === 0): ?>
                    <div class="empty-state" style="grid-column: 1 / -1;">
                        <i class="fa-solid fa-folder-open"></i>
                        <p>Henüz hiç ilan oluşturmadınız.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($my_ads as $ad): ?>
                        <div class="ad-card">
                            <h3 class="ad-title"><?= htmlspecialchars($ad['title']) ?></h3>
                            <p class="ad-info"><i class="fa-solid fa-bolt"></i> <?= htmlspecialchars($ad['expertise']) ?></p>
                            <p class="ad-info"><i class="fa-regular fa-calendar"></i> <?= date('d.m.Y H:i', strtotime($ad['created_at'])) ?></p>
                            <div class="ad-footer">
                                <a href="mentor_applications.php?ads_id=<?= $ad['ads_id'] ?>" class="btn-sm" style="background:#f1f5f9; color:var(--kbu-lacivert); border: 1px solid #e2e8f0;">
                                    <i class="fa-solid fa-users"></i> Başvuruları Gör
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
