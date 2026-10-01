<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_role'])) { header("Location: login.php"); exit; }
$db = new SQLite3('vmax.db');

$today = date('Y-m-d');
$today_start = $today . ' 00:00:00';
$today_end = $today . ' 23:59:59';

// حساب مبيعات اليوم النقدية
$cash_sales = $db->querySingle("SELECT SUM(net_amount) FROM invoices WHERE date BETWEEN '$today_start' AND '$today_end'") ?: 0;
// سندات القبض اليومية
$cash_in = $db->querySingle("SELECT SUM(amount) FROM vouchers WHERE type LIKE '%قبض%' AND date BETWEEN '$today_start' AND '$today_end'") ?: 0;
// المصاريف اليومية
$expenses = $db->querySingle("SELECT SUM(amount) FROM expenses WHERE date BETWEEN '$today_start' AND '$today_end'") ?: 0;
// سندات الصرف والمدفوعات اليومية
$cash_out = $db->querySingle("SELECT SUM(amount) FROM vouchers WHERE type LIKE '%صرف%' AND date BETWEEN '$today_start' AND '$today_end'") ?: 0;

// صافي النقدية المتوقع في الدرج
$expected_drawer_cash = ($cash_sales + $cash_in) - ($expenses + $cash_out);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <link rel="manifest" href="manifest.json">
    <meta name='mobile-web-app-capable' content='yes'>
    <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js");}</script>
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>إغلاق الوردية والدرج - مؤسسة أبو ضياء</title>
    <style>
        body { font-family: Tahoma, sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 10px; padding-bottom: 120px; direction: rtl; }
        .header { text-align: center; font-size: 16px; font-weight: 900; color: #38bdf8; margin-bottom: 15px; text-shadow: 0 0 10px rgba(56,189,248,0.5); }
        .card { background: linear-gradient(135deg, rgba(30,41,59,0.95), rgba(15,23,42,0.98)); border: 1.5px solid rgba(56,189,248,0.4); border-radius: 20px; padding: 18px; margin-bottom: 12px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.6); }
        .val { font-size: 24px; font-weight: 900; margin-top: 6px; font-family: Tahoma; }
        .btn-action { background: linear-gradient(135deg, #ca8a04, #eab308); color: #0f172a; border: none; padding: 14px; border-radius: 16px; font-weight: bold; width: 100%; cursor: pointer; font-size: 13px; box-shadow: 0 6px 20px rgba(234,179,8,0.4); }
    </style>
</head>
<body>
    <div class="header">🔒 تقرير إغلاق الوردية والدرج (Z-Report)</div>
    <div style="text-align: center; font-size: 11px; color: #94a3b8; margin-bottom: 15px;">تاريخ الوردية: <b><?php echo $today; ?></b></div>

    <div class="card" style="border-color: #38bdf8;">
        <div style="color:#cbd5e1; font-size:12px;">إجمالي مبيعات الفواتير اليوم:</div>
        <div class="val" style="color:#38bdf8; direction:ltr;"><?php echo number_format($cash_sales, 0); ?> ر.ي</div>
    </div>

    <div class="card" style="border-color: #16a34a;">
        <div style="color:#cbd5e1; font-size:12px;">سندات القبض النقدية اليوم:</div>
        <div class="val" style="color:#16a34a; direction:ltr;"><?php echo number_format($cash_in, 0); ?> ر.ي</div>
    </div>

    <div class="card" style="border-color: #ef4444;">
        <div style="color:#cbd5e1; font-size:12px;">المصاريف والمسحوبات اليوم:</div>
        <div class="val" style="color:#ef4444; direction:ltr;"><?php echo number_format($expenses + $cash_out, 0); ?> ر.ي</div>
    </div>

    <div class="card" style="border-color: #facc15; background: rgba(250,204,21,0.08);">
        <div style="color:#facc15; font-size:13px; font-weight:bold;">💎 صافي النقدية المفترض وجودها في الدرج:</div>
        <div class="val" style="color:#facc15; direction:ltr;"><?php echo number_format($expected_drawer_cash, 0); ?> ر.ي</div>
    </div>

    <div style="margin-top: 15px;">
        <button onclick="window.print()" class="btn-action">🖨️ طباعة تقرير الوردية الفوري</button>
    </div>

    <?php include "nav_menu.php"; ?>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>
</html>

