<?php
require_once 'auth.php';
$db = new SQLite3('/data/data/com.termux/files/home/secure_dbs/vmax.db');

// جلب الفواتير مع أسماء العملاء
$query = "SELECT i.*, c.name as customer_name FROM invoices i LEFT JOIN customers c ON i.customer_id = c.id ORDER BY i.id DESC";
$result = $db->query($query);
$invoices = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $invoices[] = $row;
}
$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سجل الفواتير - V-MAX PRO</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Tahoma, sans-serif; background: #F1F5F9; margin: 0; padding: 0; color: #0F172A; }
        .top-bar { background: #2563EB; color: #FFF; padding: 10px 15px; display: flex; justify-content: space-between; align-items: center; font-size: 14px; font-weight: bold; }
        .top-bar a { color: #FFF; text-decoration: none; }
        .container { padding: 10px; max-width: 700px; margin: auto; }
        .card { background: #FFF; border: 1px solid #CBD5E1; border-radius: 6px; padding: 10px; margin-bottom: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .inv-header { display: flex; justify-content: space-between; font-weight: bold; font-size: 13px; margin-bottom: 6px; color: #1E3A8A; }
        .inv-details { font-size: 12px; color: #475569; margin-bottom: 8px; display: flex; justify-content: space-between; }
        .inv-actions { display: flex; gap: 6px; }
        .btn { padding: 6px 12px; border-radius: 4px; font-size: 11px; font-weight: bold; text-decoration: none; text-align: center; border: none; cursor: pointer; display: inline-block; }
        .btn-view { background: #3B82F6; color: #FFF; }
        .btn-wa { background: #10B981; color: #FFF; }
        .empty-msg { text-align: center; padding: 20px; color: #64748B; font-size: 13px; }
    </style>
</head>
<body>

<div class="top-bar">
    <a href="index.php">🔙 الرئيسية</a>
    <span>📋 سجل الفواتير الصادرة</span>
    <span>V-MAX</span>
</div>

<div class="container">
    <?php if(empty($invoices)): ?>
        <div class="card empty-msg">لا توجد فواتير مسجلة حتى الآن.</div>
    <?php else: ?>
        <?php foreach($invoices as $inv): ?>
            <div class="card">
                <div class="inv-header">
                    <span>فاتورة #<?= $inv['id'] ?></span>
                    <span style="color: #047857;"><?= number_format($inv['grand_total'], 2) ?> ر.ي</span>
                </div>
                <div class="inv-details">
                    <span>العميل: <strong><?= !empty($inv['customer_name']) ? htmlspecialchars($inv['customer_name']) : 'عميل نقدي طيار' ?></strong></span>
                    <span>التاريخ: <?= htmlspecialchars($inv['invoice_date'] ?? '---') ?></span>
                </div>
                <div class="inv-details">
                    <span>نوع الدفع: <strong><?= htmlspecialchars($inv['payment_method'] ?? 'نقدي') ?></strong></span>
                    <span>المندوب: <?= htmlspecialchars($inv['rep_name'] ?? 'أبو ضياء') ?></span>
                </div>
                <div class="inv-actions">
                    <a href="formal_invoice.php?edit_id=<?= $inv['id'] ?>" class="btn btn-view">✏️ تعديل / معاينة</a>
                    <a href="invoice_whatsapp.php?id=<?= $inv['id'] ?>" class="btn btn-wa">💬 إرسال واتساب</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>
