<?php
require_once 'auth.php';
if(session_status()===PHP_SESSION_NONE){session_start();} if(!isset($_SESSION["user_role"])){header("Location: login.php");exit;}
$db = new SQLite3(__DIR__ . '/vmax.db');
$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
$sales = $db->querySingle("SELECT SUM(total) FROM invoices WHERE DATE(created_date) BETWEEN '$from' AND '$to'") ?: 0;
$exp = $db->querySingle("SELECT SUM(amount) FROM expenses WHERE DATE(created_date) BETWEEN '$from' AND '$to'") ?: 0;
$repairs = $db->querySingle("SELECT SUM(cost) FROM repairs WHERE DATE(created_date) BETWEEN '$from' AND '$to' AND status = 'تم التسليم للعميل'") ?: 0;
$net = ($sales + $repairs) - $exp;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl"><head>
    <link rel="manifest" href="manifest.json">
    <meta name='mobile-web-app-capable' content='yes'>
    <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js");}</script><meta charset="UTF-8"><meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>الأرباح والخسائر</title><style>body{font-family:Tahoma;background:#0f2027;color:#fff;padding:15px;}.card{background:rgba(255,255,255,0.08);padding:15px;border-radius:12px;margin-bottom:12px;text-align:center;}input{padding:8px;background:#000;color:#fff;border:1px solid #444;border-radius:6px;}</style></head>
<body><div style="max-width:600px;margin:auto;"><h2 style="text-align:center;color:#ffd200;">📈 تقرير الأرباح والخسائر</h2>
<div class="card"><form method="GET">من: <input type="date" name="from" value="<?php echo $from; ?>"> إلى: <input type="date" name="to" value="<?php echo $to; ?>"> <button type="submit" style="background:#ffd200;padding:8px 15px;border:none;border-radius:6px;font-weight:bold;">عرض</button></form></div>
<div class="card"><div>إجمالي المبيعات: <b><?php echo number_format($sales,2); ?> ر.ي</b></div><div>إيرادات الصيانة المسلمة: <b><?php echo number_format($repairs,2); ?> ر.ي</b></div><div>المصروفات النثرية: <b style="color:#ff4d4d;"><?php echo number_format($exp,2); ?> ر.ي</b></div><hr style="border-color:rgba(255,255,255,0.1);"><h3 style="color:<?php echo $net>=0?'#38ef7d':'#ff4d4d'; ?>;">صافي الربح التقديري: <?php echo number_format($net,2); ?> ر.ي</h3></div>

<p style="text-align:center;"><a href="main.php" style="color:#ffd200;">🏠 العودة للرئيسية</a></p></div><?php include 'nav_bar.php'; ?>
<?php include "nav_menu.php"; ?>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body></html>

