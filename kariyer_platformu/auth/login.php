<?php
session_start();
require_once '../includes/db.php'; 
require_once '../includes/functions.php'; 

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = clean($_POST['email']);
    $password = clean($_POST['password']);

    // PHP Validation: Sunucu tarafında boş alan kontrolü
    if (!empty($email) && !empty($password)) {
        // Hazırlıklı ifade (DBS - SQL Injection Koruması)
        $stmt = $conn->prepare("SELECT id, first_name, last_name, password, role FROM users WHERE email = ?");
        
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            // Şifre doğrulama (IBP - SHA256 Güvenliği)
            if ($user && verify_password($password, $user['password'])) {
                
                // Session yönetimi
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['role'] = $user['role'];
                
                // Giriş loglama (Bonus Fonksiyon)
                log_auth_action($user['id'], true);
                
                // Role tabanlı yönlendirme
                if ($user['role'] === 'admin') {
                    header("Location: ../admin_panel.php");
                } else {
                    header("Location: ../dashboard.php");
                }
                exit();
                
            } else {
                $error = "Hatalı e-posta veya şifre!";
                $log_user_id = $user ? $user['id'] : null;
                log_auth_action($log_user_id, false);
            }
            $stmt->close();
        } else {
            $error = "Veritabanı bağlantı hatası oluştu.";
        }
    } else {
        $error = "Lütfen tüm alanları doldurun.";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KBÜ Kariyer & Mentorluk | Giriş</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body class="auth-page">

    <div class="login-wrapper">
        <div class="glass-card">
            
            <div class="auth-header">
                <div class="icon-box">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
                <h2>KBÜ <span>GİRİŞ</span></h2>
                <p class="subtitle">Mühendislik Mentörlük Portalı</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fa-solid fa-triangle-exclamation alert-icon"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>E-posta Adresi</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-envelope input-icon"></i>
                        <input type="email" name="email" class="form-control" placeholder="mail@ogrenci.karabuk.edu.tr" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Şifre</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-lock input-icon"></i>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit" class="btn-primary mt-15">
                    Sisteme Bağlan <i class="fa-solid fa-right-to-bracket btn-icon"></i>
                </button>
            </form>

            <div class="auth-footer">
                <p>Hesabınız yok mu? <a href="register.php">Kayıt Ol</a></p>
                <p class="back-link-wrapper">
                    <a href="../index.php" class="back-link">Ana Sayfaya Dön</a>
                </p>
            </div>
            
        </div>
    </div>

    <div class="bg-text-kbu">KBÜ</div>

    <script src="../assets/js/auth_validation.js"></script>

</body>
</html>