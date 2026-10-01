<?php
// receipts.php - سندات القبض والصرف مع التحديث الذكي لقاعدة البيانات - V-MAX PRO
require_once 'auth.php';
$db_file = '/data/data/com.termux/files/home/secure_dbs/vmax.db';
$db = new SQLite3($db_file);

// 1. التأكد من وجود الجدول الأساسي
$db->exec("CREATE TABLE IF NOT EXISTS receipts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_id INTEGER,
    amount REAL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// 2. الفحص الذكي: التأكد من وجود الأعمدة الجديدة (type و notes) وإضافتها إن لم تكن موجودة
$check_cols = $db->query("PRAGMA table_info(receipts)");
$has_type = false;
$has_notes = false;
while ($row = $check_cols->fetchArray(SQLITE3_ASSOC)) {
    if ($row['name'] === 'type') { $has_type = true; }
    if ($row['name'] === 'notes') { $has_notes = true; }
}

if (!$has_type) {
    $db->exec("ALTER TABLE receipts ADD COLUMN type TEXT DEFAULT 'قبض'");
}
if (!$has_notes) {
    $db->exec("ALTER TABLE receipts ADD COLUMN notes TEXT");
}

$success_msg = "";
$error_msg = "";

// استقبال معرف العميل من الرابط
$selected_customer_id = intval($_GET['customer_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = intval($_POST['customer_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $type = $_POST['type'] ?? 'قبض';
    $notes = trim($_POST['notes'] ?? '');

    if ($customer_id > 0 && $amount > 0) {
        $stmt = $db->prepare("INSERT INTO receipts (customer_id, amount, type, notes) VALUES (:cid, :amt, :type, :notes)");
        $stmt->bindValue(':cid', $customer_id, SQLITE3_INTEGER);
        $stmt->bindValue(':amt', $amount, SQLITE3_FLOAT);
        $stmt->bindValue(':type', $type, SQLITE3_TEXT);
        $stmt->bindValue(':notes', $notes, SQLITE3_TEXT);

        if ($stmt->execute()) {
            // تحديث رصيد العميل
            $op = ($type == 'قبض') ? '-' : '+';
            $db->exec("UPDATE customers SET balance = balance {$op} {$amount} WHERE id = {$customer_id}");

            $success_msg = "✅ تم حفظ السند بنجاح وتحديث رصيد العميل!";
            $selected_customer_id = 0;
        } else {
            $error_msg = "❌ حدث خطأ أثناء حفظ السند في قاعدة البيانات.";
        }
    } else {
        $error_msg = "⚠️ الرجاء تحديد العميل وإدخال مبلغ صحيح أكبر من الصفر.";
    }
}

// جلب بيانات العملاء
$customers = [];
$res = $db->query("SELECT id, name, balance FROM customers ORDER BY name ASC");
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $customers[] = $row;
}
$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>سند قبض وصرف - V-MAX PRO</title>
    <style>
        :root {
            --bg-deep: #FFFFFF;
            --card-bg: #F8FAFC;
            --card-border: #CBD5E1;
            --primary: #2563EB;
            --success: #10B981;
            --danger: #EF4444;
            --warning: #F59E0B;
            --text-main: #0F172A;
        }
        * { box-sizing: border-box; }
        body { font-family: 'Tahoma', sans-serif; background: var(--bg-deep); margin: 0; padding: 10px; color: var(--text-main); min-height: 100vh; padding-bottom: 75px; }
        .container { max-width: 500px; margin: 0 auto; }

        .header-box { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: 10px; padding: 12px; text-align: center; margin-bottom: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .header-box h2 { margin: 0; font-size: 16px; color: #1E3A8A; font-weight: bold; }

        .alert-success { background: #ECFDF5; border: 1px solid var(--success); color: #065F46; padding: 10px; border-radius: 8px; font-size: 11.5px; text-align: center; margin-bottom: 12px; font-weight: bold; }
        .alert-error { background: #FEE2E2; border: 1px solid var(--danger); color: #991B1B; padding: 10px; border-radius: 8px; font-size: 11.5px; text-align: center; margin-bottom: 12px; font-weight: bold; }

        .form-card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: 10px; padding: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }

        .form-group { margin-bottom: 12px; }
        label { display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #1E3A8A; }
        select, input[type="number"], input[type="text"] { width: 100%; padding: 10px; background: #FFFFFF; border: 1px solid var(--card-border); border-radius: 8px; color: #0F172A; font-size: 13px; }

        .radio-group { display: flex; gap: 10px; margin-top: 5px; }
        .radio-label { flex: 1; display: flex; align-items: center; justify-content: center; gap: 5px; background: #FFFFFF; border: 1px solid var(--card-border); padding: 10px; border-radius: 8px; cursor: pointer; font-size: 12px; font-weight: bold; transition: 0.2s; }
        .radio-label input { display: none; }

        input[value="قبض"]:checked + span { color: var(--success); }
        input[value="قبض"]:checked ~ .radio-label { border-color: var(--success); background: #ECFDF5; }

        input[value="صرف"]:checked + span { color: var(--danger); }
        input[value="صرف"]:checked ~ .radio-label { border-color: var(--danger); background: #FEE2E2; }

        .btn-save { width: 100%; padding: 12px; border-radius: 9px; font-weight: bold; font-size: 13.5px; border: none; cursor: pointer; text-align: center; background: #10B981; color: #FFFFFF; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4); margin-top: 10px; }
        .btn-save:active { transform: scale(0.98); }

        .btn-close { display: block; width: 100%; padding: 12px; border-radius: 9px; font-weight: bold; font-size: 12px; border: none; cursor: pointer; text-align: center; text-decoration: none; background: #64748B; color: #FFFFFF; margin-top: 8px; }
    </style>
</head>
<body>

<div class="container">
    <div class="header-box">
        <h2>💵 سند قبض وصرف</h2>
    </div>

    <?php if(!empty($success_msg)): ?>
        <div class="alert-success"><?php echo $success_msg; ?></div>
    <?php endif; ?>
    <?php if(!empty($error_msg)): ?>
        <div class="alert-error"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <form method="POST" class="form-card">
        <div class="form-group">
            <label>اختر العميل:</label>
            <select name="customer_id" required>
                <option value="">-- اختر العميل من القائمة --</option>
                <?php foreach($customers as $c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php echo ($selected_customer_id == $c['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c['name']); ?> (المديونية: <?php echo number_format($c['balance'], 2); ?> ر.ي)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>نوع السند:</label>
            <div class="radio-group">
                <label class="radio-label" style="border-color: #10B981;">
                    <input type="radio" name="type" value="قبض" checked>
                    <span style="color: #10B981;">📥 سند قبض (استلام دفعة)</span>
                </label>
                <label class="radio-label">
                    <input type="radio" name="type" value="صرف">
                    <span>📤 سند صرف (إعطاء مبلغ)</span>
                </label>
            </div>
        </div>

        <div class="form-group">
            <label>المبلغ (ر.ي):</label>
            <input type="number" name="amount" step="any" min="1" placeholder="أدخل المبلغ هنا..." required>
        </div>

        <div class="form-group">
            <label>البيان / ملاحظات (اختياري):</label>
            <input type="text" name="notes" placeholder="مثال: دفعة من الحساب، سداد فاتورة...">
        </div>

        <button type="submit" class="btn-save">💾 حفظ واعتماد السند</button>
        <a href="javascript:history.back()" class="btn-close" style="background: #EF4444;"> ❌ إغلاق</a>
    </form>
</div>

<?php if(file_exists('nav_menu.php')) { include 'nav_menu.php'; } ?>

<script>
document.querySelectorAll('input[type="radio"]').forEach(radio => {
    radio.addEventListener('change', function() {
        let labels = document.querySelectorAll('.radio-label');
        labels.forEach(lbl => {
            lbl.style.borderColor = '#CBD5E1';
            lbl.style.background = '#FFFFFF';
            lbl.querySelector('span').style.color = '#0F172A';
        });

        let parentLabel = this.closest('.radio-label');
        if(this.value === 'قبض') {
            parentLabel.style.borderColor = '#10B981';
            parentLabel.style.background = '#ECFDF5';
            parentLabel.querySelector('span').style.color = '#10B981';
        } else {
            parentLabel.style.borderColor = '#EF4444';
            parentLabel.style.background = '#FEE2E2';
            parentLabel.querySelector('span').style.color = '#EF4444';
        }
    });
});
</script>
</body>
</html>
