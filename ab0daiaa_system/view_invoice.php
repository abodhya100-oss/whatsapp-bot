<?php
// view_invoice.php - فاتورة المبيعات (الملخص العريض الممتد بعرض الصفحة تحت الأصناف) - نظام أبوضياء
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once 'auth.php';
$db_file = '/data/data/com.termux/files/home/secure_dbs/vmax.db';
$db = new SQLite3($db_file);
$id = intval($_GET['id'] ?? 0);
$inv = @$db->querySingle("SELECT * FROM invoices WHERE id = $id", true);
if (empty($inv)) { die("الفاتورة غير موجودة!"); }

$customer_id = intval($inv['customer_id'] ?? 0);
$cust = @$db->querySingle("SELECT * FROM customers WHERE id = $customer_id", true);         
$c_name = (is_array($cust) && !empty($cust['name'])) ?$cust['name'] : 'عميل نقدي';

$items = [];
$res = @$db->query("SELECT * FROM invoice_items WHERE invoice_id = $id");
if ($res) { while($row = @$res->fetchArray(SQLITE3_ASSOC)) { if($row) $items[] =$row; } }

$prev_balance = 0;
if ($customer_id > 1) {
    $prev_res =$db->query("SELECT SUM(total) as total_debt FROM invoices WHERE customer_id = $customer_id AND payment_type = 'credit' AND id <$id");
    if($prev_res) {
        $p_row =$prev_res->fetchArray(SQLITE3_ASSOC);
        $prev_balance = floatval($p_row['total_debt'] ?? 0);
    }
}

$config = [                                   
    'store_name' => 'مؤسسة أبو ضياء للتوزيع', 
    'store_desc' => 'التوزيع والتوكيل التجاري العام<br>سيئون - السوق العام 771458609<br>للتواصل والطلبات المباشرة',
    'phone' => '771458609',
    'address' => 'سيئون - السوق العام',
    'notes_text' => '- البضاعة المباعة لا ترد ولا تستبدل إلا بوجود عيب مصنعي.<br>- يرجى الاحتفاظ بالفاتورة للمراجعة والضمان.<br>- نسعد دائماً بخدمتكم في مؤسسة أبو ضياء.'
];
$set_res = @$db->query("SELECT * FROM settings");
if ($set_res) {
    while($row = @$set_res->fetchArray(SQLITE3_ASSOC)) {
        if (is_array($row) && isset($row['setting_key']) && !empty($row['setting_key'])) {$config[$row['setting_key']] =$row['setting_value'] ?? '';
        }
    }
}
$db->close();

$payment_type =$inv['payment_type'] ?? 'cash';
$payment_label = ($payment_type === 'cash') ? 'نقدي' : 'آجل';
$payment_bg = ($payment_type === 'cash') ? '#10b981' : '#ef4444';

$currency = $inv['currency'] ?? 'YER';$exchange_rate = floatval($inv['exchange_rate'] ?? 1.0);$total_amount = floatval($inv['total'] ?? 0);$total_yemeni = ($currency !== 'YER') ? ($total_amount * $exchange_rate) :$total_amount;

$current_invoice_debt = ($payment_type === 'credit') ?$total_amount : 0;
$total_account_balance = $prev_balance +$current_invoice_debt;

$paid_amount = ($payment_type === 'cash') ? $total_amount : floatval($inv['paid_amount'] ?? 0);
$due_amount = ($payment_type === 'credit') ? ($total_amount -$paid_amount) : 0;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'>
    <meta charset="UTF-8">
    <title>فاتورة مبيعات #<?php echo sprintf('%03d', $id); ?></title>
    <!-- مكتبة التقاط الصور html2canvas -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Tahoma', sans-serif; background: #FFFFFF; margin: 0; padding: 10px; color: #1e293b; display: flex; justify-content: center; align-items: flex-start; min-height: 100vh; }
        .invoice-card { background: #FFFFFF; width: 100%; max-width: 520px; border-radius: 12px; padding: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); margin-bottom: 20px; border: 1px solid #cbd5e1; }

        /* ترويسة الفاتورة */
        .top-header { background: #FFFFFF; color: #fff; border-radius: 8px; padding: 12px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; border-bottom: 3px solid #fbbf24; }
        .top-header-right { text-align: right; font-size: 11px; line-height: 1.6; opacity: 0.95; }
        .store-title { font-size: 16px; font-weight: bold; color: #fbbf24; margin-bottom: 2px; }

        .invoice-badge-box { background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); padding: 8px 12px; border-radius: 8px; text-align: left; font-size: 10.5px; }
        .badge-pill { background: <?php echo $payment_bg; ?>; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; display: inline-block; }

        /* صندوق العميل */
        .customer-box { border: 1.5px solid #3b82f6; border-radius: 8px; padding: 10px; margin-bottom: 10px; background: #FFFFFF; font-weight: bold; font-size: 13px; color: #1e293b; text-align: right; }

        /* جدول الأصناف */
        .table-responsive { width: 100%; overflow-x: auto; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; font-size: 11.5px; border-radius: 8px; overflow: hidden; }
        th { background: #FFFFFF; color: #fff; padding: 8px 6px; text-align: center; font-size: 11px; }
        td { padding: 8px 6px; text-align: center; border-bottom: 1px solid #e2e8f0; font-weight: bold; background: #FFFFFF; }
        .t-name { text-align: right; }

        /* صندوق الملخص المالي العريض الممتد بعرض الفاتورة بالكامل تماماً تحت جدول الأصناف */
        .totals-full-box { width: 100%; background: #FFFFFF; border: 1.5px solid #cbd5e1; border-radius: 8px; padding: 10px; margin-bottom: 8px; display: block; }
        .t-row { width: 100%; display: flex; justify-content: space-between; align-items: center; font-size: 11.5px; font-weight: bold; padding: 6px 10px; border-radius: 6px; border: 1px solid #e2e8f0; background: #FFFFFF; margin-bottom: 5px; }
        .t-row:last-child { margin-bottom: 0; }
        .t-row.net { background: #FFFFFF; border-color: #93c5fd; color: #1e40af; font-size: 13px; }
        .t-row.paid { background: #FFFFFF; border-color: #86efac; color: #15803d; font-size: 12px; }
        .t-row.due { background: #FFFFFF; border-color: #fca5a5; color: #b91c1c; font-size: 13px; }

        /* صندوق الملاحظات بعرض الفاتورة */
        .notes-box { width: 100%; background: #FFFFFF; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px; font-size: 10.5px; color: #475569; text-align: right; line-height: 1.6; margin-bottom: 10px; }
        .notes-box strong { color: #1e293b; display: block; margin-bottom: 4px; font-size: 11.5px; }

        /* التواقيع الرسمية */
        .signatures-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 4px; border-top: 1px solid #cbd5e1; border-bottom: 1px solid #cbd5e1; padding: 8px 0; margin-bottom: 10px; text-align: center; font-size: 10.5px; font-weight: bold; color: #1e293b; background: #FFFFFF; border-radius: 8px; }
        .sig-col { border-left: 1px dashed #cbd5e1; padding: 0 4px; }
        .sig-col:last-child { border-left: none; }

        /* أزرار التحكم السفلية */
        .action-buttons-container { display: flex; flex-direction: column; gap: 6px; margin-top: 15px; }
        .btn-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 6px; }
        .btn { padding: 10px 6px; border: none; border-radius: 8px; font-weight: bold; font-size: 11px; cursor: pointer; text-decoration: none; color: #fff; text-align: center; display: inline-flex; align-items: center; justify-content: center; gap: 4px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .btn-hd { background: #FFFFFF; }
        .btn-pdf { background: #FFFFFF; }
        .btn-edit { background: #FFFFFF; }
        .btn-wa { background: #FFFFFF; width: 100%; padding: 12px; font-size: 13px; }
        .btn-thermer { background: #FFFFFF; width: 100%; padding: 14px; font-size: 14px; font-weight: bold; border-radius: 8px; }

        @media print {
            body { background: #FFFFFF; padding: 0; }
            .invoice-card { box-shadow: none; padding: 0; max-width: 100%; border: none; border-radius: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div style="display: flex; gap: 5px;">
    </div>
    <div style="display: flex; gap: 4px; align-items: center;">
        <span style="color: #94a3b8; font-size: 10px; font-weight: bold;">تكبير الشاشة:</span>
    </div>
</div>
</div>
    <div class="invoice-card" id="invoiceCaptureArea">
        <!-- الترويسة -->
        <div class="top-header">
            <div class="top-header-right">
                <div class="store-title"><?php echo htmlspecialchars($config['store_name']); ?></div>
                <div><?php echo $config['store_desc']; ?></div>
            </div>
            <div class="invoice-badge-box">
                <div style="display: flex; justify-content: space-between; gap: 10px; margin-bottom: 3px;">
                    <span>فاتورة مبيعات</span>
                    <span class="badge-pill"><?php echo $payment_label; ?></span>
                </div>
                <div style="font-weight: bold; color: #fbbf24;">رقم الفاتورة: #<?php echo sprintf('%03d', $id); ?></div>
                <div style="opacity: 0.9;">التاريخ: <?php echo date('Y-m-d', strtotime($inv['date'] ?? ($inv['created_date'] ?? 'now'))); ?></div>
            </div>
        </div>

        <!-- بيانات العميل -->
        <div class="customer-box">
            <div style="font-size: 10.5px; color: #64748b; margin-bottom: 2px;">فاتورة لحساب العميل</div>
            <div style="font-size: 15px; color: #1d4ed8;"><?php echo htmlspecialchars($c_name); ?></div>
        </div>

        <!-- جدول الأصناف -->
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th class="t-name">وصف الصنف</th>
                        <th>الوحدة</th>
                        <th>الكمية</th>
                        <th>السعر</th>
                        <th>الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($items as$i): ?>
                    <tr>
                        <td class="t-name">
                            <?php echo htmlspecialchars($i['product_name'] ?? ''); ?>
                            <?php if(($i['free_qty'] ?? 0) > 0) echo "<br><small style='color:#d97706;'>+ مجاني: {$i['free_qty']}</small>"; ?>
                        </td>
                        <td><span style="background: #FFFFFF; padding: 2px 6px; border-radius: 4px; font-size: 10px; color: #334155;"><?php echo htmlspecialchars($i['unit'] ?? 'حبة'); ?></span></td>
                        <td><?php echo $i['qty'] ?? 0; ?></td>
                        <td><?php echo number_format($i['price'] ?? 0, 2); ?></td>
                        <td style="color:#16a34a; font-weight:bold;"><?php echo number_format($i['total'] ?? 0, 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- صندوق الحسابات والملخص العريض الممتد بعرض الفاتورة بالكامل تحت جدول الأصناف مباشرة -->
        <div class="totals-full-box">
            <div class="t-row">
                <span>إجمالي الأصناف:</span>
                <span><?php echo number_format($total_amount, 2); ?></span>
            </div>
            <div class="t-row net">
                <span>صافي الفاتورة:</span>
                <span><?php echo number_format($total_amount, 2); ?> <small>د.ي</small></span>
            </div>
            <div class="t-row paid">
                <span>المدفوع الآن:</span>
                <span><?php echo number_format($paid_amount, 2); ?></span>
            </div>
            <div class="t-row due">
                <span>المتبقي (آجل):</span>
                <span><?php echo number_format($due_amount, 2); ?> <small>د.ي</small></span>
            </div>
            <?php if($customer_id > 1): ?>
            <div class="t-row" style="background: #FFFFFF; border-color: #fbcfe8; color: #be185d; margin-top: 5px;">
                <span>إجمالي الذمة المالية الحالية:</span>
                <span><?php echo number_format($total_account_balance, 2); ?> <small>د.ي</small></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- صندوق الملاحظات الهامة -->
        <div class="notes-box">
            <strong>📍 ملاحظات هامة:</strong>
            <?php echo $config['notes_text']; ?>
        </div>

        <!-- التواقيع الرسمية -->
        <div class="signatures-grid">
            <div class="sig-col">
                <div style="color: #64748b; margin-bottom: 2px;">الصندوق / المالك</div>
                <div style="color: #94a3b8; font-size: 9.5px; margin-top: 10px;">التوقيع: ......</div>
            </div>
            <div class="sig-col">
                <div style="color: #64748b; margin-bottom: 2px;">المسؤول / البائع</div>
                <div style="color: #1e293b; font-size: 11px; margin-top: 10px; font-weight: bold;">أبو ضياء</div>
            </div>
            <div class="sig-col">
                <div style="color: #64748b; margin-bottom: 2px;">استلام العميل</div>
                <div style="color: #94a3b8; font-size: 9.5px; margin-top: 10px;">التوقيع: ......</div>
            </div>
        </div>

        <!-- تذييل الشعار -->
        <div style="text-align: center; font-size: 9.5px; color: #fff; background: #FFFFFF; padding: 6px; border-radius: 6px;">
            💎 نظام الإمبراطورية العالمي | تصميم وتطوير أبو ضياء (771458609)
        </div>
    </div>

    <!-- أزرار التحكم السفلية -->
    <div class="action-buttons-container no-print" style="max-width: 520px; margin: 0 auto;">
        <!-- زر الطباعة الحرارية المباشر عبر تطبيق Thermer لعرض 80 مم -->
        <button onclick="printWithThermer()" class="btn btn-thermer">🖨️ طباعة حرارية (Thermer - 80mm)</button>

        <div class="btn-row-3">
            <button onclick="downloadInvoiceImage()" class="btn btn-hd">🖼️ حفظ صورة HD</button>
            <button onclick="window.print()" class="btn btn-pdf">📄 مشاركة PDF</button>
            <button onclick="window.location.href='pos.php'" class="btn btn-edit">✏️ تعديل</button>
        </div>
        <button onclick="shareInvoiceWhatsApp()" class="btn btn-wa">💬 واتساب (ملف PDF)</button>
        <a href="pos.php" style="background: #FFFFFF; color:#fff; text-align:center; padding:8px; border-radius:6px; font-size:11px; text-decoration:none; font-weight:bold; margin-top:4px;">🛒 العودة للشاشة الرئيسية (الكاشير)</a>
    </div>

<script>
function printWithThermer() {
    let cardContent = document.getElementById('invoiceCaptureArea').outerHTML;
    let fullHtml = "<!DOCTYPE html><html lang='ar' dir='rtl'><head><meta charset='UTF-8'><style>" +
        "body{font-family:Tahoma,sans-serif;background: #FFFFFF;color:#000;width:576px;margin:0;padding:10px;font-size:20px;font-weight:bold;}" +
        ".invoice-card{width:100%;max-width:576px;background: #FFFFFF;border:none;box-shadow:none;padding:0;}" +
        ".top-header{background:#eee!important;color:#000!important;border-bottom:2px solid #000;}" +
        ".store-title{color:#000!important;font-size:24px;}" +
        ".invoice-badge-box{background:none!important;border:1px solid #000;color:#000;}" +
        ".badge-pill{background:#000!important;color:#fff!important;}" +
        ".customer-box{border:1px solid #000;background: #FFFFFF;color:#000;font-size:18px;}" +
        "table{width:100%;border-collapse:collapse;font-size:18px;}" +
        "th{background:#000!important;color:#fff!important;padding:6px;text-align:center;font-size:18px;}" +
        "td{padding:6px;border-bottom:1px dashed #000;text-align:center;background:#fff!important;color:#000!important;}" +
        ".t-name{text-align:right;}" +
        ".totals-full-box{background:#fff!important;border:1px solid #000;}" +
        ".t-row{background:#fff!important;color:#000!important;border:1px solid #000;font-size:18px;padding:6px;}" +
        ".t-row.net{font-size:20px;font-weight:900;}" +
        ".notes-box{border:1px solid #000;color:#000;font-size:15px;}" +
        ".signatures-grid{background:#fff!important;border:1px solid #000;color:#000;font-size:15px;}" +
        ".no-print{display:none;}" +
        "</style></head><body>" + cardContent + "</body></html>";
    <div style="display: flex; gap: 5px;">
    </div>
    <div style="display: flex; gap: 4px; align-items: center;">
        <span style="color: #94a3b8; font-size: 10px; font-weight: bold;">تكبير الشاشة:</span>
    </div>
</div>
</div>

    let encoded = btoa(unescape(encodeURIComponent(fullHtml)));
    let thermerUrl = "intent://base64/" + encoded + "#Intent;scheme=thermer;package=mate.bluetoothPrinterble;action=mate.bluetoothPrinterble.action.PRINT_HTML;end;";
    window.location.href = thermerUrl;
}

function downloadInvoiceImage() {
    let element = document.getElementById('invoiceCaptureArea');
    html2canvas(element, { scale: 2, useCORS: true }).then(canvas => {
        let link = document.createElement('a');
        link.download = 'Invoice_#<?php echo sprintf('%03d', $id); ?>.png';
        link.href = canvas.toDataURL('image/png');
        link.link = true;
        link.click();
    });
}

function shareInvoiceWhatsApp() {
    let custName = "<?php echo htmlspecialchars($c_name, ENT_QUOTES); ?>";
    let rawPhone = "<?php echo htmlspecialchars($cust['phone'] ?? '', ENT_QUOTES); ?>";
    let total = "<?php echo number_format($total_amount, 2); ?>";
    let storeName = "<?php echo htmlspecialchars($config['store_name'], ENT_QUOTES); ?>";
    let invId = "<?php echo sprintf('%03d', $id); ?>";

    let phone = rawPhone.replace(/\D/g, '');
    if (phone.length === 9 && phone.startsWith('7')) {
        phone = '967' + phone;
    }

    let text = `📄 *فاتورة مبيعات* - ${storeName}\n` +
               `🔖 رقم الفاتورة: #${invId}\n` +
               `👤 العميل: ${custName}\n` +
               `💰 صافي الفاتورة: *${total} د.ي*\n` +
               `---------------------------\n` +
               `رابط المعاينة:\n${window.location.href}\n\n` +
               `شكراً لتعاملكم معنا! 🌿`;
    let encoded = encodeURIComponent(text);

    if (phone.length >= 9) {
        window.open(`https://wa.me/${phone}?text=${encoded}`, '_blank');
    } else {
        window.open(`https://api.whatsapp.com/send?text=${encoded}`, '_blank');
    }
}
</script>
</body>
</html>
