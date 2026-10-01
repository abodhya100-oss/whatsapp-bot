<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_role'])) { header("Location: login.php"); exit; }
$db = new SQLite3('/data/data/com.termux/files/home/secure_dbs/vmax.db');

// تعيين التواريخ الافتراضية (بداية الشهر الحالي ونهايته)
$date_from = isset($_GET['from']) ? SQLite3::escapeString($_GET['from']) : date('Y-m-01');
$date_to = isset($_GET['to']) ? SQLite3::escapeString($_GET['to']) : date('Y-m-t');

// ---------------------------------------------------------
// قسم تصدير الإكسيل (يتم تنفيذه فقط عند الضغط على زر التصدير)
// ---------------------------------------------------------
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $filename = "VMAX_Sales_Report_" . $date_from . "_to_" . $date_to . ".csv";
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // إضافة BOM لدعم اللغة العربية في برنامج Excel
    echo "\xEF\xBB\xBF";
    
    $output = fopen('php://output', 'w');
    
    // كتابة ترويسة الأعمدة في الإكسيل
    fputcsv($output, ['رقم الفاتورة', 'العميل', 'إجمالي الفاتورة', 'الخصم', 'التكلفة', 'الصافي', 'الربح المحقق', 'تاريخ الفاتورة', 'الكاشير']);
    
    // جلب البيانات
    $query = "SELECT * FROM invoices WHERE date >= '$date_from 00:00:00' AND date <= '$date_to 23:59:59' ORDER BY id ASC";
    $res = $db->query($query);
    
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $profit = $row['net_amount'] - $row['total_cost'];
        fputcsv($output, [
            $row['id'],
            $row['customer_name'],
            $row['total_amount'],
            $row['discount'],
            $row['total_cost'],
            $row['net_amount'],
            $profit,
            $row['date'],
            $row['user_name']
        ]);
    }
    
    // إضافة صف فارغ ثم صف الإجماليات في نهاية الإكسيل
    $totals = $db->querySingle("SELECT SUM(total_amount) as t_amt, SUM(discount) as t_disc, SUM(total_cost) as t_cost, SUM(net_amount) as t_net FROM invoices WHERE date >= '$date_from 00:00:00' AND date <= '$date_to 23:59:59'", true);
    $total_profit = $totals['t_net'] - $totals['t_cost'];
    
    fputcsv($output, []); // صف فارغ
    fputcsv($output, ['الإجماليات:', '', $totals['t_amt'], $totals['t_disc'], $totals['t_cost'], $totals['t_net'], $total_profit, '', '']);
    
    fclose($output);
    exit;
}

// ---------------------------------------------------------
// حسابات الشاشة بناءً على التواريخ المحددة
// ---------------------------------------------------------
$sales_q = $db->querySingle("SELECT SUM(net_amount) as tot, SUM(total_cost) as cost, COUNT(id) as inv_count FROM invoices WHERE date >= '$date_from 00:00:00' AND date <= '$date_to 23:59:59'", true);
$period_sales = $sales_q['tot'] ?? 0;
$period_cost = $sales_q['cost'] ?? 0;
$inv_count = $sales_q['inv_count'] ?? 0;

$period_expenses = $db->querySingle("SELECT SUM(amount) FROM expenses WHERE date >= '$date_from 00:00:00' AND date <= '$date_to 23:59:59'") ?? 0;
$period_returns = $db->querySingle("SELECT SUM(total_refund) FROM returns WHERE date >= '$date_from 00:00:00' AND date <= '$date_to 23:59:59'") ?? 0;

$period_profit = $period_sales - $period_cost - $period_expenses;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <link rel="manifest" href="manifest.json">
    <meta name='mobile-web-app-capable' content='yes'>
    <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js");}</script>
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>التقارير المتقدمة - أبو ضياء</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; background: #FFFFFF; color: #f8fafc; margin: 0; padding: 10px; padding-bottom: 80px; }
        .header { text-align: center; font-size: 16px; font-weight: 900; color: #10b981; margin-bottom: 15px; }
        .glass-panel { background: linear-gradient(135deg, rgba(30, 41, 59, 0.95), rgba(15, 23, 42, 0.98)); border: 1px solid rgba(16, 185, 129, 0.4); border-radius: 12px; padding: 15px; margin-bottom: 15px; text-align: center; }
        .form-control { width: 100%; padding: 10px; background: #FFFFFF; color: #fff; border: 1px solid rgba(16, 185, 129, 0.4); border-radius: 8px; margin-bottom: 10px; box-sizing: border-box; text-align: center; }
        .btn-search { background: #FFFFFF; color: white; padding: 10px; border: none; width: 100%; font-weight: bold; border-radius: 8px; cursor: pointer; margin-bottom: 10px; }
        .btn-excel { background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 15px; border: none; width: 100%; font-weight: 900; font-size: 14px; border-radius: 8px; cursor: pointer; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4); text-decoration: none; display: block; box-sizing: border-box; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 15px; }
        .metric-box { background: rgba(15, 23, 42, 0.6); border: 1px solid #334155; padding: 10px; border-radius: 8px; }
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
    <div class="header">📈 التقارير المتقدمة (تصدير Excel)</div>

    <div class="glass-panel">
        <div style="font-size: 12px; color: #93c5fd; margin-bottom: 10px; font-weight: bold;">حدد الفترة الزمنية للتقرير:</div>
        <form method="GET">
            <div class="grid-2">
                <div>
                    <label style="font-size: 9px; color: #94a3b8;">من تاريخ:</label>
                    <input type="date" name="from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>" required>
                </div>
                <div>
                    <label style="font-size: 9px; color: #94a3b8;">إلى تاريخ:</label>
                    <input type="date" name="to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>" required>
                </div>
            </div>
            <button type="submit" class="btn-search">🔍 تحديث التقرير</button>
        </form>
    </div>

    <div class="glass-panel" style="border-color: #38bdf8;">
        <div style="font-size: 12px; color: #38bdf8; margin-bottom: 15px; font-weight: bold;">📊 ملخص الفترة المحددة</div>
        
        <div class="grid-2">
            <div class="metric-box">
                <div style="font-size: 9px; color: #94a3b8;">عدد الفواتير</div>
                <div style="font-weight: bold; color: #f8fafc; font-size: 16px;"><?php echo $inv_count; ?></div>
            </div>
            <div class="metric-box">
                <div style="font-size: 9px; color: #94a3b8;">إجمالي المبيعات</div>
                <div style="font-weight: bold; color: #4ade80; font-size: 16px;"><?php echo number_format($period_sales, 0); ?></div>
            </div>
            <div class="metric-box">
                <div style="font-size: 9px; color: #94a3b8;">إجمالي المصروفات</div>
                <div style="font-weight: bold; color: #f87171; font-size: 16px;"><?php echo number_format($period_expenses, 0); ?></div>
            </div>
            <div class="metric-box">
                <div style="font-size: 9px; color: #94a3b8;">مرتجعات</div>
                <div style="font-weight: bold; color: #f59e0b; font-size: 16px;"><?php echo number_format($period_returns, 0); ?></div>
            </div>
        </div>

        <div style="background: rgba(56, 189, 248, 0.1); border: 1px dashed #38bdf8; padding: 10px; border-radius: 8px; margin-bottom: 15px;">
            <div style="font-size: 11px; color: #bae6fd;">صافي الربح خلال هذه الفترة:</div>
            <div style="font-size: 22px; font-weight: 900; color: #38bdf8; direction: ltr;"><?php echo number_format($period_profit, 0); ?> ر.ي</div>
        </div>

        <a href="reports_advanced.php?export=excel&from=<?php echo urlencode($date_from); ?>&to=<?php echo urlencode($date_to); ?>" class="btn-excel">
            📥 تصدير المبيعات إلى Excel
        </a>
    </div>

    <a href="dashboard.php" style="display: block; text-align: center; color: #94a3b8; text-decoration: none; font-size: 12px; font-weight: bold;">🔙 عودة للوحة القيادة</a>

<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>
</html>

