<?php
session_start();

// 1. Oturum Kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

require_once 'includes/db.php';
require_once 'includes/functions.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$success_msg = "";
$error_msg = "";

// ==========================================
// 1. FORM GÖNDERİLDİYSE GÜNCELLEME İŞLEMLERİ
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['bio'])) {
    $bio = clean($_POST['bio'] ?? '');
    $linkedin = clean($_POST['linkedin_url'] ?? '');
    $website = clean($_POST['website_url'] ?? '');
    $skills_input = clean($_POST['skills'] ?? ''); 

    $conn->begin_transaction();
    try {
        $p_sql = "INSERT INTO profiles (user_id, bio, website_url, linkedin_url) 
                  VALUES (?, ?, ?, ?) 
                  ON DUPLICATE KEY UPDATE bio=VALUES(bio), website_url=VALUES(website_url), linkedin_url=VALUES(linkedin_url)";
        $p_stmt = $conn->prepare($p_sql);
        $p_stmt->bind_param("isss", $user_id, $bio, $website, $linkedin);
        $p_stmt->execute();

        if ($role == 'student') {
            $department = clean($_POST['department'] ?? '');
            $s_sql = "UPDATE students SET department = ? WHERE user_id = ?";
            $s_stmt = $conn->prepare($s_sql);
            $s_stmt->bind_param("si", $department, $user_id);
            $s_stmt->execute();
        } elseif ($role == 'graduate') {
            $graduate_year = intval($_POST['graduate_year'] ?? 0);
            $is_open = isset($_POST['is_open_to_mentorship']) ? 1 : 0;
            $g_sql = "UPDATE graduates SET graduate_year = ?, is_open_to_mentorship = ? WHERE user_id = ?";
            $g_stmt = $conn->prepare($g_sql);
            $g_stmt->bind_param("iii", $graduate_year, $is_open, $user_id);
            $g_stmt->execute();
        }

        $conn->query("DELETE FROM user_skills WHERE user_id = $user_id"); 
        $skill_names = array_filter(array_map('trim', explode(',', $skills_input)));
        if (!empty($skill_names)) {
            $check_skill = $conn->prepare("SELECT id FROM skills WHERE skill_name = ?");
            $insert_skill = $conn->prepare("INSERT INTO skills (skill_name) VALUES (?)");
            $insert_user_skill = $conn->prepare("INSERT INTO user_skills (user_id, skill_id) VALUES (?, ?)");
            foreach ($skill_names as $s_name) {
                $check_skill->bind_param("s", $s_name);
                $check_skill->execute();
                $res = $check_skill->get_result();
                if ($res->num_rows > 0) {
                    $skill_id = $res->fetch_assoc()['id'];
                } else {
                    $insert_skill->bind_param("s", $s_name);
                    $insert_skill->execute();
                    $skill_id = $insert_skill->insert_id;
                }
                $insert_user_skill->bind_param("ii", $user_id, $skill_id);
                $insert_user_skill->execute();
            }
        }
        $conn->commit();
        $success_msg = "Profil bilgileriniz başarıyla güncellendi.";
    } catch (Exception $e) {
        $conn->rollback();
        $error_msg = "Güncelleme sırasında bir hata oluştu: " . $e->getMessage();
    }
}

// ==========================================
// 2. MEVCUT VERİLERİ ÇEKME
// ==========================================
$p_stmt = $conn->prepare("SELECT * FROM profiles WHERE user_id = ?");
$p_stmt->bind_param("i", $user_id);
$p_stmt->execute();
$profile_data = $p_stmt->get_result()->fetch_assoc() ?? ['bio' => '', 'website_url' => '', 'linkedin_url' => ''];

$role_data = [];
$document_status = ""; 
if ($role == 'student') {
    $s_stmt = $conn->prepare("SELECT department, student_number FROM students WHERE user_id = ?");
    $s_stmt->bind_param("i", $user_id); $s_stmt->execute();
    $role_data = $s_stmt->get_result()->fetch_assoc();
} else {
    $g_stmt = $conn->prepare("SELECT graduate_year, is_open_to_mentorship, document_link FROM graduates WHERE user_id = ?");
    $g_stmt->bind_param("i", $user_id); $g_stmt->execute();
    $role_data = $g_stmt->get_result()->fetch_assoc();
    if (!empty($role_data['document_link'])) {
        $document_status = "Mezuniyet belgeniz sistemde kayıtlı. Yenisini yüklerseniz eskisi silinir.";
    }
}

$u_sk_stmt = $conn->prepare("SELECT s.skill_name FROM user_skills us JOIN skills s ON us.skill_id = s.id WHERE us.user_id = ?");
$u_sk_stmt->bind_param("i", $user_id); $u_sk_stmt->execute();
$user_skills_res = $u_sk_stmt->get_result();
$user_skill_names = [];
while ($row = $user_skills_res->fetch_assoc()) { $user_skill_names[] = $row['skill_name']; }
$skills_string = implode(",", $user_skill_names); 
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Profilimi Düzenle | KBÜ Mentorluk</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/app.css"> 
    <link rel="stylesheet" href="assets/css/profile.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .btn-search-red { height: 48px; padding: 0 35px; background-color: #D32F2F; color: white !important; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.3s; }
        .btn-search-red:hover { background-color: #B71C1C; transform: translateY(-1px); }
        .file-upload-label { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 30px; border: 2px dashed #cbd5e1; border-radius: 12px; background-color: #f8fafc; cursor: pointer; transition: all 0.3s; }
        .file-upload-label:hover { border-color: #D32F2F; background-color: #fef2f2; }
        .file-upload-label input[type="file"] { display: none; }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'includes/sidebar.php'; ?>
        <main class="main-content">
            <div class="page-header"><h1 class="page-title">Profilimi Düzenle</h1></div>
            
            <div class="profile-container">
                <div class="profile-sidebar">
                    <div class="glass-card" style="padding: 30px 20px; text-align: center;">
                        <div class="profile-avatar" style="width: 100px; height: 100px; background: var(--kbu-lacivert); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 35px; margin: 0 auto 15px;">
                            <?= mb_substr($_SESSION['first_name'], 0, 1) . mb_substr($_SESSION['last_name'], 0, 1) ?>
                        </div>
                        <h2 class="profile-name" style="font-size: 20px; color: var(--kbu-lacivert);"><?= htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']) ?></h2>
                        <div class="profile-role" style="font-size: 13px; color: var(--text-muted); margin-bottom: 10px;"><?= ($role == 'student') ? 'Üniversite Öğrencisi' : 'Mezun / Mentor' ?></div>
                        <?php if($role == 'student' && !empty($role_data['student_number'])): ?>
                            <div style="font-size: 12px; color: #64748B;"><i class="fa-solid fa-id-card"></i> No: <?= htmlspecialchars($role_data['student_number']) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Sadece mezunlar için Kariyer & Deneyim kısmı -->
                    <?php if ($role == 'graduate'): ?>
                    <div class="glass-card" style="padding: 25px 20px; margin-top: 20px; text-align: center;">
                        <h3 class="profile-section-title" style="font-size: 15px;"><i class="fa-solid fa-briefcase"></i> Kariyer & Deneyim</h3>
                        <p style="font-size: 12px; color: #64748b; margin-bottom: 15px;">Şirket geçmişinizi güncelleyin.</p>
                        <a href="company/add_experience.php" class="btn-search-red" style="width: 100%; font-size: 13px;">
                            <i class="fa-solid fa-plus"></i> Yeni Deneyim Ekle
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="profile-form-area">
                    <div class="glass-card" style="padding: 40px; margin-bottom: 30px;">
                        <?php if($success_msg): ?><div class="alert" style="background:#ECFDF5; color:#047857; padding:15px; border-radius:12px; margin-bottom:20px;"><?= $success_msg ?></div><?php endif; ?>
                        
                        <form method="POST">
                            <h3 class="profile-section-title"><i class="fa-solid fa-user-pen"></i> Temel Bilgiler</h3>
                            <div class="form-group">
                                <label>Hakkımda</label>
                                <textarea name="bio" class="form-control" rows="4"><?= htmlspecialchars($profile_data['bio']) ?></textarea>
                            </div>
                            <div class="form-row" style="display:flex; gap:20px; margin-top:15px;">
                                <div class="form-group" style="flex:1;">
                                    <label>LinkedIn</label>
                                    <input type="url" name="linkedin_url" class="form-control" value="<?= htmlspecialchars($profile_data['linkedin_url']) ?>">
                                </div>
                                <div class="form-group" style="flex:1;">
                                    <label>Web Sitesi</label>
                                    <input type="url" name="website_url" class="form-control" value="<?= htmlspecialchars($profile_data['website_url']) ?>">
                                </div>
                            </div>

                            <h3 class="profile-section-title" style="margin-top:20px;"><i class="fa-solid fa-graduation-cap"></i> Akademik Bilgiler</h3>
                            <?php if ($role == 'student'): ?>
                                <div class="form-group">
                                    <label>Bölüm</label>
                                    <input type="text" name="department" class="form-control" value="<?= htmlspecialchars($role_data['department'] ?? '') ?>">
                                </div>
                            <?php else: ?>
                                <div class="form-row" style="display:flex; gap:20px;">
                                    <div class="form-group" style="flex:1;">
                                        <label>Mezuniyet Yılı</label>
                                        <input type="number" name="graduate_year" class="form-control" value="<?= htmlspecialchars($role_data['graduate_year'] ?? '') ?>">
                                    </div>
                                    <div class="form-group" style="flex:1; display:flex; align-items:center; margin-top:25px;">
                                        <label style="font-size:13px;"><input type="checkbox" name="is_open_to_mentorship" value="1" <?= ($role_data['is_open_to_mentorship'] == 1)?'checked':'' ?>> Mentorluğa Açığım</label>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <h3 class="profile-section-title" style="margin-top:20px;"><i class="fa-solid fa-code"></i> Yetenekler</h3>
                            <div class="skill-input-group" style="display:flex; gap:10px;">
                                <input type="text" id="skill_input" class="form-control" placeholder="Örn: PHP, Java">
                                <button type="button" id="add_skill_btn" class="btn-add-skill" style="background:#0F204B; color:white; border:none; border-radius:12px; padding:0 20px; cursor:pointer;">Ekle</button>
                            </div>
                            <div id="skills_container" class="skills-container" style="display:flex; flex-wrap:wrap; gap:10px; margin-top:10px;"></div>
                            <input type="hidden" name="skills" id="hidden_skills" value="<?= htmlspecialchars($skills_string) ?>">
                            
                            <div style="text-align:right; margin-top:30px;">
                                <button type="submit" class="btn-primary" style="width:auto; padding:12px 40px;">Kaydet</button>
                            </div>
                        </form>
                    </div>

                    <?php if ($role == 'graduate'): ?>
                    <div class="glass-card" style="padding: 40px;">
                        <h3 class="profile-section-title"><i class="fa-solid fa-file-pdf" style="color: #D32F2F;"></i> Mezuniyet Belgesi</h3>
                        <p style="font-size: 13px; color: #64748b; margin-bottom: 20px;">Mentorluk onayı için PDF yüklemelisiniz.</p>
                        <?php if($document_status): ?>
                            <div class="alert" style="background: #e0f2fe; color: #0369a1; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 12px;"><?= $document_status ?></div>
                        <?php endif; ?>
                        <form action="company/upload_document.php" method="POST" enctype="multipart/form-data">
                            <label for="graduate_document" class="file-upload-label">
                                <i class="fa-solid fa-cloud-arrow-up" style="font-size: 25px; margin-bottom: 10px;"></i>
                                <span>PDF Seç</span>
                                <input type="file" name="graduate_document" id="graduate_document" accept=".pdf" required>
                            </label>
                            <div style="text-align: right; margin-top: 15px;">
                                <button type="submit" class="btn-search-red" style="padding: 10px 25px; font-size: 13px;">Belgeyi Yükle</button>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    <script src="assets/js/app_features.js"></script>
</body>
</html>
