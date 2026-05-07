<?php
$conn = new mysqli("localhost", "root", "", "mentorship_db");

if ($conn->connect_error) {
    die("DB bağlantı hatası: " . $conn->connect_error);
}

// Türkçe karakter sorunu yaşamamak için
$conn->set_charset("utf8mb4");
?>