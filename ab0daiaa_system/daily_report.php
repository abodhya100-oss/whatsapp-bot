<?php
// daily_report.php - تقرير المبيعات اليومية وصافي الصندوق (مع فحص تلقائي للجداول والأعمدة)
require_once 'auth.php';
require_admin();
$db_file = '/data/data/com.termux/files/home/secure_dbs/vmax.db';
$db = new SQLite3($db_file);
$today = date('Y-m-d');

// التأكد من وجود جدول receipts وإضافة الأعمدة الناقصة إن وجدت
$db->exec("CREATE TABLE IF NOT EXISTS receipts (id INTEGER PRIMARY KEY AUTOINCREMENT, customer_id INTEGER, amount REAL)");
@$db->exec("ALTER TABLE receipts ADD COLUMN receipt_date DATETIME DEFAULT CURRENT_TIMESTAMP");
@$db->exec("ALTER TABLE receipts ADD COLUMN note TEXT");

// التأكد من وجود جدول expenses وإضافة الأعمدة الناقصة إن وجدت
$db->exec("CREATE TABLE IF NOT EXISTS expenses (id INTEGER PRIMARY KEY AUTOINCREMENT, amount REAL, description TEXT)");
@$db->exec("ALTER TABLE expenses ADD COLUMN expense_date DATETIME DEFAULT CURRENT_TIMESTAMP");

// 1. مبيعات الكاش
$cash_sales_q = $db->querySingle("SELECT COALESCE(SUM(total), 0) FROM invoices WHERE payment_type = 'cash' AND DATE(created_date) = DATE('now', 'localtime')");
$cash_sales = floatval($cash_sales_q);

// 2. مبيعات الدين
$credit_sales_q = $db->querySingle("SELECT COALESCE(SUM(total), 0) FROM invoices WHERE payment_type = 'credit' AND DATE(created_date) = DATE('now', 'localtime')");
$credit_sales = floatval($credit_sales_q);

// 3. المقبوضات (سدادات الديون)
$receipts_q = $db->querySingle("SELECT COALESCE(SUM(amount), 0) FROM receipts WHERE DATE(receipt_date) = DATE('now', 'localtime')");
$receipts_total = floatval($receipts_q);

// 4. إجمالي المصروفات
$exp_q = $db->querySingle("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE DATE(expense_date) = DATE('now', 'localtime')");
$expenses_total = floatval($exp_q);

// 5. صافي النقدية الفعلي في الصندوق
$net_cash_in_box = ($cash_sales + $receipts_total) - $expenses_total;

// حركة الأصناف
$sold_items = [];
$items_res = $db->query("
    SELECT ii.product_name, ii.unit, COALESCE(SUM(ii.qty), 0) as total_qty, COALESCE(SUM(ii.free_qty), 0) as total_free, COALESCE(SUM(ii.total), 0) as total_val
    FROM invoice_items ii JOIN invoices i ON ii.invoice_id = i.id
    WHERE DATE(i.created_date) = DATE('now', 'localtime')
    GROUP BY ii.product_name, ii.unit ORDER BY total_qty DESC
");
if ($items_res) {
    while ($row = $items_res->fetchArray(SQLITE3_ASSOC)) { $sold_items[] = $row; }
}
$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><meta name='mobile-web-app-capable' content='yes'>
<meta name='apple-mobile-web-app-capable' content='yes'>

    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>يومية المبيعات - نظام أبوضياء</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Tahoma', sans-serif; background: #FFFFFF; margin: 0; padding: 10px; padding-bottom: 75px; color: #1e293b; }
        .main-container { max-width: 650px; margin: 0 auto; background: #FFFFFF; border-radius: 12px; padding: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #fbbf24; padding-bottom: 10px; margin-bottom: 15px; background: #FFFFFF; color: #fff; padding: 12px; border-radius: 8px; }
        .header h2 { margin: 0; font-size: 15px; }
        .date-badge { background: #FFFFFF; color: #92400e; padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: bold; }
        
        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 15px; }
        .stat-card { background: #FFFFFF; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px; text-align: center; }
        .stat-card.main-box { grid-column: span 2; background: #FFFFFF; color: #fff; border: none; box-shadow: 0 4px 10px rgba(22, 163, 74, 0.3); padding: 15px; }
        
        .stat-title { font-size: 11px; color: #64748b; font-weight: bold; margin-bottom: 5px; }
        .main-box .stat-title { color: #dcfce7; font-size: 14px; }
        
        .stat-value { font-size: 15px; font-weight: bold; color: #1e293b; }
        .main-box .stat-value { color: #fff; font-size: 24px; text-shadow: 1px 1px 2px rgba(0,0,0,0.3); }
        
        .section-title { font-size: 13px; font-weight: bold; color: #1d4ed8; margin-bottom: 8px; border-bottom: 2px solid #e2e8f0; padding-bottom: 5px; }
        
        .table-responsive { width: 100%; overflow-x: auto; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; border-radius: 8px; overflow: hidden; }
        th { background: #FFFFFF; color: #fff; padding: 8px 4px; text-align: center; }
        td { padding: 8px 4px; text-align: center; border-bottom: 1px solid #e2e8f0; font-weight: bold; }
        .t-name { text-align: right; color: #0f172a; }
        
        /* شريط التنقل السفلي */
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
        <div class="date-badge"><?php echo $today; ?></div>
    </div>

    <div class="stats-grid">
        <div class="stat-card" style="border-color: #93c5fd; background: #FFFFFF;">
            <div class="stat-title" style="color: #2563eb;">مبيعات الكاش + الدفعات</div>
            <div class="stat-value" style="color: #1d4ed8;"><?php echo number_format($cash_sales + $receipts_total, 0); ?></div>
        </div>
        <div class="stat-card" style="border-color: #fca5a5; background: #FFFFFF;">
            <div class="stat-title" style="color: #ef4444;">إجمالي المصروفات 🔻</div>
            <div class="stat-value" style="color: #dc2626;">- <?php echo number_format($expenses_total, 0); ?></div>
        </div>
        <div class="stat-card main-box">
            <div class="stat-title">💰 صافي النقدية الفعلي في الصندوق</div>
            <div class="stat-value"><?php echo number_format($net_cash_in_box, 0); ?> ر.ي</div>
        </div>
        <div class="stat-card" style="grid-column: span 2; border-style: dashed;">
            <div class="stat-title">مبيعات آجلة (دين مسجل في الدفتر)</div>
            <div class="stat-value" style="color: #d97706;"><?php echo number_format($credit_sales, 0); ?> ر.ي</div>
        </div>
    </div>

    <div class="section-title">📦 حركة الأصناف المباعة اليوم</div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th style="width: 40%;">الصنف</th>
                    <th>الكمية</th>
                    <th>مجاني</th>
                    <th>القيمة</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($sold_items)): ?>
                <tr><td colspan="4" style="padding: 15px; color: #64748b;">لم يتم بيع أي أصناف اليوم.</td></tr>
                <?php else: ?>
                    <?php foreach($sold_items as $item): ?>
                    <tr>
                        <td class="t-name"><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td style="color:#2563eb; font-size:12px;"><?php echo floatval($item['total_qty']); ?></td>
                        <td style="color:#d97706;"><?php echo floatval($item['total_free']); ?></td>
                        <td style="color:#16a34a;"><?php echo number_format($item['total_val'], 0); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- شريط التنقل السفلي -->
<div class="bottom-nav">
    <a href="pos.php" class="nav-item"><span>🛒</span>الكاشير</a>
    <a href="inventory.php" class="nav-item"><span>📦</span>المستودع</a>
    <a href="debts.php" class="nav-item"><span>📒</span>الديون</a>
    <a href="daily_report.php" class="nav-item active"><span>📊</span>اليومية</a>
    <a href="dashboard.php" class="nav-item"><span>⚙️</span>الرئيسية</a>
</div>

</body>
</html>
