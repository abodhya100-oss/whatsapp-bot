<?php
// cash_box.php - تقرير وحركة الصندوق بدقة عالية
require_once 'auth.php';
require_once 'db.php';

// فلترة حسب التاريخ إن وجد (افتراضياً لكل الحركات)
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$where_clause = "";
if (!empty($start_date) && !empty($end_date)) {
    $where_clause = " WHERE date BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";
}

// جلب حركات المقبوضات من سندات القبض / السدادات
$transactions = [];
$total_in = 0;
$total_out = 0;

$pay_res = $db->query("SELECT p.*, c.name as customer_name FROM payments p LEFT JOIN customers c ON p.customer_id = c.id $where_clause ORDER BY p.id DESC");
if ($pay_res) {
    while ($row = $pay_res->fetchArray(SQLITE3_ASSOC)) {
        $amount = floatval($row['amount']);
        $total_in += $amount;
        $transactions[] = [
            'date' => $row['date'],
            'desc' => "سداد من العميل: " . ($row['customer_name'] ?: 'نقدي') . " - " . ($row['notes'] ?? 'قبض نقدية'),
            'type' => 'in',
            'amount' => $amount
        ];
    }
}

// جلب الفواتير النقدية (كاش) كمقبوضات للصندوق
$cash_inv_res = $db->query("SELECT i.*, c.name as customer_name FROM invoices i LEFT JOIN customers c ON i.customer_id = c.id WHERE i.payment_type = 'cash' " . (!empty($start_date) ? "AND i.date BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'" : "") . " ORDER BY i.id DESC");
if ($cash_inv_res) {
    while ($row = $cash_inv_res->fetchArray(SQLITE3_ASSOC)) {
        $amount = floatval($row['paid_amount']);
        $total_in += $amount;
        $transactions[] = [
            'date' => $row['date'],
            'desc' => "فاتورة مبيعات نقدية (كاش) رقم #" . $row['id'] . " للعميل: " . ($row['customer_name'] ?: 'عام'),
            'type' => 'in',
            'amount' => $amount
        ];
    }
}

// فرز الحركات تنازلياً حسب التاريخ الأحدث
usort($transactions, function($a, $b) {
    return strcmp($b['date'], $a['date']);
});

$net_box = $total_in - $total_out;
$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" style="direction: rtl; unicode-bidi: embed;">
<head>
<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><meta name='mobile-web-app-capable' content='yes'>
<meta name='apple-mobile-web-app-capable' content='yes'>

    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>حركة الصندوق والخزينة</title>
    <?php include 'global_style.php'; ?>
    <style>
        * { direction: rtl; text-align: right; box-sizing: border-box; }
        .lux-wrapper { padding: 10px; max-width: 700px; margin: 0 auto 80px auto; }
        .box-header { background: linear-gradient(135deg, #0F172A, #1E3A8A); color: #FFF; padding: 15px; border-radius: 10px; margin-bottom: 15px; }
        .box-header h2 { margin: 0 0 5px 0; font-size: 16px; color: #FBBF24; }
        
        .metrics-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 15px; }
        .metric-card { background: #FFFFFF; border: 1px solid #CBD5E1; padding: 10px; border-radius: 8px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
        .metric-card .title { font-size: 10.5px; color: #64748B; margin-bottom: 4px; }
        .metric-card .val { font-size: 13px; font-weight: bold; }
        
        .filter-box { background: #FFFFFF; border: 1px solid #CBD5E1; padding: 10px; border-radius: 8px; margin-bottom: 12px; display: flex; gap: 6px; align-items: flex-end; }
        .filter-input { flex: 1; padding: 6px; border: 1px solid #CBD5E1; border-radius: 4px; font-size: 11px; background: #FFFFFF; }
        .filter-btn { background: #FFFFFF; color: #FFF; border: none; padding: 7px 12px; border-radius: 4px; font-size: 11px; font-weight: bold; cursor: pointer; }

        .statement-table { width: 100%; border-collapse: collapse; background: #FFFFFF; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .statement-table th { background: #FFFFFF; color: #FFF; padding: 10px 8px; font-size: 11px; text-align: center; }
        .statement-table td { padding: 9px 8px; font-size: 11px; border-bottom: 1px solid #E2E8F0; text-align: center; color: #334155; }
        .statement-table tr:nth-child(even) { background: #FFFFFF; }
        
        .in-val { color: #16A34A; font-weight: bold; }
        .out-val { color: #DC2626; font-weight: bold; }
        
        .back-btn { display: inline-block; background: #FFFFFF; color: #FFF; padding: 8px 15px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: bold; margin-bottom: 10px; }

        @media print {
            .back-btn, .filter-box, nav, .nav-menu { display: none !important; }
            body { background: #FFF !important; color: #000 !important; }
        }
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
<div class="lux-wrapper">
    <a href="dashboard.php" class="back-btn">⬅ العودة للرئيسية</a>

    <div class="box-header">
        <h2>💰 حركة الصندوق والخزينة النقدية</h2>
        <p style="margin: 0; font-size: 11.5px; color: #CBD5E1;">متابعة تفصيلية لكافة الواردات والصادرات النقدية لحظة بلحظة</p>
    </div>

    <!-- ملخص الإحصائيات -->
    <div class="metrics-grid">
        <div class="metric-card">
            <div class="title">إجمالي المقبوضات (داخل)</div>
            <div class="val" style="color: #16A34A;" dir="ltr"><?php echo number_format($total_in, 2); ?> ر.ي</div>
        </div>
        <div class="metric-card">
            <div class="title">إجمالي المصروفات (خارج)</div>
            <div class="val" style="color: #DC2626;" dir="ltr"><?php echo number_format($total_out, 2); ?> ر.ي</div>
        </div>
        <div class="metric-card">
            <div class="title">صافي رصيد الصندوق</div>
            <div class="val" style="color: #2563EB;" dir="ltr"><?php echo number_format($net_box, 2); ?> ر.ي</div>
        </div>
    </div>

    <!-- فلتر التاريخ -->
    <form method="GET" class="filter-box">
        <div style="flex:1;">
            <label style="font-size:10px; font-weight:bold; display:block; margin-bottom:2px;">من تاريخ:</label>
            <input type="date" name="start_date" class="filter-input" value="<?php echo htmlspecialchars($start_date); ?>">
        </div>
        <div style="flex:1;">
            <label style="font-size:10px; font-weight:bold; display:block; margin-bottom:2px;">إلى تاريخ:</label>
            <input type="date" name="end_date" class="filter-input" value="<?php echo htmlspecialchars($end_date); ?>">
        </div>
        <button type="submit" class="filter-btn">🔍 تصفية</button>
        <a href="cash_box.php" style="background: #FFFFFF; color:#FFF; padding:7px 10px; border-radius:4px; font-size:11px; text-decoration:none; font-weight:bold;">إلغاء الفلتر</a>
    </form>

    <!-- جدول الحركات -->
    <?php if(empty($transactions)): ?>
        <div style="text-align: center; padding: 30px; background: #FFFFFF; border-radius: 8px; color: #64748B; font-size: 12px;">لا توجد حركات مالية مسجلة في الصندوق خلال هذه الفترة.</div>
    <?php else: ?>
        <table class="statement-table">
            <thead>
                <tr>
                    <th>التاريخ والوقت</th>
                    <th>البيان / الوصف</th>
                    <th>مقبوضات (داخل)</th>
                    <th>مدفوعات (خارج)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($transactions as $tx): ?>
                <tr>
                    <td style="font-size: 10px; color: #64748B;"><?php echo $tx['date']; ?></td>
                    <td style="text-align: right; padding-right: 10px; font-weight: bold;"><?php echo htmlspecialchars($tx['desc']); ?></td>
                    <td class="in-val" dir="ltr"><?php echo $tx['type'] == 'in' ? number_format($tx['amount'], 2) . ' ر.ي' : '-'; ?></td>
                    <td class="out-val" dir="ltr"><?php echo $tx['type'] == 'out' ? number_format($tx['amount'], 2) . ' ر.ي' : '-'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
