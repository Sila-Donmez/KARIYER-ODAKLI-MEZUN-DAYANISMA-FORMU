<?php
// Hata ayıklama modu
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}
require_once 'includes/db.php';
require_once 'includes/functions.php';

$user_id = $_SESSION['user_id'];

// Veritabanı sorgusu
$sql = "SELECT u.first_name, u.last_name, u.email, u.role, p.bio, p.linkedin_url, p.website_url,
               s.student_number, s.department,
               g.graduate_year, g.is_open_to_mentorship,
               (SELECT GROUP_CONCAT(sk.skill_name SEPARATOR ', ') 
                FROM user_skills us 
                JOIN skills sk ON us.skill_id = sk.id 
                WHERE us.user_id = u.id) AS yetenekler 
        FROM users u
        LEFT JOIN profiles p ON u.id = p.user_id 
        LEFT JOIN students s ON u.id = s.user_id 
        LEFT JOIN graduates g ON u.id = g.user_id
        WHERE u.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

if (!$user_data) {
    die("Veriler bulunamadı. Lütfen veritabanı bağlantınızı kontrol edin.");
}

// Baş harfleri alma ve temizleme
$f_name = trim($user_data['first_name'] ?? '');
$l_name = trim($user_data['last_name'] ?? '');
$initials = mb_substr($f_name, 0, 1, 'UTF-8') . mb_substr($l_name, 0, 1, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilim | KBÜ Mentorluk</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/app.css">
    <link rel="stylesheet" href="assets/css/view_profile.css">
</head>
<body>

    <div class="app-container">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            
            <div class="page-header">
                <h1 class="page-title">Profil Bilgilerim</h1>
                <p class="page-subtitle">Platformdaki kişisel ve akademik bilgileriniz.</p>

                <?php if (isset($_SESSION['success_msg'])): ?>
                    <div class="alert-success" style="margin-top: 15px;">
                        <i class="fa-solid fa-circle-check"></i>
                        <?= $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?>
                    </div>
                <?php endif; ?>
            </div>

            <div>
                <div class="card-boxy">
                    <div class="header-flex">
                        <div class="avatar-sq"><?= mb_strtoupper($initials, 'UTF-8') ?></div>
                        
                        <div class="name-zone">
                            <span class="role-badge">
                                <?= ($user_data['role'] == 'graduate') ? 'Mezun / Mentor' : 'Üniversite Öğrencisi' ?>
                            </span>
                            <h1 class="user-name">
                                <?= htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']) ?>
                            </h1>
                        </div>
                    </div>

                    <p class="user-bio">
                        "<?= !empty($user_data['bio']) ? htmlspecialchars($user_data['bio']) : 'Henüz bir biyografi eklenmemiş.' ?>"
                    </p>

                    <div class="link-row">
                        <?php if (!empty($user_data['linkedin_url'])): ?>
                            <a href="<?= htmlspecialchars($user_data['linkedin_url']) ?>" target="_blank" class="btn-box ln">
                                <i class="fa-brands fa-linkedin"></i> LinkedIn
                            </a>
                        <?php endif; ?>

                        <?php if (!empty($user_data['website_url'])): ?>
                            <a href="<?= htmlspecialchars($user_data['website_url']) ?>" target="_blank" class="btn-box pt">
                                <i class="fa-solid fa-link"></i> Portfolyo
                            </a>
                        <?php endif; ?>

                        <a href="profile.php" class="btn-box edit-btn-layout">
                            <i class="fa-solid fa-user-pen"></i> Profili Düzenle
                        </a>
                    </div>
                </div>

                <div class="info-grid">
                    <div class="card-boxy">
                        <h3 class="card-title"><i class="fa-solid fa-graduation-cap"></i> İletişim & Eğitim</h3>
                        <div class="data-item">
                            <label>E-POSTA ADRESİ</label>
                            <p><?= htmlspecialchars($user_data['email']) ?></p>
                        </div>
                        <div class="data-item">
                            <label>BÖLÜM</label>
                            <p><?= htmlspecialchars($user_data['department'] ?? 'Belirtilmemiş') ?></p>
                        </div>

                        <?php if ($user_data['role'] == 'student'): ?>
                            <div class="data-item">
                                <label>ÖĞRENCİ NUMARASI</label>
                                <p><?= htmlspecialchars($user_data['student_number'] ?? '---') ?></p>
                            </div>
                        <?php elseif ($user_data['role'] == 'graduate'): ?>
                            <div class="data-item">
                                <label>MEZUNİYET YILI</label>
                                <p><?= htmlspecialchars($user_data['graduate_year'] ?? '---') ?></p>
                            </div>
                            <div class="data-item">
                                <label>MENTORLUK DURUMU</label>
                                <p>
                                    <?= ($user_data['is_open_to_mentorship'] == 1) ? '✅ Mentorluğa Açık' : '❌ Şu an Mentorluk Vermiyor' ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="card-boxy">
                        <h3 class="card-title"><i class="fa-solid fa-bolt"></i> Yetenekler</h3>
                        <div class="skills-flex">
                            <?php
                            if (!empty($user_data['yetenekler'])) {
                                foreach (explode(', ', $user_data['yetenekler']) as $skill) {
                                    echo "<span class='skill-pill'>" . htmlspecialchars($skill) . "</span>";
                                }
                            } else {
                                echo "<p class='empty-text'>Henüz yetenek eklenmemiş.</p>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>