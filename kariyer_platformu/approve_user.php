<?php
session_start();
require_once 'includes/db.php'; // Yol ana dizine göre ayarlandı

// 1. Kullanıcı giriş yapmış mı?
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];

// 2. Güvenlik Duvarı: Rolü doğrudan veritabanından al
$check_sql = "SELECT role FROM users WHERE id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $admin_id);
$check_stmt->execute();
$admin_data = $check_stmt->get_result()->fetch_assoc();

// Eğer veritabanında 'admin' değilse işlemi engelle
if ($admin_data['role'] !== 'admin') {
    echo "<script>
            alert('Hata: Bu işlemi yapmaya yetkiniz yok!'); 
            window.location.href='view_profile.php';
          </script>";
    exit();
}

// 3. Onaylama İşlemi
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $target_user_id = intval($_GET['id']);

    // Kullanıcının is_verified durumunu 1 yap
    $sql = "UPDATE users SET is_verified = 1 WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $target_user_id);

    if ($stmt->execute()) {
        echo "<script>
                alert('Kullanıcı başarıyla onaylandı ve doğrulanmış mezun rozeti aldı!'); 
                window.location.href='admin_panel.php';
              </script>";
    } else {
        echo "<script>
                alert('Onaylama sırasında veritabanı hatası oluştu.'); 
                window.location.href='admin_panel.php';
              </script>";
    }
} else {
    header("Location: admin_panel.php");
    exit();
}
?>