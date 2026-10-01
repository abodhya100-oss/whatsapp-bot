<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
if(session_status()===PHP_SESSION_NONE){session_start();} if(!isset($_SESSION["user_role"])){header("Location: login.php");exit;}
$db = new SQLite3(__DIR__ . '/vmax.db');
$db->exec("CREATE TABLE IF NOT EXISTS shifts (id INTEGER PRIMARY KEY AUTOINCREMENT, opened_at DATETIME, closed_at DATETIME, opening_cash REAL, expected_cash REAL, actual_cash REAL, status TEXT DEFAULT 'closed')");

$msg = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['close_shift'])) {
    $actual_cash = floatval($_POST['actual_cash'] ?? 0);
    $expected_cash = floatval($_POST['expected_cash'] ?? 0);
    $db->exec("INSERT INTO shifts (opened_at, closed_at, opening_cash, expected_cash, actual_cash, status) VALUES (datetime('now', '-8 hours'), datetime('now'), 0, $expected_cash, $actual_cash, 'closed')");
    $msg = "<div style='background:rgba(56,239,125,0.3); color:#38ef7d; padding:8px; border-radius:6px; text-align:center; font-size:11px; margin-bottom:8px;'>✅ تم إغلاق الوردية بنجاح واعتماد تقرير الصندوق!</div>";
}

$today = date('Y-m-d');
$res = $db->querySingle("SELECT SUM(paid_amount) as total_cash FROM invoices WHERE date(created_date) = '$today'", true);
$expected_cash = floatval($res['total_cash'] ?? 0);
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
    <title>إغلاق الوردية والصندوق - نظام ابوضياء العالمي</title>
    <style>
        body { font-family: 'Tahoma', sans-serif; background: #0f2027; color: #fff; margin: 0; padding: 10px; }
        .container { max-width: 600px; margin: auto; }
        h1 { text-align: center; color: #ffd200; font-size: 18px; margin-bottom: 5px; }
        .card { background: rgba(255,255,255,0.08); padding: 15px; border-radius: 12px; margin-bottom: 12px; border: 1px solid rgba(255,255,255,0.15); }
        label { display: block; font-size: 11px; color: #ffd200; margin-bottom: 4px; font-weight: bold; }
        input { width: 100%; padding: 10px; margin-bottom: 10px; background: rgba(0,0,0,0.6); color: #fff; border: 1px solid #555; border-radius: 8px; box-sizing: border-box; font-size: 14px; font-weight: bold; text-align: center; }
        .btn { width: 100%; background: #ffd200; color: #000; padding: 12px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 13px; }
        .btn-back { display: block; text-align: center; margin-top: 10px; color: #aaa; text-decoration: none; font-size: 11px; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔒 إغلاق الوردية وتقفيل الصندوق (Z-Report)</h1>
    <p style="text-align:center; color:#aaa; font-size:11px; margin-bottom:15px;">نظام ابوضياء العالمي</p>
    <?php echo $msg; ?>
    <div class="card">
        <form method="POST">
            <label>💵 النقدية المتوقعة في الصندوق (حسب المبيعات النقدية اليوم):</label>
            <input type="text" value="<?php echo number_format($expected_cash, 2); ?> ر.ي" readonly style="color:#38ef7d;">
            <input type="hidden" name="expected_cash" value="<?php echo $expected_cash; ?>">

            <label>💰 النقدية الفعلية الموجودة في الدرج (عدها يدوياً):</label>
            <input type="number" step="0.01" name="actual_cash" placeholder="أدخل المبلغ العددي الموجود بالدرج..." required style="color:#ffd200;">

            <button type="submit" name="close_shift" class="btn">🔒 اعتماد إغلاق الوردية وتصفير الدرج</button>
        </form>
    </div>
    <a href="main.php" class="btn-back">⬅️ العودة للرئيسية</a>
</div>

<?php include 'nav_bar.php'; ?>
<?php include "nav_menu.php"; ?>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>
</html>

