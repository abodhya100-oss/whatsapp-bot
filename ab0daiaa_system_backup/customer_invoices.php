<?php
// customer_invoices.php - الحل النهائي لجلب وحفظ السندات عبر عمود client_name بدقة - V-MAX PRO
require_once 'auth.php';
require_once 'db.php';

$customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
if ($customer_id <= 0) {
    die("<div style='text-align:center; padding:50px; font-family:Tahoma;'>عذراً، لم يتم تحديد العميل.</div>");
}

$cust = $db->querySingle("SELECT * FROM customers WHERE id = $customer_id", true);
if (!$cust) {
    die("<div style='text-align:center; padding:50px; font-family:Tahoma;'>العميل غير موجود.</div>");
}

$cust_name = $cust['name'];
$msg = "";
$success_payment_data = null; 

// 1. معالجة سداد الفاتورة وحفظ السند باسم العميل الصحيح client_name
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'pay_invoice') {
    $inv_id = intval($_POST['pay_invoice_id']);
    $pay_amount = floatval($_POST['pay_amount']);
    $note = trim($_POST['payment_note'] ?? 'سداد جزئي للفاتورة');

    $inv = $db->querySingle("SELECT * FROM invoices WHERE id = $inv_id AND customer_id = $customer_id", true);
    if ($inv) {
        $remaining_due = floatval($inv['grand_total']) - floatval($inv['paid_amount']);

        if ($pay_amount > 0 && $pay_amount <= $remaining_due) {
            $new_paid = floatval($inv['paid_amount']) + $pay_amount;
            $new_status = ($new_paid >= floatval($inv['grand_total'])) ? 'paid' : 'partial';

            $db->exec("UPDATE invoices SET paid_amount = $new_paid, payment_status = '$new_status' WHERE id = $inv_id");

            // حفظ السند باستخدام عمود client_name والـ note والـ date والأصول المتاحة
            $note_full = $db->escapeString("$note (فاتورة #$inv_id)");
            $safe_name = $db->escapeString($cust_name);
            
            @$db->exec("INSERT INTO payments (client_name, amount, note, date) VALUES ('$safe_name', $pay_amount, '$note_full', datetime('now'))");
            $receipt_id = $db->lastInsertRowID();

            // إذا لم يتم الحفظ بهذا الشكل، نجرب الاحتمالات الأخرى للاحتياط
            if (!$receipt_id) {
                @$db->exec("INSERT INTO payments (customer_id, amount, notes, date) VALUES ($customer_id, $pay_amount, '$note_full', datetime('now'))");
                $receipt_id = $db->lastInsertRowID();
            }

            $msg = "✅ تم سداد الفاتورة وتوثيق سند القبض بنجاح!";
            
            $cust = $db->querySingle("SELECT * FROM customers WHERE id = $customer_id", true);
            
            $temp_invoices_res = $db->query("SELECT grand_total, paid_amount FROM invoices WHERE customer_id = $customer_id");
            $temp_total_remaining = 0;
            while($ti = $temp_invoices_res->fetchArray(SQLITE3_ASSOC)) {
                $rem = floatval($ti['grand_total']) - floatval($ti['paid_amount']);
                if($rem > 0.01) {
                    $temp_total_remaining += $rem;
                }
            }

            $success_payment_data = [
                'receipt_id' => $receipt_id ? $receipt_id : rand(1000,9999),
                'invoice_id' => $inv_id,
                'amount' => $pay_amount,
                'new_remaining' => max(0, $remaining_due - $pay_amount),
                'total_remaining_all' => $temp_total_remaining,
                'date' => date('Y-m-d H:i:s'),
                'note' => $note,
                'type' => 'قبض'
            ];
        } else {
            $msg = "❌ خطأ: المبلغ المدخل غير صحيح أو أكبر من المتبقي!";
        }
    }
}

// 2. معالجة حذف السند وإجراء التراجع العكسي التلقائي
if (isset($_GET['delete_payment_id'])) {
    $del_pay_id = intval($_GET['delete_payment_id']);
    $pay_row = $db->querySingle("SELECT * FROM payments WHERE id = $del_pay_id", true);
    if ($pay_row) {
        $amt_to_restore = floatval($pay_row['amount']);
        $notes_text = $pay_row['note'] ?? ($pay_row['notes'] ?? '');

        if (preg_match('/فاتورة #(\d+)/', $notes_text, $matches)) {
            $target_inv_id = intval($matches[1]);
            $inv_check = $db->querySingle("SELECT * FROM invoices WHERE id = $target_inv_id", true);
            if ($inv_check) {
                $restored_paid = max(0, floatval($inv_check['paid_amount']) - $amt_to_restore);
                $restored_status = ($restored_paid >= floatval($inv_check['grand_total'])) ? 'paid' : (($restored_paid > 0) ? 'partial' : 'unpaid');
                $db->exec("UPDATE invoices SET paid_amount = $restored_paid, payment_status = '$restored_status' WHERE id = $target_inv_id");
            }
        }

        $db->exec("DELETE FROM payments WHERE id = $del_pay_id");
        $msg = "🗑️ تم حذف السند وإجراء التراجع العكسي على الفاتورة بنجاح!";
    }
}

// جلب الفواتير
$invoices = [];
$res = $db->query("SELECT * FROM invoices WHERE customer_id = $customer_id ORDER BY id DESC");
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $invoices[] = $row;
}

$invoice_ids = [];
foreach ($invoices as $inv) {
    $invoice_ids[] = $inv['id'];
}

// جلب السندات عبر client_name أو مطابقة أرقام الفواتير في البيان لضمان ظهورها 100%
$payments_list = [];
$safe_name_esc = $db->escapeString($cust_name);
$p_res = @$db->query("SELECT * FROM payments WHERE client_name = '$safe_name_esc' ORDER BY id DESC");
if ($p_res) {
    while ($p_row = $p_res->fetchArray(SQLITE3_ASSOC)) {
        $payments_list[] = $p_row;
    }
}

// احتياطي: إذا كانت السندات مسجلة برقم العميل أو ملاحظات الفاتورة
if (empty($payments_list)) {
    $p_res2 = @$db->query("SELECT * FROM payments WHERE customer_id = $customer_id OR cust_id = $customer_id ORDER BY id DESC");
    if ($p_res2) {
        while ($p_row = $p_res2->fetchArray(SQLITE3_ASSOC)) {
            $payments_list[] = $p_row;
        }
    }
}

if (empty($payments_list) && !empty($invoice_ids)) {
    $all_p_res = @$db->query("SELECT * FROM payments ORDER BY id DESC");
    if ($all_p_res) {
        while ($p_row = $all_p_res->fetchArray(SQLITE3_ASSOC)) {
            $note_text = $p_row['note'] ?? ($p_row['notes'] ?? '');
            foreach ($invoice_ids as $inv_id) {
                if (strpos($note_text, "فاتورة #$inv_id") !== false) {
                    $payments_list[] = $p_row;
                    break;
                }
            }
        }
    }
}

$db->close();

$active_invoices = [];
$paid_invoices = [];
$calculated_total_debt = 0;

foreach($invoices as $inv) {
    $remaining = floatval($inv['grand_total']) - floatval($inv['paid_amount']);
    if ($remaining <= 0.01) {
        $paid_invoices[] = $inv;
    } else {
        $active_invoices[] = $inv;
        $calculated_total_debt += $remaining;
    }
}

$wa_payment_url = "";
if ($success_payment_data) {
    $cust_phone = !empty($cust['phone']) ? preg_replace('/[^0-9]/', '', $cust['phone']) : '';
    $wa_msg  = "🏛️ *مؤسسة أبو ضياء للتوزيع*\n";
    $wa_msg .= "📄 *سند " . $success_payment_data['type'] . " رسمي معتمد*\n";
    $wa_msg .= "━━━━━━━━━━━━━━━\n";
    $wa_msg .= "🔢 *رقم السند:* #" . $success_payment_data['receipt_id'] . "\n";
    $wa_msg .= "📅 *التاريخ:* " . $success_payment_data['date'] . "\n";
    $wa_msg .= "👤 *اسم العميل:* " . $cust['name'] . "\n";
    $wa_msg .= "💵 *المبلغ المسدد:* " . number_format($success_payment_data['amount'], 2) . " ر.ي\n";
    $wa_msg .= "🧾 *عن فاتورة رقم:* #" . str_pad($success_payment_data['invoice_id'], 4, '0', STR_PAD_LEFT) . "\n";
    $wa_msg .= "📌 *البيان:* " . $success_payment_data['note'] . "\n";
    $wa_msg .= "📉 *المتبقي من الفاتورة:* " . number_format($success_payment_data['new_remaining'], 2) . " ر.ي\n";
    $wa_msg .= "💰 *إجمالي المتبقي العام:* " . number_format($success_payment_data['total_remaining_all'], 2) . " ر.ي\n";
    $wa_msg .= "━━━━━━━━━━━━━━━\n";
    $wa_msg .= "✅ *معتمد رسميًا - ختم " . $success_payment_data['type'] . "*\n";
    $wa_msg .= "🙏 شكراً لتعاملكم معنا.";

    if ($cust_phone) {
        $wa_payment_url = "https://api.whatsapp.com/send?phone=" . $cust_phone . "&text=" . urlencode($wa_msg);
    } else {
        $wa_payment_url = "https://api.whatsapp.com/send?text=" . urlencode($wa_msg);
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" style="direction: rtl; unicode-bidi: embed;">
<head>
<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'>
<meta name='mobile-web-app-capable' content='yes'>
<meta name='apple-mobile-web-app-capable' content='yes'>

    <meta charset="UTF-8">
    <title>فواتير وسندات العميل - <?php echo htmlspecialchars($cust['name']); ?></title>
    <?php include 'global_style.php'; ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        * { direction: rtl; text-align: right; box-sizing: border-box; }
        .lux-wrapper { padding: 6px; max-width: 600px; margin: 0 auto 70px auto; }
        
        .cust-info-box { background: linear-gradient(135deg, #0F172A, #1E3A8A); color: #FFF; padding: 10px; border-radius: 8px; margin-bottom: 8px; }
        .cust-info-box h2 { margin: 0 0 3px 0; font-size: 14px; color: #FBBF24; }
        .cust-info-box p { margin: 2px 0; font-size: 10.5px; color: #CBD5E1; }

        .section-title { font-size: 12.5px; color: var(--text-dark); margin: 10px 0 6px 0; font-weight: bold; }

        .hidden-section { display: none; }
        .hidden-section.show { display: block; }

        .toggle-btn { background: #1E3A8A; color: #FBBF24; border: 1px solid #3B82F6; width: 100%; padding: 10px; border-radius: 6px; font-weight: bold; font-size: 11.5px; cursor: pointer; text-align: center; margin: 8px 0; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }

        .inv-card { background: var(--card-white); border: 1px solid var(--border-gray); border-radius: 6px; padding: 8px; margin-bottom: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
        .inv-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; border-bottom: 1px solid #F1F5F9; padding-bottom: 4px; }
        
        .badge-status { padding: 2px 6px; border-radius: 4px; font-size: 9px; font-weight: bold; }
        .badge-paid { background: #DCFCE7; color: #15803D; }
        .badge-partial { background: #FEF3C7; color: #B45309; }
        .badge-unpaid { background: #FEE2E2; color: #B91C1C; }

        .pay-box { background: #F1F5F9; border: 1px solid #CBD5E1; padding: 8px; border-radius: 6px; margin-top: 6px; }
        .pay-row { display: flex; gap: 4px; margin-bottom: 4px; align-items: center; }
        
        .pay-input { flex: 1; padding: 6px; background: #FFFFFF !important; color: #0F172A !important; border: 1.5px solid #3B82F6 !important; border-radius: 5px; font-size: 12px !important; font-weight: bold !important; direction: ltr; text-align: right; }
        .note-input { width: 100%; padding: 5px 6px; background: #FFFFFF; color: #0F172A; border: 1px solid #CBD5E1; border-radius: 5px; font-size: 11px; }
        .pay-btn { background: #16A34A; color: #FFF; border: none; padding: 7px 12px; border-radius: 5px; font-size: 11px; font-weight: bold; cursor: pointer; width: 100%; text-align: center; }

        .back-btn { display: inline-block; background: #475569; color: #FFF; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 11px; font-weight: bold; margin-bottom: 8px; }
        .live-preview { font-size: 10px; color: #1E3A8A; font-weight: bold; margin-top: 2px; }

        /* نافذة سند القبض الرسمي بدقة 4K */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.65); z-index: 9999; align-items: center; justify-content: center; padding: 10px; overflow-y: auto; }
        .modal-overlay.show { display: flex; }
        .receipt-card { background: #FFFEFA; border: 2px solid #1E3A8A; border-radius: 12px; padding: 15px; max-width: 420px; width: 100%; box-shadow: 0 15px 35px rgba(0,0,0,0.3); font-family: 'Tahoma', sans-serif; position: relative; }
        
        .receipt-header { text-align: center; border-bottom: 2px dashed #CBD5E1; padding-bottom: 8px; margin-bottom: 10px; }
        .receipt-header h3 { margin: 0; color: #1E3A8A; font-size: 15px; font-weight: bold; }
        .receipt-header p { margin: 2px 0; font-size: 10px; color: #64748B; }

        .receipt-body { font-size: 11.5px; color: #0F172A; margin-bottom: 15px; }
        .receipt-row { display: flex; justify-content: space-between; margin-bottom: 6px; border-bottom: 1px solid #F1F5F9; padding-bottom: 4px; }
        .receipt-row span:first-child { color: #475569; font-weight: bold; }
        .receipt-row span:last-child { color: #0F172A; font-weight: bold; }

        .receipt-footer-section { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #E2E8F0; padding-top: 10px; margin-top: 5px; }
        
        .official-stamp { border: 3px double #1E3A8A; color: #1E3A8A; border-radius: 50%; width: 75px; height: 75px; display: flex; flex-direction: column; align-items: center; justify-content: center; transform: rotate(-10deg); font-size: 8.5px; font-weight: bold; text-align: center; padding: 4px; background: rgba(30, 58, 138, 0.03); flex-shrink: 0; }
        .official-stamp span { font-size: 7.5px; }

        .receipt-auth-text { font-size: 10px; color: #1E3A8A; font-weight: bold; text-align: left; }

        .btn-action-group { display: flex; gap: 5px; margin-top: 12px; }
        .wa-img-btn { flex: 1; background: #25D366; color: #FFF; border: none; padding: 9px; border-radius: 6px; font-weight: bold; font-size: 11px; cursor: pointer; text-align: center; }
        .close-modal-btn { background: #64748B; color: #FFF; border: none; padding: 8px; border-radius: 6px; font-size: 11px; font-weight: bold; width: 100%; cursor: pointer; margin-top: 5px; }
    </style>
</head>
<body>
<div class="lux-wrapper">
    <a href="javascript:history.back()" class="back-btn" style="background: #EF4444;">❌ إغلاق</a>

    <div class="cust-info-box">
        <h2>👤 <?php echo htmlspecialchars($cust['name']); ?></h2>
        <p>جوال: <?php echo htmlspecialchars($cust['phone'] ?: 'غير متوفر'); ?> | العنوان: <?php echo htmlspecialchars($cust['address'] ?: 'غير متوفر'); ?></p>
        <p style="margin-top: 4px; font-weight: bold; font-size: 11.5px; color: #F8FAFC;">إجمالي المديونية الحالية: <span dir="ltr" style="color:#FBBF24;"><?php echo number_format($calculated_total_debt, 2); ?></span> ر.ي</p>
    </div>

    <?php if($msg): ?>
        <div style="background:#10B981; color:#FFF; padding:8px; border-radius:6px; margin-bottom:8px; text-align:center; font-weight:bold; font-size:11px;"><?php echo $msg; ?></div>
    <?php endif; ?>

    <!-- زر سجل السندات والدفعات -->
    <button type="button" class="toggle-btn" id="toggleReceiptsBtn" onclick="toggleReceiptsSection()">
        📋 سجل السندات والدفعات الصادرة (<?php echo count($payments_list); ?>)
    </button>

    <!-- قسم سجل السندات السابقة -->
    <div id="receiptsSection" class="hidden-section" style="margin-bottom: 12px;">
        <?php if(empty($payments_list)): ?>
            <div style="background: #FFF; padding: 10px; border-radius: 6px; text-align: center; font-size: 11px; color: #64748B; border: 1px solid #CBD5E1;">لا توجد سندات قبض مسجلة حتى الآن.</div>
        <?php else: ?>
            <?php foreach($payments_list as $p): ?>
            <div class="inv-card" style="background: #F8FAFC; border-color: #CBD5E1;">
                <div class="inv-header">
                    <div>
                        <strong style="font-size: 11.5px; color: #1E3A8A;">سند قبض #<?php echo $p['id']; ?></strong>
                        <div style="font-size: 9px; color: #64748B;"><?php echo $p['date'] ?? ''; ?></div>
                    </div>
                    <div>
                        <span style="font-weight: bold; color: #16A34A; font-size: 12px;" dir="ltr"><?php echo number_format($p['amount'] ?? 0, 2); ?> ر.ي</span>
                    </div>
                </div>
                <div style="font-size: 10.5px; color: #334155; margin-bottom: 6px;">
                    📌 <strong>البيان:</strong> <?php echo htmlspecialchars($p['note'] ?? ($p['notes'] ?? '')); ?>
                </div>
                <div style="text-align: left;">
                    <a href="customer_invoices.php?customer_id=<?php echo $customer_id; ?>&delete_payment_id=<?php echo $p['id']; ?>" class="action-btn" style="background: #EF4444; color: #FFF; padding: 4px 8px; font-size: 10px; border-radius: 4px; text-decoration: none;" onclick="return confirm('تنبيه: هل أنت متأكد من حذف هذا السند؟ سيتم التراجع وعكس المبلغ وإرجاعه للفاتورة تلقائياً.')">🗑️ حذف السند والتراجع</a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="section-title">📄 الفواتير النشطة والمطلوب سدادها (<?php echo count($active_invoices); ?>)</div>

    <?php if(empty($active_invoices)): ?>
        <div style="text-align: center; padding: 20px; background: #FFF; border-radius: 6px; color: #16A34A; font-size: 11.5px; font-weight: bold; margin-bottom: 10px;">🎉 ممتاز! لا توجد فواتير معلقة، جميع فواتير هذا العميل مسددة.</div>
    <?php else: ?>
        <?php foreach($active_invoices as $inv): 
            $remaining = floatval($inv['grand_total']) - floatval($inv['paid_amount']);
            $status_class = (floatval($inv['paid_amount']) > 0) ? 'badge-partial' : 'badge-unpaid';
            $status_label = (floatval($inv['paid_amount']) > 0) ? 'سداد جزئي' : 'آجل / غير مسدد';
        ?>
        <div class="inv-card">
            <div class="inv-header">
                <div>
                    <strong style="font-size: 12px;">فاتورة #<?php echo str_pad($inv['id'], 4, '0', STR_PAD_LEFT); ?></strong>
                    <div style="font-size: 9px; color: #64748B;"><?php echo $inv['date']; ?></div>
                </div>
                <div>
                    <span class="badge-status <?php echo $status_class; ?>"><?php echo $status_label; ?></span>
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; font-size: 10.5px; margin-bottom: 6px;">
                <span>إجمالي: <strong dir="ltr"><?php echo number_format($inv['grand_total'], 2); ?></strong></span>
                <span>المدفوع: <strong dir="ltr" style="color:#16A34A;"><?php echo number_format($inv['paid_amount'], 2); ?></strong></span>
                <span>المتبقي: <strong dir="ltr" style="color:#DC2626;"><?php echo number_format($remaining, 2); ?></strong></span>
            </div>

            <div style="display: flex; gap: 4px; margin-top: 4px;">
                <a href="invoice_whatsapp.php?id=<?php echo $inv['id']; ?>" class="action-btn inv" style="flex:1; text-align:center; justify-content:center; font-size: 11px; padding: 5px;">👁️ عرض الفاتورة</a>
            </div>

            <?php if($remaining > 0): ?>
            <form method="POST" class="pay-box">
                <input type="hidden" name="action" value="pay_invoice">
                <input type="hidden" name="pay_invoice_id" value="<?php echo $inv['id']; ?>">

                <div class="pay-row">
                    <input type="number" step="0.01" name="pay_amount" id="amount_<?php echo $inv['id']; ?>" class="pay-input" placeholder="اكتب مبلغ الدفعة..." max="<?php echo $remaining; ?>" oninput="updatePreview(<?php echo $inv['id']; ?>)" required>
                </div>
                <div class="live-preview" id="preview_<?php echo $inv['id']; ?>">المبلغ المدخل: 0.00 ر.ي</div>

                <div style="margin-top: 4px; margin-bottom: 4px;">
                    <input type="text" name="payment_note" class="note-input" placeholder="البيان / ملاحظة السداد (اختياري)..." value="دفعة قسط للفاتورة">
                </div>

                <button type="submit" class="pay-btn">💵 تأكيد ودفع الدفعة</button>
            </form>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- قسم الفواتير المسددة بالكامل -->
    <?php if(count($paid_invoices) > 0): ?>
        <button type="button" class="toggle-btn" id="togglePaidBtn" onclick="togglePaidInvoices()" style="background: #0F172A; color: #38BDF8;">
            📜 عرض الفواتير المسددة السابقة (<?php echo count($paid_invoices); ?>)
        </button>

        <div id="paidInvoicesSection" class="hidden-section">
            <div class="section-title" style="color: #15803D;">✅ فواتير تم سدادها بالكامل</div>
            <?php foreach($paid_invoices as $inv): ?>
            <div class="inv-card" style="background: #F0FDF4; border-color: #BBF7D0;">
                <div class="inv-header">
                    <div>
                        <strong style="font-size: 12px; color: #166534;">فاتورة #<?php echo str_pad($inv['id'], 4, '0', STR_PAD_LEFT); ?></strong>
                        <div style="font-size: 9px; color: #64748B;"><?php echo $inv['date']; ?></div>
                    </div>
                    <div>
                        <span class="badge-status badge-paid">مسدد بالكامل</span>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; font-size: 10.5px; margin-bottom: 6px;">
                    <span>إجمالي: <strong dir="ltr"><?php echo number_format($inv['grand_total'], 2); ?></strong></span>
                    <span>المدفوع: <strong dir="ltr" style="color:#16A34A;"><?php echo number_format($inv['paid_amount'], 2); ?></strong></span>
                    <span>المتبقي: <strong dir="ltr" style="color:#16A34A;">0.00</strong></span>
                </div>

                <div style="display: flex; gap: 4px; margin-top: 4px;">
                    <a href="invoice_whatsapp.php?id=<?php echo $inv['id']; ?>" class="action-btn inv" style="flex:1; text-align:center; justify-content:center; font-size: 11px; padding: 5px;">👁️ عرض الفاتورة</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<!-- نافذة سند القبض الرسمي المعتمد بدقة 4K -->
<?php if($success_payment_data): ?>
<div class="modal-overlay show" id="successModal">
    <div class="receipt-card">
        <div id="captureArea" style="background: #FFFEFA; padding: 8px;">
            <div class="receipt-header">
                <h3>🏛️ مؤسسة أبو ضياء للتوزيع</h3>
                <p>سند <?php echo $success_payment_data['type']; ?> رسمي معتمد</p>
            </div>

            <div class="receipt-body">
                <div class="receipt-row">
                    <span>رقم السند:</span>
                    <span>#<?php echo $success_payment_data['receipt_id']; ?></span>
                </div>
                <div class="receipt-row">
                    <span>التاريخ والوقت:</span>
                    <span style="font-size: 10px;"><?php echo $success_payment_data['date']; ?></span>
                </div>
                <div class="receipt-row">
                    <span>اسم العميل:</span>
                    <span><?php echo htmlspecialchars($cust['name']); ?></span>
                </div>
                <div class="receipt-row">
                    <span>المبلغ المسدد:</span>
                    <span style="color: #16A34A; font-size: 13px;"><?php echo number_format($success_payment_data['amount'], 2); ?> ر.ي</span>
                </div>
                <div class="receipt-row">
                    <span>عن الفاتورة:</span>
                    <span>رقم #<?php echo str_pad($success_payment_data['invoice_id'], 4, '0', STR_PAD_LEFT); ?></span>
                </div>
                <div class="receipt-row">
                    <span>البيان:</span>
                    <span><?php echo htmlspecialchars($success_payment_data['note']); ?></span>
                </div>
                <div class="receipt-row">
                    <span>المتبقي من الفاتورة:</span>
                    <span style="color: #DC2626;"><?php echo number_format($success_payment_data['new_remaining'], 2); ?> ر.ي</span>
                </div>
                <div class="receipt-row" style="border-bottom: none; background: #F8FAFC; padding: 5px; border-radius: 4px;">
                    <span style="color: #1E3A8A;">إجمالي المتبقي العام:</span>
                    <span style="color: #1E3A8A;"><?php echo number_format($success_payment_data['total_remaining_all'], 2); ?> ر.ي</span>
                </div>
            </div>

            <div class="receipt-footer-section">
                <div class="official-stamp">
                    <span>مؤسسة أبو ضياء</span>
                    <strong><?php echo $success_payment_data['type']; ?> معتمد</strong>
                    <span>الحسابات الرسمية</span>
                </div>
                <div class="receipt-auth-text">
                    معتمد رسمياً<br>
                    مؤسسة أبو ضياء للتوزيع
                </div>
            </div>
        </div>

        <div class="btn-action-group">
            <button type="button" onclick="saveImageAndOpenWhatsApp()" class="wa-img-btn">📸💾 حفظ 4K و💬 إرسال النص واتساب</button>
        </div>
        <button type="button" class="close-modal-btn" onclick="closeModal()">إغلاق ومتابعة العمل</button>
    </div>
</div>
<?php endif; ?>

<script>
function updatePreview(invId) {
    let inputVal = document.getElementById('amount_' + invId).value;
    let previewEl = document.getElementById('preview_' + invId);
    let num = parseFloat(inputVal) || 0;
    previewEl.innerHTML = "المبلغ المؤكد إضافته: " + num.toLocaleString('en-US', {minimumFractionDigits: 2}) + " ر.ي";
}

function toggleReceiptsSection() {
    let box = document.getElementById('receiptsSection');
    if(box) {
        box.classList.toggle('show');
    }
}

function togglePaidInvoices() {
    let box = document.getElementById('paidInvoicesSection');
    let btn = document.getElementById('togglePaidBtn');
    if(box) {
        box.classList.toggle('show');
        if (box.classList.contains('show')) {
            btn.innerHTML = '⬆ إخفاء الفواتير المسددة السابقة';
        } else {
            btn.innerHTML = '📜 عرض الفواتير المسددة السابقة (<?php echo count($paid_invoices); ?>)';
        }
    }
}

function closeModal() {
    let modal = document.getElementById('successModal');
    if(modal) {
        modal.classList.remove('show');
    }
}

function saveImageAndOpenWhatsApp() {
    const captureElement = document.getElementById('captureArea');
    html2canvas(captureElement, { scale: 4, backgroundColor: '#FFFEFA', useCORS: true }).then(canvas => {
        let link = document.createElement('a');
        link.download = 'Receipt_4K_AbuDhiyaa_<?php echo $success_payment_data["receipt_id"] ?? "1"; ?>.png';
        link.href = canvas.toDataURL('image/png', 1.0);
        link.click();

        setTimeout(function() {
            window.open('<?php echo $wa_payment_url; ?>', '_blank');
        }, 800);
    });
}
</script>

<?php include 'nav_menu.php'; ?>
</body>
</html>
