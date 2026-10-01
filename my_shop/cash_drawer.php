<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
if(session_status()===PHP_SESSION_NONE){session_start();} if(!isset($_SESSION["user_role"])){header("Location: login.php");exit;}
$db = new SQLite3(__DIR__ . '/vmax.db');

// إجمالي سندات القبض (النقدية الداخلة)
$total_receipts = $db->querySingle("SELECT SUM(amount) FROM receipts WHERE type = 'قبض عميل'") ?: 0;
// إجمالي سندات الصرف (النقدية الخارجة للموردين)
$total_payments = $db->querySingle("SELECT SUM(amount) FROM receipts WHERE type = 'صرف لمورد'") ?: 0;
// إجمالي المصروفات اليومية
$total_expenses = $db->querySingle("SELECT SUM(amount) FROM expenses") ?: 0;

$net_cash = $total_receipts - $total_payments - $total_expenses;
$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <link rel="manifest" href="manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js");}</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إغلاق الخزينة والوردية - ابوضياء العالمي</title>
    <style>
        body { font-family: 'Tahoma', sans-serif; background: #0f2027; color: #fff; margin: 0; padding: 10px; padding-bottom: 90px; }
        .container { max-width: 680px; margin: auto; }
        h1 { text-align: center; color: #ffd200; font-size: 19px; margin-bottom: 3px; }
        p.sub-title { text-align: center; color: #aaa; font-size: 11px; margin-bottom: 10px; }
        .card { background: rgba(255,255,255,0.08); padding: 14px; border-radius: 12px; margin-bottom: 10px; border: 1px solid rgba(255,255,255,0.15); text-align: center; }
        .drawer-box { background: rgba(0,0,0,0.5); border: 2px dashed #ffd200; padding: 16px; border-radius: 10px; margin-bottom: 12px; }
        .drawer-box h2 { color: #38ef7d; font-size: 24px; margin: 5px 0; }
        .row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.1); font-size: 12px; }
        .nav-bar { position: fixed; bottom: 0; left: 0; right: 0; background: #0f2027; border-top: 1px solid rgba(255,210,0,0.2); padding: 6px 0; text-align: center; z-index: 1000; }
        .nav-links { display: flex; justify-content: space-around; font-size: 12px; }
        .nav-links a { color: #aaa; text-decoration: none; font-weight: bold; text-align: center; }
        .nav-links a.active { color: #ffd200; }
    </style>
</head>
<body>
<div class="container">
    <h1>💰 إغلاق الخزينة والوردية اليومية</h1>
    <p class="sub-title">حساب النقدية الفعلية المفترض وجودها في الدرج</p>
    
    <div class="card drawer-box">
        <p style="color:#ffd200; font-size:13px; margin:0; font-weight:bold;">💎 صافي النقدية في الدرج (الخزينة):</p>
        <h2><?php echo number_format($net_cash, 2); ?> ر.ي</h2>
        <p style="color:#aaa; font-size:10px; margin:0;">(المقبوضات النقدية ناقصاً مدفوعات الموردين والمصروفات)</p>
    </div>

    <div class="card" style="text-align: right;">
        <b style="color:#ffd200; font-size:12px; display:block; margin-bottom:8px;">📊 ملخص حركة الخزينة:</b>
        <div class="row">
            <span>📥 إجمالي المقبوضات النقدية (من سندات القبض):</span>
            <span style="color:#38ef7d; font-weight:bold;"><?php echo number_format($total_receipts, 2); ?> ر.ي</span>
        </div>
        <div class="row">
            <span>📤 إجمالي مدفوعات الموردين (سندات الصرف):</span>
            <span style="color:#ff4d4d; font-weight:bold;"><?php echo number_format($total_payments, 2); ?> ر.ي</span>
        </div>
        <div class="row" style="border:none;">
            <span>💸 إجمالي المصروفات النثرية والتشغيلية:</span>
            <span style="color:#ff4d4d; font-weight:bold;"><?php echo number_format($total_expenses, 2); ?> ر.ي</span>
        </div>
    </div>

    <?php include 'signature.php'; ?>
    <a href="main.php" style="display:block; text-align:center; color:#ffd200; text-decoration:none; font-size:12px; margin-top:10px;">⬅️ العودة للرئيسية</a>
</div>

<?php include 'nav_bar.php'; ?>

<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
