<?php
// vault_monitor.php - الخزنة الرقمية والمراقبة - V-MAX PRO
require_once 'auth.php';
$db = new SQLite3(__DIR__ . '/vmax.db');

// 1. حساب السيولة النقدية الفعلية داخل الدرج (الخزنة)
$total_sales = @$db->querySingle("SELECT SUM(grand_total) FROM invoices") ?: 0;
$total_expenses = @$db->querySingle("SELECT SUM(amount) FROM expenses") ?: 0;
$total_receipts = @$db->querySingle("SELECT SUM(amount) FROM vouchers WHERE type='receipt'") ?: 0;
$total_payments = @$db->querySingle("SELECT SUM(amount) FROM vouchers WHERE type='payment'") ?: 0;

// الكاش المفترض وجوده في الدرج
$vault_cash = ($total_sales + $total_receipts) - ($total_expenses + $total_payments);

// 2. جلب آخر 5 عمليات تمت في النظام (فواتير كمثال للمراقبة)
$latest_actions = [];
$res_actions = $db->query("SELECT id, grand_total, date FROM invoices ORDER BY id DESC LIMIT 5");
if($res_actions) {
    while($row = $res_actions->fetchArray(SQLITE3_ASSOC)) { 
        $latest_actions[] = "تم إصدار فاتورة مبيعات بقيمة " . $row['grand_total'] . " ر.ي في " . $row['date'];
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
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>الخزنة المركزية - V-MAX PRO</title>
    <?php if(file_exists('global_style.php')) include 'global_style.php'; ?>
    <style>
        body { background: #020617; padding: 10px; }
        .ultra-wrapper { max-width: 600px; margin: 0 auto 70px auto; }
        
        /* تصميم الخزنة الرقمية */
        .vault-container {
            background: radial-gradient(circle, #0f172a 0%, #020617 100%);
            border: 2px solid #10b981;
            border-radius: 20px;
            padding: 30px 15px;
            text-align: center;
            margin-bottom: 20px;
            box-shadow: inset 0 0 30px rgba(16, 185, 129, 0.1), 0 10px 30px rgba(0,0,0,0.8);
            position: relative;
            overflow: hidden;
        }
        .vault-title {
            color: #34d399; font-size: 14px; font-weight: bold; letter-spacing: 1px;
            margin-bottom: 15px; text-shadow: 0 0 10px rgba(16,185,129,0.5);
        }
        .vault-screen {
            background: #000; border: 1px solid #10b981; border-radius: 12px; padding: 20px;
            display: inline-block; min-width: 80%; box-shadow: 0 0 20px rgba(16,185,129,0.3);
        }
        .vault-amount {
            font-family: monospace; font-size: 32px; font-weight: 900; color: #10b981;
            text-shadow: 0 0 15px #10b981; margin: 0;
        }

        /* شاشة المراقبة الأمنية (Terminal) */
        .log-container {
            background: #000; border: 1px solid #334155; border-radius: 12px; padding: 15px;
            font-family: monospace; font-size: 11px; color: #38bdf8;
            box-shadow: inset 0 0 15px rgba(0,0,0,0.9);
        }
        .log-header {
            color: #94a3b8; font-size: 12px; font-weight: bold; border-bottom: 1px dashed #334155;
            padding-bottom: 8px; margin-bottom: 10px; font-family: 'Tahoma', sans-serif;
        }
        .log-item {
            margin-bottom: 8px; padding-right: 10px; border-right: 2px solid #0ea5e9;
            background: rgba(14, 165, 233, 0.05); padding: 5px 10px;
        }
    </style>
</head>
<body>
<div class="ultra-wrapper">

    <!-- الخزنة الرقمية -->
    <div class="vault-container">
        <div class="vault-title">🔒 محتويات الدرج (الخزنة الحية) 🔒</div>
        <div class="vault-screen">
            <div class="vault-amount"><?php echo number_format($vault_cash, 2); ?></div>
            <div style="color:#64748b; font-size:12px; margin-top:8px; font-family: 'Tahoma', sans-serif;">ريال يمني (صافي الكاش)</div>
        </div>
        <p style="font-size:10px; color:#64748b; margin-top:15px; font-family: 'Tahoma', sans-serif;">
            هذا الرقم يمثل المبيعات والسندات مطروحاً منها المصروفات. يجب أن يطابق الكاش الموجود في الدرج الآن.
        </p>
    </div>

    <!-- سجل المراقبة -->
    <div class="log-container">
        <div class="log-header">📡 سجل العمليات والمراقبة الأخير</div>
        <?php if(empty($latest_actions)): ?>
            <div style="color:#64748b;">[ لا توجد عمليات مسجلة حتى الآن ]</div>
        <?php else: ?>
            <?php foreach($latest_actions as $log): ?>
                <div class="log-item">> <?php echo $log; ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>
<?php if(file_exists('nav_menu.php')) { include 'nav_menu.php'; } ?>
</body>
</html>
