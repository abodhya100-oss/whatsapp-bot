<?php
// history.php - سجل الفواتير والمبيعات السابقة
require_once 'auth.php';
$db_file = '/data/data/com.termux/files/home/secure_dbs/vmax.db';
$db = new SQLite3($db_file);

// التأكد من وجود جدول الفواتير والعملاء
$db->exec("CREATE TABLE IF NOT EXISTS invoices (id INTEGER PRIMARY KEY AUTOINCREMENT, customer_id INTEGER, total REAL, discount REAL DEFAULT 0, tax REAL DEFAULT 0, payment_type TEXT DEFAULT 'cash', currency TEXT DEFAULT 'YER', created_date DATETIME DEFAULT CURRENT_TIMESTAMP)");
$db->exec("CREATE TABLE IF NOT EXISTS customers (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, phone TEXT, address TEXT)");

// معالجة حذف فاتورة (للمدير فقط)
if (isset($_POST['action']) && $_POST['action'] === 'delete_invoice' && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $inv_id = intval($_POST['invoice_id']);
    if ($inv_id > 0) {
        $db->exec("DELETE FROM invoice_items WHERE invoice_id = $inv_id");
        $db->exec("DELETE FROM invoices WHERE id = $inv_id");
        echo "<script>alert('🗑️ تم حذف الفاتورة بنجاح!'); window.location.href='history.php';</script>";
        exit;
    }
}

// جلب آخر 50 فاتورة
$invoices = [];
$res = @$db->query("
    SELECT i.*, c.name as customer_name 
    FROM invoices i 
    LEFT JOIN customers c ON i.customer_id = c.id 
    ORDER BY i.id DESC LIMIT 50
");
if ($res) {
    while ($row = @$res->fetchArray(SQLITE3_ASSOC)) {
        if ($row) $invoices[] = $row;
    }
}
$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><meta name='mobile-web-app-capable' content='yes'>
<meta name='apple-mobile-web-app-capable' content='yes'>

    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>سجل الفواتير - نظام أبوضياء</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Tahoma', sans-serif; background: #FFFFFF; margin: 0; padding: 10px; padding-bottom: 75px; color: #1e293b; }
        .main-container { max-width: 650px; margin: 0 auto; background: #FFFFFF; border-radius: 12px; padding: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #fbbf24; padding-bottom: 10px; margin-bottom: 15px; background: #FFFFFF; color: #fff; padding: 12px; border-radius: 8px; }
        .header h2 { margin: 0; font-size: 15px; }
        
        .table-responsive { width: 100%; overflow-x: auto; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; border-radius: 8px; overflow: hidden; }
        th { background: #FFFFFF; color: #fff; padding: 8px 4px; text-align: center; }
        td { padding: 8px 4px; text-align: center; border-bottom: 1px solid #e2e8f0; font-weight: bold; background: #FFFFFF; }
        .t-name { text-align: right; color: #1d4ed8; }
        
        .btn { padding: 5px 8px; border: none; border-radius: 6px; font-weight: bold; font-size: 10px; cursor: pointer; color: #fff; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 2px; }
        .btn-info { background: #FFFFFF; }
        .btn-warning { background: #FFFFFF; }
        .btn-danger { background: #FFFFFF; }
        
        .badge-cash { background: #FFFFFF; color: #15803d; padding: 2px 6px; border-radius: 4px; font-size: 9px; }
        .badge-credit { background: #FFFFFF; color: #b45309; padding: 2px 6px; border-radius: 4px; font-size: 9px; }
        
        .bottom-nav { position: fixed; bottom: 0; left: 0; width: 100%; background: #FFFFFF; border-top: 2px solid #fbbf24; display: flex; justify-content: space-around; padding: 8px 0; z-index: 9999; box-shadow: 0 -5px 15px rgba(0,0,0,0.3); }
        .nav-item { color: #94a3b8; text-decoration: none; font-size: 10px; font-weight: bold; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 2px; flex: 1; }
        .nav-item span { font-size: 16px; }
        .nav-item.active, .nav-item:hover { color: #fbbf24; }
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

<div class="main-container">
    <div class="header">
        <h2>🗂️ سجل الفواتير والمبيعات</h2>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>رقم</th>
                    <th style="width: 35%;">العميل</th>
                    <th>الإجمالي</th>
                    <th>النوع</th>
                    <th>التاريخ</th>
                    <th>خيارات</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($invoices)): ?>
                <tr>
                    <td colspan="6" style="padding: 20px; color: #64748b;">لا توجد فواتير مسجلة حتى الآن.</td>
                </tr>
                <?php else: ?>
                    <?php foreach($invoices as $inv): ?>
                    <tr>
                        <td style="color:#d97706;">#<?php echo $inv['id']; ?></td>
                        <td class="t-name"><?php echo htmlspecialchars($inv['customer_name'] ?? 'عميل نقدي'); ?></td>
                        <td style="color:#16a34a;"><?php echo number_format($inv['total'] ?? 0, 0); ?> <?php echo $inv['currency'] ?? 'YER'; ?></td>
                        <td>
                            <?php if(($inv['payment_type'] ?? 'cash') === 'cash'): ?>
                                <span class="badge-cash">كاش</span>
                            <?php else: ?>
                                <span class="badge-credit">آجل</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size: 9.5px; color: #475569;"><?php echo !empty($inv['created_date']) ? date('m/d H:i', strtotime($inv['created_date'])) : '-'; ?></td>
                        <td>
                            <a href="view_invoice.php?id=<?php echo $inv['id']; ?>" class="btn btn-info" title="عرض PDF">📄</a>
                            <a href="thermal.php?id=<?php echo $inv['id']; ?>" class="btn btn-warning" title="طباعة حرارية">🖨️</a>
                            <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('⚠️ هل أنت متأكد من حذف هذه الفاتورة نهائياً؟');">
                                <input type="hidden" name="action" value="delete_invoice">
                                <input type="hidden" name="invoice_id" value="<?php echo $inv['id']; ?>">
                                <button type="submit" class="btn btn-danger" title="حذف">❌</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="bottom-nav">
    <a href="pos.php" class="nav-item"><span>🛒</span>الكاشير</a>
    <a href="inventory.php" class="nav-item"><span>📦</span>المستودع</a>
    <a href="debts.php" class="nav-item"><span>📒</span>الديون</a>
    <a href="daily_report.php" class="nav-item"><span>📊</span>اليومية</a>
    <a href="dashboard.php" class="nav-item"><span>⚙️</span>الرئيسية</a>
</div>

</body>
</html>
