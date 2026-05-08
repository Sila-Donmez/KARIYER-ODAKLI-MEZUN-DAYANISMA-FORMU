<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'];
$role = $_SESSION['role'];

$stats = array(
    'request_count' => 0,
    'forum_count' => 0,
    'active_mentors' => 0
);

// 1. Dinamik Sayaçlar
try {
    if ($role == 'student') {
        // Öğrencinin yaptığı toplam başvuru sayısı
        $q = $conn->prepare("SELECT COUNT(*) as total FROM mentor_applications WHERE student_id = ?");
    } else {
        // Mezuna gelen bekleyen başvuru sayısı
        $q = $conn->prepare("SELECT COUNT(*) as total FROM mentor_applications ma 
                             JOIN mentor_ads ad ON ma.ads_id = ad.ads_id 
                             WHERE ad.graduate_id = ? AND ma.status = 'pending'");
    }

    if ($q) {
        $q->bind_param("i", $user_id);
        $q->execute();
        $res = $q->get_result();
        $row = $res->fetch_assoc();
        $stats['request_count'] = $row['total'];
    }
} catch (Exception $e) {
    $stats['request_count'] = 0;
}

// 2. Forum ve Aktif Mentor Sayıları
$forum_res = $conn->query("SELECT COUNT(*) FROM forum_posts");
$stats['forum_count'] = ($forum_res) ? $forum_res->fetch_row()[0] : 0;

$mentor_res = $conn->query("SELECT COUNT(DISTINCT graduate_id) FROM mentor_ads");
$stats['active_mentors'] = ($mentor_res) ? $mentor_res->fetch_row()[0] : 0;

// 3. Forumdan Son Sorular
$forum_q = $conn->query("SELECT id, title FROM forum_posts ORDER BY created_at DESC LIMIT 5");

// 4. Yeni Mentorluk İlanları (Güncellenen Kısım)
// Burada artık direkt ilanları (ads) çekiyoruz
$ads_sql = "SELECT ma.ads_id, ma.title, ma.expertise, u.first_name, u.last_name 
            FROM mentor_ads ma 
            JOIN users u ON ma.graduate_id = u.id 
            WHERE u.id != $user_id 
            ORDER BY ma.created_at DESC LIMIT 3";
$ads_q = $conn->query($ads_sql);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontrol Paneli | KBÜ Mentorluk</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/app.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>
    <div class="app-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">Hoş Geldin, <?= htmlspecialchars($first_name) ?>! 👋</h1>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon <?= ($role == 'student' ? 'icon-blue' : 'icon-red') ?>">
                        <i class="fa-solid <?= ($role == 'student' ? 'fa-paper-plane' : 'fa-envelope-open-text') ?>"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= (int) $stats['request_count'] ?></h3>
                        <span><?= ($role == 'student' ? 'Yaptığım Başvurular' : 'Bekleyen Talepler') ?></span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon icon-green"><i class="fa-solid fa-users"></i></div>
                    <div class="stat-info">
                        <h3><?= (int) $stats['active_mentors'] ?></h3>
                        <span>Aktif Mentorlar</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon icon-yellow"><i class="fa-solid fa-comments"></i></div>
                    <div class="stat-info">
                        <h3><?= (int) $stats['forum_count'] ?></h3>
                        <span>Forum Başlıkları</span>
                    </div>
                </div>
            </div>

            <div class="dashboard-grid">
                <!-- Forum Bölümü -->
                <div class="glass-card dashboard-box">
                    <h3 class="box-title"><i class="fa-solid fa-fire icon-red-text"></i> Forumda Yeni Ne Var?</h3>
                    <div class="forum-badges-container">
                        <?php if ($forum_q && $forum_q->num_rows > 0): ?>
                            <?php while ($post = $forum_q->fetch_assoc()): ?>
                                <a href="forum/post_detail.php?id=<?= (int) $post['id'] ?>" class="forum-badge">
                                    # <?= htmlspecialchars($post['title']) ?>
                                </a>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="empty-text">Henüz bir soru sorulmamış.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Mentor İlanları Bölümü (Güncellenen Kısım) -->
                <div class="glass-card dashboard-box">
                    <h3 class="box-title"><i class="fa-solid fa-bullhorn icon-navy"></i> Yeni Mentorluk İlanları</h3>
                    <div class="mentor-list">
                        <?php if ($ads_q && $ads_q->num_rows > 0): ?>
                            <?php while ($ad = $ads_q->fetch_assoc()): ?>
                                <div class="mentor-item">
                                    <div class="mentor-avatar">
                                        <?= mb_substr(htmlspecialchars($ad['first_name']), 0, 1, 'UTF-8') ?>
                                    </div>
                                    <div class="mentor-info">
                                        <div class="mentor-name"><?= htmlspecialchars($ad['title']) ?></div>
                                        <div class="mentor-year">
                                            <i class="fa-solid fa-user-tie"></i> <?= htmlspecialchars($ad['first_name'] . " " . $ad['last_name']) ?> 
                                            | <i class="fa-solid fa-tag"></i> <?= htmlspecialchars($ad['expertise']) ?>
                                        </div>
                                    </div>
                                    <!-- Burada student_apply.php sayfasına ads_id ile gönderiyoruz -->
                                    <a href="mentor/student_apply.php?ads_id=<?= $ad['ads_id'] ?>" class="btn-outline-navy">İncele</a>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="empty-text">Şu an aktif ilan bulunmuyor.</p>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </main>
    </div>
</body>
</html>