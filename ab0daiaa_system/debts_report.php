<?php
require_once 'auth.php';
if(session_status()===PHP_SESSION_NONE){session_start();} if(!isset($_SESSION["user_role"])){header("Location: login.php");exit;}
$db = new SQLite3('/data/data/com.termux/files/home/secure_dbs/vmax.db');
$list = $db->query("SELECT * FROM customers WHERE balance > 0 ORDER BY balance DESC");
$total_debts = $db->querySingle("SELECT SUM(balance) FROM customers WHERE balance > 0") ?: 0;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl"><head>
    <link rel="manifest" href="manifest.json">
    <meta name='mobile-web-app-capable' content='yes'>
    <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js");}</script><meta charset="UTF-8"><meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>تقرير المديونيات</title><style>body{font-family:Tahoma;background: #FFFFFF;color:#fff;padding:15px;}.card{background:rgba(255,255,255,0.08);padding:12px;border-radius:12px;margin-bottom:12px;}.row{background:rgba(0,0,0,0.3);padding:8px;border-radius:6px;margin-bottom:6px;display:flex;justify-content:space-between;}</style></head>
<body><div style="max-width:600px;margin:auto;"><h2 style="text-align:center;color:#ff4d4d;">🚨 تقرير مديونيات العملاء</h2>
    <div style="display: flex; gap: 5px;">
    </div>
    <div style="display: flex; gap: 4px; align-items: center;">
        <span style="color: #94a3b8; font-size: 10px; font-weight: bold;">تكبير الشاشة:</span>
    </div>
</div>
</div>
<div class="card" style="text-align:center;">إجمالي الديون في السوق: <h3 style="color:#ff4d4d;margin:5px 0;"><?php echo number_format($total_debts,2); ?> ر.ي</h3></div>
<div class="card">
<?php while($c = $list->fetchArray(SQLITE3_ASSOC)): ?>
<div class="row"><span><?php echo htmlspecialchars($c['name']); ?> (<?php echo htmlspecialchars($c['phone']?:'بدون'); ?>)</span><span style="color:#ff4d4d;font-weight:bold;"><?php echo number_format($c['balance'],2); ?> ر.ي</span></div>
<?php endwhile; ?>

</div><p style="text-align:center;"><a href="main.php" style="color:#ffd200;">🏠 العودة للرئيسية</a></p></div><?php include 'nav_bar.php'; ?>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body></html>

