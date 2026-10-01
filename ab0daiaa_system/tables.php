<?php ini_set("display_errors", 1); error_reporting(E_ALL); ?>
<?php
require_once 'db.php';

// استعلام لجلب كافة أسماء الجداول من قاعدة البيانات
$result = $conn->query("SHOW TABLES");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <link rel="manifest" href="manifest.json">
    <meta name='mobile-web-app-capable' content='yes'>
    <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js");}</script>
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>عرض جدول قاعدة البيانات - نظام أبو ضياء</title>
    <style>
        body { font-family: Tahoma, sans-serif; background: #FFFFFF; margin: 0; padding: 20px; direction: rtl; }
        .container { max-width: 500px; margin: auto; background: #FFFFFF; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h3 { color: #333; text-align: center; margin-bottom: 20px; }
        ul { padding-right: 20px; }
        li { font-size: 18px; padding: 8px 0; border-bottom: 1px solid #eee; color: #007bff; font-weight: bold; }
    </style>
</head>
<body>
    <div style="display: flex; gap: 5px;">
    </div>
    <div style="display: flex; gap: 4px; align-items: center;">
        <span style="color: #94a3b8; font-size: 10px; font-weight: bold;">تكبير الشاشة:</span>
    </div>
</div>
</div>

<div class="container">
    <h3>📋 جداول قاعدة البيانات الحالية</h3>
    <ul>
        <?php
        if ($result) {
            while ($row = $result->fetch_array()) {
                echo "<li>" . htmlspecialchars($row[0]) . "</li>";
            }
        } else {
            echo "<li style='color:red;'>خطأ في الاتصال أو جلب الجداول: " . $conn->error . "</li>";
        }
        ?>
    </ul>
</div>


<?php include 'nav_bar.php'; ?>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>
</html>


