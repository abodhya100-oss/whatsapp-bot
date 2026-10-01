<?php
// reminders.php - نظام التذكيرات والمواعيد لـ نظام ابوضياء العالمي
error_reporting(E_ALL);
ini_set('display_errors', 1);

$db_file = __DIR__ . '/vmax.db';
if(session_status()===PHP_SESSION_NONE){session_start();} if(!isset($_SESSION["user_role"])){header("Location: login.php");exit;}
$db = new SQLite3($db_file);

// إنشاء جدول التذكيرات
$db->exec("CREATE TABLE IF NOT EXISTS reminders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_id INTEGER,
    title TEXT NOT NULL,
    reminder_date DATETIME,
    status TEXT DEFAULT 'pending',
    notes TEXT,
    created_date DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$msg = "";

// إضافة تذكير جديد
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_reminder'])) {
    $customer_id = intval($_POST['customer_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $reminder_date = trim($_POST['reminder_date'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (!empty($title) && !empty($reminder_date)) {
        $stmt = $db->prepare("INSERT INTO reminders (customer_id, title, reminder_date, notes) VALUES (:cust, :title, :date, :notes)");
        $stmt->bindValue(':cust', $customer_id, SQLITE3_INTEGER);
        $stmt->bindValue(':title', $title, SQLITE3_TEXT);
        $stmt->bindValue(':date', $reminder_date, SQLITE3_TEXT);
        $stmt->bindValue(':notes', $notes, SQLITE3_TEXT);
        
        if ($stmt->execute()) {
            $msg = "<div style='background:rgba(40,167,69,0.3); color:#28a745; padding:10px; border-radius:10px; text-align:center; font-weight:bold; margin-bottom:10px;'>✅ تم حفظ التذكير بنجاح!</div>";
        }
        $stmt->close();
    } else {
        $msg = "<div style='background:rgba(255,193,7,0.3); color:#ffc107; padding:10px; border-radius:10px; text-align:center; font-weight:bold; margin-bottom:10px;'>⚠️ يرجى إدخال عنوان التذكير وتاريخه.</div>";
    }
}

// تغيير حالة التذكير (منجز / قيد الانتظار)
if (isset($_GET['action']) && $_GET['action'] == 'done') {
    $rem_id = intval($_GET['id'] ?? 0);
    $db->exec("UPDATE reminders SET status = 'done' WHERE id = $rem_id");
    header("Location: reminders.php");
    exit;
}

// جلب العملاء
$customers_res = $db->query("SELECT id, name FROM customers ORDER BY name ASC");
$customers_list = [];
while ($c = $customers_res->fetchArray(SQLITE3_ASSOC)) {
    $customers_list[] = $c;
}

// جلب التذكيرات
$reminders_res = $db->query("
    SELECT r.*, c.name as customer_name 
    FROM reminders r 
    LEFT JOIN customers c ON r.customer_id = c.id 
    ORDER BY r.reminder_date ASC
");
$reminders_list = [];
while ($r = $reminders_res->fetchArray(SQLITE3_ASSOC)) {
    $reminders_list[] = $r;
}
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
    <title>نظام ابوضياء العالمي - التذكيرات والمواعيد</title>
    <style>
        body { font-family: 'Tahoma', sans-serif; background: linear-gradient(135deg, #0f2027, #203a43, #2c5364); color: #fff; margin: 0; padding: 0; padding-bottom: 90px; }
        .container { max-width: 650px; margin: 15px auto; padding: 15px; }
        h1 { text-align: center; font-size: 20px; margin-bottom: 4px; color: #f7b733; }
        p.sub-title { text-align: center; color: #bbb; font-size: 12px; margin-bottom: 12px; }
        .card { background: rgba(255, 255, 255, 0.08); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.15); border-radius: 14px; padding: 12px; margin-bottom: 12px; box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37); }
        label { display: block; font-size: 11px; color: #f7b733; margin-bottom: 3px; font-weight: bold; }
        select, input, textarea { width: 100%; padding: 9px; margin-bottom: 8px; border: 1px solid rgba(255, 255, 255, 0.25); background: rgba(0, 0, 0, 0.5); color: #fff; border-radius: 8px; box-sizing: border-box; font-size: 13px; }
        select option { background: #203a43; color: white; }
        .row-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .btn-save { width: 100%; background: linear-gradient(135deg, #f7b733, #fc4a1a); color: white; border: none; padding: 12px; border-radius: 10px; font-size: 15px; font-weight: bold; cursor: pointer; }
        .rem-card { background: rgba(0, 0, 0, 0.3); padding: 10px 12px; border-radius: 10px; margin-bottom: 8px; border-right: 4px solid #f7b733; font-size: 12px; }
        .badge-pending { background: #d35400; color: #fff; padding: 2px 5px; border-radius: 4px; font-size: 9px; }
        .badge-done { background: #27ae60; color: #fff; padding: 2px 5px; border-radius: 4px; font-size: 9px; }
        .btn-done { display: inline-block; background: #27ae60; color: #fff; padding: 3px 8px; border-radius: 4px; font-size: 10px; text-decoration: none; font-weight: bold; margin-top: 4px; }
        .nav-bar { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(15, 32, 39, 0.95); backdrop-filter: blur(10px); display: flex; justify-content: space-around; padding: 10px 0; border-top: 1px solid rgba(255, 255, 255, 0.1); z-index: 1000; }
        .nav-item { text-align: center; color: #aaa; text-decoration: none; font-size: 12px; }
        .nav-item.active, .nav-item:hover { color: #00f2fe; }
        .nav-item div { font-size: 20px; margin-bottom: 2px; }
    </style>
</head>
<body>

<div class="container">
    <h1>⏰ التذكيرات والمواعيد</h1>
    <p class="sub-title">إدارة المتابعات والتحصيلات الميدانية</p>

    <?php echo $msg; ?>

    <!-- نموذج إضافة تذكير -->
    <div class="card">
        <form method="POST">
            <label>📌 عنوان التذكير / المهمة:</label>
            <input type="text" name="title" placeholder="مثال: متابعة تحصيل دين محلات صبري..." required>

            <div class="row-grid">
                <div>
                    <label>👤 العميل المرتبط (اختياري):</label>
                    <select name="customer_id">
                        <option value="0">-- بدون عميل محدد --</option>
                        <?php foreach ($customers_list as $cust): ?>
                            <option value="<?php echo $cust['id']; ?>"><?php echo htmlspecialchars($cust['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>📅 موعد التذكير (التاريخ والوقت):</label>
                    <input type="datetime-local" name="reminder_date" required>
                </div>
            </div>

            <label>📝 ملاحظات تفصيلية:</label>
            <input type="text" name="notes" placeholder="ملاحظات إضافية...">

            <button type="submit" name="add_reminder" class="btn-save">⏰ إضافة وتثبيت التذكير</button>
        </form>
    </div>

    <!-- قائمة التذكيرات -->
    <div class="card">
        <h3 style="margin-top:0; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:6px; font-size: 13px; color: #f7b733;">📋 قائمة المواعيد والتذكيرات</h3>
        
        <?php if (count($reminders_list) > 0): ?>
            <?php foreach ($reminders_list as $rem): 
                $is_done = ($rem['status'] == 'done');
            ?>
                <div class="rem-card" style="border-right-color: <?php echo $is_done ? '#27ae60' : '#f7b733'; ?>; opacity: <?php echo $is_done ? '0.6' : '1'; ?>;">
                    <div style="display:flex; justify-content:space-between; font-size:11px; margin-bottom:2px;">
                        <div>
                            <span class="<?php echo $is_done ? 'badge-done' : 'badge-pending'; ?>">
                                <?php echo $is_done ? 'منجز' : 'قيد الانتظار'; ?>
                            </span>
                            <strong><?php echo htmlspecialchars($rem['title']); ?></strong>
                        </div>
                        <span style="color:#ffd200; font-size:10px; font-weight:bold;">🕒 <?php echo $rem['reminder_date']; ?></span>
                    </div>
                    <?php if(!empty($rem['customer_name'])): ?>
                        <div style="color:#fff; margin-top:2px;">العميل: <b><?php echo htmlspecialchars($rem['customer_name']); ?></b></div>
                    <?php endif; ?>
                    <?php if(!empty($rem['notes'])): ?>
                        <div style="color:#bbb; font-size:11px;">ملاحظات: <?php echo htmlspecialchars($rem['notes']); ?></div>
                    <?php endif; ?>
                    
                    <?php if(!$is_done): ?>
                        <div style="text-align: left;">
                            <a href="reminders.php?action=done&id=<?php echo $rem['id']; ?>" class="btn-done">✔️ تم الإنجاز</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align: center; color: #aaa; font-size: 11px;">لا توجد تذكيرات مسجلة حتى الآن.</p>
        <?php endif; ?>
    </div>
</div>


<?php include 'nav_bar.php'; ?>

<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
