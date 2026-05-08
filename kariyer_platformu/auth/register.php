<?php
session_start();
require_once '../includes/db.php'; 
require_once '../includes/functions.php';

$error = "";
$success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = clean($_POST['first_name']);
    $last_name = clean($_POST['last_name']);
    $email = clean($_POST['email']);
    $password = clean($_POST['password']);
    $role = clean($_POST['role']);
    $gender = clean($_POST['gender']);
    $student_number = "";

    // PHP Validation: Sunucu tarafında boş alan denetimi (Güvenlik Katmanı)
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($role) || empty($gender)) {
        $error = "Lütfen tüm alanları doldurun.";
    } else {
        // Öğrenci e-posta kontrolü ve numara ayrıştırma (Regex kullanımı)
        if ($role == 'student') {
            if (preg_match('/^([0-9]+)@ogrenci\.karabuk\.edu\.tr$/', $email, $matches)) {
                $student_number = $matches[1]; 
            } else {
                $error = "Öğrenciler sadece okul e-postası (@ogrenci.karabuk.edu.tr) ile kayıt olabilir!";
            }
        }

        if (empty($error)) {
            // E-posta mükerrerlik kontrolü (DBS - Unique Constraint Check)
            $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            if ($check_stmt) {
                $check_stmt->bind_param("s", $email);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();

                if ($check_result->num_rows > 0) {
                    $error = "Bu e-posta adresi zaten sisteme kayıtlı!";
                }
                $check_stmt->close();
            } else {
                $error = "Veritabanı kontrol hatası.";
            }
        }

        // Veritabanı İşlemleri (Transaction kullanarak veri bütünlüğünü koruyoruz)
        if (empty($error)) {
            $conn->begin_transaction(); 

            try {
                $hashed_password = hash_password($password); // IBP - SHA256 hashing

                // 1. Users tablosuna ana kaydı ekle
                $sql = "INSERT INTO users (first_name, last_name, email, password, role, gender) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssss", $first_name, $last_name, $email, $hashed_password, $role, $gender);
                $stmt->execute();
                
                $new_user_id = $stmt->insert_id; 
                $stmt->close();

                // 2. Role göre alt tablolara (students/graduates) kayıt yap (DBS - Relationship Management)
                if ($role == 'student') {
                    $dept = ""; 
                    $s_sql = "INSERT INTO students (user_id, student_number, department) VALUES (?, ?, ?)";
                    $s_stmt = $conn->prepare($s_sql);
                    $s_stmt->bind_param("iss", $new_user_id, $student_number, $dept);
                    $s_stmt->execute();
                    $s_stmt->close();
                } elseif ($role == 'graduate') {
                    $grad_year = 0; 
                    $g_sql = "INSERT INTO graduates (user_id, graduate_year) VALUES (?, ?)";
                    $g_stmt = $conn->prepare($g_sql);
                    $g_stmt->bind_param("ii", $new_user_id, $grad_year);
                    $g_stmt->execute();
                    $g_stmt->close();
                }

                $conn->commit(); 
                $success = true;

            } catch (Exception $e) {
                $conn->rollback(); 
                $error = "Kayıt sırasında teknik bir hata oluştu: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KBÜ Kariyer & Mentorluk | Kayıt Ol</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body class="auth-page">

    <div class="login-wrapper" style="max-width: 500px;">
        <div class="glass-card" style="padding: 30px;">
            
            <div style="text-align: center; margin-bottom: 20px;">
                <div class="icon-box" style="padding: 10px; margin-bottom: 5px;">
                    <i class="fa-solid fa-user-plus" style="font-size: 20px;"></i>
                </div>
                <h2>AĞA <span>KATILIN</span></h2>
                <p class="subtitle" style="margin-bottom: 10px;">Öğrenmek veya öğretmek için ilk adım</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error" style="padding: 10px; margin-bottom: 15px;">
                    <i class="fa-solid fa-triangle-exclamation" style="margin-right: 5px;"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert" style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.4); color: #6ee7b7; padding: 10px; margin-bottom: 15px;">
                    <i class="fa-solid fa-circle-check" style="margin-right: 5px;"></i>
                    Kayıt Başarılı! <a href="login.php" style="color: white; font-weight: bold; text-decoration: underline; margin-left: 5px;">Giriş Yap</a>
                </div>
            <?php else: ?>

                <form method="POST">
                    
                    <div class="flex-group">
                        <div class="form-group">
                            <label>Ad</label>
                            <div style="position: relative;">
                                <i class="fa-solid fa-user input-icon"></i>
                                <input type="text" name="first_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Soyad</label>
                            <div style="position: relative;">
                                <i class="fa-solid fa-user input-icon"></i>
                                <input type="text" name="last_name" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>E-posta Adresi</label>
                        <div style="position: relative;">
                            <i class="fa-solid fa-envelope input-icon"></i>
                            <input type="email" name="email" class="form-control" placeholder="Öğrenciler için: @ogrenci.karabuk.edu.tr" required>
                        </div>
                    </div>

                    <div class="flex-group">
                        <div class="form-group">
                            <label>Rolünüz</label>
                            <div style="position: relative;">
                                <i class="fa-solid fa-user-tag input-icon"></i>
                                <select name="role" class="form-control" required>
                                    <option value="" disabled selected>Seçiniz...</option>
                                    <option value="student">Öğrenci</option>
                                    <option value="graduate">Mezun</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Cinsiyet</label>
                            <div style="position: relative;">
                                <i class="fa-solid fa-venus-mars input-icon"></i>
                                <select name="gender" class="form-control" required>
                                    <option value="" disabled selected>Seçiniz...</option>
                                    <option value="male">Erkek</option>
                                    <option value="female">Kadın</option>
                                    <option value="other">Diğer</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Güvenli Şifre</label>
                        <div style="position: relative;">
                            <i class="fa-solid fa-lock input-icon"></i>
                            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary" style="margin-top: 5px; padding: 12px;">
                        Kayıt Ol <i class="fa-solid fa-user-plus" style="margin-left: 8px;"></i>
                    </button>
                </form>

            <?php endif; ?>

            <div class="auth-footer" style="margin-top: 15px; padding-top: 15px;">
                <p>Zaten hesabınız var mı? <a href="login.php">Giriş Yap</a></p>
                <p style="margin-top: 5px; font-size: 11px;">
                    <a href="../index.php" style="color: rgba(255,255,255,0.4); text-decoration: underline;">Ana Sayfaya Dön</a>
                </p>
            </div>
            
        </div>
    </div>

    <div class="bg-text-kbu">KBÜ</div>

    <script src="../assets/js/auth_validation.js"></script>

</body>
</html>