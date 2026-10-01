<?php
// suppliers.php - إدارة الموردين والشركات - V-MAX PRO
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'auth.php';

$db_file = '/data/data/com.termux/files/home/secure_dbs/vmax.db';
if(!file_exists($db_file)) { $db_file = 'database.db'; }
$db = new SQLite3($db_file);
$db->busyTimeout(5000);

// التأكد من وجود جدول الموردين
$db->exec("CREATE TABLE IF NOT EXISTS suppliers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    phone TEXT,
    company TEXT,
    balance REAL DEFAULT 0,
    note TEXT
);");

$msg = "";

// إضافة مورد جديد
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_supplier'])) {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $balance = floatval($_POST['balance'] ?? 0);
    $note = trim($_POST['note'] ?? '');

    if (!empty($name)) {
        $stmt = $db->prepare("INSERT INTO suppliers (name, phone, company, balance, note) VALUES (:name, :phone, :company, :balance, :note)");
        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
        $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
        $stmt->bindValue(':company', $company, SQLITE3_TEXT);
        $stmt->bindValue(':balance', $balance, SQLITE3_FLOAT);
        $stmt->bindValue(':note', $note, SQLITE3_TEXT);
        $stmt->execute();
        $msg = "✅ تم إضافة المورد ($name) بنجاح!";
    } else {
        $msg = "⚠️ يرجى إدخال اسم المورد على الأقل!";
    }
}

// جلب إجمالي أرصدة الموردين
$total_sup_balance = $db->querySingle("SELECT SUM(balance) FROM suppliers") ?: 0;
$total_sup_count = $db->querySingle("SELECT COUNT(*) FROM suppliers") ?: 0;

// جلب قائمة الموردين
$suppliers = [];
$res = $db->query("SELECT * FROM suppliers ORDER BY id DESC");
if ($res) {
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $suppliers[] = $row;
    }
}
$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'>
    <title>إدارة الموردين - V-MAX PRO</title>
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
            margin: 0;
            padding: 15px;
            padding-bottom: 120px;
        }
        .header-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(241, 245, 249, 0.9));
            border: 1.5px solid var(--primary-blue);
            border-radius: 20px;
            padding: 18px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.06);
            margin-bottom: 15px;
        }
        .header-card h1 { margin: 0 0 5px 0; font-size: 18px; color: var(--primary-blue); }
        .header-card p { margin: 0; font-size: 11px; color: var(--text-muted); }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 15px;
        }
        .stat-box {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 14px;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.03);
        }
        .stat-box .title { font-size: 11px; color: var(--text-muted); font-weight: bold; margin-bottom: 4px; }
        .stat-box .value { font-size: 16px; font-weight: 900; color: var(--primary-blue); }

        .glass-panel {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
        }
        .form-control {
            width: 100%;
            padding: 10px;
            background: #FFFFFF;
            border: 1px solid var(--border-color);
            color: var(--text-main);
            border-radius: 10px;
            font-size: 12px;
            box-sizing: border-box;
            margin-bottom: 10px;
            outline: none;
        }
        .form-control:focus { border-color: var(--primary-blue); background: #FFFFFF; }
        .btn-submit {
            background: linear-gradient(135deg, var(--primary-blue), #1D4ED8);
            color: #fff;
            border: none;
            padding: 12px;
            border-radius: 10px;
            font-weight: bold;
            width: 100%;
            font-size: 13px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(37,99,235,0.25);
        }
        .alert-success {
            background: #FFFFFF;
            color: #15803D;
            border: 1px solid #BBF7D0;
            padding: 10px;
            border-radius: 10px;
            text-align: center;
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 15px;
        }
        .supplier-card {
            background: #FFFFFF;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 12px 15px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }
        .supplier-info h4 { margin: 0 0 4px 0; font-size: 14px; color: var(--text-main); }
        .supplier-info p { margin: 0; font-size: 11px; color: var(--text-muted); }
        .supplier-balance { font-size: 13px; font-weight: 800; color: var(--danger); text-align: left; }
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
    <h1>🏢 إدارة الموردين والشركات</h1>
    <p>متابعة حسابات الموردين والذمم المالية بدقة عالية</p>
</div>

<?php if ($msg): ?>
<div class="alert-success"><?php echo $msg; ?></div>
<?php endif; ?>

<div class="stats-grid">
    <div class="stat-box">
        <div class="title">إجمالي الموردين</div>
        <div class="value"><?php echo $total_sup_count; ?> مورد</div>
    </div>
    <div class="stat-box">
        <div class="title">إجمالي الأرصدة (عليهم/لهم)</div>
        <div class="value" style="color: var(--danger);"><?php echo number_format($total_sup_balance, 2); ?> ر.ي</div>
    </div>
</div>

<!-- نموذج إضافة مورد جديد -->
<div class="glass-panel">
    <div style="font-size: 13px; font-weight: bold; color: var(--primary-blue); margin-bottom: 10px;">➕ إضافة مورد أو شركة جديدة</div>
    <form method="POST">
        <input type="hidden" name="add_supplier" value="1">
        
        <input type="text" name="name" class="form-control" placeholder="اسم المورد (مثلاً: شركة بفك رنجو)..." required>
        <input type="text" name="company" class="form-control" placeholder="اسم الشركة أو الوكالة...">
        <input type="text" name="phone" class="form-control" placeholder="رقم الهاتف...">
        <input type="number" step="any" name="balance" class="form-control" placeholder="الرصيد الافتتاحي (0)">
        <input type="text" name="note" class="form-control" placeholder="ملاحظات إضافية...">

        <button type="submit" class="btn-submit">💾 حفظ المورد الجديد</button>
    </form>
</div>

<!-- قائمة الموردين -->
<div class="glass-panel">
    <div style="font-size: 13px; font-weight: bold; color: var(--text-main); margin-bottom: 12px;">📋 قائمة الموردين المسجلين:</div>
    
    <?php if (count($suppliers) > 0): ?>
        <?php foreach ($suppliers as $s): ?>
        <div class="supplier-card">
            <div class="supplier-info">
                <h4><?php echo htmlspecialchars($s['name']); ?> <?php echo !empty($s['company']) ? '('.htmlspecialchars($s['company']).')' : ''; ?></h4>
                <p>📞 <?php echo !empty($s['phone']) ? htmlspecialchars($s['phone']) : 'بدون رقم'; ?> | 📝 <?php echo !empty($s['note']) ? htmlspecialchars($s['note']) : 'لا توجد ملاحظات'; ?></p>
            </div>
            <div class="supplier-balance">
                <?php echo number_format($s['balance'], 2); ?> ر.ي
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div style="text-align: center; color: var(--text-muted); font-size: 12px; padding: 20px;">لا يوجد موردين مسجلين حتى الآن.</div>
    <?php endif; ?>
</div>

</body>
</html>
