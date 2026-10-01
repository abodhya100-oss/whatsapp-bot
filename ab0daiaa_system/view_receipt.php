<?php
// view_receipt.php - سند القبض مع دعم PDF ومشاركة الواتساب الرسمية
require_once 'auth.php';
$db_file = '/data/data/com.termux/files/home/secure_dbs/vmax.db';
$db = new SQLite3($db_file);
$id = intval($_GET['id'] ?? 0);

$receipt = @$db->querySingle("SELECT * FROM receipts WHERE id = $id", true);
if (empty($receipt)) { die("سند القبض غير موجود!"); }

$cust = @$db->querySingle("SELECT * FROM customers WHERE id = " . intval($receipt['customer_id'] ?? 0), true);
$c_name = (is_array($cust) && !empty($cust['name'])) ? $cust['name'] : 'عميل نقدي';

$config = [
    'store_name' => 'أبو ضياء للتوزيع',
    'store_desc' => 'بفك رنجو | بطاطس طبيعي | عصير أكواب | غوار',
    'phone' => '771458609',
    'address' => 'سيئون - السوق العام',
    'commercial_reg' => 'سجل تجاري: 7714586 | ضريبي: 99887766',
    'notes_text' => '📦 نتشرف بخدمة كافة المحلات وتوريد أفضل أنواع البفك، البطاطس الطبيعية، والعصيرات طازجة ويومياً.'
];
$set_res = @$db->query("SELECT * FROM settings");
if ($set_res) { 
    while($row = @$set_res->fetchArray(SQLITE3_ASSOC)) { 
        if (is_array($row) && isset($row['setting_key']) && !empty($row['setting_key'])) {
            $config[$row['setting_key']] = $row['setting_value'] ?? ''; 
        }
    } 
}
$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><meta name='mobile-web-app-capable' content='yes'>
<meta name='apple-mobile-web-app-capable' content='yes'>

    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>سند قبض #<?php echo $id; ?></title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Tahoma', sans-serif; background: #FFFFFF; margin: 0; padding: 10px; color: #1e293b; display: flex; justify-content: center; align-items: flex-start; min-height: 100vh; }
        .invoice-card { background: #FFFFFF; width: 100%; max-width: 500px; border-radius: 14px; padding: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); margin-bottom: 20px; border: 1px solid #cbd5e1; }
        .top-header { background: #FFFFFF; color: #fff; border-radius: 10px; padding: 12px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 4px solid #fbbf24; }
        .top-header-right { text-align: right; }
        .top-header-left { text-align: left; background: rgba(255,255,255,0.15); padding: 6px 10px; border-radius: 8px; font-size: 11px; }
        .meta-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; font-size: 11px; font-weight: bold; }
        .payment-badge { background: #FFFFFF; color: #15803d; padding: 4px 10px; border-radius: 6px; font-size: 11px; }
        .customer-box { border: 2px solid #2563eb; border-radius: 8px; padding: 10px; margin-bottom: 12px; background: #FFFFFF; font-weight: bold; font-size: 13px; color: #1e293b; text-align: right; }
        .table-responsive { width: 100%; overflow-x: auto; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; font-size: 11.5px; border-radius: 8px; overflow: hidden; }
        th { background: #FFFFFF; color: #fff; padding: 8px 4px; text-align: center; }
        td { padding: 10px 6px; text-align: center; border-bottom: 1px solid #e2e8f0; font-weight: bold; background: #FFFFFF; }
        .t-name { text-align: right; }
        .notes-box { background: #FFFFFF; border: 1px solid #bbf7d0; border-radius: 8px; padding: 8px 10px; margin-bottom: 12px; font-size: 11px; color: #166534; text-align: right; }
        .total-banner { background: #FFFFFF; color: #fff; border-radius: 10px; padding: 12px 15px; display: flex; justify-content: space-between; align-items: center; font-size: 16px; font-weight: bold; margin-bottom: 12px; box-shadow: 0 4px 10px rgba(22,163,74,0.3); }
        .bank-box { background: #FFFFFF; border: 1px solid #fde047; border-radius: 8px; padding: 10px; margin-bottom: 12px; font-size: 11px; color: #854d0e; text-align: right; }
        .signatures { display: flex; justify-content: space-between; margin-top: 15px; margin-bottom: 15px; font-size: 11px; font-weight: bold; color: #475569; border-top: 1px dashed #cbd5e1; padding-top: 10px; }
        .btn-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin-top: 10px; }
        .btn { padding: 10px 6px; border: none; border-radius: 8px; font-weight: bold; font-size: 11.5px; cursor: pointer; text-decoration: none; color: #fff; text-align: center; display: inline-flex; align-items: center; justify-content: center; gap: 4px; }
        .btn-print { background: #FFFFFF; }
        .btn-pdf { background: #FFFFFF; }
        .btn-share { background: #FFFFFF; grid-column: span 2; }
        .btn-back { background: #FFFFFF; grid-column: span 2; }
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
    <div class="invoice-card">
        <div class="top-header">
            <div class="top-header-right">
                <div style="font-size: 16px; font-weight: bold;"><?php echo htmlspecialchars($config['store_name']); ?></div>
                <div style="font-size: 10px; opacity: 0.9; margin-top: 2px;"><?php echo htmlspecialchars($config['store_desc']); ?></div>
                <div style="font-size: 9.5px; opacity: 0.8; margin-top: 2px;">جوال: <?php echo htmlspecialchars($config['phone']); ?> | <?php echo htmlspecialchars($config['address']); ?></div>
            </div>
            <div class="top-header-left">
                <div style="font-size: 10px; font-weight: bold;">سند قبض نقدي</div>
                <div style="font-size: 14px; font-weight: bold; color: #fbbf24; text-align: center;">#<?php echo $id; ?></div>
            </div>
        </div>

        <div class="meta-row">
            <span class="payment-badge">سداد دين / دفعة من الحساب</span>
            <span style="color: #64748b; font-size: 10.5px;">التاريخ: <?php echo date('Y/m/d - H:i', strtotime($receipt['receipt_date'] ?? 'now')); ?></span>
        </div>

        <div class="customer-box">
            👤 استلمنا من السيد: <span style="color:#1d4ed8;"><?php echo htmlspecialchars($c_name); ?></span><br>
            <span style="font-size: 11px; color: #475569;">📞 الجوال: <?php echo htmlspecialchars($cust['phone'] ?? 'غير متوفر'); ?></span>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th class="t-name">بيان السند</th>
                        <th>المبلغ المستلم</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="t-name"><?php echo htmlspecialchars($receipt['note'] ?? 'دفعة نقدية تسديد دين'); ?></td>
                        <td style="color:#16a34a; font-size:14px; font-weight:bold;"><?php echo number_format($receipt['amount'] ?? 0, 0); ?> ر.ي</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="notes-box">
            <strong>🌿 ملاحظة التوزيع:</strong><br>
            <?php echo htmlspecialchars($config['notes_text']); ?>
        </div>

        <div class="total-banner">
            <span>المبلغ الإجمالي المستلم:</span>
            <span><?php echo number_format($receipt['amount'] ?? 0, 0); ?> ر.ي</span>
        </div>

        <div class="bank-box">
            <div>
                <strong>💳 الحساب المعتمد:</strong> شركة المشقاص للصرافة<br>
                <span style="font-size: 10px; color: #a16207;">باسم: محمد كرامة علي حيمد | رقم الحساب: 254176581</span>
            </div>
        </div>

        <div class="signatures">
            <div>المسؤول / المندوب: أبو ضياء</div>
            <div>ختم وتوقيع المسلم</div>
        </div>

        <div style="text-align: center; font-size: 9.5px; color: #94a3b8; margin-bottom: 10px;">
            💎 نظام الإمبراطورية العالمي | تصميم وتطوير: أبو ضياء (<?php echo htmlspecialchars($config['phone']); ?>)
        </div>

        <div class="btn-grid no-print">
            <button onclick="window.print()" class="btn btn-print">🖨️ طباعة</button>
            <button onclick="window.print()" class="btn btn-pdf">📄 حفظ PDF</button>
            <button onclick="shareReceiptWhatsApp()" class="btn btn-share">💬 إرسال سند القبض / PDF عبر الواتساب</button>
        </div>
    </div>

<script>
function shareReceiptWhatsApp() {
    let custName = "<?php echo htmlspecialchars($c_name, ENT_QUOTES); ?>";
    let rawPhone = "<?php echo htmlspecialchars($cust['phone'] ?? '', ENT_QUOTES); ?>";
    let amount = "<?php echo number_format($receipt['amount'] ?? 0, 0); ?>";
    let storeName = "<?php echo htmlspecialchars($config['store_name'], ENT_QUOTES); ?>";
    let note = "<?php echo htmlspecialchars($receipt['note'] ?? 'دفعة نقدية', ENT_QUOTES); ?>";
    let receiptId = "<?php echo $id; ?>";
    
    let phone = rawPhone.replace(/\D/g, '');
    if (phone.length === 9 && phone.startsWith('7')) {
        phone = '967' + phone;
    }
    
    let text = `📜 *سند قبض نقدي (PDF)* - ${storeName}\n` +
               `🔖 رقم السند: #${receiptId}\n` +
               `👤 العميل: ${custName}\n` +
               `💰 المبلغ المستلم: *${amount} ر.ي*\n` +
               `📝 البيان: ${note}\n` +
               `---------------------------\n` +
               `رابط المعاينة والحفظ كـ PDF:\n${window.location.href}\n\n` +
               `تم استلام المبلغ بنجاح. شكراً لتعاملكم معنا! 🌿`;
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
