<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/functions.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$role = $_SESSION['role'];
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$post_res = $conn->query("
    SELECT fp.*, fc.name as category_name, u.first_name, u.last_name 
    FROM forum_posts fp 
    JOIN forum_categories fc ON fp.category_id = fc.id
    JOIN users u ON fp.user_id = u.id 
    WHERE fp.id = $id
");

if (!$post_res || $post_res->num_rows == 0) { 
    die("Konu bulunamadı. <a href='forum.php'>Foruma Dön</a>"); 
}

$post = $post_res->fetch_assoc();

$comments = $conn->query("
    SELECT fc.*, u.first_name, u.last_name 
    FROM forum_comments fc 
    JOIN users u ON fc.user_id = u.id 
    WHERE fc.post_id = $id 
    ORDER BY fc.created_at ASC
");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($post['title']) ?> | Forum</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- CSS Yolları -->
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/app.css"> 
    <link rel="stylesheet" href="../assets/css/forum.css">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

    <div class="app-container">
        
        <!-- SOL MENÜ -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-brand">KBÜ <span>Panel</span></div>
            </div>
            <nav class="sidebar-nav">
                <a href="../dashboard.php"><i class="fa-solid fa-house"></i> Ana Panel</a>
                <a href="../profile.php"><i class="fa-solid fa-user-pen"></i> Profilim</a>
                
                <?php if($role == 'student'): ?>
                    <a href="../user/mentors.php"><i class="fa-solid fa-users-viewfinder"></i> Mentor Bul</a>
                    <a href="../user/my_requests.php"><i class="fa-solid fa-paper-plane"></i> İsteklerim</a>
                <?php elseif($role == 'graduate'): ?>
                    <a href="../mentor/requests.php"><i class="fa-solid fa-handshake-angle"></i> Gelen İstekler</a>
                <?php endif; ?>
                
                <a href="forum.php" class="active"><i class="fa-solid fa-comments"></i> Kariyer Forumu</a>
            </nav>
            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="logout-link">
                    <i class="fa-solid fa-right-from-bracket"></i> Çıkış Yap
                </a>
            </div>
        </aside>

        <!-- ANA İÇERİK -->
        <main class="main-content">
            
            <div style="margin-bottom: 20px;">
                <a href="forum.php" style="color: var(--text-muted); font-weight: 600; text-decoration: none;">
                    <i class="fa-solid fa-arrow-left"></i> Foruma Dön
                </a>
            </div>

            <!-- ANA GÖNDERİ -->
            <article class="create-post-card" style="border-left: 5px solid var(--kbu-kirmizi);">
                <span class="post-badge"><?= $post['category_name'] ?></span>
                <h1 style="margin: 15px 0; color: var(--text-main); font-size: 24px;"><?= htmlspecialchars($post['title']) ?></h1>
                
                <div style="margin-bottom: 25px; line-height: 1.8; color: var(--text-main); font-size: 15px;">
                    <?= nl2br(htmlspecialchars($post['content'])) ?>
                </div>
                
                <div style="font-size: 13px; color: var(--text-muted); border-top: 1px solid rgba(0,0,0,0.05); padding-top: 15px;">
                    <i class="fa-solid fa-user"></i> <strong><?= $post['is_anonymous'] ? 'Anonim Kullanıcı' : $post['first_name'].' '.$post['last_name'] ?></strong>
                    <span style="margin: 0 10px;">|</span>
                    <i class="fa-solid fa-calendar"></i> <?= format_date($post['created_at']) ?>
                </div>
            </article>

            <h3 style="color: var(--kbu-lacivert); margin-bottom: 15px;">Yorumlar (<?= $comments->num_rows ?>)</h3>

            <!-- YORUMLAR -->
            <div id="commentList">
                <?php if($comments->num_rows > 0): ?>
                    <?php while($c = $comments->fetch_assoc()): ?>
                        <div class="comment-box">
                            <div class="comment-header">
                                <span class="comment-author"><i class="fa-solid fa-reply" style="color: var(--text-muted); margin-right: 5px;"></i> <?= $c['is_anonymous'] ? 'Anonim Kullanıcı' : $c['first_name'].' '.$c['last_name'] ?></span>
                                <span class="comment-date"><?= format_date($c['created_at']) ?></span>
                            </div>
                            <p style="margin: 0; line-height: 1.6; font-size: 14px;"><?= nl2br(htmlspecialchars($c['content'])) ?></p>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 20px;">Henüz yorum yapılmamış. İlk yorumu sen yap!</p>
                <?php endif; ?>
            </div>

            <!-- YORUM YAPMA FORMU -->
            <div class="create-post-card" style="margin-top: 30px;">
                <h4 style="margin-top:0; color: var(--kbu-lacivert); margin-bottom: 15px;">Cevap Yaz</h4>
                <form id="commentForm">
                    <input type="hidden" name="post_id" value="<?= $id ?>">
                    <textarea name="content" placeholder="Fikrinizi veya çözümünüzü buraya yazın..." required></textarea>
                    
                    <div class="form-footer">
                        <label style="font-size: 14px; cursor: pointer; color: var(--text-muted); display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" name="is_anonymous" value="1"> Anonim Yanıtla
                        </label>
                        <button type="submit" class="btn-primary" id="submitBtn" style="width: auto; padding: 10px 25px;">Gönder</button>
                    </div>
                </form>
            </div>

        </main>
    </div>

    <!-- JQUERY AJAX -->
    <script>
    $(document).ready(function() {
        $('#commentForm').on('submit', function(e) {
            e.preventDefault();
            const btn = $('#submitBtn');
            btn.prop('disabled', true).text('Gönderiliyor...');

            $.ajax({
                url: 'forum_operations.php', // Aynı klasörde
                type: 'POST',
                data: $(this).serialize() + '&action=add_comment',
                dataType: 'json',
                success: function(res) {
                    if(res.status === 'success') {
                        location.reload();
                    } else {
                        alert('Hata: ' + (res.error_msg || 'Bir sorun oluştu.'));
                        btn.prop('disabled', false).text('Gönder');
                    }
                },
                error: function(xhr) {
                    console.error(xhr.responseText);
                    alert('Sunucu hatası! Sayfayı yenileyip tekrar deneyin.');
                    btn.prop('disabled', false).text('Gönder');
                }
            });
        });
    });
    </script>
</body>
</html>