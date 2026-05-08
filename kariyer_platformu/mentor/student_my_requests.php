<?php
require_once "../includes/db.php";
session_start();

// 1. Oturum Kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// 2. Veri Çekme İşlemi
// Başvuruları, İlan bilgilerini ve Mentorun e-posta dahil bilgilerini çekiyoruz
$sql = "
    SELECT 
        ma.application_id,
        ma.status,
        ma.applied_at as applied_date,
        ads.title,
        ads.expertise,
        u.first_name as mentor_first_name,
        u.last_name as mentor_last_name,
        u.email as mentor_email
    FROM mentor_applications ma
    JOIN mentor_ads ads ON ma.ads_id = ads.ads_id
    JOIN graduates g ON ads.graduate_id = g.user_id
    JOIN users u ON g.user_id = u.id
    WHERE ma.student_id = $student_id
    ORDER BY ma.applied_at DESC
";

$result = mysqli_query($conn, $sql);
$requests = mysqli_fetch_all($result, MYSQLI_ASSOC);
$total_requests = count($requests);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İsteklerim | KBÜ Mentorluk</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/app.css">
    <link rel="stylesheet" href="../assets/css/mentor.css">
</head>
<body>
    <div class="app-container">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">İsteklerim</h1>
                <p class="page-subtitle">Yaptığınız mentorlük başvurularının güncel durumunu buradan takip edebilirsiniz.</p>
            </div>

            <div class="mentor-grid">
                <?php if ($total_requests === 0): ?>
                    <div class="empty-state" style="grid-column: 1 / -1;">
                        <i class="fa-solid fa-paper-plane"></i>
                        <p>Henüz hiçbir mentora başvuru yapmadınız.</p>
                        <a href="student_ads_list.php" class="btn-sm btn-action" style="margin-top: 15px;">Mentor Bul</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($requests as $req): ?>
                        <?php 
                            // Durum kontrolü: Veritabanından gelen veriyi standardize ediyoruz
                            $current_status = trim(strtolower($req['status'])); 
                            
                            // Border rengi belirleme
                            $border_color = ($current_status === 'approved') ? '#10b981' : (($current_status === 'rejected') ? '#ef4444' : 'var(--kbu-lacivert)');
                        ?>
                        <div class="ad-card" style="border-left: 5px solid <?= $border_color ?>;">
                            
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                                <h3 class="ad-title" style="margin: 0;"><?= htmlspecialchars($req['title']) ?></h3>
                                
                                <?php 
                                    if($current_status === 'approved') { 
                                        $bg = 'bg-approved'; $text = '<i class="fa-solid fa-check"></i> Kabul Edildi'; 
                                    } elseif($current_status === 'rejected') { 
                                        $bg = 'bg-rejected'; $text = '<i class="fa-solid fa-xmark"></i> Reddedildi'; 
                                    } else { 
                                        $bg = 'bg-waiting'; $text = 'Değerlendirmede'; 
                                    }
                                ?>
                                <span class="status-badge <?= $bg ?>"><?= $text ?></span>
                            </div>

                            <p class="ad-info"><i class="fa-solid fa-user-tie"></i> <strong>Mentor:</strong> <?= htmlspecialchars($req['mentor_first_name'] . ' ' . $req['mentor_last_name']) ?></p>
                            <p class="ad-info"><i class="fa-solid fa-bolt"></i> <strong>Uzmanlık:</strong> <?= htmlspecialchars($req['expertise']) ?></p>
                            <p class="ad-info"><i class="fa-regular fa-calendar"></i> <?= date('d.m.Y H:i', strtotime($req['applied_date'])) ?></p>
                            
                            <div style="margin-top: 20px;">
                                <?php if ($current_status === 'approved'): ?>
                                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 20px;">
                                        <h4 style="color: #166534; margin: 0 0 10px 0; font-size: 15px;">
                                            <i class="fa-solid fa-certificate" style="margin-right: 5px;"></i> Tebrikler! Başvurunuz kabul edildi.
                                        </h4>
                                        <p style="color: #15803d; font-size: 13px; margin: 0 0 15px 0;">
                                            Mentorunuzla iletişime geçerek kariyer yolculuğunuza başlayabilirsiniz.
                                        </p>
                                        <div style="background: white; padding: 12px 15px; border-radius: 8px; border: 1px solid #bbf7d0; display: inline-flex; align-items: center; gap: 10px;">
                                            <i class="fa-solid fa-envelope" style="color: #166534; font-size: 16px;"></i>
                                            <div>
                                                <span style="display: block; font-size: 11px; font-weight: 800; color: #166534; text-transform: uppercase; letter-spacing: 0.5px;">Mentor İletişim</span>
                                                <a href="mailto:<?= htmlspecialchars($req['mentor_email']) ?>" style="color: #15803d; font-weight: bold; text-decoration: none; font-size: 14px;">
                                                    <?= htmlspecialchars($req['mentor_email']) ?>
                                                </a>
                                            </div>
                                        </div>
                                    </div>

                                <?php elseif ($current_status === 'rejected'): ?>
                                    <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; padding: 15px;">
                                        <p style="color: #991b1b; font-size: 13px; margin: 0;">
                                            <i class="fa-solid fa-circle-info" style="margin-right: 5px;"></i> Mentorunuz şu an kontenjanı dolu olduğu için talebinizi karşılayamadı. Diğer ilanları inceleyebilirsiniz.
                                        </p>
                                    </div>

                                <?php else: ?>
                                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 15px;">
                                        <p style="color: #64748b; font-size: 13px; margin: 0;">
                                            <i class="fa-solid fa-hourglass-half" style="margin-right: 5px;"></i> Başvurunuz henüz değerlendirme aşamasında. Sonuçlandığında bildirim alacaksınız.
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>

                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
