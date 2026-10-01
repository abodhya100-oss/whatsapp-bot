<?php
// subscriptions.php - إدارة الاشتراكات والتراخيص - V-MAX PRO
error_reporting(0);
ini_set('display_errors', 0);

include_once 'config_loader.php';
require_once 'auth.php';

$db_file = '/data/data/com.termux/files/home/secure_dbs/vmax.db';
if(!file_exists($db_file)) { $db_file = 'database.db'; }
$db = new SQLite3($db_file);

// إنشاء جدول الاشتراكات إذا لمم يكن موجوداً
$db->exec("CREATE TABLE IF NOT EXISTS subscriptions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_name TEXT NOT NULL,
    phone TEXT,
    start_date TEXT,
    expiry_date TEXT,
    status TEXT DEFAULT 'active',
    activation_key TEXT
);");

$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_sub'])) {
    $client_name = trim($_POST['client_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $duration_months = intval($_POST['duration'] ?? 1);
    
    if (!empty($client_name)) {
        $start_date = date('Y-m-d');
        $expiry_date = date('Y-m-d', strtotime("+$duration_months months"));
        $activation_key = 'VMAX-' . strtoupper(substr(md5(mt_rand()), 0, 8));

        $stmt = $db->prepare("INSERT INTO subscriptions (client_name, phone, start_date, expiry_date, status, activation_key) VALUES (:name, :phone, :start, :expiry, 'active', :key)");
        $stmt->bindValue(':name', $client_name, SQLITE3_TEXT);
        $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
        $stmt->bindValue(':start', $start_date, SQLITE3_TEXT);
        $stmt->bindValue(':expiry', $expiry_date, SQLITE3_TEXT);
        $stmt->bindValue(':key', $activation_key, SQLITE3_TEXT);
        $stmt->execute();
        $msg = "✅ تم إنشاء الاشتراك للمشترك ($client_name) بنجاح!";
    } else {
        $msg = "⚠️ يرجى إدخال اسم المشترك!";
    }
}

$subscriptions = [];
$res = $db->query("SELECT * FROM subscriptions ORDER BY id DESC");
if ($res) {
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $subscriptions[] = $row;
    }
}
$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'>
    <title>إدارة الاشتراكات - V-MAX PRO</title>
    <style>
        :root {
            --bg-deep: #F1F5F9;
            --card-bg: #FFFFFF;
            --text-main: #0F172A;
            --text-muted: #64748B;
            --primary-blue: #2563EB;
            --success: #10B981;
            --danger: #E11D48;
            --border-color: #CBD5E1;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Tahoma', sans-serif;
            background: #FFFFFF;
            color: var(--text-main);
            margin: 0; padding: 15px;
            padding-bottom: 120px;
        }
        .header-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(241, 245, 249, 0.9));
            border: 1.5px solid var(--primary-blue);
            border-radius: 20px; padding: 18px; text-align: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.06); margin-bottom: 15px;
        }
        .header-card h1 { margin: 0 0 5px 0; font-size: 18px; color: var(--primary-blue); }
        .header-card p { margin: 0; font-size: 11px; color: var(--text-muted); }

        .glass-panel {
            background: var(--card-bg); border: 1px solid var(--border-color);
            border-radius: 16px; padding: 15px; margin-bottom: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
        }
        .form-control {
            width: 100%; padding: 10px; background: #FFFFFF;
            border: 1px solid var(--border-color); color: var(--text-main);
            border-radius: 10px; font-size: 12px; margin-bottom: 10px; outline: none;
        }
        .form-control:focus { border-color: var(--primary-blue); background: #FFFFFF; }
        .btn-submit {
            background: linear-gradient(135deg, var(--primary-blue), #1D4ED8);
            color: #fff; border: none; padding: 12px; border-radius: 10px;
            font-weight: bold; width: 100%; font-size: 13px; cursor: pointer;
            box-shadow: 0 4px 12px rgba(37,99,235,0.25);
        }
        .alert-success {
            background: #FFFFFF; color: #15803D; border: 1px solid #BBF7D0;
            padding: 10px; border-radius: 10px; text-align: center; font-weight: bold;
            font-size: 12px; margin-bottom: 15px;
        }
        .sub-card {
            background: #FFFFFF; border: 1px solid var(--border-color);
            border-radius: 12px; padding: 12px 15px; margin-bottom: 10px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .sub-info h4 { margin: 0 0 4px 0; font-size: 14px; color: var(--text-main); }
        .sub-info p { margin: 0; font-size: 11px; color: var(--text-muted); }
        .sub-status { font-size: 11px; font-weight: bold; padding: 4px 10px; border-radius: 20px; background: #FFFFFF; color: #15803D; }
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

<div class="header-card">
    <h1>🎟️ إدارة الاشتراكات والتراخيص</h1>
    <p>تتبع اشتراكات العملاء وتوليد مفاتيح التفعيل للنسخ التجارية</p>
</div>

<?php if ($msg): ?>
<div class="alert-success"><?php echo $msg; ?></div>
<?php endif; ?>

<!-- نموذج إضافة اشتراك جديد -->
<div class="glass-panel">
    <div style="font-size: 13px; font-weight: bold; color: var(--primary-blue); margin-bottom: 10px;">➕ تسجيل مشترك جديد وتوليد ترخيص</div>
    <form method="POST">
        <input type="hidden" name="add_sub" value="1">
        <input type="text" name="client_name" class="form-control" placeholder="اسم المشترك أو المحل..." required>
        <input type="text" name="phone" class="form-control" placeholder="رقم الهاتف...">
        <select name="duration" class="form-control">
            <option value="1">اشتراك شهر واحد</option>
            <option value="3">اشتراك 3 أشهر</option>
            <option value="6">اشتراك 6 أشهر</option>
            <option value="12">اشتراك سنوي (سنة كاملة)</option>
        </select>
        <button type="submit" class="btn-submit">🔑 إنشاء الاشتراك وتوليد المفتاح</button>
    </form>
</div>

<!-- قائمة الاشتراكات -->
<div class="glass-panel">
    <div style="font-size: 13px; font-weight: bold; color: var(--text-main); margin-bottom: 12px;">📋 قائمة المشتركين والتراخيص النشطة:</div>
    
    <?php if (count($subscriptions) > 0): ?>
        <?php foreach ($subscriptions as $s): ?>
        <div class="sub-card">
            <div class="sub-info">
                <h4><?php echo htmlspecialchars($s['client_name']); ?> (مفتاح: <code style="color:var(--primary-blue);"><?php echo $s['activation_key']; ?></code>)</h4>
                <p>📞 <?php echo $s['phone']; ?> | 📅 ينتهي في: <b><?php echo $s['expiry_date']; ?></b></p>
            </div>
            <div>
                <span class="sub-status"><?php echo $s['status'] === 'active' ? 'نشط' : 'منتهي'; ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div style="text-align: center; color: var(--text-muted); font-size: 12px; padding: 20px;">لا توجد اشتراكات مسجلة حتى الآن.</div>
    <?php endif; ?>
</div>

</body>
</html>
