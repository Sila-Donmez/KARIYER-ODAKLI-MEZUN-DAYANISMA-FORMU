<?php
session_start();
session_destroy();
header("Location: ../index.php"); // login.php yerine index.php yapıldı
exit();
?>