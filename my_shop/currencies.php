<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
if(session_status()===PHP_SESSION_NONE){session_start();} if(!isset($_SESSION["user_role"])){header("Location: login.php");exit;}
$db = new SQLite3(__DIR__ . '/vmax.db');
$db->exec("CREATE TABLE IF NOT EXISTS currencies (id INTEGER PRIMARY KEY AUTOINCREMENT, code TEXT, name TEXT, rate REAL DEFAULT 1)");

// إدخال العملات الافتراضية إذا لم تكن موجودة
$check = $db->querySingle("SELECT COUNT(*) FROM currencies");
if ($check == 0) {
    $db->exec("INSERT INTO currencies (code, name, rate) VALUES ('YER', 'ريال يمني', 1)");
    $db->exec("INSERT INTO currencies (code, name, rate) VALUES ('SAR', 'ريال سعودي', 250)");
    $db->exec("INSERT INTO currencies (code, name, rate) VALUES ('USD', 'دولار أمريكي', 930)");
}

$msg = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_rates'])) {
    foreach ($_POST['rates'] as $id => $rate) {
        $r = floatval($rate);
        $db->exec("UPDATE currencies SET rate = $r WHERE id = " . intval($id));
    }
    $msg = "<div style='background:rgba(56,239,125,0.3); color:#38ef7d; padding:8px; border-radius:6px; text-align:center; font-size:11px; margin-bottom:8px;'>✅ تم تحديث أسعار الصرف بنجاح!</div>";
}

$res = $db->query("SELECT * FROM currencies");
$currencies = [];
while($c = $res->fetchArray(SQLITE3_ASSOC)) { $currencies[] = $c; }
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
    <title>إدارة العملات والصرف - نظام ابوضياء العالمي</title>
    <style>
        body { font-family: 'Tahoma', sans-serif; background: #0f2027; color: #fff; margin: 0; padding: 10px; }
        .container { max-width: 600px; margin: auto; }
        h1 { text-align: center; color: #ffd200; font-size: 18px; margin-bottom: 5px; }
        .card { background: rgba(255,255,255,0.08); padding: 15px; border-radius: 12px; margin-bottom: 12px; border: 1px solid rgba(255,255,255,0.15); }
        label { display: block; font-size: 11px; color: #ffd200; margin-bottom: 4px; font-weight: bold; }
        input { width: 100%; padding: 10px; margin-bottom: 10px; background: rgba(0,0,0,0.6); color: #fff; border: 1px solid #555; border-radius: 8px; box-sizing: border-box; font-size: 13px; font-weight: bold; text-align: center; }
        .btn { width: 100%; background: #ffd200; color: #000; padding: 12px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 13px; }
        .btn-back { display: block; text-align: center; margin-top: 10px; color: #aaa; text-decoration: none; font-size: 11px; }
    </style>
</head>
<body>
<div class="container">
    <h1>💱 إدارة العملات وأسعار الصرف العالمية</h1>
    <p style="text-align:center; color:#aaa; font-size:11px; margin-bottom:15px;">نظام ابوضياء العالمي</p>
    <?php echo $msg; ?>
    <div class="card">
        <form method="POST">
            <?php foreach($currencies as $cur): ?>
                <label><?php echo htmlspecialchars($cur['name']); ?> (<?php echo $cur['code']; ?>) - مقابل الأساس:</label>
                <input type="number" step="0.01" name="rates[<?php echo $cur['id']; ?>]" value="<?php echo $cur['rate']; ?>" required>
            <?php endforeach; ?>
            <button type="submit" name="update_rates" class="btn">💾 حفظ وتحديث أسعار الصرف</button>
        </form>
    </div>
    <a href="main.php" class="btn-back">⬅️ العودة للرئيسية</a>
</div>

<?php include 'nav_bar.php'; ?>
<?php include "nav_menu.php"; ?>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>
</html>

