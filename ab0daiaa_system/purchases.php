<?php
// purchases.php - نظام إدارة الموردين والمشتريات (محدث ومحصن ضد الأخطاء) - نظام أبوضياء العالمي
require_once 'auth.php';
$db_file = '/data/data/com.termux/files/home/secure_dbs/vmax.db';
$db = new SQLite3($db_file);

// التأكد من وجود جدول المشتريات والموردين وإضافة الأعمدة إن لم تكن موجودة
$db->exec("CREATE TABLE IF NOT EXISTS purchases (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    supplier_name TEXT,
    item_desc TEXT,
    amount REAL,
    notes TEXT,
    created_date TEXT
)");

// معالجة إضافة عملية شراء/توريد جديدة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_purchase'])) {
    $supplier_name = $db->escapeString($_POST['supplier_name'] ?? 'مورد عام');
    $item_desc = $db->escapeString($_POST['item_desc'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $notes = $db->escapeString($_POST['notes'] ?? '');
    
    if ($amount > 0) {
        $db->exec("INSERT INTO purchases (supplier_name, item_desc, amount, notes, created_date) VALUES ('$supplier_name', '$item_desc', $amount, '$notes', datetime('now', 'localtime'))");
        echo "<script>window.location.href='purchases.php';</script>";
        exit;
    }
}

// جلب المشتريات المسجلة مع معالجة آمنة للبيانات
$purchases_res = $db->query("SELECT * FROM purchases ORDER BY id DESC LIMIT 50");
$purchases = [];
$total_purchases = 0;
while($p = $purchases_res->fetchArray(SQLITE3_ASSOC)) {
    $p['amount'] = floatval($p['amount'] ?? 0);
    $purchases[] = $p;
    $total_purchases += $p['amount'];
}
$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><meta name='mobile-web-app-capable' content='yes'>
<meta name='apple-mobile-web-app-capable' content='yes'>

    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>إدارة الموردين والمشتريات - نظام أبوضياء العالمي</title>
    <style>
        body { font-family: 'Tahoma', sans-serif; background: #FFFFFF; color: #f8fafc; margin: 0; padding: 15px; direction: rtl; padding-bottom: 95px; }
        .container { max-width: 680px; margin: auto; }
        .card { background: rgba(30, 41, 59, 0.9); backdrop-filter: blur(10px); border: 1.5px solid rgba(56, 189, 248, 0.3); border-radius: 16px; padding: 15px; margin-bottom: 15px; box-shadow: 0 8px 30px rgba(0,0,0,0.5); }
        h1 { text-align: center; color: #38bdf8; font-size: 20px; margin-bottom: 5px; }
        p.sub { text-align: center; color: #94a3b8; font-size: 12px; margin-bottom: 15px; }
        h3 { color: #38bdf8; font-size: 14px; margin-top: 0; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 6px; }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px; }
        @media(max-width: 500px) { .form-grid { grid-template-columns: 1fr; } }
        
        input, textarea { width: 100%; padding: 9px; background: rgba(15, 23, 42, 0.9); border: 1px solid rgba(56, 189, 248, 0.3); color: #fff; border-radius: 8px; font-size: 12px; box-sizing: border-box; }
        .btn-submit { width: 100%; background: linear-gradient(135deg, #38bdf8, #0284c7); color: #fff; border: none; padding: 11px; border-radius: 8px; font-weight: bold; font-size: 13px; cursor: pointer; box-shadow: 0 4px 15px rgba(56,189,248,0.4); }
        .btn-submit:active { transform: scale(0.98); }
        
        table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        th, td { padding: 8px; text-align: center; font-size: 11px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        th { background: rgba(56, 189, 248, 0.15); color: #38bdf8; }
        
        .total-box { display: flex; justify-content: space-between; font-size: 14px; font-weight: bold; background: rgba(56, 189, 248, 0.1); padding: 10px; border-radius: 10px; margin-bottom: 10px; color: #38bdf8; border: 1px solid rgba(56,189,248,0.2); }
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

<div class="container">
    <h1>🏢 إدارة الموردين والمشتريات</h1>
    <p class="sub">تسجيل ومتابعة فواتير التوريد والمشتريات من الموردين بدقة عالية</p>

    <div class="card">
        <h3>➕ تسجيل عملية شراء جديدة</h3>
        <form method="POST">
            <input type="hidden" name="add_purchase" value="1">
            <div class="form-grid">
                <div>
                    <label style="font-size: 11px; color: #94a3b8; display: block; margin-bottom: 3px;">اسم المورد:</label>
                    <input type="text" name="supplier_name" placeholder="اسم الشركة أو المورد..." required>
                </div>
                <div>
                    <label style="font-size: 11px; color: #94a3b8; display: block; margin-bottom: 3px;">إجمالي الفاتورة (ر.ي):</label>
                    <input type="number" step="0.01" name="amount" placeholder="0.00" required>
                </div>
            </div>
            <div style="margin-bottom: 10px;">
                <label style="font-size: 11px; color: #94a3b8; display: block; margin-bottom: 3px;">تفاصيل البضاعة / أصناف الشراء:</label>
                <input type="text" name="item_desc" placeholder="وصف البضاعة المشتراة...">
            </div>
            <div style="margin-bottom: 10px;">
                <label style="font-size: 11px; color: #94a3b8; display: block; margin-bottom: 3px;">ملاحظات إضافية:</label>
                <input type="text" name="notes" placeholder="ملاحظات السداد أو الاستلام...">
            </div>
            <button type="submit" class="btn-submit">💾 حفظ عملية الشراء وإضافتها</button>
        </form>
    </div>

    <div class="card">
        <h3>📋 سجل المشتريات الأخيرة</h3>
        
        <div class="total-box">
            <span>إجمالي المشتريات المسجلة:</span>
            <span><?php echo number_format($total_purchases, 2); ?> ر.ي</span>
        </div>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>المورد</th>
                    <th>الأصناف والتفاصيل</th>
                    <th>المبلغ</th>
                    <th>التاريخ</th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($purchases)): foreach($purchases as $p): 
                    $p_id = $p['id'] ?? 0;
                    $p_supp = $p['supplier_name'] ?? 'مورد';
                    $p_desc = $p['item_desc'] ?? '-';
                    $p_amt = floatval($p['amount'] ?? 0);
                    $p_date = $p['created_date'] ?? '-';
                ?>
                <tr>
                    <td><b>#<?php echo $p_id; ?></b></td>
                    <td style="font-weight: bold; color: #f8fafc;"><?php echo htmlspecialchars($p_supp); ?></td>
                    <td><?php echo htmlspecialchars($p_desc); ?></td>
                    <td style="color: #38bdf8; font-weight: bold;"><?php echo number_format($p_amt, 2); ?> ر.ي</td>
                    <td style="font-size: 10px; color: #aaa;"><?php echo htmlspecialchars($p_date); ?></td>
                </tr>
                <?php endforeach; else: ?>
                <tr>
                    <td colspan="5" style="text-align: center; color: #94a3b8; padding: 20px;">لا توجد مشريات مسجلة حتى الآن.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
