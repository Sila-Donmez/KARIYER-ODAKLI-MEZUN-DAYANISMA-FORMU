<?php
require_once "../includes/db.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
$student_id = $_SESSION['user_id'];
$ads_id = isset($_GET['ads_id']) ? (int)$_GET['ads_id'] : 0;

if ($ads_id === 0) {
    die("Geçersiz ilan. Lütfen listeleme sayfasına dönün.");
}

$error_message = "";
$already_applied = false;

$ad_sql = "SELECT ma.title, ma.expertise, u.first_name, u.last_name FROM mentor_ads ma JOIN graduates g ON ma.graduate_id = g.user_id JOIN users u ON g.user_id = u.id WHERE ma.ads_id = $ads_id";
$ad_result = mysqli_query($conn, $ad_sql);
$ad = mysqli_fetch_assoc($ad_result);

if (!$ad) { die("İlan bulunamadı veya yayından kaldırılmış."); }

$check_sql = "SELECT application_id FROM mentor_applications WHERE ads_id = $ads_id AND student_id = $student_id";
$check_result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($check_result) > 0) {
    $already_applied = true;
    $error_message = "Bu ilana zaten başvuru yaptınız. Aynı ilana tekrar başvuramazsınız.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$already_applied) {
    $message = mysqli_real_escape_string($conn, htmlspecialchars($_POST['message']));
    $insert_sql = "INSERT INTO mentor_applications (ads_id, student_id, message) VALUES ($ads_id, $student_id, '$message')";
    if (mysqli_query($conn, $insert_sql)) {
        header("Location: student_ads_list.php?success=1");
        exit;
    } else {
        $error_message = "Başvuru sırasında bir hata oluştu: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>İlana Başvur | KBÜ Mentorluk</title>
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
                <h1 class="page-title">Mentorlük Başvurusu</h1>
                <p class="page-subtitle">Mentorunuza kendinizi tanıtın ve hedeflerinizi anlatın.</p>
            </div>

            <div style="max-width: 700px;">
                <div class="ad-card" style="margin-bottom: 30px; border-left-color: var(--kbu-kirmizi);">
                    <h3 class="ad-title"><?= htmlspecialchars($ad['title']) ?></h3>
                    <p class="ad-info"><i class="fa-solid fa-user-tie"></i> <strong>Mentor:</strong> <?= htmlspecialchars($ad['first_name'] . ' ' . $ad['last_name']) ?></p>
                    <p class="ad-info"><i class="fa-solid fa-bolt"></i> <strong>Uzmanlık:</strong> <?= htmlspecialchars($ad['expertise']) ?></p>
                </div>

                <?php if (!empty($error_message)): ?>
                    <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
                        <i class="fa-solid fa-circle-exclamation"></i> <?= $error_message ?>
                    </div>
                <?php endif; ?>

                <div class="glass-card">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="message">Mentora İletmek İstediğiniz Mesaj</label>
                            <textarea class="form-control" name="message" id="message" rows="6" required <?= $already_applied ? 'disabled' : '' ?> placeholder="Neden bu mentorluğu almak istiyorsunuz? Beklentileriniz nelerdir? Kısaca bahsedin..."></textarea>
                        </div>
                        <button type="submit" class="btn-primary" <?= $already_applied ? 'disabled style="background:#94a3b8; cursor:not-allowed;"' : '' ?>>
                            <?= $already_applied ? 'Başvuru Yapıldı' : '<i class="fa-solid fa-paper-plane"></i> Başvuruyu Gönder' ?>
                        </button>
                    </form>
                    <div style="margin-top: 15px; text-align: center;">
                        <a href="student_ads_list.php" style="color: var(--kbu-lacivert); text-decoration: none; font-size: 14px;"><i class="fa-solid fa-arrow-left"></i> İlan Listesine Dön</a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>