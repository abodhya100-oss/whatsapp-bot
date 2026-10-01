<?php
// test_db.php - سكريبت الفحص والربط الشامل لنظام أبو ضياء
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = '127.0.0.1';
$user = 'root';
$password = ''; 
$dbname = 'vmax_db';

echo "<div style='font-family:Tahoma; direction:rtl; padding:20px;'>";
echo "<h2>🚀 تقرير فحص وصيانة قاعدة البيانات لـ نظام ابوضياء العالمي</h2>";

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("<p style='color:red; font-weight:bold;'>❌ فشل الاتصال بقاعدة البيانات: " . $conn->connect_error . "</p>");
}
echo "<p style='color:green; font-weight:bold;'>✅ تم الاتصال بقاعدة البيانات بنجاح عبر 127.0.0.1!</p>";

// فحص الجداول الموجودة
$result = $conn->query("SHOW TABLES");
echo "<h3>📁 الجداول النشطة في القاعدة:</h3><ul>";
while ($row = $result->fetch_row()) {
    echo "<li>" . $row[0] . "</li>";
}
echo "</ul>";

// فحص أعمدة جدول invoice_details لتجنب خطأ الحقول غير المعروفة
echo "<h3>🔍 تفاصيل أعمدة جدول invoice_details:</h3>";
$columns = $conn->query("DESCRIBE invoice_details");
if ($columns) {
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse; background: #FFFFFF;'>
          <tr style='background: #FFFFFF; color:white;'><th>الحقل (Field)</th><th>النوع (Type)</th><th>مسموح الفراغ (Null)</th></tr>";
    while ($col = $columns->fetch_assoc()) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:orange; font-weight:bold;'>⚠️ جدول invoice_details غير موجود أو بحاجة للإنشاء في قاعدة البيانات!</p>";
}

echo "</div>";
$conn->close();
?>

<?php include 'nav_bar.php'; ?>

<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
