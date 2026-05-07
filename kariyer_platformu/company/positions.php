<?php
//BU SAYFA PROJEDEN ÇIKARILABİLİR/ÇIKARILACAK DATABASEDEN POSİTİONS SİLİNMEMELİ

session_start();
include 'db.php';

$search = isset($_GET['q']) ? mysqli_real_escape_string($conn, $_GET['q']) : '';

// SİHİRLİ BAĞLANTI SORGUMUZ: Experiences üzerinden tabloları birleştir
$query = "SELECT 
            p.position_name, 
            c.name AS company_name, 
            u.first_name, 
            u.last_name,
            e.start_date,
            e.end_date
          FROM experiences e
          JOIN positions p ON e.position_id = p.id
          JOIN companies c ON e.company_id = c.id
          JOIN users u ON e.user_id = u.id
          WHERE p.position_name LIKE '%$search%'
             OR c.name LIKE '%$search%'
             OR u.first_name LIKE '%$search%'
             OR u.last_name LIKE '%$search%'
          ORDER BY p.position_name ASC, e.start_date DESC";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Pozisyonlar ve Çalışanlar</title>
</head>
<body>

    <h2>Pozisyonlar ve Çalışanlar</h2>
    
    <form method="GET">
        <input type="text" name="q" value="<?= $search ?>" placeholder="Pozisyon, şirket veya kişi ara...">
        <button type="submit">Ara</button>
        <a href="positions.php">Temizle</a>
    </form>
    <br>

    <table border="1">
        <tr>
            <th>Pozisyon</th>
            <th>Şirket</th>
            <th>Kişi</th>
            <th>Tarih</th>
        </tr>
        
        <?php if(mysqli_num_rows($result) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?= $row['position_name'] ?></td>
                <td><?= $row['company_name'] ?></td>
                <td><?= $row['first_name'] . ' ' . $row['last_name'] ?></td>
                <td><?= $row['start_date'] ?> / <?= $row['end_date'] ? $row['end_date'] : 'Devam Ediyor' ?></td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="4">Kayıt bulunamadı.</td>
            </tr>
        <?php endif; ?>
    </table>

    <br>
    <a href="profile.php">Geri Dön</a>

</body>
</html>