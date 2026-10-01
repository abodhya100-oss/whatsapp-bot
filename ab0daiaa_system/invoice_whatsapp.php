<?php
// invoice_whatsapp.php - V-MAX PRO (Professional Layout matching user reference)
require_once 'auth.php';
$db_file = '/data/data/com.termux/files/home/secure_dbs/vmax.db';
$db = new SQLite3($db_file);

$invoice_id = intval($_GET['id'] ?? 0);
$auto_wa = trim($_GET['auto_wa'] ?? '');

if ($invoice_id <= 0) { die("رقم الفاتورة غير صالح"); }

$inv_stmt =$db->prepare("SELECT i.*, c.name as customer_name, c.phone as customer_phone FROM invoices i LEFT JOIN customers c ON i.customer_id = c.id WHERE i.id = :id");
$inv_stmt->bindValue(':id',$invoice_id, SQLITE3_INTEGER);
$invoice =$inv_stmt->execute()->fetchArray(SQLITE3_ASSOC);
if (!$invoice) { die("الفاتورة غير موجودة"); }

$invoice_date = $invoice['invoice_date'] ?? ($invoice['created_at'] ?? date('Y-m-d'));
$customer_name = !empty($invoice['customer_name']) ?$invoice['customer_name'] : 'عميل نقدي عام';
$customer_phone = !empty($auto_wa) ?$auto_wa : (!empty($invoice['customer_phone']) ? preg_replace('/[^0-9]/', '', $invoice['customer_phone']) : '');
$payment_type =$invoice['payment_method'] ?? 'نقدي';
$rep_name =$invoice['rep_name'] ?? 'أبو ضياء';

// جلب أصناف الفاتورة
$items_stmt =$db->prepare("SELECT ii.qty, ii.price, COALESCE(p.name, 'صنف عام') as product_name FROM invoice_items ii LEFT JOIN products p ON ii.product_id = p.id WHERE ii.invoice_id = :id");
$items_stmt->bindValue(':id',$invoice_id, SQLITE3_INTEGER);
$items_res =$items_stmt->execute();
$items = [];$subtotal_sum = 0;
while ($row =$items_res->fetchArray(SQLITE3_ASSOC)) {
    $items[] =$row;
    $subtotal_sum += ($row['qty'] *$row['price']);
}
$db->close();

$grand_total = floatval($invoice['grand_total']);$paid_amount = floatval($invoice['paid_amount'] ?? 0);$remaining = max(0, $grand_total -$paid_amount);

$wa_lines = "🧾 *مؤسسة أبو ضياء للتوزيع*\n" .
            "بفك رنجو | بطاطس طبيعي | عصير أكواب | غوار\n" .
            "سيئون - السوق العام | جوال: 771458609\n" .
            "━━━━━━━━━━━━━━━\n" .
            "📄 *فاتورة مبيعات رقم:* #" . str_pad($invoice['id'], 4, '0', STR_PAD_LEFT) . "\n" .
            "👤 *العميل:* " . $customer_name . "\n" .
            "📅 *التاريخ:* " . $invoice_date . " \vert{} *النوع:* " . $payment_type . "\n" .
            "━━━━━━━━━━━━━━━\n" .
            "*الأصناف المباعة:*";
foreach ($items as $index =>$item) {
    $num =$index + 1;
    $p_total = $item['qty'] *$item['price'];
    $wa_lines .= "\n{$num}. " . $item['product_name'] . " (كارتون) \vert{} الكمية: {$item['qty']} | السعر: {$item['price']} \vert{} المجموع: {$p_total}";
}
$wa_lines .= "\n━━━━━━━━━━━━━━━\n" .
             "💎 *الإجمالي النهائي:* *" . number_format($grand_total, 2) . " ر.ي*\n" .
             "💵 *المدفوع:* " . number_format($paid_amount, 2) . " ر.ي \vert{} *المتبقي:* " . number_format($remaining, 2) . " ر.ي\n" .
             "━━━━━━━━━━━━━━━\n" .
             "📌 *ملاحظة:* نتشرف بخدمة كافة المحلات وتوريد أفضل أنواع البفك والبطاطس والعصائر الطازجة يومياً.\n" .
             "🙏 شكراً لتعاملكم معنا 🌿";
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>فاتورة #<?php echo $invoice['id']; ?> - V-MAX PRO</title>
    <style>
        * { box-sizing: border-box; font-family: 'Tahoma', sans-serif; }
        body { background: #E2E8F0; margin: 0; padding: 10px; color: #0F172A; }
        .invoice-card { width: 100%; max-width: 480px; background: #FFFFFF; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin: 0 auto; overflow: hidden; border: 1px solid #CBD5E1; }
        
        /* ترويسة الفاتورة الزرقاء تماماً مثل الصورة */
        .inv-top-banner { background: #1E40AF; color: #FFFFFF; padding: 15px; text-align: center; position: relative; }
        .inv-top-banner h2 { margin: 0 0 4px 0; font-size: 22px; font-weight: bold; }
        .inv-top-banner p { margin: 3px 0; font-size: 12px; color: #DBEAFE; }
        .inv-badge-no { position: absolute; top: 12px; left: 12px; background: rgba(255,255,255,0.15); padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }

        /* معلومات التاريخ ونوع البيع */
        .info-bar { display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; background: #F8FAFC; border-bottom: 1px solid #E2E8F0; font-size: 12px; }
        .type-badge { background: #DCFCE7; color: #166534; padding: 3px 8px; border-radius: 4px; font-weight: bold; font-size: 11px; }

        /* صندوق العميل */
        .customer-section { padding: 10px 12px; }
        .customer-box { border: 2px solid #2563EB; border-radius: 6px; padding: 8px 12px; font-size: 14px; font-weight: bold; color: #1E3A8A; background: #EFF6FF; display: flex; justify-content: space-between; align-items: center; }

        /* جدول الأصناف */
        .table-container { padding: 0 12px 10px 12px; overflow-x: auto; }
        .invoice-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .invoice-table th { background: #1E293B; color: #FFFFFF; padding: 8px; text-align: center; font-weight: bold; }
        .invoice-table td { padding: 8px; border-bottom: 1px solid #E2E8F0; text-align: center; color: #334155; font-weight: bold; }
        .unit-badge { background: #EFF6FF; color: #1D4ED8; padding: 2px 6px; border-radius: 4px; font-size: 11px; }

        /* صندوق الملاحظات */
        .notes-section { padding: 0 12px 10px 12px; }
        .notes-box { background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 6px; padding: 8px 10px; font-size: 11px; color: #166534; line-height: 1.5; }

        /* شريط الإجمالي النهائي الأزرق */
        .grand-total-bar { background: #2563EB; color: #FFFFFF; padding: 12px; text-align: center; font-size: 18px; font-weight: bold; display: flex; justify-content: space-between; align-items: center; }

        /* صندوق الحساب البنكي والصرافة */
        .bank-section { padding: 10px 12px; background: #FEFCE8; border-top: 1px solid #FEF08A; font-size: 11px; color: #854D0E; text-align: center; font-weight: bold; }

        /* التوقيع والمذيل */
        .footer-section { padding: 10px 12px; display: flex; justify-content: space-between; align-items: center; font-size: 11px; color: #64748B; border-top: 1px solid #E2E8F0; background: #FAFAFA; }
        .brand-sub { text-align: center; padding: 6px; font-size: 10px; color: #94A3B8; background: #F1F5F9; border-top: 1px solid #E2E8F0; }

        /* أزرار التحكم */
        .actions-panel { background: #FFFFFF; border-radius: 8px; padding: 12px; margin: 12px auto; max-width: 480px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .actions-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 8px; }
        .btn { padding: 10px 4px; border-radius: 6px; font-weight: bold; font-size: 12px; border: none; cursor: pointer; text-align: center; text-decoration: none; color: #FFFFFF; }
        .btn-edit { background: #3B82F6; }
        .btn-new-sale { background: #10B981; }
        .btn-whatsapp { background: #25D366; grid-column: span 3; font-size: 14px; padding: 12px; }
        .btn-thermer { grid-column: span 3; background: #0284C7; padding: 14px; font-size: 15px; font-weight: bold; color: #FFF; }
    </style>
</head>
<body>

<div class="invoice-card" id="invoiceCard">
    <div class="inv-top-banner">
        <div class="inv-badge-no">رقم #<?php echo str_pad($invoice['id'], 4, '0', STR_PAD_LEFT); ?></div>
        <h2>أبو ضياء للتوزيع</h2>
        <p>بفك رنجو | بطاطس طبيعي | عصير أكواب | غوار</p>
        <p>سيئون - السوق العام | جوال: 771458609</p>
    </div>

    <div class="info-bar">
        <span>التاريخ والوقت: <strong><?php echo $invoice_date; ?></strong></span>
        <span class="type-badge">بيع <?php echo $payment_type; ?></span>
    </div>

    <div class="customer-section">
        <div class="customer-box">
            <span>فاتورة السيد: <strong><?php echo htmlspecialchars($customer_name); ?></strong></span>
            <?php if(!empty($invoice['customer_phone'])): ?>
                <span style="font-size: 12px; color: #64748B;"><?php echo $invoice['customer_phone']; ?></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-container">
        <table class="invoice-table">
            <thead>
                <tr>
                    <th style="width: 35%;">وصف الصنف</th>
                    <th style="width: 15%;">الوحدة</th>
                    <th style="width: 15%;">الكمية</th>
                    <th style="width: 15%;">السعر</th>
                    <th style="width: 20%;">المجموع</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($items)): ?>
                <tr><td colspan="5" style="padding: 15px; color: #64748B;">لا توجد أصناف مسجلة</td></tr>
                <?php else: ?>
                    <?php foreach($items as$item): ?>
                    <tr>
                        <td style="text-align: right;"><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td><span class="unit-badge">كارتون</span></td>
                        <td><?php echo $item['qty']; ?></td>
                        <td><?php echo number_format($item['price'], 2); ?></td>
                        <td style="color: #1E3A8A; font-weight: bold;"><?php echo number_format($item['qty'] *$item['price'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="notes-section">
        <div class="notes-box">
            🌿 <strong>ملاحظة التوزيع:</strong><br>
            نتشرف بخدمة كافة المحلات وتوريد أفضل أنواع البفك والبطاطس والعصائر الطازجة يومياً.
        </div>
    </div>

    <div class="grand-total-bar">
        <span>الإجمالي: النهائي</span>
        <span><?php echo number_format($grand_total, 2); ?> ر.ي</span>
    </div>

    <div class="bank-section">
        💳 الحساب: المعتمد شركة المشقاص للصرافة<br>
        باسم: محمد كرامة علي حيمد | رقم الحساب: 254176581
    </div>

    <div class="footer-section">
        <span>المسؤول: المندوب <?php echo htmlspecialchars($rep_name); ?></span>
        <span>ختم وتوقيع الموزع: ....................</span>
    </div>

    <div class="brand-sub">
        💎 نظام الإمبراطورية العالمي | تصميم وتطوير أبو ضياء (771458609)
    </div>
</div>

<div class="actions-panel">
    <div class="actions-row">
        <button id="thermerBtn" onclick="window.print()" class="btn btn-thermer">🖨️ طباعة الفاتورة الفورية</button>
    </div>
    <div class="actions-row">
        <a href="formal_invoice.php?edit_id=<?php echo $invoice['id']; ?>" class="btn btn-edit">✏ تعديل الفاتورة</a>
        <button onclick="window.location.href='formal_invoice.php'" class="btn btn-new-sale">⚡ بيع جديد</button>
        <a href="invoices_list.php" class="btn" style="background: #64748B; text-decoration: none;">📊 سجل الفواتير</a>
    </div>
    <div class="actions-row">
        <button onclick="sendWhatsApp()" class="btn btn-whatsapp">💬 إرسال عبر واتساب</button>
    </div>
</div>

<script>
function sendWhatsApp() {
    let phone = "<?php echo $customer_phone; ?>";
    let text = `<?php echo addslashes($wa_lines); ?>`;
    let url = "";
    if (phone) {
        url = "https://api.whatsapp.com/send?phone=" + phone + "&text=" + encodeURIComponent(text);
    } else {
        url = "https://api.whatsapp.com/send?text=" + encodeURIComponent(text);
    }
    window.open(url, '_blank');
}
</script>
</body>
</html>
