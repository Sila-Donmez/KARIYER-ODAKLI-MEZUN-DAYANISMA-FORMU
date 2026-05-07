<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/functions.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$role = $_SESSION['role'];

$catFilter = isset($_GET['cat']) ? intval($_GET['cat']) : 0;
$q = isset($_GET['q']) ? clean($_GET['q']) : '';

$sql = "SELECT fp.*, fc.name as category_name, u.first_name, u.last_name,
        (SELECT COUNT(*) FROM forum_comments WHERE post_id = fp.id) as comment_count
        FROM forum_posts fp
        LEFT JOIN forum_categories fc ON fp.category_id = fc.id
        LEFT JOIN users u ON fp.user_id = u.id
        WHERE 1=1";

if ($catFilter > 0) {
    $sql .= " AND fp.category_id = $catFilter";
}

if ($q != '') {
    $q_safe = $conn->real_escape_string($q);
    $sql .= " AND (fp.title LIKE '%$q_safe%' OR fp.content LIKE '%$q_safe%')";
}

$sql .= " ORDER BY fp.created_at DESC";

$posts = $conn->query($sql);
$categories = $conn->query("SELECT * FROM forum_categories");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forum | KBÜ Mentorluk</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/app.css"> 
    <link rel="stylesheet" href="../assets/css/forum.css">
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

    <div class="app-container">
        
        <!-- MERKEZİ MENÜ ÇAĞRILDI (Yol bir üst dizine çıkacak şekilde ayarlandı) -->
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            
            <div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1 class="page-title">Kariyer Forumu</h1>
                    <p class="page-subtitle">Sektörel sorularınızı sorun, tartışmalara katılın.</p>
                </div>
                <button onclick="$('#postModal').slideToggle()" class="btn-primary" style="width: auto; padding: 10px 20px;">
                    <i class="fa-solid fa-plus"></i> Yeni Konu Aç
                </button>
            </div>

            <form method="GET" class="search-form">
                <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Forumda ara...">
                <select name="cat" onchange="this.form.submit()">
                    <option value="0">Tüm Kategoriler</option>
                    <?php while($cat = $categories->fetch_assoc()): ?>
                        <option value="<?= $cat['id'] ?>" <?= $catFilter==$cat['id']?'selected':'' ?>>
                            <?= $cat['name'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </form>

            <div id="postModal" class="create-post-card" style="display:none;">
                <h3 style="margin-top: 0; color: var(--kbu-lacivert); margin-bottom: 15px;">Yeni Bir Tartışma Başlat</h3>
                <form id="newPostForm">
                    <div class="form-group-row">
                        <input type="text" name="title" placeholder="İlgi çekici bir başlık yazın" required>
                        <select name="category_id" required>
                            <option value="">Kategori Seçin</option>
                            <?php $categories->data_seek(0); while($cat = $categories->fetch_assoc()): ?>
                                <option value="<?= $cat['id'] ?>"><?= $cat['name'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <textarea name="content" required placeholder="Sorunuzu veya düşüncenizi detaylıca açıklayın..."></textarea>
                    
                    <div class="form-footer">
                        <label style="cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 14px; color: var(--text-muted);">
                            <input type="checkbox" name="is_anonymous" value="1"> Anonim olarak paylaş
                        </label>
                        <button type="submit" class="btn-primary" style="width: auto; padding: 10px 25px;">Paylaş</button>
                    </div>
                </form>
            </div>

            <div class="post-list">
                <?php if($posts && $posts->num_rows > 0): ?>
                    <?php while($p = $posts->fetch_assoc()): ?>
                        <a href="post_detail.php?id=<?= $p['id'] ?>" class="post-card">
                            <span class="post-badge"><?= $p['category_name'] ?></span>
                            <h3 class="post-title"><?= htmlspecialchars($p['title']) ?></h3>
                            <p class="post-excerpt"><?= mb_substr(htmlspecialchars($p['content']),0,200) ?>...</p>
                            
                            <div class="post-meta">
                                <span><i class="fa-solid fa-user"></i> <?= $p['is_anonymous'] ? 'Anonim Kullanıcı' : $p['first_name'].' '.$p['last_name'] ?></span>
                                <span><i class="fa-solid fa-comment"></i> <?= $p['comment_count'] ?> Yorum</span>
                            </div>
                        </a>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="glass-card" style="text-align: center; padding: 40px; color: var(--text-muted);">
                        <i class="fa-solid fa-folder-open" style="font-size: 40px; margin-bottom: 15px; color: #cbd5e1;"></i>
                        <p>Henüz bu kriterlere uygun bir konu bulunmuyor.</p>
                    </div>
                <?php endif; ?>
            </div>

        </main>
    </div>

    <script>
    $(document).ready(function() {
        $('#newPostForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: 'forum_operations.php',
                type: 'POST',
                data: $(this).serialize() + '&action=create_post',
                success: function() {
                    location.reload();
                }
            });
        });
    });
    </script>
</body>
</html>