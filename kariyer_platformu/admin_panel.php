<?php
session_start();
require_once 'includes/db.php';

// Sadece Admin Girebilir
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];
$check_sql = "SELECT role FROM users WHERE id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $admin_id);
$check_stmt->execute();
$admin_data = $check_stmt->get_result()->fetch_assoc();

if ($admin_data['role'] !== 'admin') {
    // Admin değilse normal kullanıcının profiline postala
    header("Location: view_profile.php");
    exit();
}

// Onay Bekleyen (is_verified = 0) ve Belge Yüklemiş olanları getir
$query = "SELECT u.id, u.first_name, u.last_name, u.email, g.document_link, g.graduate_year 
          FROM users u 
          JOIN graduates g ON u.id = g.user_id 
          WHERE u.is_verified = 0 AND g.document_link IS NOT NULL AND g.document_link != ''";

$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Yönetici Paneli | KBÜ Kariyer</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/app.css">
    <!-- Tablo tasarımı için company.css'i çağırıyoruz -->
    <link rel="stylesheet" href="assets/css/company.css">
    <!-- Admine özel kart ve buton tasarımları -->
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <div class="app-container">
        
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title"><i class="fa-solid fa-shield-halved" style="color: #D32F2F; margin-right: 10px;"></i>Yönetici Paneli</h1>
                <p class="page-subtitle">Sisteme yüklenen mezuniyet belgelerini inceleyip onaylayın.</p>
            </div>

            <div class="admin-stat-card">
                <h3 style="margin-top: 0; color: #1e293b;">Bekleyen Onaylar</h3>
                <p style="color: #64748b; margin-bottom: 0;">Şu anda onayınızı bekleyen <strong style="color: #D32F2F; font-size: 16px;"><?= mysqli_num_rows($result) ?></strong> adet mezun belgesi bulunuyor.</p>
            </div>

            <div class="glass-card" style="padding: 0; overflow: hidden;">
                <div style="width: 100%; overflow-x: auto;">
                    <table class="custom-company-table" style="margin-top: 0;">
                        <thead>
                            <tr>
                                <th>Ad Soyad</th>
                                <th>E-Posta</th>
                                <th>Mezuniyet Yılı</th>
                                <th style="text-align: right;">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($result && mysqli_num_rows($result) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td>
                                        <strong style="color: #1e293b;"><i class="fa-solid fa-user-graduate" style="color: var(--text-muted); margin-right: 8px;"></i><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><span class="industry-pill"><?= htmlspecialchars($row['graduate_year'] == 0 ? 'Belirtilmedi' : $row['graduate_year']) ?></span></td>
                                    <td style="text-align: right;">
                                        <!-- Yola dikkat: DB'ye "uploads/documents/doc_..." şeklinde kaydedilmişti -->
                                        <a href="<?= htmlspecialchars($row['document_link']) ?>" target="_blank" class="btn-view">
                                            <i class="fa-solid fa-file-pdf" style="margin-right: 5px;"></i> Belgeyi Gör
                                        </a>
                                        <a href="approve_user.php?id=<?= $row['id'] ?>" class="btn-approve" onclick="return confirm('Bu kullanıcıyı onaylamak istediğinize emin misiniz?');">
                                            <i class="fa-solid fa-check" style="margin-right: 5px;"></i> Onayla
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align:center; padding: 60px; color: var(--text-muted);">
                                        <i class="fa-solid fa-check-double" style="font-size: 45px; margin-bottom: 15px; color: #cbd5e1;"></i>
                                        <p style="margin: 0; font-size: 16px;">Harika! Bekleyen hiçbir onay işlemi yok.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>
</body>
</html>