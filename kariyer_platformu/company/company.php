<?php
session_start();
require_once '../includes/db.php';

// Oturum kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$search = isset($_GET['q']) ? mysqli_real_escape_string($conn, $_GET['q']) : '';
$db_error = "";

// SİHİRLİ VE GÜVENLİ SORGUMUZ
$query = "SELECT DISTINCT c.id, c.name, c.industry 
          FROM companies c
          LEFT JOIN experiences e ON c.id = e.company_id
          LEFT JOIN positions p ON e.position_id = p.id
          WHERE 1=1";

if (!empty($search)) {
    $query .= " AND (c.name LIKE '%$search%' 
                OR c.industry LIKE '%$search%' 
                OR p.position_name LIKE '%$search%')";
}

$query .= " ORDER BY c.id DESC";
$result = mysqli_query($conn, $query);

if (!$result) {
    $db_error = "Veritabanı hatası oluştu: " . mysqli_error($conn);
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Şirketler | KBÜ Kariyer</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/app.css">
    <link rel="stylesheet" href="../assets/css/company.css">
</head>
<body>
    <div class="app-container">
        
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title"><i class="fa-solid fa-building" style="color: var(--kbu-lacivert); margin-right: 10px;"></i>Şirketler ve Departmanlar</h1>
                <p class="page-subtitle">Mezunlarımızın çalıştığı şirketleri, sektörleri ve kariyer alanlarını keşfedin.</p>
            </div>

            <?php if (!empty($db_error)): ?>
                <div class="alert" style="background: #fee2e2; border: 1px solid #ef4444; color: #b91c1c; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <i class="fa-solid fa-triangle-exclamation"></i> <?= $db_error ?>
                </div>
            <?php endif; ?>

            <div class="glass-card" style="padding: 25px; margin-bottom: 35px; background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                <form action="company.php" method="GET" style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                    
                    <div style="flex: 1; min-width: 250px; position: relative; display: flex; align-items: center;">
                        <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 18px; color: #94a3b8; font-size: 16px;"></i>
                        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Şirket, sektör veya departman ara..." style="width: 100%; height: 48px; padding-left: 50px; border-radius: 8px; border: 1px solid #cbd5e1; font-family: inherit; font-size: 15px; outline: none; transition: border-color 0.3s;">
                    </div>
                    
                    <button type="submit" class="btn-search-red">
                        <i class="fa-solid fa-filter" style="margin-right: 8px;"></i> Ara
                    </button>
                    
                    <a href="company.php" class="btn-clear">
                        <i class="fa-solid fa-rotate-right" style="margin-right: 8px;"></i> Temizle
                    </a>
                </form>
            </div>

            <div style="width: 100%; overflow-x: auto;">
                <table class="custom-company-table">
                    <thead>
                        <tr>
                            <th style="width: 35%;">Şirket Adı</th>
                            <th style="width: 40%;">Sektör</th>
                            <th style="width: 25%; text-align: right;">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($result && mysqli_num_rows($result) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td>
                                    <strong style="font-size: 16px; color: #1e293b;"><?= htmlspecialchars($row['name']) ?></strong>
                                </td>
                                <td>
                                    <span class="industry-pill"><?= htmlspecialchars($row['industry']) ?></span>
                                </td>
                                <td style="text-align: right;">
                                    <a href="company_detail.php?id=<?= $row['id'] ?>" class="btn-action-view">
                                        Detayları Gör <i class="fa-solid fa-chevron-right" style="font-size: 11px; margin-left: 5px;"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" style="text-align:center; padding: 60px; color: var(--text-muted);">
                                    <i class="fa-solid fa-building-circle-xmark" style="font-size: 45px; margin-bottom: 15px; color: #cbd5e1;"></i>
                                    <p style="margin: 0; font-size: 16px;">Aradığınız kriterlere uygun şirket bulunamadı.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
        </main>
    </div>
</body>
</html>