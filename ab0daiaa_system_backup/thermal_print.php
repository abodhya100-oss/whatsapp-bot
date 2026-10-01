<?php
require_once 'auth.php';
$db = new SQLite3(__DIR__ . '/vmax.db');

$id = intval($_GET['id'] ?? 0);
if ($id == 0) die("رقم الفاتورة غير صحيح");

$stmt = $db->prepare("SELECT i.*, c.name as customer_name, c.balance as customer_balance FROM invoices i LEFT JOIN customers c ON i.customer_id = c.id WHERE i.id = :id");
$stmt->bindValue(':id', $id, SQLITE3_INTEGER);
$inv = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
if (!$inv) die("الفاتورة غير موجودة");

$items_stmt = $db->prepare("SELECT ii.*, p.name as p_name FROM invoice_items ii LEFT JOIN products p ON ii.product_id = p.id WHERE ii.invoice_id = :id");
$items_stmt->bindValue(':id', $id, SQLITE3_INTEGER);
$items = [];
$items_res = $items_stmt->execute();
while ($r = $items_res->fetchArray(SQLITE3_ASSOC)) {
    if (empty($r['p_name']) && isset($r['custom_name'])) $r['p_name'] = $r['custom_name'];
    $items[] = $r;
}
$db->close();

$sym = ($inv['currency'] === 'ريال سعودي') ? 'ر.س' : (($inv['currency'] === 'دولار') ? '$' : 'ر.ي');
$is_return = ($inv['invoice_type'] === 'مرتجع' || $inv['invoice_type'] === 'return');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=576, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>فاتورة #<?=$id?> - Thermer</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif; 
            margin: 0; 
            padding: 0; 
            width: 576px; 
            color: #000; 
            background: #FFF; 
            font-size: 22px; 
            font-weight: 900;
        }
        .text-center { text-align: center; }
        .title { font-size: 32px; font-weight: 900; margin-bottom: 2px; }
        .subtitle { font-size: 20px; margin-bottom: 8px; }
        .divider { border-top: 2px dashed #000; margin: 10px 0; }
        .double-divider { border-top: 4px solid #000; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th { border-bottom: 3px solid #000; padding: 8px 4px; font-size: 20px; text-align: center; font-weight: 900; }
        td { border-bottom: 1px dotted #000; padding: 10px 4px; font-size: 22px; font-weight: 900; text-align: center; }
        .flex-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 22px; font-weight: 900; }
        .btn-print { display: block; width: 100%; padding: 20px; background: #2563EB; color: #FFF; text-align: center; text-decoration: none; margin-bottom: 15px; border: none; font-weight: bold; font-size: 26px; cursor: pointer; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.15); }
    </style>
</head>
<body>

<div id="action-buttons" style="padding: 15px; background: #f8fafc; margin-bottom: 15px; border-bottom: 2px solid #e2e8f0; width: 576px;">
    <button class="btn-print" onclick="printWithThermer()">🖨️ طباعة عبر تطبيق Thermer</button>
</div>

<div class="text-center title">أبو ضياء للتوزيع</div>
<div class="text-center subtitle">توزيع شامل - حضرموت</div>

<div class="divider"></div>
<div class="text-center" style="font-size: 26px; margin: 8px 0;">
    <?= $is_return ? 'فاتورة مرتجع' : 'فاتورة مبيعات' ?> #<?= sprintf("%04d", $id) ?>
</div>
<?php if($inv['is_quotation'] == 1): ?>
<div class="text-center" style="font-size: 20px;">(عرض سعر - مسودة)</div>
<?php endif; ?>
<div class="divider"></div>

<div class="flex-row">
    <span>التاريخ:</span>
    <span><?= htmlspecialchars($inv['invoice_date']) ?></span>
</div>
<div class="flex-row">
    <span>العميل:</span>
    <span><?= $inv['customer_id'] > 0 ? htmlspecialchars($inv['customer_name']) : 'عميل نقدي' ?></span>
</div>
<div class="flex-row">
    <span>الدفع:</span>
    <span><?= htmlspecialchars($inv['payment_method']) ?></span>
</div>
<?php if(!empty($inv['rep_name'])): ?>
<div class="flex-row">
    <span>المندوب:</span>
    <span><?= htmlspecialchars($inv['rep_name']) ?></span>
</div>
<?php endif; ?>

<div class="double-divider"></div>

<table>
    <thead>
        <tr>
            <th style="text-align: right; width: 50%;">الصنف</th>
            <th style="width: 15%;">كمية</th>
            <th style="width: 15%;">سعر</th>
            <th style="width: 20%;">إجمالي</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($items as $item): 
            $total = $item['qty'] * $item['price'];
        ?>
        <tr>
            <td style="text-align: right;"><?= htmlspecialchars($item['p_name']) ?></td>
            <td><?= $item['qty'] ?></td>
            <td><?= $item['price'] ?></td>
            <td><?= $total ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div class="double-divider"></div>

<div class="flex-row" style="font-size: 24px;">
    <span>الإجمالي:</span>
    <span><?= number_format($inv['grand_total'], 2) ?></span>
</div>
<?php if($inv['discount'] > 0): ?>
<div class="flex-row">
    <span>الخصم:</span>
    <span><?= number_format($inv['discount'], 2) ?></span>
</div>
<?php endif; ?>
<div class="flex-row">
    <span><?= $is_return ? 'المُسلَّم:' : 'المدفوع:' ?></span>
    <span><?= number_format($inv['paid_amount'], 2) ?></span>
</div>
<div class="flex-row" style="font-size: 24px; margin-top: 10px;">
    <span><?= $is_return ? 'مطلوب إرجاعه:' : 'المتبقي:' ?></span>
    <span><?= number_format(max(0, $inv['grand_total'] - $inv['paid_amount']), 2) ?></span>
</div>

<div class="divider"></div>

<?php if($inv['customer_id'] > 0 && !empty($inv['customer_balance'])): ?>
<div class="flex-row text-center" style="justify-content: center; font-size: 22px; margin-top: 10px;">
    المديونية: <?= number_format($inv['customer_balance'], 2) ?> <?= $sym ?>
</div>
<div class="divider"></div>
<?php endif; ?>

<div class="text-center" style="font-size: 18px; margin-top: 12px;">شكرًا لتعاملكم معنا</div>
<div class="text-center" style="font-size: 16px; margin-top: 6px;">V-MAX PRO - Thermer System</div>

<script>
function printWithThermer() {
    var btnBlock = document.getElementById('action-buttons');
    btnBlock.style.display = 'none';
    
    var htmlContent = "<!DOCTYPE html><html lang='ar' dir='rtl'><head><meta charset='UTF-8'><style>" + 
        document.querySelector('style').innerHTML + 
        "</style></head><body>" + document.body.innerHTML + "</body></html>";
    
    btnBlock.style.display = 'block';
    
    var encoded = btoa(unescape(encodeURIComponent(htmlContent)));
    // استخدام الحزمة المخصصة لتطبيق Thermer للإرسال الفوري
    var thermerUrl = "intent://base64/" + encoded + "#Intent;scheme=thermer;package=mate.bluetoothPrinterble;action=mate.bluetoothPrinterble.action.PRINT_HTML;end;";
    
    window.location.href = thermerUrl;
}
</script>
</body>
</html>
