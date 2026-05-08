<?php
require_once "../includes/db.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
$graduate_id = $_SESSION['user_id'];
$ads_id = isset($_GET['ads_id']) ? (int)$_GET['ads_id'] : 0;

if ($ads_id === 0) {
    die("Geçersiz ilan. Lütfen ilanlarım sayfasına dönün.");
}

$check_owner_sql = "SELECT ads_id FROM mentor_ads WHERE ads_id = $ads_id AND graduate_id = $graduate_id";
$owner_result = mysqli_query($conn, $check_owner_sql);
if (mysqli_num_rows($owner_result) === 0) { die("Bu ilanın başvurularını görüntüleme yetkiniz yok."); }

if (isset($_POST['update_status'])) {
    $app_id = (int)$_POST['app_id'];
    $new_status = mysqli_real_escape_string($conn, $_POST['new_status']);
    $update_sql = "UPDATE mentor_applications SET status = '$new_status' WHERE application_id = $app_id";
    mysqli_query($conn, $update_sql);
    
    // İşlem sonrası aynı sayfaya geri yönlendir
    header("Location: mentor_applications.php?ads_id=$ads_id");
    exit;
}

$sql = "
    SELECT ma.application_id, ma.message, ma.status, u.first_name, u.last_name
    FROM mentor_applications ma
    JOIN students s ON ma.student_id = s.user_id
    JOIN users u ON s.user_id = u.id
    WHERE ma.ads_id = $ads_id
";
$result = mysqli_query($conn, $sql);
$applications = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Gelen Başvurular | KBÜ Mentorluk</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/app.css">
    <link rel="stylesheet" href="../assets/css/mentor.css">
</head>
<body>
    <div class="app-container">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h1 class="page-title">Gelen Başvurular</h1>
                    <p class="page-subtitle">İlanınıza yapılan başvuruları değerlendirin.</p>
                </div>
                <a href="mentor_my_ads.php" class="btn-sm" style="background:#f1f5f9; color:var(--kbu-lacivert); border: 1px solid #e2e8f0;"><i class="fa-solid fa-arrow-left"></i> İlanlarıma Dön</a>
            </div>

            <?php if (empty($applications)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-inbox"></i>
                    <p>Bu ilana henüz başvuru yapılmamış.</p>
                </div>
            <?php else: ?>
                <div class="req-table-container">
                    <table class="req-table">
                        <thead>
                            <tr>
                                <th>Öğrenci</th>
                                <th style="width: 40%;">Mesaj</th>
                                <th>Durum</th>
                                <th style="text-align: right;">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applications as $app): ?>
                                <tr>
                                    <td><strong><i class="fa-solid fa-user-graduate" style="color:var(--kbu-lacivert); margin-right:5px;"></i> <?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?></strong></td>
                                    <td style="color: var(--text-muted); font-size:13px;"><?= nl2br(htmlspecialchars($app['message'])) ?></td>
                                    <td>
                                        <?php 
                                            if($app['status'] === 'Waiting') { $bg = 'bg-waiting'; $text = 'Bekliyor'; }
                                            elseif($app['status'] === 'Approved') { $bg = 'bg-approved'; $text = '<i class="fa-solid fa-check"></i> Onaylandı'; }
                                            else { $bg = 'bg-rejected'; $text = '<i class="fa-solid fa-xmark"></i> Reddedildi'; }
                                        ?>
                                        <span class="status-badge <?= $bg ?>"><?= $text ?></span>
                                    </td>
                                    <td style="text-align: right;">
                                        <?php if ($app['status'] === 'Waiting'): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="app_id" value="<?= $app['application_id'] ?>">
                                                <input type="hidden" name="new_status" value="Approved">
                                                <button type="submit" name="update_status" class="btn-sm btn-success"><i class="fa-solid fa-check"></i> Onayla</button>
                                            </form>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="app_id" value="<?= $app['application_id'] ?>">
                                                <input type="hidden" name="new_status" value="Rejected">
                                                <button type="submit" name="update_status" class="btn-sm btn-danger"><i class="fa-solid fa-xmark"></i> Reddet</button>
                                            </form>
                                        <?php else: ?>
                                            <span style="color:#94a3b8; font-size:12px; font-weight:700;"><i class="fa-solid fa-lock"></i> İşlem Tamamlandı</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>