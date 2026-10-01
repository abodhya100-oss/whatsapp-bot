<?php
require_once 'auth.php';
$db = new SQLite3('/data/data/com.termux/files/home/secure_dbs/vmax.db');

$today = date('Y-m-d');
$stmt = $db->prepare("SELECT 
    COUNT(*) as total_invoices,
    SUM(grand_total) as total_sales,
    SUM(paid_amount) as total_cash,
    SUM(CASE WHEN payment_method = 'آجل' THEN (grand_total - paid_amount) ELSE 0 END) as total_credit
FROM invoices WHERE invoice_date = :today AND is_quotation = 0");
$stmt->bindValue(':today', $today, SQLITE3_TEXT);
$res = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

$total_invoices = $res['total_invoices'] ?? 0;
$total_sales = $res['total_sales'] ?? 0;
$total_cash = $res['total_cash'] ?? 0;
$total_credit = $res['total_credit'] ?? 0;

$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير مبيعات اليوم - V-MAX PRO</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Tahoma, sans-serif; background: #F1F5F9; margin: 0; padding: 0; color: #0F172A; }
        .top-bar { background: #2563EB; color: #FFF; padding: 10px 15px; display: flex; justify-content: space-between; align-items: center; font-size: 14px; font-weight: bold; }
        .top-bar a { color: #FFF; text-decoration: none; }
        .container { padding: 10px; max-width: 600px; margin: auto; }
        .card { background: #FFF; border: 1px solid #CBD5E1; border-radius: 6px; padding: 12px; margin-bottom: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .report-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: bold; }
        .report-row:last-child { border-bottom: none; }
        .val-primary { color: #2563EB; }
        .val-success { color: #10B981; }
        .val-danger { color: #EF4444; }
    </style>
</head>
<body>

<div class="top-bar">
    <a href="index.php">🔙 الرئيسية</a>
    <span>📊 تقرير مبيعات اليوم (<?= $today ?>)</span>
    <span>V-MAX</span>
</div>

<div class="container">
    <div class="card">
        <div class="report-row">
            <span>عدد الفواتير المصدرة اليوم:</span>
            <span class="val-primary"><?= $total_invoices ?> فاتورة</span>
        </div>
        <div class="report-row">
            <span>إجمالي المبيعات الكلي:</span>
            <span class="val-success"><?= number_format($total_sales, 2) ?> ر.ي</span>
        </div>
        <div class="report-row">
            <span>المبالغ النقدية المحصلة:</span>
            <span class="val-success"><?= number_format($total_cash, 2) ?> ر.ي</span>
        </div>
        <div class="report-row">
            <span>المبيعات الآجلة (ديون جديدة):</span>
            <span class="val-danger"><?= number_format($total_credit, 2) ?> ر.ي</span>
        </div>
    </div>
</div>

</body>
</html>
