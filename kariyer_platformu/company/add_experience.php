<?php
session_start();
require_once '../includes/db.php';

// Güvenlik: Giriş yapılmamışsa login sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// FORM GÖNDERİLDİYSE İŞLE
if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['company_name'])) {
    
    $company_name = mysqli_real_escape_string($conn, trim($_POST['company_name']));
    $industry = mysqli_real_escape_string($conn, trim($_POST['industry']));
    $position_name = mysqli_real_escape_string($conn, trim($_POST['position_name']));
    $start_date = mysqli_real_escape_string($conn, $_POST['start_date']);

    // "Halen çalışıyorum" seçiliyse bitiş tarihini NULL yap
    if (isset($_POST['is_current']) && $_POST['is_current'] == '1') {
        $end_date = "NULL"; 
    } else {
        $val = mysqli_real_escape_string($conn, $_POST['end_date']);
        $end_date = empty($val) ? "NULL" : "'$val'";
    }

    // Şirket Mevcut mu Kontrol Et (DBS - MySQL Process)
    $comp_check = mysqli_query($conn, "SELECT id FROM companies WHERE name = '$company_name'");
    if (mysqli_num_rows($comp_check) > 0) {
        $company_id = mysqli_fetch_assoc($comp_check)['id'];
    } else {
        mysqli_query($conn, "INSERT INTO companies (name, industry) VALUES ('$company_name', '$industry')");
        $company_id = mysqli_insert_id($conn);
    }

    // Pozisyon Mevcut mu Kontrol Et
    $pos_check = mysqli_query($conn, "SELECT id FROM positions WHERE position_name = '$position_name'");
    if (mysqli_num_rows($pos_check) > 0) {
        $position_id = mysqli_fetch_assoc($pos_check)['id'];
    } else {
        mysqli_query($conn, "INSERT INTO positions (position_name) VALUES ('$position_name')");
        $position_id = mysqli_insert_id($conn);
    }

    // Deneyim Kaydını Oluştur
    $sql = "INSERT INTO experiences (user_id, company_id, position_id, start_date, end_date) 
            VALUES ('$user_id', '$company_id', '$position_id', '$start_date', $end_date)";
    mysqli_query($conn, $sql);

    // İşlem bittikten sonra profile yönlendir
    header("Location: ../profile.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Deneyim Ekle | KBÜ Kariyer</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/app.css">
    <link rel="stylesheet" href="../assets/css/company.css">
    <link rel="stylesheet" href="../assets/css/profile.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="app-container">
        
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <a href="../profile.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Profile Dön</a>
            
            <div class="page-header" style="margin-top: 15px;">
                <h1 class="page-title"><i class="fa-solid fa-plus-circle" style="color: #D32F2F; margin-right: 10px;"></i>Yeni Deneyim Ekle</h1>
                <p class="page-subtitle">Kariyer geçmişinize yeni bir çalışma deneyimi ekleyin.</p>
            </div>

            <div class="glass-card form-card">
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Şirket Adı:</label>
                            <input type="text" name="company_name" class="form-control" placeholder="Örn: Aselsan, Trendyol..." required>
                        </div>
                        
                        <div class="form-group">
                            <label>Sektör:</label>
                            <input type="text" name="industry" class="form-control" placeholder="Örn: Savunma Sanayi, E-Ticaret..." required>
                        </div>

                        <div class="form-group" style="grid-column: span 2;">
                            <label>Pozisyon Adı:</label>
                            <input type="text" name="position_name" class="form-control" placeholder="Örn: Kıdemli Yazılım Mühendisi..." required>
                        </div>
                        
                        <div class="form-group">
                            <label>Başlangıç Tarihi:</label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                        
                        <div class="form-group" id="e_box">
                            <label>Bitiş Tarihi:</label>
                            <input type="date" name="end_date" class="form-control">
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 20px; display: flex; align-items: center;">
                        <input type="checkbox" name="is_current" value="1" id="c" class="custom-checkbox"> 
                        <label for="c" style="cursor: pointer; margin-left: 8px; font-weight: 600; color: #334155;">Halen bu pozisyonda çalışıyorum</label>
                    </div>
                    
                    <hr style="border:none; border-top: 1px solid #e2e8f0; margin: 25px 0;">
                    
                    <div style="display: flex; gap: 15px; justify-content: flex-end;">
                        <a href="../profile.php" class="btn-clear" style="padding: 0 25px;">İptal</a>
                        <button type="submit" class="btn-search-red" style="width: auto; padding: 0 40px;"><i class="fa-solid fa-check"></i> Kaydet</button>
                    </div>
                </form>
            </div>
            
        </main>
    </div>

    <script src="../assets/js/app_features.js"></script>
</body>
</html>