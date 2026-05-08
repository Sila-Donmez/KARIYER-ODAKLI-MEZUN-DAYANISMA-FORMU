<?php
session_start();
// Yol düzeltildi
require_once '../includes/db.php';
require_once '../includes/functions.php';

// 1. GİRİŞ KONTROLÜ
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$company_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($company_id === 0) {
    die("Şirket ID bulunamadı!");
}

// --- ŞİRKET DEĞERLENDİRMESİ SİLME İŞLEMİ ---
if (isset($_GET['delete_rev_id'])) {
    $del_id = intval($_GET['delete_rev_id']);
    
    // Fonksiyonu çağır ve sonucunu kontrol et
    $result = delete_experience($conn, $del_id, $user_id);
    
    if ($result) {
        // Silme başarılıysa sayfayı tamamen tazeleyerek yönlendir
        header("Location: company_detail.php?id=" . $company_id . "&status=deleted");
        exit();
    } else {
        // Silme başarısızsa hatayı URL'de göster
        header("Location: company_detail.php?id=" . $company_id . "&status=error");
        exit();
    }
}
// ------------------------------------------

// 2. RÜTBEYİ VERİTABANINDAN ÖĞREN
$role_query = mysqli_query($conn, "SELECT role FROM users WHERE id = '$user_id'");
$user_data = mysqli_fetch_assoc($role_query);
$current_user_role = $user_data['role'];

// 3. YORUM KAYDETME İŞLEMİ
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['content'])) {
    if ($current_user_role !== 'graduate') {
        die("Yetki Hatası: Sadece mezunlar yorum yapabilir!");
    }
    
    $content = mysqli_real_escape_string($conn, $_POST['content']);
    $rating = (int)$_POST['rating'];
    
    if ($rating < 1 || $rating > 5) {
        die("Hata: Puan sadece 1 ile 5 arasında olabilir!");
    }
    
    $is_anon = isset($_POST['is_anonymous']) ? 1 : 0;
    
    $insert_query = "INSERT INTO company_reviews (company_id, user_id, content, rating, is_anonymous) 
                     VALUES ('$company_id', '$user_id', '$content', '$rating', '$is_anon')";
    
    if(mysqli_query($conn, $insert_query)) {
        header("Location: company_detail.php?id=" . $company_id);
        exit(); 
    }
}

// 4. ŞİRKET BİLGİSİNİ ÇEKME
$comp_query = mysqli_query($conn, "SELECT * FROM companies WHERE id = '$company_id'");
$company = mysqli_fetch_assoc($comp_query);

// 5. FİLTRE İÇİN POZİSYONLARI ÇEKME
$pos_query_str = "SELECT p.position_name, COUNT(e.id) as sayi 
                  FROM experiences e 
                  JOIN positions p ON e.position_id = p.id 
                  WHERE e.company_id = '$company_id' 
                  GROUP BY p.position_name";
$pos_query = mysqli_query($conn, $pos_query_str);

// 6. YORUMLARI ÇEKME
$filter_pos = isset($_GET['pos']) ? mysqli_real_escape_string($conn, $_GET['pos']) : null;

$sql_rev = "SELECT r.*, u.first_name, u.last_name, p.position_name
            FROM company_reviews r
            JOIN users u ON r.user_id = u.id
            LEFT JOIN experiences e ON r.user_id = e.user_id AND r.company_id = e.company_id
            LEFT JOIN positions p ON e.position_id = p.id
            WHERE r.company_id = '$company_id'";

if ($filter_pos) {
    $sql_rev .= " AND p.position_name = '$filter_pos'";
}
$sql_rev .= " GROUP BY r.id ORDER BY r.created_at DESC";
$rev_query = mysqli_query($conn, $sql_rev);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($company['name']) ?> | KBÜ Kariyer</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/app.css">
    <link rel="stylesheet" href="../assets/css/company.css">
</head>
<body>
    <div class="app-container">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <a href="company.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Şirketlere Dön</a>
            
            <?php if(isset($_GET['status'])): ?>
                <div style="margin-top: 15px; padding: 10px; border-radius: 8px; font-size: 14px; <?= $_GET['status'] == 'deleted' ? 'background: #dcfce7; color: #166534;' : 'background: #fee2e2; color: #991b1b;' ?>">
                    <?= $_GET['status'] == 'deleted' ? '<i class="fa-solid fa-check"></i> Değerlendirme başarıyla silindi.' : '<i class="fa-solid fa-xmark"></i> Silme işlemi başarısız.' ?>
                </div>
            <?php endif; ?>

            <div class="page-header" style="margin-top: 15px;">
                <h1 class="page-title"><i class="fa-solid fa-building" style="color: var(--kbu-lacivert); margin-right: 10px;"></i><?= htmlspecialchars($company['name']) ?></h1>
                <p class="page-subtitle">Sektör: <span class="industry-badge"><?= htmlspecialchars($company['industry']) ?></span></p>
            </div>

            <div class="company-content-grid">
                <div class="reviews-section">
                    <div class="filter-container glass-card" style="padding: 15px; margin-bottom: 20px;">
                        <h4 style="margin-top: 0; margin-bottom: 10px; font-size: 14px; color: var(--text-muted);">Departmana Göre Filtrele</h4>
                        <div class="filter-pills">
                            <a href="company_detail.php?id=<?= $company_id ?>" class="filter-pill <?= empty($filter_pos) ? 'active' : '' ?>">Tümünü Göster</a>
                            <?php while($pos = mysqli_fetch_assoc($pos_query)): ?>
                                <a href="company_detail.php?id=<?= $company_id ?>&pos=<?= urlencode($pos['position_name']) ?>" class="filter-pill <?= $filter_pos === $pos['position_name'] ? 'active' : '' ?>">
                                    <?= htmlspecialchars($pos['position_name']) ?> <span class="count-badge"><?= $pos['sayi'] ?></span>
                                </a>
                            <?php endwhile; ?>
                        </div>
                    </div>

                    <h3 class="section-title">Şirket Değerlendirmeleri</h3>
                    
                    <?php if(mysqli_num_rows($rev_query) > 0): ?>
                        <div class="reviews-list">
                            <?php while($rev = mysqli_fetch_assoc($rev_query)): ?>
                                <div class="glass-card review-card" style="position: relative;">
                                    <div class="review-header">
                                        <div class="reviewer-info">
                                            <div class="reviewer-avatar">
                                                <i class="fa-solid <?= $rev['is_anonymous'] ? 'fa-user-secret' : 'fa-user' ?>"></i>
                                            </div>
                                            <div>
                                                <strong><?= $rev['is_anonymous'] ? "Gizli Kullanıcı" : htmlspecialchars($rev['first_name'] . " " . $rev['last_name']) ?></strong>
                                                <div class="review-meta">
                                                    <i class="fa-solid fa-briefcase"></i> <?= htmlspecialchars($rev['position_name'] ?? 'Genel Değerlendirme') ?>
                                                    <span style="margin: 0 5px;">•</span>
                                                    <?= date('d.m.Y', strtotime($rev['created_at'])) ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="review-rating">
                                            <?php 
                                            for($i=1; $i<=5; $i++) {
                                                echo $i <= $rev['rating'] ? '<i class="fa-solid fa-star star-filled"></i>' : '<i class="fa-regular fa-star star-empty"></i>';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <div class="review-content">
                                        <p><?= nl2br(htmlspecialchars($rev['content'])) ?></p>
                                    </div>

                                    <?php if($rev['user_id'] == $user_id): ?>
                                        <a href="company_detail.php?id=<?= $company_id ?>&delete_rev_id=<?= $rev['id'] ?>" 
                                           onclick="return confirm('Bu değerlendirmeyi silmek istediğinize emin misiniz?');"
                                           style="position: absolute; bottom: 15px; right: 20px; color: #ef4444; font-size: 13px; text-decoration: none;">
                                            <i class="fa-solid fa-trash-can"></i> Sil
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="glass-card" style="text-align:center; padding: 40px; color: var(--text-muted);">
                            <i class="fa-regular fa-comment-dots" style="font-size: 40px; margin-bottom: 15px; color: #cbd5e1;"></i>
                            <p>Henüz bir değerlendirme bulunmuyor.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="add-review-section">
                    <div class="glass-card sticky-card">
                        <h3 style="margin-top: 0; color: var(--kbu-lacivert);"><i class="fa-solid fa-pen-to-square"></i> Deneyim Paylaş</h3>
                        <?php if($current_user_role === 'graduate'): ?>
                            <form method="POST">
                                <div class="form-group">
                                    <label>Şirket Puanınız (1-5)</label>
                                    <select name="rating" class="form-control" required>
                                        <option value="5">⭐⭐⭐⭐⭐ (5 - Mükemmel)</option>
                                        <option value="4">⭐⭐⭐⭐ (4 - Çok İyi)</option>
                                        <option value="3">⭐⭐⭐ (3 - Ortalama)</option>
                                        <option value="2">⭐⭐ (2 - Kötü)</option>
                                        <option value="1">⭐ (1 - Çok Kötü)</option>
                                    </select>
                                </div>
                                <div class="form-group" style="margin-top: 15px;">
                                    <label>Değerlendirmeniz</label>
                                    <textarea name="content" class="form-control" rows="5" placeholder="Deneyiminizi paylaşın..." required></textarea>
                                </div>
                                <div class="form-group" style="margin-top: 15px; display: flex; align-items: center;">
                                    <input type="checkbox" name="is_anonymous" value="1" id="gizli" style="width: 18px; height: 18px; margin-right: 8px;"> 
                                    <label for="gizli" style="cursor: pointer; font-size: 14px;">İsmim gizli kalsın</label>
                                </div>
                                <button type="submit" class="btn-primary" style="margin-top: 15px; width: 100%;">Gönder</button>
                            </form>
                        <?php else: ?>
                            <div class="alert" style="background: #f1f5f9; color: #475569; text-align: center; font-size: 14px; padding: 20px;">
                                <i class="fa-solid fa-lock" style="font-size: 24px; margin-bottom: 10px; display: block; color: #94a3b8;"></i>
                                Sadece <strong>Mezunlar</strong> değerlendirme yapabilir.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>