<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
if(session_status()===PHP_SESSION_NONE){session_start();} if(!isset($_SESSION["user_role"])){header("Location: login.php");exit;}
$db = new SQLite3(__DIR__ . '/vmax.db');
$products = [];
$res = $db->query("SELECT * FROM products WHERE stock <= 5 ORDER BY stock ASC");
while($p = $res->fetchArray(SQLITE3_ASSOC)) { $products[] = $p; }
$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <link rel="manifest" href="manifest.json">
    <meta name='mobile-web-app-capable' content='yes'>
    <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js");}</script>
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>تنبيهات نقص المخزون - نظام ابوضياء العالمي</title>
    <style>
        body { font-family: 'Tahoma', sans-serif; background: #0f2027; color: #fff; margin: 0; padding: 10px; padding-bottom: 90px; }
        .container { max-width: 680px; margin: auto; }
        h1 { text-align: center; color: #ffd200; font-size: 19px; margin-bottom: 3px; }
        p.sub-title { text-align: center; color: #aaa; font-size: 11px; margin-bottom: 10px; }
        .card { background: rgba(255,255,255,0.08); padding: 12px; border-radius: 12px; margin-bottom: 10px; border: 1px solid rgba(255,255,255,0.15); }
        .prod-row { background: rgba(0,0,0,0.4); padding: 9px; border-radius: 8px; margin-bottom: 6px; border-right: 3px solid #ff4d4d; font-size: 11px; display: flex; justify-content: space-between; align-items: center; }
        .nav-bar { position: fixed; bottom: 0; left: 0; right: 0; background: #0f2027; border-top: 1px solid rgba(255,210,0,0.2); padding: 6px 0; text-align: center; z-index: 1000; }
        .nav-links { display: flex; justify-content: space-around; font-size: 12px; }
        .nav-links a { color: #aaa; text-decoration: none; font-weight: bold; text-align: center; }
        .nav-links a.active { color: #ffd200; }
    </style>
</head>
<body>
<div class="container">
    <h1>🚨 تنبيهات النواقص وانخفاض المخزون</h1>
    <p class="sub-title">الأصناف التي كميتها 5 أو أقل بالمستودع</p>
    <div class="card">
        <?php if(!empty($products)): foreach($products as $p): ?>
            <div class="prod-row">
                <div>
                    <b><?php echo htmlspecialchars($p['name']); ?></b>
                    <div style="color:#aaa; font-size:10px;">التصنيف: <?php echo $p['category']; ?></div>
                </div>
                <div>
                    <span style="background:rgba(255,77,77,0.2); color:#ff4d4d; padding:4px 8px; border-radius:5px; font-weight:bold; font-size:11px;">متبقي: <?php echo $p['stock']; ?> <?php echo $p['unit']; ?></span>
                </div>
            </div>
        <?php endforeach; else: ?>
            <p style="text-align:center; color:#38ef7d; font-size:12px; padding:15px;">🌟 ممتاز! لا توجد أصناف منخفضة المخزون حالياً.</p>
        <?php endif; ?>
    </div>
    <a href="main.php" style="display:block; text-align:center; color:#ffd200; text-decoration:none; font-size:12px; margin-top:10px;">⬅️ العودة للرئيسية</a>
</div>

<?php include 'nav_bar.php'; ?>

<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
