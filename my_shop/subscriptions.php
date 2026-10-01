<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$db = new SQLite3('vmax.db', SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
$db->busyTimeout(5000);

// إنشاء الجدول والتأكد من وجود كافة الأعمدة المطلوبة لتجنب أي أخطاء
$db->exec("CREATE TABLE IF NOT EXISTS subscriptions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    store_name TEXT,
    owner_name TEXT,
    phone TEXT,
    package_type TEXT,
    start_date TEXT,
    expiry_date TEXT,
    status TEXT DEFAULT 'ACTIVE'
);");

// فحص الأعمدة وإضافتها إن لم تكن موجودة
$result = $db->query("PRAGMA table_info(subscriptions);");
$existing_columns = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $existing_columns[] = $row['name'];
}
if (!in_array('owner_name', $existing_columns)) {
    $db->exec("ALTER TABLE subscriptions ADD COLUMN owner_name TEXT;");
}
if (!in_array('phone', $existing_columns)) {
    $db->exec("ALTER TABLE subscriptions ADD COLUMN phone TEXT;");
}
if (!in_array('package_type', $existing_columns)) {
    $db->exec("ALTER TABLE subscriptions ADD COLUMN package_type TEXT;");
}

$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_sub'])) {
    $store = trim($_POST['store_name'] ?? '');
    $owner = trim($_POST['owner_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $package = trim($_POST['package_type'] ?? '');
    $start = $_POST['start_date'] ?? date('Y-m-d');
    $expiry = $_POST['expiry_date'] ?? date('Y-m-d');
    $status = $_POST['status'] ?? 'ACTIVE';
    $sub_id = intval($_POST['sub_id'] ?? 0);

    if ($sub_id > 0) {
        $stmt = $db->prepare("UPDATE subscriptions SET store_name=:st, owner_name=:ow, phone=:ph, package_type=:pk, start_date=:sd, expiry_date=:ed, status=:st_val WHERE id=:id");
        $stmt->bindValue(':id', $sub_id, SQLITE3_INTEGER);
    } else {
        $stmt = $db->prepare("INSERT INTO subscriptions (store_name, owner_name, phone, package_type, start_date, expiry_date, status) VALUES (:st, :ow, :ph, :pk, :sd, :ed, :st_val)");
    }
    $stmt->bindValue(':st', $store, SQLITE3_TEXT);
    $stmt->bindValue(':ow', $owner, SQLITE3_TEXT);
    $stmt->bindValue(':ph', $phone, SQLITE3_TEXT);
    $stmt->bindValue(':pk', $package, SQLITE3_TEXT);
    $stmt->bindValue(':sd', $start, SQLITE3_TEXT);
    $stmt->bindValue(':ed', $expiry, SQLITE3_TEXT);
    $stmt->bindValue(':st_val', $status, SQLITE3_TEXT);
    $stmt->execute();
    $msg = "✅ تم حفظ اشتراك الزبون بنجاح!";
}

if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $db->exec("DELETE FROM subscriptions WHERE id = $del_id");
    header("Location: subscriptions.php");
    exit;
}

$subs = [];
$res = $db->query("SELECT * FROM subscriptions ORDER BY id DESC");
if ($res) {
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $subs[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>إدارة الاشتراكات - V-MAX PRO</title>
    <style>
        body { font-family: Tahoma, sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 10px; padding-bottom: 110px; }
        .header { text-align: center; font-size: 16px; font-weight: 900; color: #38bdf8; margin-bottom: 12px; }
        .card { background: #1e293b; border: 1px solid #38bdf8; border-radius: 10px; padding: 12px; margin-bottom: 12px; }
        .form-control { width: 100%; padding: 8px; background: #0f172a; color: #fff; border: 1px solid #38bdf8; border-radius: 8px; box-sizing: border-box; margin-bottom: 8px; font-size: 12px; }
        .btn { background: #22c55e; color: #fff; border: none; padding: 10px; border-radius: 8px; font-weight: bold; width: 100%; font-size: 13px; cursor: pointer; }
        .sub-table { width: 100%; border-collapse: collapse; font-size: 11px; margin-top: 8px; }
        .sub-table th, .sub-table td { border: 1px solid #334155; padding: 6px; text-align: center; }
        .sub-table th { background: #1e3a8a; color: #fff; }
        .badge-active { background: #dcfce7; color: #16a34a; padding: 2px 6px; border-radius: 4px; font-weight: bold; }
        .badge-expired { background: #fee2e2; color: #dc2626; padding: 2px 6px; border-radius: 4px; font-weight: bold; }
        .actions-cell a { text-decoration: none; padding: 3px 6px; border-radius: 4px; font-size: 10px; margin: 2px; display: inline-block; }
        .btn-edit { background: #0284c7; color: #fff; }
        .btn-del { background: #ef4444; color: #fff; }
    </style>
</head>
<body>
    <div class="header">💎 لوحة تحكم المشتركين والباقات (أبو ضياء)</div>

    <?php if($msg): ?>
    <div style="background: #16a34a; color: #fff; padding: 8px; border-radius: 8px; text-align: center; margin-bottom: 10px; font-size: 12px; font-weight: bold;"><?php echo $msg; ?></div>
    <?php endif; ?>

    <div class="card">
        <h3 style="margin-top: 0; font-size: 13px; color: #38bdf8;">➕ إضافة أو تفعيل اشتراك زبون جديد</h3>
        <form method="POST">
            <input type="hidden" name="save_sub" value="1">
            <input type="hidden" name="sub_id" id="sub_id" value="0">
            
            <label style="font-size: 10px;">اسم المحل / المعرض:</label>
            <input type="text" name="store_name" id="store_name" class="form-control" required placeholder="مثال: محل الجوال السريع">
            
            <label style="font-size: 10px;">اسم صاحب المحل:</label>
            <input type="text" name="owner_name" id="owner_name" class="form-control" required placeholder="مثال: محمد أحمد">
            
            <label style="font-size: 10px;">رقم الهاتف / واتساب:</label>
            <input type="text" name="phone" id="phone" class="form-control" required placeholder="77xxxxxxx">
            
            <label style="font-size: 10px;">نوع الباقة:</label>
            <select name="package_type" id="package_type" class="form-control">
                <option value="باقة شهرياً">باقة شهرياً</option>
                <option value="باقة 6 أشهر">باقة 6 أشهر</option>
                <option value="باقة سنوياً">باقة سنوياً</option>
            </select>

            <div style="display: flex; gap: 6px;">
                <div style="flex:1;">
                    <label style="font-size: 10px;">تاريخ البداية:</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div style="flex:1;">
                    <label style="font-size: 10px;">تاريخ النهاية:</label>
                    <input type="date" name="expiry_date" id="expiry_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                </div>
            </div>

            <label style="font-size: 10px;">حالة الاشتراك:</label>
            <select name="status" id="status" class="form-control">
                <option value="ACTIVE">نشط (ACTIVE)</option>
                <option value="EXPIRED">منتهٍ (EXPIRED)</option>
            </select>

            <button type="submit" class="btn">💾 حفظ وحماية الاشتراك</button>
        </form>
    </div>

    <div class="card">
        <h3 style="margin-top: 0; font-size: 13px; color: #38bdf8;">📋 قائمة المشتركين المسجلين (<?php echo count($subs); ?>)</h3>
        <div style="overflow-x: auto;">
            <table class="sub-table">
                <tr>
                    <th>المحل</th>
                    <th>المالك</th>
                    <th>الهاتف</th>
                    <th>الباقة</th>
                    <th>الانتهاء</th>
                    <th>الحالة</th>
                    <th>تحكم</th>
                </tr>
                <?php foreach($subs as $s): ?>
                <tr>
                    <td><b><?php echo htmlspecialchars($s['store_name'] ?? ''); ?></b></td>
                    <td><?php echo htmlspecialchars($s['owner_name'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($s['phone'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($s['package_type'] ?? ''); ?></td>
                    <td style="direction:ltr;"><?php echo htmlspecialchars($s['expiry_date'] ?? ''); ?></td>
                    <td>
                        <?php if(($s['status'] ?? '') === 'ACTIVE'): ?>
                            <span class="badge-active">نشط</span>
                        <?php else: ?>
                            <span class="badge-expired">منتهي</span>
                        <?php endif; ?>
                    </td>
                    <td class="actions-cell">
                        <a href="javascript:void(0);" onclick="editSub(<?php echo htmlspecialchars(json_encode($s)); ?>)" class="btn-edit">تعديل</a>
                        <a href="subscriptions.php?delete=<?php echo $s['id']; ?>" onclick="return confirm('هل أنت متأكد من حذف هذا الاشتراك؟');" class="btn-del">حذف</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($subs)): ?>
                <tr><td colspan="7" style="padding: 10px; color: #94a3b8;">لا يوجد مشتركين حالياً</td></tr>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <script>
    function editSub(data) {
        document.getElementById('sub_id').value = data.id || 0;
        document.getElementById('store_name').value = data.store_name || '';
        document.getElementById('owner_name').value = data.owner_name || '';
        document.getElementById('phone').value = data.phone || '';
        document.getElementById('package_type').value = data.package_type || 'باقة شهرياً';
        document.getElementById('start_date').value = data.start_date || '<?php echo date('Y-m-d'); ?>';
        document.getElementById('expiry_date').value = data.expiry_date || '<?php echo date('Y-m-d'); ?>';
        document.getElementById('status').value = data.status || 'ACTIVE';
        window.scrollTo({top: 0, behavior: 'smooth'});
    }
    </script>
    <?php if(file_exists('nav_menu.php')) { include 'nav_menu.php'; } ?>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>
</html>

