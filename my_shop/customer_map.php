<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
if(session_status()===PHP_SESSION_NONE){session_start();} if(!isset($_SESSION["user_role"])){header("Location: login.php");exit;}
$db = new SQLite3(__DIR__ . '/vmax.db');
$customers = [];
$res = $db->query("SELECT * FROM customers ORDER BY name ASC");
while($c = $res->fetchArray(SQLITE3_ASSOC)) { $customers[] = $c; }
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
    <title>خريطة مواقع العملاء GPS - ابوضياء العالمي</title>
    <style>
        body { font-family: 'Tahoma', sans-serif; background: #0f2027; color: #fff; margin: 0; padding: 10px; padding-bottom: 90px; }
        .container { max-width: 680px; margin: auto; }
        h1 { text-align: center; color: #ffd200; font-size: 19px; margin-bottom: 3px; }
        p.sub-title { text-align: center; color: #aaa; font-size: 11px; margin-bottom: 10px; }
        .card { background: rgba(255,255,255,0.08); padding: 12px; border-radius: 12px; margin-bottom: 10px; border: 1px solid rgba(255,255,255,0.15); display: flex; justify-content: space-between; align-items: center; }
        .btn-map { background: #25d366; color: #fff; padding: 6px 10px; border-radius: 6px; text-decoration: none; font-size: 11px; font-weight: bold; }
        .nav-bar { position: fixed; bottom: 0; left: 0; right: 0; background: #0f2027; border-top: 1px solid rgba(255,210,0,0.2); padding: 6px 0; text-align: center; z-index: 1000; }
        .nav-links { display: flex; justify-content: space-around; font-size: 12px; }
        .nav-links a { color: #aaa; text-decoration: none; font-weight: bold; text-align: center; }
        .nav-links a.active { color: #ffd200; }
    </style>
</head>
<body>
<div class="container">
    <h1>🗺️ خريطة وتتبع مواقع العملاء GPS</h1>
    <p class="sub-title">الوصول المباشر لمواقع الزبائن عبر خرائط جوجل</p>
    
    <?php if(!empty($customers)): foreach($customers as $c): 
        $lat = $c['latitude'] ?? 0;
        $lng = $c['longitude'] ?? 0;
    ?>
        <div class="card">
            <div>
                <b><?php echo htmlspecialchars($c['name']); ?></b>
                <div style="color:#aaa; font-size:10px;">هاتف: <?php echo $c['phone']; ?> | المنطقة: <?php echo $c['region']; ?></div>
            </div>
            <div>
                <?php if($lat != 0 && $lng != 0): ?>
                    <a href="https://maps.google.com/?q=<?php echo $lat; ?>,<?php echo $lng; ?>" target="_blank" class="btn-map">🗺️ فتح الخريطة</a>
                <?php else: ?>
                    <span style="color:#aaa; font-size:10px;">بدون إحداثيات</span>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; else: ?>
        <p style="text-align:center; color:#aaa; font-size:12px;">لا توجد عملاء مسجلين.</p>
    <?php endif; ?>

    <?php include 'signature.php'; ?>
    <a href="main.php" style="display:block; text-align:center; color:#ffd200; text-decoration:none; font-size:12px; margin-top:10px;">⬅️ العودة للرئيسية</a>
</div>

<?php include 'nav_bar.php'; ?>

<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
