<?php
include_once 'config_loader.php';
require_once 'auth.php';
$db_file = '/data/data/com.termux/files/home/secure_dbs/vmax.db';
$db = new SQLite3($db_file);

$inv_cols_res = $db->query("PRAGMA table_info(invoices)");
$inv_columns = [];
while($col = $inv_cols_res->fetchArray(SQLITE3_ASSOC)) {
    $inv_columns[] = $col['name'];
}
$total_col = 'total';
foreach(['total', 'total_amount', 'grand_total', 'net_total', 'amount'] as $tc) {
    if(in_array($tc, $inv_columns)) { $total_col = $tc; break; }
}

$sales_res = @$db->query("SELECT SUM($total_col) as total_sales, COUNT(*) as count_sales FROM invoices");
$sales_data = $sales_res ? $sales_res->fetchArray(SQLITE3_ASSOC) : ['total_sales' => 0, 'count_sales' => 0];
$total_sales = (float)($sales_data['total_sales'] ?? 0);
$count_sales = (int)($sales_data['count_sales'] ?? 0);

$receipts_res = @$db->query("SELECT SUM(amount) as total_receipts FROM vouchers WHERE type = 'receipt'");
$receipts_data = $receipts_res ? $receipts_res->fetchArray(SQLITE3_ASSOC) : ['total_receipts' => 0];
$total_receipts = (float)($receipts_data['total_receipts'] ?? 0);

$payments_res = @$db->query("SELECT SUM(amount) as total_payments FROM vouchers WHERE type = 'payment'");
$payments_data = $payments_res ? $payments_res->fetchArray(SQLITE3_ASSOC) : ['total_payments' => 0];
$total_payments = (float)($payments_data['total_payments'] ?? 0);

$expenses_res = @$db->query("SELECT SUM(amount) as total_expenses FROM expenses");
$expenses_data = $expenses_res ? $expenses_res->fetchArray(SQLITE3_ASSOC) : ['total_expenses' => 0];
$total_expenses = (float)($expenses_data['total_expenses'] ?? 0);

$net_treasury = $total_sales + $total_receipts - $total_payments - $total_expenses;

$distributors = [
    'صبري بازرقان', 'صادق باسلوم', 'محمد باجمال', 'محمد كرامه', 
    'حسن الكبيش', 'جلال عويض', 'صدام هويدي', 'عمر باللحزز', 
    'يونس صالح بن ناشي', 'محمد ادريس', 'مفيد شرقه', 'وليد باجابر', 'صفوان الحاشدي'
];

$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'>
    <meta name='mobile-web-app-capable' content='yes'>
    <meta name='apple-mobile-web-app-capable' content='yes'>
    <title>التقرير المالي وحركة الموزعين - مؤسسة أبو ضياء</title>
    <style>
        body { font-family: 'Tahoma', Arial, sans-serif; background: #FFFFFF; color: #1e293b; margin: 0; padding: 15px; direction: rtl; padding-bottom: 95px; }
        .container { max-width: 680px; margin: auto; }
        .card { background: #FFFFFF; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; margin-bottom: 15px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02); }
        h1 { text-align: center; color: #0f172a; font-size: 20px; margin-bottom: 8px; font-weight: 800; }
        p.sub { text-align: center; color: #64748b; font-size: 13px; margin-bottom: 20px; }
        h3 { color: #334155; font-size: 15px; margin-top: 0; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px; font-weight: 700; }
        .report-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 15px; }
        @media(max-width: 500px) { .report-grid { grid-template-columns: 1fr; } }
        .stat-box { background: #FFFFFF; border: 1px solid #e2e8f0; border-radius: 12px; padding: 15px; text-align: center; }
        .stat-title { font-size: 12px; color: #64748b; margin-bottom: 8px; font-weight: 600; }
        .stat-value { font-size: 18px; font-weight: 800; color: #10b981; }
        .stat-value.expense { color: #ef4444; }
        .stat-value.primary { color: #0284c7; }
        .net-box { background: #FFFFFF; border: 1px solid #a7f3d0; border-radius: 14px; padding: 20px; text-align: center; margin-top: 15px; }
        .dist-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #F1F5F9; font-size: 13px; font-weight: bold; }
        .dist-row:last-child { border-bottom: none; }
        .actions { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 20px; }
        .btn { padding: 12px; border-radius: 10px; border: none; font-weight: bold; font-size: 13px; cursor: pointer; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 6px; }
        .btn-print { background: linear-gradient(135deg, #0284c7, #0369a1); color: #fff; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3); }
        .btn-wa { background: linear-gradient(135deg, #10b981, #059669); color: #fff; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); }
        .btn:active { transform: scale(0.98); }
        @media print {
            body { background: #fff !important; color: #000 !important; }
            .card { background: #fff !important; border: 1px solid #ddd !important; box-shadow: none; }
            .actions { display: none !important; }
            .stat-box { background: #f8f9fa !important; border: 1px solid #ddd !important; }
            .net-box { background: #f0fdf4 !important; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📈 التقرير المالي والأرباح الشامل</h1>
        <p class="sub">مؤسسة أبو ضياء للتوزيع - تقرير الأرباح والحركة المالية والموزعين</p>
        <div class="card">
            <h3>📊 إحصائيات الحركة المالية العامة</h3>
            <div class="report-grid">
                <div class="stat-box">
                    <div class="stat-title">إجمالي المبيعات (<?php echo $count_sales; ?> فاتورة)</div>
                    <div class="stat-value"><?php echo number_format($total_sales, 2); ?> ر.ي</div>
                </div>
                <div class="stat-box">
                    <div class="stat-title">إجمالي المقبوضات النقدية</div>
                    <div class="stat-value primary"><?php echo number_format($total_receipts, 2); ?> ر.ي</div>
                </div>
                <div class="stat-box">
                    <div class="stat-title">إجمالي المدفوعات والصرف</div>
                    <div class="stat-value expense"><?php echo number_format($total_payments, 2); ?> ر.ي</div>
                </div>
                <div class="stat-box">
                    <div class="stat-title">إجمالي المصاريف المسجلة</div>
                    <div class="stat-value expense"><?php echo number_format($total_expenses, 2); ?> ر.ي</div>
                </div>
            </div>
            <div class="net-box">
                <div style="font-size: 13px; color: #065f46; margin-bottom: 8px; font-weight: 600;">صافي السيولة النقدية في الخزينة:</div>
                <div style="font-size: 24px; font-weight: 800; color: #047857;"><?php echo number_format($net_treasury, 2); ?> ر.ي</div>
            </div>
        </div>
        <div class="card">
            <h3>🚚 تقرير حركة وتحصيلات الموزعين</h3>
            <?php foreach($distributors as $index => $name): ?>
            <div class="dist-row">
                <span><?php echo ($index + 1) . '. ' . htmlspecialchars($name); ?></span>
                <span style="color: #2563EB; font-size: 11.5px;">مندوب معتمد 📋</span>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="actions">
            <button onclick="window.print()" class="btn btn-print">🖨️ طباعة التقرير / PDF</button>
            <a href="https://api.whatsapp.com/send?text=<?php echo urlencode("📊 التقرير المالي الشامل - مؤسسة أبو ضياء للتوزيع\n\nإجمالي المبيعات: " . number_format($total_sales,2) . " ر.ي\nالمقبوضات: " . number_format($total_receipts,2) . " ر.ي\nالمدفوعات: " . number_format($total_payments,2) . " ر.ي\nالمصاريف: " . number_format($total_expenses,2) . " ر.ي\n\n✨ صافي الخزينة: " . number_format($net_treasury,2) . " ر.ي"); ?>" target="_blank" class="btn btn-wa">💬 مشاركة واتساب</a>
        </div>
    </div>
</body>
</html>
