<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <link rel="manifest" href="manifest.json">
    <meta name='mobile-web-app-capable' content='yes'>
    <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js");}</script>
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>فحص صحة النظام - ابوضياء العالمي</title>
    <style>
        body { font-family: 'Tahoma', sans-serif; background: #0f2027; color: #fff; margin: 0; padding: 15px; }
        .container { max-width: 600px; margin: auto; background: rgba(255,255,255,0.08); padding: 20px; border-radius: 12px; border: 1px solid rgba(255,210,0,0.3); }
        h1 { color: #ffd200; font-size: 20px; text-align: center; }
        .success { color: #38ef7d; font-weight: bold; }
        .warn { color: #ffc107; }
        .box { background: rgba(0,0,0,0.5); padding: 10px; border-radius: 8px; margin-top: 10px; font-size: 12px; line-height: 1.6; }
    </style>
</head>
<body>
<div class="container">
    <h1>🩺 تقرير الفحص والتشخيص الشامل</h1>
    <div class="box">
        <?php
        $db_file = __DIR__ . '/vmax.db';
        if (file_exists($db_file)) {
            echo "✅ قاعدة البيانات الأساسية <b>vmax.db</b> موجودة وتعمل بنجاح.<br>";
            echo "📁 حجم ملف القاعدة: " . round(filesize($db_file) / 1024, 2) . " كيلوبايت.<br><br>";
            
            if(session_status()===PHP_SESSION_NONE){session_start();} if(!isset($_SESSION["user_role"])){header("Location: login.php");exit;}
$db = new SQLite3($db_file);
            $tables = ['customers', 'products', 'invoices', 'invoice_items', 'returns', 'expenses', 'suppliers', 'purchases', 'receipts', 'installments', 'staff', 'staff_advances', 'users', 'audit_logs', 'customer_subscriptions'];
            
            echo "<b>📊 حالة جداول النظام:</b><br>";
            foreach($tables as $t) {
                $res = $db->querySingle("SELECT name FROM sqlite_master WHERE type='table' AND name='$t'");
                if ($res) {
                    $count = $db->querySingle("SELECT COUNT(*) FROM $t");
                    echo "- جدول <span style='color:#ffd200;'>$t</span>: <span class='success'>موجود وصالح</span> (عدد السجلات: $count)<br>";
                } else {
                    echo "- جدول <span style='color:#ffd200;'>$t</span>: <span class='warn'>غير مفعّل حالياً (سيُنشأ تلقائياً عند الاستخدام)</span><br>";
                }
            }
            $db->close();
        } else {
            echo "❌ تحذير: ملف قاعدة البيانات غير موجود!";
        }
        ?>
    </div>
    <br>
    <a href="main.php" style="display:block; text-align:center; background:#ffd200; color:#000; padding:10px; border-radius:8px; text-decoration:none; font-weight:bold;">⬅️ العودة للرئيسية</a>
</div>

<?php include 'nav_bar.php'; ?>
<?php include "nav_menu.php"; ?>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>
</html>

