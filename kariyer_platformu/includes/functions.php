<?php

function clean($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function format_date($date) {
    return date('d.m.Y H:i', strtotime($date));
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function get_user_info($user_id) {
    global $conn;
    $user_id = intval($user_id);

    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    if (!$stmt) return null;

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc();
}

/* ===============================
   🔐 PASSWORD FUNCTIONS
================================ */

function hash_password($password) {
    return hash('sha256', $password);
}

function verify_password($input_password, $hashed_password) {
    return hash('sha256', $input_password) === $hashed_password;
}

/* ===============================
   📊 AUTH LOG
================================ */

function log_auth_action($user_id, $is_success) {
    global $conn;

    if (!$conn) return;

    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $status = $is_success ? 1 : 0;

    $stmt = $conn->prepare("
        INSERT INTO auth_logs (user_id, ip_address, user_agent, is_success)
        VALUES (?, ?, ?, ?)
    ");

    if ($stmt) {
        $stmt->bind_param("issi", $user_id, $ip_address, $user_agent, $status);
        $stmt->execute();
    }
}

/* ===============================
   📂 CATEGORY SEED
================================ */

function reset_and_seed_categories($conn) {

    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    $conn->query("TRUNCATE TABLE forum_categories");
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");

    $default_categories = [
        'Yazılım & Teknoloji',
        'İş Görüşmeleri & Mülakatlar',
        'Özgeçmiş (CV) Hazırlama',
        'Sektörel Sohbetler',
        'Staj İmkanları',
        'Yurtdışı Fırsatları',
        'Freelance & Uzaktan Çalışma'
    ];

    $stmt = $conn->prepare("INSERT INTO forum_categories (name) VALUES (?)");

    foreach ($default_categories as $cat_name) {
        $stmt->bind_param("s", $cat_name);
        $stmt->execute();
    }

    $stmt->close();
}
/* ===============================
    🗑️ FORUM & YORUM SİLME FONKSİYONLARI
================================ */
function delete_forum_post($conn, $post_id, $user_id) {
    $stmt = $conn->prepare("DELETE FROM forum_posts WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $post_id, $user_id);
    if ($stmt->execute()) {
        $stmt2 = $conn->prepare("DELETE FROM forum_comments WHERE post_id = ?");
        $stmt2->bind_param("i", $post_id);
        $stmt2->execute();
        return true;
    }
    return false;
}

function delete_forum_comment($conn, $comment_id, $user_id) {
    $stmt = $conn->prepare("DELETE FROM forum_comments WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $comment_id, $user_id);
    return $stmt->execute();
}
/* ===============================
    🏢 ŞİRKET DENEYİMİ SİLME
================================ */
function delete_experience($conn, $exp_id, $user_id) {
    // Tablo adının company_reviews olduğundan emin olalım
    $stmt = $conn->prepare("DELETE FROM company_reviews WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $exp_id, $user_id);
    $stmt->execute();
    
    // Etkilenen satır sayısı 0'dan büyükse silme başarılıdır
    return $stmt->affected_rows > 0;
}

?>