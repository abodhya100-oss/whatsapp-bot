<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
if(session_status()===PHP_SESSION_NONE){session_start();} if(!isset($_SESSION["user_role"])){header("Location: login.php");exit;}
$db = new SQLite3(__DIR__ . '/vmax.db');
$db->exec("CREATE TABLE IF NOT EXISTS maintenance (id INTEGER PRIMARY KEY AUTOINCREMENT, customer_name TEXT, device_name TEXT, issue TEXT, cost REAL DEFAULT 0, status TEXT, created_date DATETIME DEFAULT CURRENT_TIMESTAMP);");

$msg = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_repair'])) {
    $cname = trim($_POST['customer_name'] ?? '');
    $dev = trim($_POST['device_name'] ?? '');
    $issue = trim($_POST['issue'] ?? '');
    $cost = floatval($_POST['cost'] ?? 0);
    $status = trim($_POST['status'] ?? 'قيد الصيانة');

    if (!empty($cname) && !empty($dev)) {
        $stmt = $db->prepare("INSERT INTO maintenance (customer_name, device_name, issue, cost, status) VALUES (:cname, :dev, :iss, :cost, :stat)");
        $stmt->bindValue(':cname', $cname, SQLITE3_TEXT);
        $stmt->bindValue(':dev', $dev, SQLITE3_TEXT);
        $stmt->bindValue(':iss', $issue, SQLITE3_TEXT);
        $stmt->bindValue(':cost', $cost, SQLITE3_FLOAT);
        $stmt->bindValue(':stat', $status, SQLITE3_TEXT);
        $stmt->execute();
        $stmt->close();
        $msg = "✅ تم تسجيل جهاز الصيانة بنجاح!";
    }
}
if (isset($_GET['delete'])) {
    $db->exec("DELETE FROM maintenance WHERE id = " . intval($_GET['delete']));
    header("Location: maintenance.php");
    exit;
}
$res = $db->query("SELECT * FROM maintenance ORDER BY id DESC");
$repairs = [];
while($r = $res->fetchArray(SQLITE3_ASSOC)) { $repairs[] = $r; }
$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <link rel="manifest" href="manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js");}</script>
    <meta charset="UTF-8"><title>صيانة الهواتف والورشة</title>
    <style>
        body { font-family: 'Tahoma', sans-serif; background: #0f2027; color: #fff; margin: 0; padding: 10px; padding-bottom: 70px; }
        .container { max-width: 680px; margin: auto; }
        h1 { text-align: center; color: #ffd200; font-size: 19px; }
        .card { background: rgba(255,255,255,0.08); padding: 12px; border-radius: 12px; margin-bottom: 10px; border: 1px solid rgba(255,255,255,0.15); }
        input, select { width: 100%; padding: 9px; margin-bottom: 8px; background: rgba(0,0,0,0.6); color: #fff; border: 1px solid #555; border-radius: 8px; box-sizing: border-box; font-size: 12px; }
        .btn { width: 100%; background: #ffd200; color: #000; padding: 11px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; }
        .table { width: 100%; border-collapse: collapse; font-size: 11px; background: rgba(0,0,0,0.4); border-radius: 8px; overflow: hidden; }
        .table th, .table td { padding: 8px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .table th { background: rgba(255,210,0,0.2); color: #ffd200; }
    </style>
</head>
<body>
<div class="container">
    <h1>📱 صيانة الهواتف والورشة</h1>
    <?php if(!empty($msg)): ?><div style="background:rgba(56,239,125,0.2); color:#38ef7d; padding:8px; border-radius:6px; text-align:center; font-size:11px; margin-bottom:8px;"><?php echo $msg; ?></div><?php endif; ?>
    <div class="card">
        <form method="POST">
            <input type="text" name="customer_name" placeholder="اسم العميل..." required>
            <input type="text" name="device_name" placeholder="نوع الجهاز (مثال: iPhone 13 Pro)..." required>
            <input type="text" name="issue" placeholder="وصف العطل..." required>
            <input type="number" step="0.01" name="cost" placeholder="تكلفة الصيانة المتفق عليها..." required>
            <select name="status">
                <option value="قيد الصيانة">قيد الصيانة والفحص</option>
                <option value="تم الإنجاز وجاهز للاستلام">تم الإنجاز وجاهز للاستلام</option>
                <option value="تم التسليم للعميل">تم التسليم للعميل</option>
            </select>
            <button type="submit" name="add_repair" class="btn">💾 تسجيل جهاز للورشة</button>
        </form>
    </div>
    <div class="card">
        <b style="color:#ffd200; font-size:12px;">📋 أجهزة الورشة الحالية:</b>
        <table class="table" style="margin-top:8px;">
            <thead><tr><th>العميل والجهاز</th><th>العطل</th><th>التكلفة والحالة</th><th>إجراء</th></tr></thead>
            <tbody>
                <?php foreach($repairs as $r): ?>
                <tr>
                    <td style="text-align:right;"><b><?php echo htmlspecialchars($r['customer_name']); ?></b><br><span style="color:#ffd200;"><?php echo htmlspecialchars($r['device_name']); ?></span></td>
                    <td><?php echo htmlspecialchars($r['issue']); ?></td>
                    <td><span style="color:#38ef7d;"><?php echo number_format($r['cost'], 2); ?> ر.ي</span><br><b style="color:#aaa; font-size:10px;"><?php echo $r['status']; ?></b></td>
                    <td><a href="maintenance.php?delete=<?php echo $r['id']; ?>" style="color:#ff4d4d; text-decoration:none; font-weight:bold;" onclick="return confirm('حذف سجل الصيانة؟');">حذف</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <a href="main.php" style="display:block; text-align:center; color:#ffd200; text-decoration:none; font-size:11px; margin-top:10px;">⬅️ العودة للرئيسية</a>
</div>

<?php include 'nav_bar.php'; ?>
<?php include "nav_menu.php"; ?>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>
</html>

