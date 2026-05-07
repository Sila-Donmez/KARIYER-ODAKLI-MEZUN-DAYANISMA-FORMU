<?php
ob_start();
header('Content-Type: application/json');

session_start();
// Bir üst dizindeki includes klasörüne gidiyoruz
require_once "../includes/db.php";
require_once "../includes/functions.php";

$user_id = $_SESSION['user_id'] ?? null;
$action = $_POST['action'] ?? '';

if (!$user_id) {
    echo json_encode(["status" => "error", "error_msg" => "Lütfen önce giriş yapın."]);
    exit;
}

// --- YORUM EKLEME ---
if($action == "add_comment"){
    $post_id = intval($_POST['post_id']);
    $content = clean($_POST['content']);
    $anon = isset($_POST['is_anonymous']) ? 1 : 0;

    if(empty($content) || $post_id <= 0) {
        echo json_encode(["status" => "error", "error_msg" => "Geçersiz veri gönderildi."]);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO forum_comments (post_id, user_id, content, is_anonymous) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iisi", $post_id, $user_id, $content, $anon);
        if($stmt->execute()){
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => "error", "error_msg" => "Veritabanı hatası: " . $stmt->error]);
        }
    } else {
        echo json_encode(["status" => "error", "error_msg" => "Sorgu hazırlanamadı."]);
    }
    exit;
}

// --- YENİ KONU OLUŞTURMA ---
if($action == "create_post"){
    $title = clean($_POST['title']);
    $cat = intval($_POST['category_id']);
    $content = clean($_POST['content']);
    $anon = isset($_POST['is_anonymous']) ? 1 : 0;

    $stmt = $conn->prepare("INSERT INTO forum_posts (user_id, category_id, title, content, is_anonymous) VALUES (?, ?, ?, ?, ?)");
    if($stmt) {
        $stmt->bind_param("iissi", $user_id, $cat, $title, $content, $anon);
        echo json_encode(["status" => $stmt->execute() ? "success" : "error"]);
    }
    exit;
}

echo json_encode(["status" => "invalid_action"]);
ob_end_flush();
?>