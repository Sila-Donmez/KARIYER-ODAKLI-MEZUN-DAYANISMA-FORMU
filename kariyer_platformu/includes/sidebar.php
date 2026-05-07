<?php
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

$sub_folders = ['mentor', 'forum', 'user', 'auth', 'company'];
$path = in_array($current_dir, $sub_folders) ? '../' : '';

$user_role = $_SESSION['role'] ?? 'student'; 
?>

<aside class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand">KBÜ <span>Panel</span></div>
    </div>
    
    <nav class="sidebar-nav">
        <?php if($user_role === 'admin'): ?>
            <a href="<?= $path ?>admin_panel.php" class="<?= $current_page == 'admin_panel.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-shield-halved"></i> Yönetim Paneli
            </a>
            
        <?php else: ?>
            <a href="<?= $path ?>dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-house"></i> Ana Panel
            </a>

            <a href="<?= $path ?>view_profile.php" class="<?= in_array($current_page, ['view_profile.php', 'profile.php']) ? 'active' : '' ?>">
                <i class="fa-solid fa-user"></i> Profilim
            </a>
            
            <?php if($user_role == 'student'): ?>
                <a href="<?= $path ?>mentor/student_ads_list.php" class="<?= $current_page == 'student_ads_list.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-user-tie"></i> Mentor Bul
                </a>
                <a href="<?= $path ?>mentor/student_my_requests.php" class="<?= $current_page == 'student_my_requests.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-paper-plane"></i> İsteklerim
                </a>

            <?php elseif($user_role == 'graduate'): ?>
                <a href="<?= $path ?>mentor/mentor_my_ads.php" class="<?= in_array($current_page, ['mentor_my_ads.php', 'mentor_ads_create.php', 'mentor_applications.php']) ? 'active' : '' ?>">
                    <i class="fa-solid fa-bullhorn"></i> İlanlarım
                </a>
            <?php endif; ?>
            
            <a href="<?= $path ?>company/company.php" class="<?= ($current_dir == 'company') ? 'active' : '' ?>">
                <i class="fa-solid fa-building"></i> Şirketler
            </a>
            
            <a href="<?= $path ?>forum/forum.php" class="<?= ($current_dir == 'forum') ? 'active' : '' ?>">
                <i class="fa-solid fa-comments"></i> Kariyer Forumu
            </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <a href="<?= $path ?>auth/logout.php" class="logout-link">
            <i class="fa-solid fa-right-from-bracket"></i> Çıkış Yap
        </a>
    </div>
</aside>