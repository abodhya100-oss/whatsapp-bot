<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
if(session_status()===PHP_SESSION_NONE){session_start();} if(!isset($_SESSION["user_role"])){header("Location: login.php");exit;}
$db = new SQLite3(__DIR__ . '/vmax.db');
$db->exec("CREATE TABLE IF NOT EXISTS staff (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, phone TEXT, salary REAL DEFAULT 0, notes TEXT)");
$db->exec("CREATE TABLE IF NOT EXISTS staff_advances (id INTEGER PRIMARY KEY AUTOINCREMENT, staff_id INTEGER, amount REAL, notes TEXT, date TEXT)");

$msg = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_staff'])) {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $salary = floatval($_POST['salary'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    if(!empty($name)) {
        $stmt = $db->prepare("INSERT INTO staff (name, phone, salary, notes) VALUES (:n, :p, :s, :no)");
        $stmt->bindValue(':n', $name, SQLITE3_TEXT);
        $stmt->bindValue(':p', $phone, SQLITE3_TEXT);
        $stmt->bindValue(':s', $salary, SQLITE3_FLOAT);
        $stmt->bindValue(':no', $notes, SQLITE3_TEXT);
        $stmt->execute();
        $stmt->close();
        $msg = "<div style='background:rgba(56,239,125,0.2); color:#38ef7d; padding:8px; border-radius:8px; text-align:center; font-size:11px; margin-bottom:8px; font-weight:bold;'>✅ تم إضافة الموظف بنجاح!</div>";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_advance'])) {
    $staff_id = intval($_POST['staff_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    $date = date('Y-m-d H:i:s');
    if($staff_id > 0 && $amount > 0) {
        $stmt = $db->prepare("INSERT INTO staff_advances (staff_id, amount, notes, date) VALUES (:sid, :amt, :notes, :date)");
        $stmt->bindValue(':sid', $staff_id, SQLITE3_INTEGER);
        $stmt->bindValue(':amt', $amount, SQLITE3_FLOAT);
        $stmt->bindValue(':notes', $notes, SQLITE3_TEXT);
        $stmt->bindValue(':date', $date, SQLITE3_TEXT);
        $stmt->execute();
        $stmt->close();
        $msg = "<div style='background:rgba(56,239,125,0.2); color:#38ef7d; padding:8px; border-radius:8px; text-align:center; font-size:11px; margin-bottom:8px; font-weight:bold;'>✅ تم تسجيل السلفة النقدية بنجاح!</div>";
    }
}

if(isset($_GET['delete'])) {
    $db->exec("DELETE FROM staff WHERE id = " . intval($_GET['delete']));
    header("Location: payroll.php");
    exit;
}

$staff_list = [];
$res_st = $db->query("SELECT * FROM staff ORDER BY name ASC");
while($st = $res_st->fetchArray(SQLITE3_ASSOC)) {
    // حساب مجموع السلف للموظف
    $adv = $db->querySingle("SELECT SUM(amount) FROM staff_advances WHERE staff_id = " . $st['id']) ?: 0;
    $st['total_advances'] = $adv;
    $st['net_salary'] = $st['salary'] - $adv;
    $staff_list[] = $st;
}
$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <link rel="manifest" href="manifest.json">
    <meta name='mobile-web-app-capable' content='yes'>
    <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js");}</script>
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>رواتب وسلف الموظفين - ابوضياء العالمي</title>
    <style>
        body { font-family: 'Tahoma', sans-serif; background: #0f2027; color: #fff; margin: 0; padding: 10px; padding-bottom: 90px; }
        .container { max-width: 680px; margin: auto; }
        h1 { text-align: center; color: #ffd200; font-size: 19px; margin-bottom: 3px; }
        p.sub-title { text-align: center; color: #aaa; font-size: 11px; margin-bottom: 10px; }
        .card { background: rgba(255,255,255,0.08); padding: 12px; border-radius: 12px; margin-bottom: 10px; border: 1px solid rgba(255,255,255,0.15); }
        label { display: block; font-size: 11px; color: #ffd200; margin-bottom: 3px; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 9px; margin-bottom: 8px; background: rgba(0,0,0,0.6); color: #fff; border: 1px solid #555; border-radius: 8px; box-sizing: border-box; font-size: 12px; }
        select option { background: #203a43; color: #fff; }
        textarea { height: 45px; resize: vertical; }
        .btn { width: 100%; background: #ffd200; color: #000; padding: 11px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 13px; }
        .btn-adv { background: #17a2b8; color: #fff; }
        .table { width: 100%; border-collapse: collapse; font-size: 11px; background: rgba(0,0,0,0.4); border-radius: 8px; overflow: hidden; margin-top: 6px; }
        .table th, .table td { padding: 8px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .table th { background: rgba(255,210,0,0.2); color: #ffd200; }
        .btn-del { background: #ff4d4d; color: #fff; padding: 4px 8px; border-radius: 5px; text-decoration: none; font-weight: bold; font-size: 10px; }
        .nav-bar { position: fixed; bottom: 0; left: 0; right: 0; background: #0f2027; border-top: 1px solid rgba(255,210,0,0.2); padding: 6px 0; text-align: center; z-index: 1000; }
        .nav-links { display: flex; justify-content: space-around; font-size: 12px; }
        .nav-links a { color: #aaa; text-decoration: none; font-weight: bold; text-align: center; }
        .nav-links a.active { color: #ffd200; }
    </style>
</head>
<body>
<div class="container">
    <h1>💼 إدارة رواتب وسلف الموظفين</h1>
    <p class="sub-title">تتبع المرتبات، السلف النقدية، وصافي المستحقات</p>
    <?php echo $msg; ?>
    
    <div class="card">
        <b style="color:#ffd200; font-size:12px; display:block; margin-bottom:6px;">👤 إضافة موظف جديد:</b>
        <form method="POST">
            <label>اسم الموظف:</label>
            <input type="text" name="name" placeholder="مثال: محمد علي (كاشير)" required>
            <label>هاتف الموظف:</label>
            <input type="text" name="phone" placeholder="77xxxxxxx">
            <label>الراتب الأساسي:</label>
            <input type="number" step="0.01" name="salary" placeholder="0.00" required>
            <button type="submit" name="add_staff" class="btn">💾 حفظ الموظف</button>
        </form>
    </div>

    <div class="card">
        <b style="color:#17a2b8; font-size:12px; display:block; margin-bottom:6px;">💸 تسجيل سلفة نقدية للموظف:</b>
        <form method="POST">
            <label>اختر الموظف:</label>
            <select name="staff_id" required>
                <option value="">-- اختر الموظف --</option>
                <?php foreach($staff_list as $st): ?>
                    <option value="<?php echo $st['id']; ?>"><?php echo htmlspecialchars($st['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <label>مبلغ السلفة:</label>
            <input type="number" step="0.01" name="amount" placeholder="0.00" required>
            <label>ملاحظات السلفة:</label>
            <textarea name="notes" placeholder="سبب السلفة، عهدة..."></textarea>
            <button type="submit" name="add_advance" class="btn btn-adv">💾 تسجيل السلفة</button>
        </form>
    </div>

    <div class="card">
        <b style="color:#ffd200; font-size:12px; display:block; margin-bottom:6px;">📋 قائمة الموظفين والحسابات:</b>
        <table class="table">
            <thead><tr><th>الموظف</th><th>الراتب</th><th>السلف</th><th>الصافي</th><th>إجراء</th></tr></thead>
            <tbody>
                <?php if(!empty($staff_list)): foreach($staff_list as $st): ?>
                    <tr>
                        <td><b><?php echo htmlspecialchars($st['name']); ?></b></td>
                        <td><?php echo number_format($st['salary'],2); ?></td>
                        <td style="color:#ff4d4d;"><?php echo number_format($st['total_advances'],2); ?></td>
                        <td style="color:#38ef7d; font-weight:bold;"><?php echo number_format($st['net_salary'],2); ?></td>
                        <td>
                            <a href="payroll.php?delete=<?php echo $st['id']; ?>" class="btn-del" onclick="return confirm('حذف هذا الموظف؟');">حذف</a>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="5" style="color:#aaa;">لا توجد موظفين مسجلين.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php include 'signature.php'; ?>
    <a href="main.php" style="display:block; text-align:center; color:#ffd200; text-decoration:none; font-size:12px; margin-top:10px;">⬅️ العودة للرئيسية</a>
</div>

<?php include 'nav_bar.php'; ?>

<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
