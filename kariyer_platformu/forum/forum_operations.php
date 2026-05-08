<?php
ob_start();
// JSON içeriği göndereceğimizi tarayıcıya bildiriyoruz (AJAX için şart)
header('Content-Type: application/json');
session_start();
require_once "../includes/db.php";
require_once "../includes/functions.php";

$user_id = $_SESSION['user_id'] ?? null;
$action = $_POST['action'] ?? '';

// 1. Güvenlik Kontrolü
if (!$user_id) {
    echo json_encode(["status" => "error", "error_msg" => "Lütfen önce giriş yapın."]);
    exit;
}

// 2. Aksiyon Yönetimi
if($action == "add_comment"){
    $post_id = intval($_POST['post_id']);
    $content = clean($_POST['content']);
    $anon = isset($_POST['is_anonymous']) ? 1 : 0;

    if(empty($content)) {
        echo json_encode(["status" => "error", "error_msg" => "Yorum içeriği boş olamaz."]);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO forum_comments (post_id, user_id, content, is_anonymous) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iisi", $post_id, $user_id, $content, $anon);
        $success = $stmt->execute();
        echo json_encode(["status" => $success ? "success" : "error"]);
    }
    exit;
}

if($action == "create_post"){
    $title = clean($_POST['title']);
    $cat = intval($_POST['category_id']);
    $content = clean($_POST['content']);
    $anon = isset($_POST['is_anonymous']) ? 1 : 0;

    if(empty($title) || empty($content) || $cat == 0) {
        echo json_encode(["status" => "error", "error_msg" => "Lütfen tüm alanları doldurun."]);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO forum_posts (user_id, category_id, title, content, is_anonymous) VALUES (?, ?, ?, ?, ?)");
    if($stmt) {
        $stmt->bind_param("iissi", $user_id, $cat, $title, $content, $anon);
        $success = $stmt->execute();
        echo json_encode(["status" => $success ? "success" : "error"]);
    }
    exit;
}

if($action == "delete_comment"){
    $comment_id = intval($_POST['comment_id']);
    
    // delete_forum_comment fonksiyonu functions.php içinde tanımlı olmalı
    if($comment_id > 0 && delete_forum_comment($conn, $comment_id, $user_id)){
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "error_msg" => "Bu yorumu silme yetkiniz yok veya yorum bulunamadı."]);
    }
    exit;
}

// Geçersiz bir action gönderilirse
echo json_encode(["status" => "error", "error_msg" => "Geçersiz işlem isteği."]);
ob_end_flush();
?>