<?php
session_start();
require_once 'db.php';
require_once 'functions.php';

$error = "";
$success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = clean($_POST['first_name']);
    $last_name = clean($_POST['last_name']);
    $email = clean($_POST['email']);
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $student_number = "";

    if ($role == 'student') {
        if (preg_match('/^([0-9]+)@ogrenci\.karabuk\.edu\.tr$/', $email, $matches)) {
            $student_number = $matches[1];
        } else {
            $error = "Öğrenciler sadece okul e-postası ile kayıt olabilir!";
        }
    }

    if (empty($error)) {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = "Bu e-posta adresi zaten kayıtlı!";
        }
    }

    if (empty($error)) {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $first_name, $last_name, $email, $password, $role);
            $stmt->execute();
            $user_id = $conn->insert_id;

            if ($role == 'student') {
                $s_stmt = $conn->prepare("INSERT INTO students (user_id, student_number, department) VALUES (?, ?, '')");
                $s_stmt->bind_param("is", $user_id, $student_number);
                $s_stmt->execute();
            } else {
                $g_stmt = $conn->prepare("INSERT INTO graduates (user_id, graduate_year) VALUES (?, 0)");
                $g_stmt->bind_param("i", $user_id);
                $g_stmt->execute();
            }
            $conn->commit();
            $success = true;
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Kayıt hatası: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>KBÜ Mentörlük | Kayıt</title>
    <link rel="stylesheet" href="main.css">
    <link rel="stylesheet" href="auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="auth-container glass">
        <div class="image-side">
            <div style="position:relative; z-index:10;">
                <div style="display:flex; align-items:center; gap:8px; color:#3b82f6; margin-bottom:24px;">
                    <i class="fa-solid fa-graduation-cap" style="font-size:24px;"></i>
                    <span style="font-size:10px; font-weight:900; text-transform:uppercase; tracking-widest:0.3em;">Join Us</span>
                </div>
                <h2 style="font-size:2rem; font-weight:800; color:white; line-height:1.2;">BİLGİYİ <br><span style="color:#3b82f6;">PAYLAŞ</span> <br>GÜÇLEN</h2>
            </div>
        </div>
        <div class="form-side">
            <h1 style="font-size:1.5rem; font-weight:700; color:white; margin-bottom:30px;">Kayıt Ol</h1>
            
            <?php if ($error): ?> <div class="alert alert-error"><?php echo $error; ?></div> <?php endif; ?>
            <?php if ($success): ?> <div class="alert alert-success">Kayıt Başarılı! <a href="login.php" style="font-weight:800; text-decoration:underline; margin-left:5px;">Giriş Yap</a></div> <?php endif; ?>

            <form method="POST">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                    <div class="input-group">
                        <label>Ad</label>
                        <div class="input-wrapper"><input type="text" name="first_name" required></div>
                    </div>
                    <div class="input-group">
                        <label>Soyad</label>
                        <div class="input-wrapper"><input type="text" name="last_name" required></div>
                    </div>
                </div>
                <div class="input-group">
                    <label>E-Posta</label>
                    <div class="input-wrapper"><input type="email" name="email" required></div>
                </div>
                <div class="input-group">
                    <label>Şifre</label>
                    <div class="input-wrapper"><input type="password" name="password" required></div>
                </div>
                <div class="input-group" style="margin-bottom:30px;">
                    <label>Katılım Amacı</label>
                    <div class="input-wrapper">
                        <select name="role" required>
                            <option value="student">Öğrenci (Mentee)</option>
                            <option value="graduate">Mezun (Mentor)</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Kaydı Tamamla</button>
            </form>
            <div style="text-align:center; margin-top:20px;">
                <p style="font-size:0.75rem; color:var(--text-dim);">Hesabın var mı? <a href="login.php" style="color:var(--accent-blue); font-weight:700;">Giriş Yap</a></p>
            </div>
        </div>
    </div>
</body>
</html>