<?php
// backup.php - مركز النسخ الاحتياطي الفوري - V-MAX PRO
require_once 'auth.php';
$db_file = __DIR__ . '/vmax.db';

// تحميل ملف قاعدة البيانات مباشرة
if (isset($_GET['download']) && file_exists($db_file)) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="vmax_backup_' . date('Y-m-d_H-i-s') . '.db"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($db_file));
    readfile($db_file);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><meta name='mobile-web-app-capable' content='yes'>
<meta name='apple-mobile-web-app-capable' content='yes'>

    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>النسخ الاحتياطي - V-MAX PRO</title>
    <?php if(file_exists('global_style.php')) include 'global_style.php'; ?>
</head>
<body>
<div class="lux-wrapper">
    <div class="lux-card" style="text-align: center;">
        <h2 class="lux-title" style="border:none; text-align:center;">🛡️ مركز الحماية والنسخ الاحتياطي</h2>
        <p style="font-size:11px; color:var(--text-muted); margin-bottom:20px;">
            احفظ نسخة أمان من قاعدة بيانات محلك (`vmax.db`) على جوالك لضمان عدم ضياع أي بيانات تجارية أو مالية مهما كانت الظروف.
        </p>

        <a href="backup.php?download=1" class="lux-btn" style="margin-bottom:12px; text-decoration:none; display:block;">
            📥 تحميل نسخة احتياطية الآن (DB)
        </a>

        <a href="index.php" class="lux-btn" style="background:rgba(30,41,59,0.8); color:var(--cyan-glow); border:1px solid var(--border-glass); text-decoration:none;">
            🏠 العودة للرئيسية الملكية
        </a>
    </div>
</div>

<?php if(file_exists('nav_menu.php')) { include 'nav_menu.php'; } ?>
</body>
</html>
