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

// --- VERİ ÇEKME İŞLEMLERİ ---
$stats = array(
    'request_count' => 0,
    'forum_count' => 0,
    'active_mentors' => 0
);

// 1. Dinamik Sayaçlar
try {
    if ($role == 'student') {
        $q = $conn->prepare("SELECT COUNT(*) as total FROM mentorship_requests WHERE student_id = ?");
    } else {
        $q = $conn->prepare("SELECT COUNT(*) as total FROM mentorship_requests WHERE graduate_id = ? AND status = 'pending'");
    }

    if ($q) {
        $q->bind_param("i", $user_id);
        $q->execute();
        $res = $q->get_result();
        if ($res) {
            $row = $res->fetch_assoc();
            $stats['request_count'] = $row['total'];
        }
    }
} catch (Exception $e) {
    $stats['request_count'] = 0;
}

// 2. Forum ve Aktif Mentor Sayıları
$forum_res = $conn->query("SELECT COUNT(*) FROM forum_posts");
$stats['forum_count'] = ($forum_res) ? $forum_res->fetch_row()[0] : 0;

$mentor_res = $conn->query("SELECT COUNT(*) FROM graduates WHERE is_open_to_mentorship = 1");
$stats['active_mentors'] = ($mentor_res) ? $mentor_res->fetch_row()[0] : 0;

// 3. Forumdan Son Sorular
$forum_q = $conn->query("SELECT id, title FROM forum_posts ORDER BY created_at DESC LIMIT 5");

// 4. Önerilen Mentorlar 
$suggest_sql = "SELECT MIN(ma.ads_id) as ads_id, u.first_name, u.last_name, g.graduate_year 
                FROM graduates g 
                JOIN users u ON g.user_id = u.id 
                JOIN mentor_ads ma ON g.user_id = ma.graduate_id 
                WHERE g.is_open_to_mentorship = 1 AND u.id != $user_id 
                GROUP BY u.id, u.first_name, u.last_name, g.graduate_year 
                ORDER BY RAND() LIMIT 3";
$mentor_suggest_q = $conn->query($suggest_sql);
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
                        <span><?= ($role == 'student' ? 'Yaptığım İstekler' : 'Bekleyen Talepler') ?></span>
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
                
                <div class="glass-card dashboard-box">
                    <h3 class="box-title"><i class="fa-solid fa-fire icon-red-text"></i> Forumda Yeni Ne Var?</h3>
                    <div class="forum-badges-container">
                        <?php if (isset($forum_q) && $forum_q->num_rows > 0): ?>
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

                <div class="glass-card dashboard-box">
                    <h3 class="box-title"><i class="fa-solid fa-magnifying-glass icon-navy"></i> En Yeni Mentorlar</h3>
                    <div class="mentor-list">
                        <?php if (isset($mentor_suggest_q) && $mentor_suggest_q->num_rows > 0): ?>
                            <?php while ($m = $mentor_suggest_q->fetch_assoc()): ?>
                                <div class="mentor-item">
                                    <div class="mentor-avatar">
                                        <?php
                                        $char = !empty($m['first_name']) ? mb_substr($m['first_name'], 0, 1, 'UTF-8') : 'M';
                                        echo htmlspecialchars(mb_strtoupper($char, 'UTF-8'));
                                        ?>
                                    </div>
                                    <div class="mentor-info">
                                        <div class="mentor-name"><?= htmlspecialchars($m['first_name'] . " " . $m['last_name']) ?></div>
                                        <div class="mentor-year"><?= htmlspecialchars($m['graduate_year']) ?> Mezunu</div>
                                    </div>
                                    <a href="mentor/apply.php?ads_id=<?= $m['ads_id'] ?>" class="btn-outline-navy">İncele</a>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="empty-text">Şu an önerilen mentor bulunmuyor.</p>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </main>
    </div>
</body>
</html>