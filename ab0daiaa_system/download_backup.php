<?php
session_start();
if (!isset($_SESSION['user_role'])) { header("Location: login.php"); exit; }
$file = '/data/data/com.termux/files/home/secure_dbs/vmax.db';
if (file_exists($file)) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="System_Backup_' . date('Y-m-d_H-i-s') . '.db"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
} else {
    echo "ملف القاعدة غير موجود!";
}
?>

<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
