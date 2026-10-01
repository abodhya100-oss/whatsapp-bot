<?php
// system_settings.php - الإعدادات والصلاحيات والتنبيهات - V-MAX PRO
require_once 'auth.php';
$db = new SQLite3(__DIR__ . '/vmax.db');

// إضافة مستخدم جديد
if (isset($_POST['add_user'])) {
    $u = trim($_POST['username']);
    $p = trim($_POST['password']);
    $r = $_POST['role'];
    if (!empty($u) && !empty($p)) {
        $stmt = $db->prepare("INSERT OR IGNORE INTO users (username, password, role) VALUES (:u, :p, :r)");
        $stmt->bindValue(':u', $u);
        $stmt->bindValue(':p', $p);
        $stmt->bindValue(':r', $r);
        $stmt->execute();
    }
    header("Location: system_settings.php");
    exit;
}

// حذف مستخدم
if (isset($_GET['del_user'])) {
    $uid = intval($_GET['del_user']);
    if ($uid > 1) { // حماية الحساب الأساسي
        $db->exec("DELETE FROM users WHERE id = $uid");
    }
    header("Location: system_settings.php");
    exit;
}

// جلب الأصناف ذات المخزون المنخفض (أقل من 5 قطع)
$low_stock = [];
$res_stock = $db->query("SELECT * FROM products WHERE qty <= 5 ORDER BY qty ASC");
while($row = $res_stock->fetchArray(SQLITE3_ASSOC)) { $low_stock[] = $row; }

// جلب المستخدمين
$users = [];
$res_users = $db->query("SELECT * FROM users ORDER BY id ASC");
while($row_u = $res_users->fetchArray(SQLITE3_ASSOC)) { $users[] = $row_u; }

$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><meta name='mobile-web-app-capable' content='yes'>
<meta name='apple-mobile-web-app-capable' content='yes'>

    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>الإعدادات والتنبيهات - V-MAX PRO</title>
    <style>
        :root {
            --bg: #02040a;
            --surface: rgba(15, 23, 42, 0.85);
            --border: rgba(245, 158, 11, 0.25);
            --primary: #38bdf8;
            --gold: #f59e0b;
            --success: #10b981;
            --danger: #f43f5e;
            --text: #f8fafc;
            --muted: #94a3b8;
        }
        * { box-sizing: border-box; }
        body { font-family: Tahoma, sans-serif; background: var(--bg); margin: 0; padding: 12px; color: var(--text); min-height: 100vh; }
        .wrap { max-width: 650px; margin: 0 auto 90px auto; }
        .card { background: var(--surface); border: 1px solid var(--border); border-radius: 18px; padding: 18px; margin-bottom: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.6); }
        h2, h3 { margin-top: 0; color: var(--gold); font-size: 16px; }
        .form-control { width: 100%; padding: 10px; margin-bottom: 10px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.15); background: #1e293b; color: #fff; font-size: 12px; outline: none; }
        .btn { width: 100%; padding: 12px; border-radius: 10px; border: none; font-weight: bold; font-size: 13px; cursor: pointer; text-align: center; text-decoration: none; display: block; }
        .btn-gold { background: linear-gradient(135deg, #f59e0b, #d97706); color: #02040a; }
        .alert-item { display: flex; justify-content: space-between; align-items: center; background: rgba(244,63,94,0.15); border: 1px solid rgba(244,63,94,0.3); padding: 10px; margin-bottom: 8px; border-radius: 10px; }
        .user-item { display: flex; justify-content: space-between; align-items: center; background: #1e293b; padding: 10px; margin-bottom: 8px; border-radius: 10px; }
    </style>
</head>
<body>

<div class="wrap">
    <!-- تنبيهات المخزون الذكية -->
    <div class="card" style="border-color: rgba(244,63,94,0.4);">
        <h3>🚨 تنبيهات نفاذ المخزون الحرجة</h3>
        <?php if(empty($low_stock)): ?>
            <p style="text-align:center; color:var(--success); font-size:12px; margin:5px 0;">ممتاز! جميع أصناف المخزون بمستويات آمنة.</p>
        <?php else: ?>
            <?php foreach($low_stock as $ls): ?>
                <div class="alert-item">
                    <div>
                        <b style="font-size:12px; color:#fff;"><?php echo htmlspecialchars($ls['name']); ?></b>
                        <br><small style="color:var(--muted);">السعر: <?php echo $ls['price']; ?></small>
                    </div>
                    <div style="text-align:left;">
                        <span style="background:var(--danger); color:#fff; padding:3px 8px; border-radius:6px; font-size:11px; font-weight:bold;">
                            الكمية: <?php echo $ls['qty']; ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- إدارة الصلاحيات والمستخدمين -->
    <div class="card">
        <h3>👥 إدارة الصلاحيات والمستخدمين</h3>
        <form method="POST">
            <input type="hidden" name="add_user" value="1">
            <label style="font-size:11px; color:var(--muted); font-weight:bold;">اسم المستخدم</label>
            <input type="text" name="username" class="form-control" placeholder="اسم المستخدم الجديد..." required>

            <label style="font-size:11px; color:var(--muted); font-weight:bold;">كلمة المرور</label>
            <input type="password" name="password" class="form-control" placeholder="كلمة المرور..." required>

            <label style="font-size:11px; color:var(--muted); font-weight:bold;">الصلاحية</label>
            <select name="role" class="form-control">
                <option value="cashier">كاشير (مبيعات وسندات فقط)</option>
                <option value="admin">مدير عام (صلاحيات كاملة)</option>
            </select>

            <button type="submit" class="btn btn-gold">إضافة المستخدم الجديد</button>
        </form>

        <hr style="border-color:rgba(255,255,255,0.1); margin:15px 0;">
        
        <h4 style="color:var(--primary); font-size:13px; margin-bottom:8px;">المستخدمون المسجلون:</h4>
        <?php foreach($users as $usr): ?>
            <div class="user-item">
                <div>
                    <b style="font-size:12px;"><?php echo htmlspecialchars($usr['username']); ?></b>
                    <span style="font-size:10px; color:var(--muted); background:#0f172a; padding:2px 6px; border-radius:4px; margin-right:6px;">
                        <?php echo $usr['role']=='admin'?'مدير عام':'كاشير'; ?>
                    </span>
                </div>
                <div>
                    <?php if($usr['id'] > 1): ?>
                        <a href="system_settings.php?del_user=<?php echo $usr['id']; ?>" onclick="return confirm('حذف المستخدم؟');" style="color:var(--danger); text-decoration:none; font-size:12px; font-weight:bold;">❌ حذف</a>
                    <?php else: ?>
                        <small style="color:var(--success);">الرئيسي</small>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- تصدير وتقارير سريعة -->
    <div class="card">
        <h3>📊 التصدير والتقارير</h3>
        <button class="btn" style="background:#1e293b; color:#38bdf8; border:1px solid var(--border); margin-bottom:8px;" onclick="window.location.href='reports.php'">📈 الانتقال لتقرير الأرباح الشامل</button>
        <button class="btn" style="background:#1e293b; color:var(--success); border:1px solid var(--border);" onclick="window.print()">🖨️ طباعة الصفحة الحالية / تصدير PDF</button>
    </div>

    <a href="index.php" class="btn" style="background:#334155; color:#fff;">🏠 العودة للرئيسية</a>
</div>

<?php if(file_exists('nav_menu.php')) { include 'nav_menu.php'; } ?>
</body>
</html>
