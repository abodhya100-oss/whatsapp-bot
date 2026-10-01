<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$db = new SQLite3('vmax.db', SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
$db->busyTimeout(5000);

// إنشاء جدول المستخدمين والتأكد من وجود الأعمدة لتجنب أي تحذيرات
$db->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE,
    password TEXT,
    role TEXT,
    full_name TEXT
);");

$result = $db->query("PRAGMA table_info(users);");
$existing_cols = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $existing_cols[] = $row['name'];
}
if (!in_array('full_name', $existing_cols)) {
    $db->exec("ALTER TABLE users ADD COLUMN full_name TEXT;");
}
if (!in_array('role', $existing_cols)) {
    $db->exec("ALTER TABLE users ADD COLUMN role TEXT;");
}

$admin_check = $db->querySingle("SELECT COUNT(*) FROM users");
if ($admin_check == 0) {
    $db->exec("INSERT INTO users (username, password, role, full_name) VALUES ('admin', '123456', 'مدير عام', 'أبو ضياء')");
}

$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_user'])) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = trim($_POST['role'] ?? 'كاشير');
    $full_name = trim($_POST['full_name'] ?? '');

    if (!empty($username) && !empty($password)) {
        $stmt = $db->prepare("INSERT OR REPLACE INTO users (username, password, role, full_name) VALUES (:u, :p, :r, :f)");
        $stmt->bindValue(':u', $username, SQLITE3_TEXT);
        $stmt->bindValue(':p', $password, SQLITE3_TEXT);
        $stmt->bindValue(':r', $role, SQLITE3_TEXT);
        $stmt->bindValue(':f', $full_name, SQLITE3_TEXT);
        $stmt->execute();
        $msg = "✅ تم حفظ المستخدم وصلاحياته بنجاح!";
    } else {
        $msg = "❌ يرجى إدخال اسم المستخدم وكلمة المرور!";
    }
}

if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    if ($del_id > 1) {
        $db->exec("DELETE FROM users WHERE id = $del_id");
    }
    header("Location: users.php");
    exit;
}

$users_list = [];
$res = $db->query("SELECT * FROM users ORDER BY id ASC");
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $users_list[] = $row;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><meta name='mobile-web-app-capable' content='yes'>
<meta name='apple-mobile-web-app-capable' content='yes'>

    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>إدارة المستخدمين - V-MAX PRO</title>
    <style>
        body { font-family: Tahoma, sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 10px; padding-bottom: 110px; }
        .header { text-align: center; font-size: 16px; font-weight: 900; color: #38bdf8; margin-bottom: 12px; }
        .card { background: #1e293b; border: 1px solid #38bdf8; border-radius: 10px; padding: 12px; margin-bottom: 12px; }
        .form-control { width: 100%; padding: 8px; background: #0f172a; color: #fff; border: 1px solid #38bdf8; border-radius: 8px; box-sizing: border-box; margin-bottom: 8px; font-size: 11px; }
        .btn { background: #22c55e; color: #fff; border: none; padding: 10px; border-radius: 8px; font-weight: bold; width: 100%; font-size: 12px; cursor: pointer; }
        .sub-table { width: 100%; border-collapse: collapse; font-size: 11px; margin-top: 8px; }
        .sub-table th, .sub-table td { border: 1px solid #334155; padding: 6px; text-align: center; }
        .sub-table th { background: #1e3a8a; color: #fff; }
        .btn-del { background: #ef4444; color: #fff; text-decoration: none; padding: 3px 6px; border-radius: 4px; font-size: 10px; }
    </style>
</head>
<body>
    <div class="header">👮 إدارة المستخدمين والصلاحيات</div>

    <?php if($msg): ?>
    <div style="background: <?php echo strpos($msg, '✅') !== false ? '#16a34a' : '#dc2626'; ?>; color: #fff; padding: 8px; border-radius: 6px; text-align: center; font-size: 11px; font-weight: bold; margin-bottom: 10px;"><?php echo $msg; ?></div>
    <?php endif; ?>

    <div class="card">
        <h3 style="margin-top: 0; font-size: 12px; color: #38bdf8;">➕ إضافة مستخدم جديد للنظام</h3>
        <form method="POST">
            <input type="hidden" name="save_user" value="1">
            
            <label style="font-size: 10px;">الاسم الكامل:</label>
            <input type="text" name="full_name" class="form-control" required placeholder="مثال: أحمد محمد">

            <label style="font-size: 10px;">اسم المستخدم (للدخول):</label>
            <input type="text" name="username" class="form-control" required placeholder="username">

            <label style="font-size: 10px;">كلمة المرور:</label>
            <input type="password" name="password" class="form-control" required placeholder="******">

            <label style="font-size: 10px;">الصلاحية / الدور:</label>
            <select name="role" class="form-control">
                <option value="كاشير">كاشير (مبيعات فقط)</option>
                <option value="محاسب">محاسب (مبيعات وحسابات)</option>
                <option value="مدير عام">مدير عام (صلاحيات كاملة)</option>
            </select>

            <button type="submit" class="btn">💾 حفظ المستخدم</button>
        </form>
    </div>

    <div class="card">
        <h3 style="margin-top: 0; font-size: 12px; color: #38bdf8;">📋 قائمة المستخدمين الحاليين</h3>
        <table class="sub-table">
            <tr>
                <th>الاسم</th>
                <th>المعرف</th>
                <th>الصلاحية</th>
                <th>حذف</th>
            </tr>
            <?php foreach($users_list as $u): ?>
            <tr>
                <td><b><?php echo htmlspecialchars($u['full_name'] ?? ''); ?></b></td>
                <td><?php echo htmlspecialchars($u['username'] ?? ''); ?></td>
                <td><span style="background: #0284c7; padding: 2px 6px; border-radius: 4px; font-size: 9px;"><?php echo htmlspecialchars($u['role'] ?? ''); ?></span></td>
                <td>
                    <?php if(($u['id'] ?? 0) > 1): ?>
                    <a href="users.php?delete=<?php echo $u['id']; ?>" onclick="return confirm('هل أنت متأكد من حذف هذا المستخدم؟');" class="btn-del">حذف</a>
                    <?php else: ?>
                    <span style="color: #94a3b8; font-size: 9px;">محمي</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <?php if(file_exists('nav_menu.php')) { include 'nav_menu.php'; } ?>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>
</html>

