<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if(session_status()===PHP_SESSION_NONE){session_start();} if(!isset($_SESSION["user_role"])){header("Location: login.php");exit;}
$db = new SQLite3('/data/data/com.termux/files/home/secure_dbs/vmax.db');

$db->exec("CREATE TABLE IF NOT EXISTS repairs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_name TEXT,
    phone TEXT,
    device_model TEXT,
    imei TEXT,
    issue TEXT,
    cost REAL,
    status TEXT,
    date TEXT
)");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_repair'])) {
    $cname = trim($_POST['customer_name']);
    $phone = trim($_POST['phone']);
    $model = trim($_POST['device_model']);
    $imei = trim($_POST['imei']);
    $issue = trim($_POST['issue']);
    $cost = floatval($_POST['cost']);
    $status = 'تحت الصيانة';
    $date = date('Y-m-d H:i:s');

    if (!empty($cname) && !empty($model)) {
        $stmt = $db->prepare("INSERT INTO repairs (customer_name, phone, device_model, imei, issue, cost, status, date) VALUES (:c, :p, :m, :i, :iss, :cost, :st, :dt)");
        $stmt->bindValue(':c', $cname, SQLITE3_TEXT);
        $stmt->bindValue(':p', $phone, SQLITE3_TEXT);
        $stmt->bindValue(':m', $model, SQLITE3_TEXT);
        $stmt->bindValue(':i', $imei, SQLITE3_TEXT);
        $stmt->bindValue(':iss', $issue, SQLITE3_TEXT);
        $stmt->bindValue(':cost', $cost, SQLITE3_FLOAT);
        $stmt->bindValue(':st', $status, SQLITE3_TEXT);
        $stmt->bindValue(':dt', $date, SQLITE3_TEXT);
        $stmt->execute();
        header("Location: repairs.php");
        exit;
    }
}

if (isset($_GET['update_status']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $st = $_GET['update_status'];
    $db->exec("UPDATE repairs SET status = '$st' WHERE id = $id");
    header("Location: repairs.php");
    exit;
}

$res = $db->query("SELECT * FROM repairs ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <link rel="manifest" href="manifest.json">
    <meta name='mobile-web-app-capable' content='yes'>
    <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js");}</script>
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>نظام تذاكر الصيانة (النظام المحاسبي العام)</title>
    <style>
        body { font-family: Tahoma, sans-serif; background: #FFFFFF; color: #f8fafc; margin: 0; padding: 8px; padding-bottom: 75px !important; direction: rtl; }
        .header { text-align: center; font-size: 16px; font-weight: bold; color: #38bdf8; margin-bottom: 5px; }
        .card-box { background: #FFFFFF; border: 1px solid #334155; border-radius: 6px; padding: 10px; margin-bottom: 8px; }
        .label { font-size: 11px; color: #cbd5e1; margin-bottom: 4px; font-weight: bold; }
        .input-box { width: 100%; padding: 8px; background: #FFFFFF; color: #fff; border: 1px solid #38bdf8; border-radius: 6px; font-size: 12px; margin-bottom: 6px; box-sizing: border-box; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #334155; padding: 6px 4px; text-align: center; font-size: 10px; }
        th { background: #FFFFFF; color: #fff; }
        td { background: #FFFFFF; color: #f8fafc; }
        .btn { background: #FFFFFF; color: #fff; border: none; padding: 8px 12px; border-radius: 6px; font-weight: bold; font-size: 12px; cursor: pointer; width: 100%; }
        .badge { padding: 2px 6px; border-radius: 4px; font-size: 9px; font-weight: bold; display: inline-block; }
        .st-repair { background: #FFFFFF; color: #0f172a; }
        .st-parts { background: #FFFFFF; color: #fff; }
        .st-ready { background: #FFFFFF; color: #fff; }
        .st-done { background: #FFFFFF; color: #fff; }
        .nav-bar { position: fixed; bottom: 12px; left: 12px; right: 12px; z-index: 9999; display: flex; justify-content: space-around; background: rgba(30, 41, 59, 0.92); backdrop-filter: blur(10px); padding: 10px 5px; border-radius: 14px; border: 1px solid rgba(51, 65, 85, 0.8); }
        .nav-item { text-decoration: none; color: #94a3b8; font-size: 11px; font-weight: bold; text-align: center; }
        .nav-item:hover, .nav-item.active { color: #38bdf8; }
    </style>
</head>
<body>
    <div style="display: flex; gap: 5px;">
    </div>
    <div style="display: flex; gap: 4px; align-items: center;">
        <span style="color: #94a3b8; font-size: 10px; font-weight: bold;">تكبير الشاشة:</span>
    </div>
</div>
</div>
    <div class="header">🛠️ نظام تذاكر الصيانة والأجهزة (النظام المحاسبي العام)</div>

    <div class="card-box">
        <form method="POST">
            <div style="display: flex; gap: 6px;">
                <div style="flex: 1;"><div class="label">اسم الزبون:</div><input type="text" name="customer_name" class="input-box" required placeholder="اسم العميل..."></div>
                <div style="flex: 1;"><div class="label">رقم الجوال:</div><input type="text" name="phone" class="input-box" placeholder="77xxxxxxx"></div>
            </div>
            <div style="display: flex; gap: 6px;">
                <div style="flex: 1;"><div class="label">مواصفات الجهاز / الموديل:</div><input type="text" name="device_model" class="input-box" required placeholder="مثال: ايفون 13 برو..."></div>
                <div style="flex: 1;"><div class="label">رقم IMEI / السيريال:</div><input type="text" name="imei" class="input-box" placeholder="اختياري للضمان"></div>
            </div>
            <div style="display: flex; gap: 6px;">
                <div style="flex: 2;"><div class="label">وصف العطل المطلوب صيانته:</div><input type="text" name="issue" class="input-box" required placeholder="شاشة، تفليش، شحن..."></div>
                <div style="flex: 1;"><div class="label">التكلفة المقدرة (ر.ي):</div><input type="number" step="any" name="cost" class="input-box" value="0" placeholder="0"></div>
            </div>
            <button type="submit" name="add_repair" class="btn">➕ تسجيل وتوليد تذكرة صيانة جديدة</button>
        </form>
    </div>

    <div class="card-box">
        <div class="label">📋 قائمة الأجهزة والسيارات تحت الصيانة:</div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>الزبون والجوال</th>
                    <th>الجهاز / IMEI</th>
                    <th>العطل والتكلفة</th>
                    <th>الحالة الحالية</th>
                    <th>تغيير الحالة</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $res->fetchArray(SQLITE3_ASSOC)): 
                    $st_class = 'st-repair';
                    if($row['status'] == 'بانتظار القطع') $st_class = 'st-parts';
                    if($row['status'] == 'جاهز للاستلام') $st_class = 'st-ready';
                    if($row['status'] == 'تم التسليم') $st_class = 'st-done';
                ?>
                <tr>
                    <td style="font-weight: bold; color: #38bdf8;">#<?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['customer_name']); ?><br><span style="color:#94a3b8; font-size:9px;"><?php echo $row['phone']; ?></span></td>
                    <td><b><?php echo htmlspecialchars($row['device_model']); ?></b><br><span style="font-size:8px; color:#cbd5e1;"><?php echo $row['imei']; ?></span></td>
                    <td><?php echo htmlspecialchars($row['issue']); ?><br><b style="color:#4ade80;"><?php echo $row['cost']; ?> ر.ي</b></td>
                    <td><span class="badge <?php echo $st_class; ?>"><?php echo $row['status']; ?></span></td>
                    <td>
                        <select onchange="location.href='repairs.php?id=<?php echo $row['id']; ?>&update_status=' + encodeURIComponent(this.value)" style="background: #FFFFFF; color:#fff; border:1px solid #38bdf8; font-size:9px; padding:2px; border-radius:4px;">
                            <option value="">تحديث...</option>
                            <option value="تحت الصيانة">تحت الصيانة</option>
                            <option value="بانتظار القطع">بانتظار القطع</option>
                            <option value="جاهز للاستلام">جاهز للاستلام</option>
                            <option value="تم التسليم">تم التسليم</option>
                        </select>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>
</html>

