<?php
// profit_calculator.php - حاسبة الأرباح وصافي الدخل المباشر - V-MAX PRO
require_once 'auth.php';
$db = new SQLite3(__DIR__ . '/vmax.db');

// حساب الإيرادات الفعلية من الفواتير
$total_sales = @$db->querySingle("SELECT SUM(grand_total) FROM invoices") ?: 0;

// حساب المصروفات الفعلية المسجلة
$total_expenses = @$db->querySingle("SELECT SUM(amount) FROM expenses") ?: 0;

// حساب إجمالي سندات القبض الأخرى
$total_receipts = @$db->querySingle("SELECT SUM(amount) FROM vouchers WHERE type='receipt'") ?: 0;

// حساب إجمالي سندات الصرف
$total_payments = @$db->querySingle("SELECT SUM(amount) FROM vouchers WHERE type='payment'") ?: 0;

// تقدير تكلفة البضاعة أو صافي الدخل العام
$net_profit = ($total_sales + $total_receipts) - ($total_expenses + $total_payments);

$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><meta name='mobile-web-app-capable' content='yes'>
<meta name='apple-mobile-web-app-capable' content='yes'>

    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>حاسبة الأرباح والمالية - V-MAX PRO</title>
    <?php if(file_exists('global_style.php')) include 'global_style.php'; ?>
</head>
<body>
<div class="lux-wrapper">
    <div class="lux-card" style="text-align: center;">
        <h2 class="lux-title" style="border:none; text-align:center;">📊 حاسبة الأرباح وصافي الدخل</h2>
        <p style="font-size:11px; color:var(--text-muted); margin-bottom:20px;">
            حساب تحليلي مباشر ومبني على العمليات المالية والفواتير المسجلة فعلياً في النظام.
        </p>

        <div style="background:rgba(16,185,129,0.1); border:1px solid var(--border-glass); border-radius:16px; padding:20px; margin-bottom:15px;">
            <div style="font-size:12px; color:var(--text-muted); font-weight:bold;">صافي السيولة والأرباح التقديرية</div>
            <div style="font-size:24px; font-weight:bold; color:var(--emerald-glow); margin-top:8px; text-shadow:0 0 15px rgba(16,185,129,0.4);">
                <?php echo number_format($net_profit, 2); ?> ر.ي
            </div>
        </div>

        <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:10px; text-align:right; margin-bottom:20px;">
            <div style="background:rgba(30,41,59,0.6); padding:12px; border-radius:12px;">
                <div style="font-size:10px; color:var(--text-muted);">إجمالي مبيعات الفواتير</div>
                <div style="font-size:14px; font-weight:bold; color:var(--cyan-glow); margin-top:4px;"><?php echo number_format($total_sales, 2); ?></div>
            </div>
            <div style="background:rgba(30,41,59,0.6); padding:12px; border-radius:12px;">
                <div style="font-size:10px; color:var(--text-muted);">إجمالي المصروفات</div>
                <div style="font-size:14px; font-weight:bold; color:#f43f5e; margin-top:4px;"><?php echo number_format($total_expenses, 2); ?></div>
            </div>
            <div style="background:rgba(30,41,59,0.6); padding:12px; border-radius:12px;">
                <div style="font-size:10px; color:var(--text-muted);">سندات القبض الواردة</div>
                <div style="font-size:14px; font-weight:bold; color:var(--emerald-glow); margin-top:4px;"><?php echo number_format($total_receipts, 2); ?></div>
            </div>
            <div style="background:rgba(30,41,59,0.6); padding:12px; border-radius:12px;">
                <div style="font-size:10px; color:var(--text-muted);">سندات الصرف الصادرة</div>
                <div style="font-size:14px; font-weight:bold; color:#fbbf24; margin-top:4px;"><?php echo number_format($total_payments, 2); ?></div>
            </div>
        </div>

        <a href="index.php" class="lux-btn" style="background:#1e293b; color:var(--cyan-glow); border:1px solid var(--border-glass); text-decoration:none;">🏠 العودة للرئيسية الملكية</a>
    </div>
</div>

<?php if(file_exists('nav_menu.php')) { include 'nav_menu.php'; } ?>
</body>
</html>
