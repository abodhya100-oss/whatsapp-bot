<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_role'])) { header("Location: login.php"); exit; }
$db = new SQLite3('vmax.db');

// حساب إجمالي المبيعات والأرباح وتكلفة البضاعة المباعة
$sales_info = $db->querySingle("SELECT SUM(net_amount) as total_sales, SUM(total_cost) as total_cost FROM invoices", true);
$total_sales = $sales_info['total_sales'] ?? 0;
$total_cost = $sales_info['total_cost'] ?? 0;
$gross_profit = $total_sales - $total_cost;

// حساب إجمالي المصروفات
$exp_info = $db->querySingle("SELECT SUM(amount) as total_exp FROM expenses", true);
$total_exp = $exp_info['total_exp'] ?? 0;

// صافي الأرباح الحقيقي
$net_profit = $gross_profit - $total_exp;

// عدد الفواتير الإجمالي
$invoices_count = $db->querySingle("SELECT COUNT(*) FROM invoices");
$customers_count = $db->querySingle("SELECT COUNT(*) FROM customers");
$inventory_count = $db->querySingle("SELECT COUNT(*) FROM inventory");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
    <link rel="manifest" href="manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js");}</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التقارير المالية والأرباح - نظام الإمبراطورية</title>
    <style>
        body { font-family: Tahoma, sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 10px; padding-bottom: 90px; direction: rtl; }
        .header { text-align: center; font-size: 15px; font-weight: 900; color: #38bdf8; margin-bottom: 12px; text-shadow: 0 0 10px rgba(56,189,248,0.5); }
        .glass-panel { background: linear-gradient(135deg, rgba(30, 41, 59, 0.95), rgba(15, 23, 42, 0.98)); backdrop-filter: blur(20px); border: 1.5px solid rgba(56, 189, 248, 0.4); border-radius: 18px; padding: 15px; margin-bottom: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.6); }
        .kpi-card { background: rgba(15, 23, 42, 0.85); border: 1px solid rgba(56, 189, 248, 0.3); border-radius: 14px; padding: 14px; margin-bottom: 10px; text-align: center; }
        .kpi-title { font-size: 11px; color: #94a3b8; margin-bottom: 4px; }
        .kpi-value { font-size: 18px; font-weight: 900; direction: ltr; }
    </style>
</head>
<body>
    <div class="header">📊 التقارير المالية وتحليل الأرباح</div>

    <div class="glass-panel">
        <div style="font-size: 13px; font-weight: bold; color: #38bdf8; margin-bottom: 12px;">📈 المؤشرات المالية والإدارية:</div>
        
        <div class="kpi-card">
            <div class="kpi-title">إجمالي إيرادات المبيعات</div>
            <div class="kpi-value" style="color: #38bdf8;"><?php echo number_format($total_sales, 0); ?> ر.ي</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-title">إجمالي تكلفة البضاعة المباعة</div>
            <div class="kpi-value" style="color: #f59e0b;"><?php echo number_format($total_cost, 0); ?> ر.ي</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-title">إجمالي الأرباح الإجمالية (قبل المصاريف)</div>
            <div class="kpi-value" style="color: #4ade80;"><?php echo number_format($gross_profit, 0); ?> ر.ي</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-title">إجمالي المصروفات والنثريات</div>
            <div class="kpi-value" style="color: #ef4444;"><?php echo number_format($total_exp, 0); ?> ر.ي</div>
        </div>

        <div class="kpi-card" style="border: 2px solid #22c55e; background: rgba(34,197,94,0.1);">
            <div class="kpi-title" style="color: #4ade80; font-weight: bold;">صافي الربح الحقيقي 👑</div>
            <div class="kpi-value" style="color: #4ade80; font-size: 22px;"><?php echo number_format($net_profit, 0); ?> ر.ي</div>
        </div>

        <div style="display: flex; gap: 8px; margin-top: 15px;">
            <div style="flex: 1; background: rgba(56,189,248,0.1); border-radius: 10px; padding: 10px; text-align: center;">
                <div style="font-size: 10px; color: #94a3b8;">إجمالي الفواتير</div>
                <div style="font-size: 14px; font-weight: bold; color: #38bdf8;"><?php echo $invoices_count; ?></div>
            </div>
            <div style="flex: 1; background: rgba(56,189,248,0.1); border-radius: 10px; padding: 10px; text-align: center;">
                <div style="font-size: 10px; color: #94a3b8;">العملاء المسجلون</div>
                <div style="font-size: 14px; font-weight: bold; color: #38bdf8;"><?php echo $customers_count; ?></div>
            </div>
            <div style="flex: 1; background: rgba(56,189,248,0.1); border-radius: 10px; padding: 10px; text-align: center;">
                <div style="font-size: 10px; color: #94a3b8;">أصناف المخزون</div>
                <div style="font-size: 14px; font-weight: bold; color: #38bdf8;"><?php echo $inventory_count; ?></div>
            </div>
        </div>

        <div style="text-align: center; font-size: 9px; color: #64748b; border-top: 1px solid rgba(56,189,248,0.2); padding-top: 10px; margin-top: 15px;">
        </div>
    </div>

    <?php include "nav_menu.php"; ?>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>
</html>

