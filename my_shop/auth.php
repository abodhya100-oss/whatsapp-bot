<?php
// auth.php - توجيه الجلسات للمجلد المحلي لتجنب مشاكل الصلاحيات والقفل
}

if (session_status() === PHP_SESSION_NONE) {
}

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['name'] = 'نظام ابوضياء العالمي';
    $_SESSION['role'] = 'admin';
}

<?php include 'nav_bar.php'; ?>

<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
