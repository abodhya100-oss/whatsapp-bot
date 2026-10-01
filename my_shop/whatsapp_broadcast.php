<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
if(session_status()===PHP_SESSION_NONE){session_start();} if(!isset($_SESSION["user_role"])){header("Location: login.php");exit;}
$db = new SQLite3(__DIR__ . '/vmax.db');
$customers = [];
$res = $db->query("SELECT * FROM customers WHERE phone != '' ORDER BY name ASC");
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
    <title>التسويق والإرسال الجماعي واتساب - ابوضياء العالمي</title>
    <style>
        body { font-family: 'Tahoma', sans-serif; background: #0f2027; color: #fff; margin: 0; padding: 10px; padding-bottom: 90px; }
        .container { max-width: 680px; margin: auto; }
        h1 { text-align: center; color: #ffd200; font-size: 19px; margin-bottom: 3px; }
        p.sub-title { text-align: center; color: #aaa; font-size: 11px; margin-bottom: 10px; }
        .card { background: rgba(255,255,255,0.08); padding: 12px; border-radius: 12px; margin-bottom: 10px; border: 1px solid rgba(255,255,255,0.15); }
        label { display: block; font-size: 11px; color: #ffd200; margin-bottom: 3px; font-weight: bold; }
        textarea { width: 100%; height: 90px; padding: 9px; background: rgba(0,0,0,0.6); color: #fff; border: 1px solid #555; border-radius: 8px; box-sizing: border-box; font-size: 12px; resize: vertical; }
        .cust-row { background: rgba(0,0,0,0.4); padding: 8px; border-radius: 8px; margin-bottom: 6px; display: flex; justify-content: space-between; align-items: center; font-size: 11px; border-right: 3px solid #25d366; }
        .btn-wa { background: #25d366; color: #fff; padding: 5px 10px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 10px; }
        .nav-bar { position: fixed; bottom: 0; left: 0; right: 0; background: #0f2027; border-top: 1px solid rgba(255,210,0,0.2); padding: 6px 0; text-align: center; z-index: 1000; }
        .nav-links { display: flex; justify-content: space-around; font-size: 12px; }
        .nav-links a { color: #aaa; text-decoration: none; font-weight: bold; text-align: center; }
        .nav-links a.active { color: #ffd200; }
    </style>
</head>
<body>
<div class="container">
    <h1>📢 التسويق والإرسال الجماعي واتساب</h1>
    <p class="sub-title">إرسال العروض والتنبيهات للعملاء بضغطة زر</p>
    
    <div class="card">
        <label>💬 نص الرسالة التسويقية:</label>
    </div>

    <div class="card">
        <b style="color:#ffd200; font-size:12px; display:block; margin-bottom:6px;">📋 قائمة العملاء الجاهزين للإرسال (<?php echo count($customers); ?>):</b>
        <?php if(!empty($customers)): foreach($customers as $c): 
            $clean_phone = preg_replace('/[^0-9]/', '', $c['phone']);
            if(!empty($clean_phone) && substr($clean_phone, 0, 3) != '967') { $clean_phone = '967' . preg_replace('/^0/', '', $clean_phone); }
        ?>
            <div class="cust-row">
                <div>
                    <b><?php echo htmlspecialchars($c['name']); ?></b>
                    <div style="color:#aaa; font-size:10px;">هاتف: <?php echo $c['phone']; ?></div>
                </div>
                <div>
                    <?php if(!empty($clean_phone)): ?>
                        <a href="#" onclick="sendWa('<?php echo $clean_phone; ?>', this)" class="btn-wa">💬 إرسال واتساب</a>
                    <?php else: ?>
                        <span style="color:#ff4d4d; font-size:10px;">بدون هاتف صحيح</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; else: ?>
            <p style="text-align:center; color:#aaa; font-size:11px;">لا توجد عملاء بأرقام هواتف مسجلة.</p>
        <?php endif; ?>
    </div>

    <?php include 'signature.php'; ?>
    <a href="main.php" style="display:block; text-align:center; color:#ffd200; text-decoration:none; font-size:12px; margin-top:10px;">⬅️ العودة للرئيسية</a>
</div>

<?php include 'nav_bar.php'; ?>

<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
