<?php
require_once "../includes/db.php";
session_start();
 
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$expertise_filter = isset($_GET['expertise']) ? mysqli_real_escape_string($conn, $_GET['expertise']) : '';
 
$expertise_query = "SELECT DISTINCT expertise FROM mentor_ads ORDER BY expertise ASC";
$expertise_result = mysqli_query($conn, $expertise_query);
 
$sql = "
    SELECT 
        ma.ads_id, ma.title, ma.expertise, ma.created_at,
        u.first_name, u.last_name
    FROM mentor_ads ma
    JOIN graduates g ON ma.graduate_id = g.user_id
    JOIN users u ON g.user_id = u.id
    WHERE 1=1
";
 
if ($search !== '') {
    $sql .= " AND (ma.title LIKE '%$search%' OR ma.expertise LIKE '%$search%')";
}
if ($expertise_filter !== '') {
    $sql .= " AND ma.expertise = '$expertise_filter'";
}
 
$sql .= " ORDER BY ma.created_at DESC";
$result = mysqli_query($conn, $sql);
$ads = mysqli_fetch_all($result, MYSQLI_ASSOC);
$total = count($ads);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Mentor Bul | KBÜ Mentorluk</title>
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
                <h1 class="page-title">Mentor Bul</h1>
                <p class="page-subtitle">Kariyerinize yön verecek mezunlarımızla iletişime geçin.</p>
                <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
                    <div class="alert alert-success" style="background:#dcfce7; color:#166534; padding:15px; border-radius:10px; margin-top:15px;">
                        <i class="fa-solid fa-check-circle"></i> Başvurunuz başarıyla gönderildi.
                    </div>
                <?php endif; ?>
            </div>

            <form method="GET" action="" class="mentor-filter-card">
                <input type="text" name="search" placeholder="İlan başlığı veya kelime ara..." value="<?= htmlspecialchars($search) ?>">
                <select name="expertise">
                    <option value="">Tüm Uzmanlık Alanları</option>
                    <?php while ($row = mysqli_fetch_assoc($expertise_result)): ?>
                        <option value="<?= htmlspecialchars($row['expertise']) ?>" <?= $expertise_filter === $row['expertise'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($row['expertise']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <button type="submit" class="btn-sm btn-action"><i class="fa-solid fa-filter"></i> Filtrele</button>
            </form>

            <div class="mentor-grid">
                <?php if ($total === 0): ?>
                    <div class="empty-state" style="grid-column: 1 / -1;">
                        <i class="fa-solid fa-box-open"></i>
                        <p>Arama kriterlerinize uygun ilan bulunamadı.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($ads as $ad): ?>
                        <div class="ad-card">
                            <h3 class="ad-title"><?= htmlspecialchars($ad['title']) ?></h3>
                            <p class="ad-info"><i class="fa-solid fa-user-tie"></i> <strong>Mentor:</strong> <?= htmlspecialchars($ad['first_name'] . ' ' . $ad['last_name']) ?></p>
                            <p class="ad-info"><i class="fa-solid fa-bolt"></i> <strong>Uzmanlık:</strong> <?= htmlspecialchars($ad['expertise']) ?></p>
                            <p class="ad-info"><i class="fa-regular fa-calendar"></i> <?= date('d.m.Y', strtotime($ad['created_at'])) ?></p>
                            <div class="ad-footer">
                                <a href="student_apply.php?ads_id=<?= $ad['ads_id'] ?>" class="btn-sm btn-action">İncele & Başvur</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>