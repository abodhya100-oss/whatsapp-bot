<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

$inv_id = intval($_GET['id'] ?? 0);
if(session_status()===PHP_SESSION_NONE){session_start();} if(!isset($_SESSION["user_role"])){header("Location: login.php");exit;}
$db = new SQLite3(__DIR__ . '/vmax.db');

$inv_stmt = $db->prepare("SELECT i.*, c.name as customer_name, c.phone as customer_phone FROM invoices i LEFT JOIN customers c ON i.customer_id = c.id WHERE i.id = :id");
$inv_stmt->bindValue(':id', $inv_id, SQLITE3_INTEGER);
$invoice = $inv_stmt->execute()->fetchArray(SQLITE3_ASSOC);
$inv_stmt->close();

$items_stmt = $db->prepare("SELECT ii.*, p.name as product_name FROM invoice_items ii LEFT JOIN products p ON ii.product_id = p.id WHERE ii.invoice_id = :id");
$items_stmt->bindValue(':id', $inv_id, SQLITE3_INTEGER);
$items_res = $items_stmt->execute();
$items = [];
while($it = $items_res->fetchArray(SQLITE3_ASSOC)) { $items[] = $it; }
$items_stmt->close();
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
    <title>فاتورة رقم #<?php echo $inv_id; ?></title>
    <style>
        body { font-family: 'Tahoma', sans-serif; background: #0f2027; color: #fff; margin: 0; padding: 15px; }
        .receipt-card { max-width: 400px; margin: auto; background: #fff; color: #000; padding: 15px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.5); }
        .receipt-header { text-align: center; border-bottom: 2px dashed #000; padding-bottom: 8px; margin-bottom: 10px; }
        .receipt-header h2 { margin: 0; font-size: 16px; color: #000; }
        .receipt-header p { margin: 2px 0; font-size: 11px; color: #333; }
        .info-row { display: flex; justify-content: space-between; font-size: 11px; margin-bottom: 4px; }
        .receipt-table { width: 100%; border-collapse: collapse; margin-top: 8px; margin-bottom: 8px; font-size: 11px; }
        .receipt-table th, .receipt-table td { border-bottom: 1px solid #ddd; padding: 5px; text-align: center; }
        .receipt-table th { background: #f1f1f1; color: #000; }
        .totals-box { border-top: 1px dashed #000; padding-top: 5px; font-size: 11px; }
        .total-row { display: flex; justify-content: space-between; margin-bottom: 3px; font-weight: bold; }
        .btn-print { width: 100%; background: #ffd200; color: #000; padding: 10px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 13px; margin-top: 10px; }
        .btn-back { display: block; text-align: center; margin-top: 8px; color: #aaa; text-decoration: none; font-size: 11px; }

        /* تنسيق خاص للطابعات الحرارية عند الطباعة */
        @media print {
            body { background: #fff; padding: 0; color: #000; }
            .receipt-card { box-shadow: none; width: 100%; max-width: 100%; padding: 0; margin: 0; }
            .btn-print, .btn-back { display: none; }
        }
    </style>
</head>
<body>

<div class="receipt-card">
    <div class="receipt-header">
        <h2>👑 نظام ابوضياء العالمي المركزي</h2>
        <p>إدارة المحل التجاري والورشة - سيئون السوق العام</p>
        <hr style="border:0; border-top:1px dashed #000; margin:5px 0;">
        <b>فاتورة مبيعات رقم: #<?php echo $inv_id; ?></b>
    </div>

    <div class="info-row">
        <span><b>العميل:</b> <?php echo htmlspecialchars($invoice['customer_name'] ?? 'نقدي'); ?></span>
        <span><b>التاريخ:</b> <?php echo $invoice['created_date']; ?></span>
    </div>

    <table class="receipt-table">
        <thead>
            <tr>
                <th>الصنف</th>
                <th>كمية</th>
                <th>سعر</th>
                <th>إجمالي</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($items as $it): ?>
            <tr>
                <td style="text-align:right;"><?php echo htmlspecialchars($it['product_name']); ?></td>
                <td><?php echo $it['quantity']; ?></td>
                <td><?php echo number_format($it['unit_price'], 2); ?></td>
                <td><b><?php echo number_format($it['total'], 2); ?></b></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totals-box">
        <div class="total-row"><span>إجمالي الفاتورة:</span><span><?php echo number_format($invoice['total'], 2); ?> ر.ي</span></div>
        <div class="total-row"><span>المبلغ المدفوع (كاش):</span><span><?php echo number_format($invoice['paid_amount'], 2); ?> ر.ي</span></div>
        <div class="total-row" style="color:red;"><span>المتبقي (دين):</span><span><?php echo number_format($invoice['remaining_amount'], 2); ?> ر.ي</span></div>
    </div>

    <div style="text-align:center; font-size:10px; margin-top:10px; border-top:1px dashed #000; padding-top:5px;">
        شكراً لتعاملكم مع نظام ابوضياء العالمي 🌟
    </div>

    <button onclick="window.print()" class="btn-print">🖨️ طباعة الفاتورة (طابعة حرارية / بلوتوث)</button>
    <a href="invoices.php" class="btn-back">⬅️ العودة للكاشير وسلة المشتريات</a>
</div>


<?php include 'nav_bar.php'; ?>
<?php include "nav_menu.php"; ?>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>
</html>

