<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// الاتصال بقاعدة البيانات مع تحديد مسار السوكيت الصحيح في تيرامكس
$socket = '/data/data/com.termux/files/usr/var/run/mysqld.sock';
$conn = @new mysqli('localhost', 'root', '', 'vmax_db', 3306, $socket);

if ($conn->connect_error) {
    // محاولة ثانية بالطريقة العادية إذا لم ينجح السوكيت
    $conn = @new mysqli('localhost', 'root', '', 'vmax_db');
    if ($conn->connect_error) {
        echo "<div style='background:#ffcccc; color:#990000; padding:20px; font-size:18px; direction:rtl;'>";
        echo "<h3>⚠️ خطأ في الاتصال بقاعدة البيانات:</h3>";
        echo "<p>" . $conn->connect_error . "</p>";
        echo "</div>";
        exit;
    }
}
$conn->set_charset("utf8");
?>

<?php include 'nav_bar.php'; ?>

<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
