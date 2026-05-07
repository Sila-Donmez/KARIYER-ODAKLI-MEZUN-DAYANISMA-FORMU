<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

require_once 'includes/db.php';
// require_once 'includes/functions.php'; // Eğer bu dosya yoksa yorum satırında kalabilir, varsa başındaki // işaretini kaldır.

// Güvenlik için basit bir clean fonksiyonu (Eğer functions.php içinde yoksa diye buraya da ekledim)
if (!function_exists('clean')) {
    function clean($data) {
        global $conn;
        return mysqli_real_escape_string($conn, htmlspecialchars(strip_tags(trim($data))));
    }
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$success_msg = "";
$error_msg = "";

// ==========================================
// 1. FORM GÖNDERİLDİYSE GÜNCELLEME İŞLEMLERİ (ORİJİNAL KODLARIN)
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
                    $row = $res->fetch_assoc();
                    $skill_id = $row['id'];
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
// 2. MEVCUT VERİLERİ VERİTABANINDAN ÇEKME
// ==========================================
$p_stmt = $conn->prepare("SELECT * FROM profiles WHERE user_id = ?");
$p_stmt->bind_param("i", $user_id);
$p_stmt->execute();
$profile_data = $p_stmt->get_result()->fetch_assoc() ?? ['bio' => '', 'website_url' => '', 'linkedin_url' => ''];

$role_data = [];
$document_status = ""; // Belge durumu için değişken

if ($role == 'student') {
    $s_stmt = $conn->prepare("SELECT department, student_number FROM students WHERE user_id = ?");
    $s_stmt->bind_param("i", $user_id);
    $s_stmt->execute();
    $role_data = $s_stmt->get_result()->fetch_assoc();
} elseif ($role == 'graduate') {
    $g_stmt = $conn->prepare("SELECT graduate_year, is_open_to_mentorship, document_link FROM graduates WHERE user_id = ?");
    $g_stmt->bind_param("i", $user_id);
    $g_stmt->execute();
    $role_data = $g_stmt->get_result()->fetch_assoc();
    
    // Eğer veritabanında belge linki varsa durumu güncelle
    if (!empty($role_data['document_link'])) {
        $document_status = "Mezuniyet belgeniz sistemde kayıtlı. Yenisini yüklerseniz eskisi silinir.";
    }
}

$u_sk_stmt = $conn->prepare("SELECT s.skill_name FROM user_skills us JOIN skills s ON us.skill_id = s.id WHERE us.user_id = ?");
$u_sk_stmt->bind_param("i", $user_id);
$u_sk_stmt->execute();
$user_skills_res = $u_sk_stmt->get_result();
$user_skill_names = [];
while ($row = $user_skills_res->fetch_assoc()) {
    $user_skill_names[] = $row['skill_name'];
}
$skills_string = implode(",", $user_skill_names); 
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilimi Düzenle | KBÜ Mentorluk</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/app.css"> 
    <link rel="stylesheet" href="assets/css/profile.css"> 
    <style>
        /* Sadece bu sayfaya özel birkaç form stili */
        .btn-search-red {
            height: 48px; padding: 0 35px; background-color: #D32F2F; color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 15px; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 6px -1px rgba(211, 47, 47, 0.3); display: inline-flex; align-items: center; justify-content: center; text-decoration: none;
        }
        .btn-search-red:hover { background-color: #B71C1C; box-shadow: 0 6px 8px -1px rgba(211, 47, 47, 0.4); transform: translateY(-1px); }
        .file-upload-label { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 30px; border: 2px dashed #cbd5e1; border-radius: 12px; background-color: #f8fafc; color: #64748b; cursor: pointer; transition: all 0.3s; font-weight: 600; }
        .file-upload-label i { font-size: 30px; margin-bottom: 10px; color: #94a3b8; transition: all 0.3s; }
        .file-upload-label:hover { border-color: #D32F2F; background-color: #fef2f2; color: #D32F2F; }
        .file-upload-label:hover i { color: #D32F2F; transform: translateY(-5px); }
        .file-upload-label input[type="file"] { display: none; }
    </style>
</head>
<body>

    <div class="app-container">
        
        <!-- MERKEZİ MENÜ ÇAĞRILDI -->
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">Profilimi Düzenle</h1>
            </div>

            <div class="profile-container">
                
                <!-- SOL SÜTUN (Kişi Bilgileri + Deneyim Butonu) -->
                <div class="profile-sidebar">
                    <div class="glass-card" style="padding: 30px 20px;">
                        <div class="profile-avatar">
                            <?= mb_substr($_SESSION['first_name'], 0, 1) . mb_substr($_SESSION['last_name'], 0, 1) ?>
                        </div>
                        <h2 class="profile-name"><?= htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']) ?></h2>
                        <div class="profile-role">
                            <?php 
                                if($role == 'student') echo 'Üniversite Öğrencisi';
                                elseif($role == 'graduate') echo 'Mezun (Mentor)';
                                else echo 'Yönetici';
                            ?>
                        </div>
                        <?php if($role == 'student' && !empty($role_data['student_number'])): ?>
                            <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 10px;">
                                <i class="fa-solid fa-id-card"></i> Öğrenci No: <?= htmlspecialchars($role_data['student_number']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- YENİ: KARİYER & DENEYİM KARTI BURAYA EKLENDİ -->
                    <div class="glass-card" style="padding: 25px 20px; margin-top: 20px;">
                        <h3 class="profile-section-title" style="font-size: 16px; text-align: center;"><i class="fa-solid fa-briefcase" style="color: var(--kbu-lacivert);"></i> Kariyer & Deneyim</h3>
                        <p style="font-size: 13px; color: #64748b; text-align: center; margin-bottom: 15px;">Çalıştığınız şirketleri ve pozisyonları ekleyin.</p>
                        <a href="company/add_experience.php" class="btn-search-red" style="width: 100%;">
                            <i class="fa-solid fa-plus" style="margin-right: 8px;"></i> Yeni Deneyim Ekle
                        </a>
                    </div>
                </div>

                <!-- SAĞ SÜTUN (Formlar) -->
                <div class="profile-form-area">
                    
                    <!-- BİRİNCİ FORM: ORİJİNAL PROFİL BİLGİLERİ GÜNCELLEME FORMU -->
                    <div class="glass-card" style="padding: 40px; margin-bottom: 30px;">
                        
                        <?php if (!empty($success_msg)): ?>
                            <div class="alert" style="background: #ECFDF5; border: 1px solid #6EE7B7; color: #047857;">
                                <i class="fa-solid fa-circle-check" style="margin-right: 5px;"></i> <?= $success_msg ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($error_msg)): ?>
                            <div class="alert alert-error">
                                <i class="fa-solid fa-triangle-exclamation" style="margin-right: 5px;"></i> <?= $error_msg ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            
                            <h3 class="profile-section-title"><i class="fa-solid fa-user-pen"></i> Temel Bilgiler</h3>
                            <div class="form-group">
                                <label>Hakkımda (Biyografi)</label>
                                <textarea name="bio" class="form-control" rows="4" placeholder="Kendinizden veya hedeflerinizden bahsedin..."><?= htmlspecialchars($profile_data['bio']) ?></textarea>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>LinkedIn Profil URL</label>
                                    <input type="url" name="linkedin_url" class="form-control" value="<?= htmlspecialchars($profile_data['linkedin_url']) ?>" placeholder="https://linkedin.com/in/...">
                                </div>
                                <div class="form-group">
                                    <label>Kişisel Web Sitesi</label>
                                    <input type="url" name="website_url" class="form-control" value="<?= htmlspecialchars($profile_data['website_url']) ?>" placeholder="https://github.com/...">
                                </div>
                            </div>

                            <h3 class="profile-section-title" style="margin-top: 20px;"><i class="fa-solid fa-graduation-cap"></i> Akademik Bilgiler</h3>
                            <?php if ($role == 'student'): ?>
                                <div class="form-group">
                                    <label>Okuduğunuz Bölüm</label>
                                    <input type="text" name="department" class="form-control" value="<?= htmlspecialchars($role_data['department'] ?? '') ?>" placeholder="Örn: Bilgisayar Mühendisliği">
                                </div>
                            <?php elseif ($role == 'graduate'): ?>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Mezuniyet Yılı</label>
                                        <input type="number" name="graduate_year" class="form-control" min="1990" max="<?= date('Y') ?>" value="<?= htmlspecialchars($role_data['graduate_year'] ?? '') ?>" placeholder="Örn: 2021">
                                    </div>
                                    <div class="form-group" style="display: flex; align-items: center; margin-top: 15px;">
                                        <label style="display: flex; align-items: center; cursor: pointer; text-transform: none; font-size: 14px; letter-spacing: normal;">
                                            <input type="checkbox" name="is_open_to_mentorship" value="1" <?= (!empty($role_data['is_open_to_mentorship']) && $role_data['is_open_to_mentorship'] == 1) ? 'checked' : '' ?> style="width: 20px; height: 20px; margin-right: 10px; accent-color: #D32F2F;">
                                            Öğrencilere mentorluk yapmaya açığım.
                                        </label>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <h3 class="profile-section-title" style="margin-top: 20px;"><i class="fa-solid fa-code"></i> Yetenekler</h3>
                            
                            <div class="form-group">
                                <label>Yeteneklerinizi Ekleyin</label>
                                <div class="skill-input-group">
                                    <input type="text" id="skill_input" class="form-control" placeholder="Örn: PHP, Java, Yapay Zeka (Enter'a basın)">
                                    <button type="button" id="add_skill_btn" class="btn-add-skill"><i class="fa-solid fa-plus"></i> Ekle</button>
                                </div>
                                <div id="skills_container" class="skills-container"></div>
                                <input type="hidden" name="skills" id="hidden_skills" value="<?= htmlspecialchars($skills_string) ?>">
                                <p style="font-size: 11px; color: var(--text-muted); margin-top: 5px; font-weight: 600;">Yeteneği yazıp 'Ekle' butonuna veya klavyenizden 'Enter' tuşuna basın.</p>
                            </div>

                            <div style="margin-top: 30px; text-align: right;">
                                <button type="submit" class="btn-primary" style="width: auto; padding: 12px 30px;">
                                    <i class="fa-solid fa-floppy-disk" style="margin-right: 8px;"></i> Profilimi Kaydet
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- İKİNCİ FORM: MEZUN BELGESİ YÜKLEME FORMU (Sadece mezunlar görür) -->
                    <?php if ($role == 'graduate'): ?>
                    <div class="glass-card" style="padding: 40px;">
                        <h3 class="profile-section-title"><i class="fa-solid fa-file-pdf" style="color: #D32F2F;"></i> Mezuniyet Belgesi</h3>
                        <p style="font-size: 14px; color: #64748b; margin-bottom: 20px;">Mentorluk yapabilmek için PDF formatında e-devlet veya diploma belgenizi yüklemelisiniz.</p>
                        
                        <?php if($document_status): ?>
                            <div class="alert" style="background: #e0f2fe; border: 1px solid #bae6fd; color: #0369a1; margin-bottom: 20px;">
                                <i class="fa-solid fa-circle-info"></i> <?= $document_status ?>
                            </div>
                        <?php endif; ?>

                        <!-- Form action upload_document.php dosyasına gider -->
                        <form action="company/upload_document.php" method="POST" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="graduate_document" class="file-upload-label">
                                    <i class="fa-solid fa-cloud-arrow-up"></i> PDF Belgesi Seç
                                    <input type="file" name="graduate_document" id="graduate_document" accept=".pdf" required>
                                </label>
                            </div>
                            <div style="text-align: right; margin-top: 15px;">
                                <button type="submit" class="btn-search-red" style="width: auto;"><i class="fa-solid fa-upload" style="margin-right: 8px;"></i> Belgeyi Yükle</button>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
        </main>
    </div>

    <!-- ORİJİNAL SKILL (YETENEK) JAVASCRIPT KODU -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const skillInput = document.getElementById('skill_input');
            const addSkillBtn = document.getElementById('add_skill_btn');
            const skillsContainer = document.getElementById('skills_container');
            const hiddenSkillsInput = document.getElementById('hidden_skills');

            let skillsArray = hiddenSkillsInput.value ? hiddenSkillsInput.value.split(',').map(s => s.trim()).filter(s => s !== '') : [];

            function renderSkills() {
                skillsContainer.innerHTML = '';
                skillsArray.forEach((skill, index) => {
                    const badge = document.createElement('div');
                    badge.className = 'skill-badge';
                    badge.innerHTML = `
                        ${skill}
                        <i class="fa-solid fa-circle-xmark remove-skill" onclick="removeSkill(${index})"></i>
                    `;
                    skillsContainer.appendChild(badge);
                });
                hiddenSkillsInput.value = skillsArray.join(',');
            }

            function addSkill() {
                const val = skillInput.value.trim();
                if (val) {
                    const newSkills = val.split(',').map(s => s.trim()).filter(s => s !== '');
                    newSkills.forEach(s => {
                        if(!skillsArray.some(existingSkill => existingSkill.toLowerCase() === s.toLowerCase())) {
                            skillsArray.push(s);
                        }
                    });
                    skillInput.value = ''; 
                    renderSkills(); 
                }
            }

            window.removeSkill = function(index) {
                skillsArray.splice(index, 1);
                renderSkills();
            };

            addSkillBtn.addEventListener('click', addSkill);
            skillInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault(); 
                    addSkill();
                }
            });
            renderSkills();
        });
    </script>
</body>
</html>