<?php
// customer_ledger.php - كشف حساب العميل مع مشاركة الكشف والـ PDF مباشرة عبر رقم العميل في الواتساب
require_once 'auth.php';
$db_file = '/data/data/com.termux/files/home/secure_dbs/vmax.db';
$db = new SQLite3($db_file);
$customer_id = intval($_GET['id'] ?? 0);

$cust = @$db->querySingle("SELECT * FROM customers WHERE id = $customer_id", true);
if (empty($cust)) { die("العميل غير موجود!"); }

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

$transactions = [];

// 1. الفواتير الآجلة (مدين - عليه)
$inv_res = @$db->query("SELECT id, total, created_date as t_date FROM invoices WHERE customer_id = $customer_id AND payment_type = 'credit'");
if ($inv_res) {
    while ($row = @$inv_res->fetchArray(SQLITE3_ASSOC)) {
        if ($row) {
            $transactions[] = [
                'date' => $row['t_date'] ?? date('Y-m-d H:i:s'),
                'details' => 'فاتورة مبيعات آجل رقم #' . ($row['id'] ?? ''),
                'debit' => floatval($row['total'] ?? 0),
                'credit' => 0
            ];
        }
    }
}

// 2. سندات القبض والدفعات (دائن - له)
$rec_res = @$db->query("SELECT id, amount, note, receipt_date as t_date FROM receipts WHERE customer_id = $customer_id");
if ($rec_res) {
    while ($row = @$rec_res->fetchArray(SQLITE3_ASSOC)) {
        if ($row) {
            $transactions[] = [
                'date' => $row['t_date'] ?? date('Y-m-d H:i:s'),
                'details' => !empty($row['note']) ? $row['note'] : 'سند قبض نقدي رقم #' . ($row['id'] ?? ''),
                'debit' => 0,
                'credit' => floatval($row['amount'] ?? 0)
            ];
        }
    }
}

// ترتيب الحركات حسب التاريخ تصاعدياً
usort($transactions, function($a, $b) {
    return strtotime($a['date']) - strtotime($b['date']);
});

// حساب الرصيد الجاري
$running_balance = 0;
foreach($transactions as &$t) {
    $running_balance += ($t['debit'] - $t['credit']);
    $t['balance'] = $running_balance;
}
unset($t);

$total_debit = 0;
$total_credit = 0;
foreach($transactions as $t) {
    $total_debit += $t['debit'];
    $total_credit += $t['credit'];
}
$final_balance = $total_debit - $total_credit;

$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><meta name='mobile-web-app-capable' content='yes'>
<meta name='apple-mobile-web-app-capable' content='yes'>

    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>كشف حساب العميل: <?php echo htmlspecialchars($cust['name'] ?? ''); ?></title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Tahoma', sans-serif; background: #FFFFFF; margin: 0; padding: 10px; color: #1e293b; display: flex; justify-content: center; align-items: flex-start; min-height: 100vh; }
        .invoice-card { background: #FFFFFF; width: 100%; max-width: 520px; border-radius: 14px; padding: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); margin-bottom: 20px; border: 1px solid #cbd5e1; }
        .top-header { background: #FFFFFF; color: #fff; border-radius: 10px; padding: 12px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 4px solid #fbbf24; }
        .top-header-right { text-align: right; }
        .top-header-left { text-align: left; background: rgba(255,255,255,0.15); padding: 6px 10px; border-radius: 8px; font-size: 11px; }
        .meta-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; font-size: 11px; font-weight: bold; }
        .payment-badge { background: #FFFFFF; color: #15803d; padding: 4px 10px; border-radius: 6px; font-size: 11px; }
        .customer-box { border: 2px solid #2563eb; border-radius: 8px; padding: 10px; margin-bottom: 12px; background: #FFFFFF; font-weight: bold; font-size: 13px; color: #1e293b; text-align: right; }
        .table-responsive { width: 100%; overflow-x: auto; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; font-size: 10.5px; border-radius: 8px; overflow: hidden; }
        th { background: #FFFFFF; color: #fff; padding: 8px 4px; text-align: center; }
        td { padding: 8px 4px; text-align: center; border-bottom: 1px solid #e2e8f0; font-weight: bold; background: #FFFFFF; }
        .t-desc { text-align: right; }
        .notes-box { background: #FFFFFF; border: 1px solid #bbf7d0; border-radius: 8px; padding: 8px 10px; margin-bottom: 12px; font-size: 11px; color: #166534; text-align: right; }
        .total-banner { background: #FFFFFF; color: #fff; border-radius: 10px; padding: 12px 15px; display: flex; justify-content: space-between; align-items: center; font-size: 15px; font-weight: bold; margin-bottom: 12px; box-shadow: 0 4px 10px rgba(220,38,38,0.3); }
        .total-banner.zero { background: #FFFFFF; }
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
                <div style="font-size: 10px; font-weight: bold;">كشف حساب معتمد</div>
                <div style="font-size: 13px; font-weight: bold; color: #fbbf24; text-align: center;">ID: #<?php echo $cust['id']; ?></div>
            </div>
        </div>

        <div class="meta-row">
            <span class="payment-badge">كشف تفصيلي (مدين / دائن)</span>
            <span style="color: #64748b; font-size: 10.5px;">تاريخ الطباعة: <?php echo date('Y/m/d - H:i'); ?></span>
        </div>

        <div class="customer-box">
            👤 اسم العميل: <span style="color:#1d4ed8;"><?php echo htmlspecialchars($cust['name'] ?? ''); ?></span><br>
            <span style="font-size: 11px; color: #475569;">📞 الجوال: <?php echo htmlspecialchars($cust['phone'] ?? 'غير متوفر'); ?></span>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th class="t-desc">البيان</th>
                        <th>مدين (عليه)</th>
                        <th>دائن (له)</th>
                        <th>الرصيد</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($transactions)): ?>
                    <tr>
                        <td colspan="5" style="padding: 15px; color: #64748b;">لا توجد حركات مسجلة لهذا العميل.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach($transactions as $t): ?>
                        <tr>
                            <td style="font-size: 9px; color: #475569;"><?php echo !empty($t['date']) ? date('m/d H:i', strtotime($t['date'])) : '-'; ?></td>
                            <td class="t-desc"><?php echo htmlspecialchars($t['details'] ?? ''); ?></td>
                            <td style="color:#dc2626;"><?php echo $t['debit'] > 0 ? number_format($t['debit'], 0) : '-'; ?></td>
                            <td style="color:#16a34a;"><?php echo $t['credit'] > 0 ? number_format($t['credit'], 0) : '-'; ?></td>
                            <td style="color:#1d4ed8; font-weight:bold;"><?php echo number_format($t['balance'], 0); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="notes-box">
            <strong>🌿 ملاحظة التوزيع:</strong><br>
            <?php echo htmlspecialchars($config['notes_text']); ?>
        </div>

        <div class="total-banner <?php echo $final_balance <= 0 ? 'zero' : ''; ?>">
            <span>الرصيد المتبقي (الذمة):</span>
            <span><?php echo number_format($final_balance, 0); ?> ر.ي</span>
        </div>

        <div class="bank-box">
            <div>
                <strong>💳 الحساب المعتمد:</strong> شركة المشقاص للصرافة<br>
                <span style="font-size: 10px; color: #a16207;">باسم: محمد كرامة علي حيمد | رقم الحساب: 254176581</span>
            </div>
        </div>

        <div class="signatures">
            <div>المسؤول / المندوب: أبو ضياء</div>
            <div>ختم وتوقيع العميل</div>
        </div>

        <div style="text-align: center; font-size: 9.5px; color: #94a3b8; margin-bottom: 10px;">
            💎 نظام الإمبراطورية العالمي | تصميم وتطوير: أبو ضياء (<?php echo htmlspecialchars($config['phone']); ?>)
        </div>

        <div class="btn-grid no-print">
            <button onclick="window.print()" class="btn btn-print">🖨️ طباعة</button>
            <button onclick="window.print()" class="btn btn-pdf">📄 حفظ PDF</button>
            <button onclick="sharePDFWhatsApp()" class="btn btn-share">💬 مشاركة كشف الحساب/PDF عبر الواتساب برقم العميل</button>
        </div>
    </div>

<script>
async function sharePDFWhatsApp() {
    let custName = "<?php echo htmlspecialchars($cust['name'] ?? '', ENT_QUOTES); ?>";
    let rawPhone = "<?php echo htmlspecialchars($cust['phone'] ?? '', ENT_QUOTES); ?>";
    let balance = "<?php echo number_format($final_balance, 0); ?>";
    let storeName = "<?php echo htmlspecialchars($config['store_name'] ?? '', ENT_QUOTES); ?>";
    
    let phone = rawPhone.replace(/\D/g, '');
    if (phone.length === 9 && phone.startsWith('7')) {
        phone = '967' + phone;
    }
    
    let text = `📋 *كشف حساب تفصيلي (PDF/رصيد)* - ${storeName}\n` +
               `👤 العميل: ${custName}\n` +
               `💰 إجمالي الرصيد المتبقي (الذمة): *${balance} ر.ي*\n` +
               `---------------------------\n` +
               `يرجى التكرم بسداد المبلغ المتبقي في أقرب وقت. وشكراً لتعاملكم معنا! 🌿\n` +
               `رابط الكشف: ${window.location.href}`;

    // استخدام خاصية المشاركة الأصلية للمتصفح (لتضمين الملفات أو الـ PDF إن أمكن)
    if (navigator.share) {
        try {
            await navigator.share({
                title: 'كشف حساب ' + custName,
                text: text,
                url: window.location.href
            });
            return;
        } catch (err) {
            // الاستمرار للفتح المباشر عبر الواتساب في حال إلغاء المشاركة
        }
    }

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
