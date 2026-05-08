<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/functions.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$post_res = $conn->query("SELECT fp.*, fc.name as category_name, u.first_name, u.last_name FROM forum_posts fp JOIN forum_categories fc ON fp.category_id = fc.id JOIN users u ON fp.user_id = u.id WHERE fp.id = $id");

if (!$post_res || $post_res->num_rows == 0) { die("Konu bulunamadı."); }
$post = $post_res->fetch_assoc();
$comments = $conn->query("SELECT fc.*, u.first_name, u.last_name FROM forum_comments fc JOIN users u ON fc.user_id = u.id WHERE fc.post_id = $id ORDER BY fc.created_at ASC");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($post['title']) ?></title>
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
            <a href="forum.php" style="text-decoration:none; color:#64748b; font-size: 14px;"><i class="fa-solid fa-arrow-left"></i> Foruma Dön</a>
            
            <div class="post-card" style="padding:25px; margin:20px 0; border-left: 4px solid #cc182a;">
                <span class="post-badge"><?= htmlspecialchars($post['category_name']) ?></span>
                <h1 style="margin: 10px 0;"><?= htmlspecialchars($post['title']) ?></h1>
                <p style="color: #334155; line-height: 1.6;"><?= nl2br(htmlspecialchars($post['content'])) ?></p>
                <div style="font-size: 12px; color: #94a3b8; border-top: 1px solid #eee; pt: 10px; margin-top: 15px;">
                    <span><i class="fa-solid fa-user"></i> <?= $post['is_anonymous'] ? 'Anonim' : $post['first_name'].' '.$post['last_name'] ?></span>
                </div>
            </div>

            <div id="commentList">
                <h3 style="font-size: 16px; margin-bottom: 15px;">Yorumlar (<?= $comments->num_rows ?>)</h3>
                <?php while($c = $comments->fetch_assoc()): ?>
                    <div class="comment-box" style="background:#f8fafc; padding:15px; margin-bottom:10px; border-radius:8px; border: 1px solid #f1f5f9;">
                        <div style="display:flex; justify-content:space-between; font-size:12px; margin-bottom: 8px;">
                            <span style="font-weight: bold;"><?= $c['is_anonymous'] ? 'Anonim' : $c['first_name'].' '.$c['last_name'] ?></span>
                            <div>
                                <span><?= format_date($c['created_at']) ?></span>
                                <?php if($c['user_id'] == $_SESSION['user_id']): ?>
                                    <a href="javascript:void(0);" onclick="deleteComment(<?= $c['id'] ?>)" style="color:#ef4444; margin-left:10px;"><i class="fa-solid fa-trash"></i></a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <p style="margin:0; font-size: 14px;"><?= nl2br(htmlspecialchars($c['content'])) ?></p>
                    </div>
                <?php endwhile; ?>
            </div>

            <div class="create-post-card" style="margin-top: 30px; padding: 20px;">
                <form id="commentForm">
                    <input type="hidden" name="post_id" value="<?= $id ?>">
                    <textarea name="content" required placeholder="Bir cevap yazın..." style="width: 100%; min-height: 100px; padding: 12px; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 10px;"></textarea>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <label style="font-size: 13px; color: #64748b;"><input type="checkbox" name="is_anonymous" value="1"> Anonim Yanıtla</label>
                        <button type="submit" class="btn-primary" id="submitBtn" style="width: auto; padding: 10px 30px; cursor: pointer;">GÖNDER</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
    <script src="../assets/js/app_features.js"></script>
</body>
</html>