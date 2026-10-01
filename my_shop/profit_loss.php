<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$db = new SQLite3('vmax.db', SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
$db->busyTimeout(5000);

$settings = [];
$s_res = @$db->query("SELECT key, value FROM settings");
if ($s_res) { while ($s_row = $s_res->fetchArray(SQLITE3_ASSOC)) { $settings[$s_row['key']] = $s_row['value']; } }

$store_name = $settings['store_name'] ?? 'محل هوت فون';
$store_tagline = $settings['store_tagline'] ?? 'جوالات إكسسوارات وصيانة شاملة';
$store_address = $settings['store_address'] ?? 'سيئون - السوق العام';

// حساب إجمالي المبيعات وتكلفة البضاعة المباعة
$sales_data = $db->querySingle("SELECT SUM(net_amount) as total_sales, SUM(total_cost) as total_cost FROM invoices", true);
$total_sales = $sales_data['total_sales'] ?? 0;
$total_cost = $sales_data['total_cost'] ?? 0;
$gross_profit = $total_sales - $total_cost;

// حساب إجمالي المصروفات وسندات الصرف
$exp_data = $db->querySingle("SELECT SUM(amount) as total_exp FROM expenses", true);
$total_expenses = $exp_data['total_exp'] ?? 0;

// صافي الربح النهائي
$net_profit = $gross_profit - $total_expenses;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير الأرباح والخسائر - V-MAX PRO</title>
    <style>
        body { font-family: Tahoma, sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 10px; padding-bottom: 110px; }
        .header { text-align: center; font-size: 16px; font-weight: 900; color: #38bdf8; margin-bottom: 12px; }
        .card { background: #1e293b; border: 1px solid #38bdf8; border-radius: 10px; padding: 14px; margin-bottom: 12px; }
        .row-stat { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #334155; font-size: 12px; }
        .row-stat:last-child { border-bottom: none; }
        .net-box { background: #1d4ed8; color: #fff; padding: 12px; border-radius: 8px; text-align: center; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="header">📈 تقرير الأرباح والخسائر الختامي</div>

    <div class="card">
        <div style="font-size: 13px; font-weight: 900; color: #38bdf8; margin-bottom: 8px; text-align: center;"><?php echo htmlspecialchars($store_name); ?></div>
        
        <div class="row-stat">
            <span>إجمالي المبيعات (الإيرادات):</span>
            <span style="color: #4ade80; font-weight: bold; direction: ltr;"><?php echo number_format($total_sales, 0); ?> ر.ي</span>
        </div>
        <div class="row-stat">
            <span>تكلفة البضاعة المباعة:</span>
            <span style="color: #f87171; font-weight: bold; direction: ltr;"><?php echo number_format($total_cost, 0); ?> ر.ي</span>
        </div>
        <div class="row-stat" style="font-weight: bold; background: rgba(56,189,248,0.1); padding: 8px; border-radius: 6px;">
            <span>مجمل الربح (إجمالي - تكلفة):</span>
            <span style="color: #38bdf8; direction: ltr;"><?php echo number_format($gross_profit, 0); ?> ر.ي</span>
        </div>
        <div class="row-stat">
            <span>إجمالي المصروفات والتشغيل:</span>
            <span style="color: #f87171; font-weight: bold; direction: ltr;"><?php echo number_format($total_expenses, 0); ?> ر.ي</span>
        </div>

        <div class="net-box">
            <div style="font-size: 11px; opacity: 0.9; margin-bottom: 4px;">صافي الربح الصافي النهائي:</div>
            <div style="font-size: 20px; font-weight: 900; direction: ltr; color: #fff;"><?php echo number_format($net_profit, 0); ?> ر.ي</div>
        </div>
    </div>

    <?php if(file_exists('nav_menu.php')) { include 'nav_menu.php'; } ?>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>
</html>

