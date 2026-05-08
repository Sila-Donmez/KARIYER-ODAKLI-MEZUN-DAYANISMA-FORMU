<?php
session_start();
require_once '../includes/db.php'; // YOL DÜZELTİLDİ

// 1. Kullanıcı giriş yapmış mı kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. Kullanıcının GERÇEK rolünü hafızadan değil, doğrudan veritabanından alıyoruz
$sql_role = "SELECT role FROM users WHERE id = ?";
$stmt_role = $conn->prepare($sql_role);
$stmt_role->bind_param("i", $user_id);
$stmt_role->execute();
$user_data = $stmt_role->get_result()->fetch_assoc();
$user_role = $user_data['role'];

// Eğer kişi mezun değilse pop-up ile uyar ve engelle
if ($user_role !== 'graduate') {
    echo "<script>
            alert('Hata: Belge yükleme işlemini sadece Mezunlar yapabilir!'); 
            window.location.href='../profile.php';
          </script>";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['graduate_document'])) {
    $file = $_FILES['graduate_document'];

    // Dosyada yapısal bir hata var mı?
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo "<script>alert('Dosya yüklenirken hata oluştu! Hata Kodu: " . $file['error'] . "'); window.history.back();</script>";
        exit();
    }

    // Sadece PDF kontrolü
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($file_ext !== 'pdf') {
        echo "<script>alert('Hata: Lütfen sadece PDF formatında belge yükleyin!'); window.history.back();</script>";
        exit();
    }

    // YOL DÜZELTİLDİ: ../uploads/documents/ (company klasöründen dışarı çıkıyoruz)
    $upload_dir = '../uploads/documents/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // İsmi benzersiz yap
    $new_file_name = 'doc_' . $user_id . '_' . time() . '.' . $file_ext;
    $destination = $upload_dir . $new_file_name;

    // Dosyayı sunucuya kaydet
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        
        // Veritabanını güncelle. Eğer kullanıcının graduates tablosunda kaydı yoksa oluşturur.
        // YOLU DB'YE KAYDEDERKEN BASE PATH OLARAK (uploads/...) KAYDEDELİM Kİ GÖSTERİRKEN SORUN OLMASIN
        $db_path = 'uploads/documents/' . $new_file_name;

        $sql = "INSERT INTO graduates (user_id, graduate_year, document_link) 
                VALUES (?, 0, ?) 
                ON DUPLICATE KEY UPDATE document_link = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $user_id, $db_path, $db_path);
        
        if ($stmt->execute()) {
            echo "<script>
                    alert('Harika! Belgeniz başarıyla yüklendi.'); 
                    window.location.href='../profile.php';
                  </script>";
        } else {
            echo "<script>alert('Veritabanı hatası: " . $stmt->error . "'); window.history.back();</script>";
        }

    } else {
        echo "<script>alert('Hata: Dosya sunucuya kaydedilemedi. Klasör izinlerini kontrol edin.'); window.history.back();</script>";
    }
} else {
    echo "<script>alert('Geçersiz form gönderimi!'); window.location.href='../profile.php';</script>";
}
?>