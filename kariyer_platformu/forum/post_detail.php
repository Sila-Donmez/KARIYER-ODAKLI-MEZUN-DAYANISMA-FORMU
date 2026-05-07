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
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/app.css"> 
    <link rel="stylesheet" href="../assets/css/forum.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

    <div class="app-container">
        <?php include "../includes/sidebar.php"; ?>

        <main class="main-content">
            <div style="margin-bottom: 20px;">
                <a href="forum.php" style="text-decoration: none; color: #64748b; font-size: 14px; font-weight: 500;">
                    <i class="fa-solid fa-arrow-left"></i> Foruma Dön
                </a>
            </div>

            <div class="post-card" style="cursor: default; padding: 25px; border-left: 4px solid #cc182a; width: 100%; margin-bottom: 30px;">
                <span class="post-badge" style="display: inline-block; margin-bottom: 15px;"><?= htmlspecialchars($post['category_name']) ?></span>
                <h1 style="font-size: 24px; color: #1e293b; margin-bottom: 10px; font-weight: 700;"><?= htmlspecialchars($post['title']) ?></h1>
                <div style="font-size: 15px; color: #475569; line-height: 1.6; margin-bottom: 20px;">
                    <?= nl2br(htmlspecialchars($post['content'])) ?>
                </div>
                <div style="padding-top: 15px; border-top: 1px solid #f1f5f9; font-size: 13px; color: #94a3b8; display: flex; gap: 15px;">
                    <span><i class="fa-solid fa-user"></i> <?= $post['is_anonymous'] ? 'Anonim Kullanıcı' : htmlspecialchars($post['first_name'].' '.$post['last_name']) ?></span>
                    <span><i class="fa-solid fa-calendar"></i> <?= format_date($post['created_at']) ?></span>
                </div>
            </div>

            <h3 style="font-size: 18px; color: #1e293b; margin-bottom: 20px;">Yorumlar (<?= $comments->num_rows ?>)</h3>

            <div id="commentList">
                <?php if($comments->num_rows > 0): ?>
                    <?php while($c = $comments->fetch_assoc()): ?>
                        <div class="comment-box" style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 15px; margin-bottom: 15px;">
                            <div style="display: flex; justify-content: space-between; font-size: 12px; color: #94a3b8; margin-bottom: 8px;">
                                <span style="font-weight: 600; color: #1e293b;">
                                    <i class="fa-solid fa-reply" style="transform: rotate(180deg); margin-right: 5px;"></i>
                                    <?= $c['is_anonymous'] ? 'Anonim Kullanıcı' : htmlspecialchars($c['first_name'].' '.$c['last_name']) ?>
                                </span>
                                <span><?= format_date($c['created_at']) ?></span>
                            </div>
                            <p style="margin: 0; font-size: 14px; color: #334155; line-height: 1.5;"><?= nl2br(htmlspecialchars($c['content'])) ?></p>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="color: #94a3b8; font-size: 14px; margin-bottom: 30px;">Henüz yorum yapılmamış. İlk yorumu sen yap!</p>
                <?php endif; ?>
            </div>

            <div class="create-post-card" style="padding: 25px; margin-top: 30px; width: 100%;">
                <h4 style="margin-top: 0; margin-bottom: 15px; color: #1e293b;">Cevap Yaz</h4>
                <form id="commentForm">
                    <input type="hidden" name="post_id" value="<?= $id ?>">
                    <textarea name="content" placeholder="Fikrinizi veya çözümünüzü buraya yazın..." required style="width: 100%; min-height: 100px; padding: 15px; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 15px; font-family: inherit;"></textarea>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <label style="font-size: 13px; color: #64748b; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" name="is_anonymous" value="1"> Anonim Yanıtla
                        </label>
                        <button type="submit" class="btn-primary" id="submitBtn" style="width: auto; padding: 10px 30px; font-weight: 600;">GÖNDER</button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
    $(document).ready(function() {
        $('#commentForm').on('submit', function(e) {
            e.preventDefault();
            const btn = $('#submitBtn');
            btn.prop('disabled', true).text('Gönderiliyor...');
            $.ajax({
                url: 'forum_operations.php',
                type: 'POST',
                data: $(this).serialize() + '&action=add_comment',
                dataType: 'json',
                success: function(res) {
                    if(res.status === 'success') { location.reload(); }
                    else { alert('Hata: ' + (res.error_msg || 'Bir sorun oluştu.')); btn.prop('disabled', false).text('GÖNDER'); }
                },
                error: function() { alert('Sunucu hatası!'); btn.prop('disabled', false).text('GÖNDER'); }
            });
        });
    });
    </script>
</body>
</html>
