<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>تقرير فحص صفحة الفواتير:</h2>";

try {
    if (file_exists('invoices.php')) {
        include('invoices.php');
    } else {
        echo "❌ ملف invoices.php غير موجود!";
    }
} catch (Throwable $e) {
    echo "<div style='background: #FFFFFF; padding:15px; border:1px solid red; border-radius:5px;'>";
    echo "<h3 style='color:red;'>⚠️ تم اكتشاف خطأ برمجي (Exception):</h3>";
    echo "<b>الرسالة:</b> " . $e->getMessage() . "<br>";
    echo "<b>الملف:</b> " . $e->getFile() . "<br>";
    echo "<b>السطر:</b> " . $e->getLine() . "<br>";
    echo "</div>";
}
?>

<?php include 'nav_bar.php'; ?>

<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
