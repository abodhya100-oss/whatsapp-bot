<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
if(session_status()===PHP_SESSION_NONE){session_start();} if(!isset($_SESSION["user_role"])){header("Location: login.php");exit;}
$db = new SQLite3(__DIR__ . '/vmax.db');
$db->exec("CREATE TABLE IF NOT EXISTS invoices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_id INTEGER,
    total REAL DEFAULT 0,
    paid REAL DEFAULT 0,
    type TEXT DEFAULT 'مبيعات',
    date TEXT
)");

$invoices = [];
$res = $db->query("
    SELECT i.*, c.name as customer_name, c.phone as customer_phone 
    FROM invoices i 
    LEFT JOIN customers c ON i.customer_id = c.id 
    ORDER BY i.id DESC LIMIT 100
");
while($inv = $res->fetchArray(SQLITE3_ASSOC)) { $invoices[] = $inv; }
$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <link rel="manifest" href="manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js");}</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سجل الفواتير - ابوضياء العالمي</title>
    <style>
        body { font-family: 'Tahoma', sans-serif; background: #0f2027; color: #fff; margin: 0; padding: 10px; padding-bottom: 90px; }
        .container { max-width: 680px; margin: auto; }
        h1 { text-align: center; color: #ffd200; font-size: 19px; margin-bottom: 3px; }
        p.sub-title { text-align: center; color: #aaa; font-size: 11px; margin-bottom: 10px; }
        .card { background: rgba(255,255,255,0.08); padding: 12px; border-radius: 12px; margin-bottom: 10px; border: 1px solid rgba(255,255,255,0.15); }
        .table { width: 100%; border-collapse: collapse; font-size: 11px; background: rgba(0,0,0,0.4); border-radius: 8px; overflow: hidden; margin-top: 6px; }
        .table th, .table td { padding: 8px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .table th { background: rgba(255,210,0,0.2); color: #ffd200; }
        .nav-bar { position: fixed; bottom: 0; left: 0; right: 0; background: #0f2027; border-top: 1px solid rgba(255,210,0,0.2); padding: 6px 0; text-align: center; z-index: 1000; }
        .nav-links { display: flex; justify-content: space-around; font-size: 12px; }
        .nav-links a { color: #aaa; text-decoration: none; font-weight: bold; text-align: center; }
        .nav-links a.active { color: #ffd200; }
    </style>
</head>
<body>
<div class="container">
    <h1>📜 أرشيف وسجل الفواتير</h1>
    <p class="sub-title">مراجعة الفواتير الصادرة وحسابات المبيعات</p>
    <div class="card">
        <b style="color:#ffd200; font-size:12px; display:block; margin-bottom:6px;">📋 قائمة الفواتير السابقة (<?php echo count($invoices); ?>):</b>
        <table class="table">
            <thead><tr><th>رقم الفاتورة</th><th>العميل</th><th>الإجمالي</th><th>التاريخ</th></tr></thead>
            <tbody>
                <?php if(!empty($invoices)): foreach($invoices as $inv): ?>
                    <tr>
                        <td><b>#<?php echo $inv['id']; ?></b></td>
                        <td><?php echo htmlspecialchars($inv['customer_name'] ?? 'عميل نقدي'); ?></td>
                        <td style="color:#38ef7d; font-weight:bold;"><?php echo number_format($inv['total'],2); ?> ر.ي</td>
                        <td style="font-size:10px; color:#aaa;"><?php echo $inv['date']; ?></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="4" style="color:#aaa;">لا توجد فواتير مسجلة حتى الآن.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php include 'signature.php'; ?>
    <a href="main.php" style="display:block; text-align:center; color:#ffd200; text-decoration:none; font-size:12px; margin-top:10px;">⬅️ العودة للرئيسية</a>
</div>

<?php include 'nav_bar.php'; ?>

<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
