<?php
// treasury.php - سندات القبض والصرف مع معالجة مشاركة وتنزيل الـ PDF بدقة تامة
error_reporting(E_ALL & ~E_WARNING & ~E_DEPRECATED);  
ini_set('display_errors', 0);

require_once 'auth.php';
require_once 'db.php';

$is_sqlite = isset($db);
if (!$is_sqlite) { exit("خطأ في الاتصال بقاعدة البيانات"); }

@$db->exec("ALTER TABLE vouchers ADD COLUMN notes TEXT");
@$db->exec("ALTER TABLE vouchers ADD COLUMN invoice_id INTEGER");

$customers = [];
$res_c = $db->query("SELECT * FROM customers ORDER BY name ASC");
if ($res_c) { while ($row_c = $res_c->fetchArray(SQLITE3_ASSOC)) { $customers[] = $row_c; } }

if (isset($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);
    $db->exec("DELETE FROM vouchers WHERE id = $del_id");
    header("Location: treasury.php");
    exit;
}

$edit_mode = false;
$edit_data = ['id' => '', 'customer_id' => 1, 'invoice_id' => '', 'amount' => '', 'type' => 'receipt', 'notes' => ''];
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $edit_data = $db->querySingle("SELECT * FROM vouchers WHERE id = $edit_id", true);
    if ($edit_data) {
        $edit_mode = true;
    }
}

$get_cust_id = $edit_mode ? $edit_data['customer_id'] : ($_GET['customer_id'] ?? ($customers[0]['id'] ?? 1));

$saved_id = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_voucher'])) {
    $type = $_POST['type'] ?? 'receipt';
    $cust_id = intval($_POST['customer_id'] ?? 0);
    $invoice_id = !empty($_POST['invoice_id']) ? intval($_POST['invoice_id']) : "NULL";
    $amount = floatval($_POST['amount'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    $post_edit_id = intval($_POST['edit_id'] ?? 0);
    $date = date('Y-m-d H:i:s');

    if ($amount > 0) {
        if ($post_edit_id > 0) {
            $db->exec("UPDATE vouchers SET type='$type', customer_id=$cust_id, invoice_id=$invoice_id, amount=$amount, notes='$notes' WHERE id=$post_edit_id");
            $saved_id = $post_edit_id;
        } else {
            $db->exec("INSERT INTO vouchers (type, customer_id, invoice_id, amount, notes, date) VALUES ('$type', $cust_id, $invoice_id, $amount, '$notes', '$date')");
            $saved_id = $db->lastInsertRowID();

            if ($type === 'receipt' && $cust_id > 0) {
                $db->exec("UPDATE customers SET debt = debt - $amount WHERE id = $cust_id");
            } elseif ($type === 'payment' && $cust_id > 0) {
                $db->exec("UPDATE customers SET debt = debt + $amount WHERE id = $cust_id");
            }
        }
        header("Location: treasury.php?saved_id=" . $saved_id);
        exit;
    }
}

$view_saved_id = isset($_GET['saved_id']) ? intval($_GET['saved_id']) : 0;
$is_submitted = ($view_saved_id > 0);
$saved_voucher = [];
$client_name = "عميل نقدي عام";
$client_phone = "";
$rem_balance = 0;
$created_date = date('Y-m-d');

if ($is_submitted) {
    $v_info = $db->querySingle("SELECT * FROM vouchers WHERE id = $view_saved_id", true);
    if ($v_info) {
        $saved_voucher = $v_info;
        $created_date = date('Y-m-d', strtotime($v_info['date'] ?? 'now'));
        
        if ($v_info['customer_id'] > 0) {
            $c_info = $db->querySingle("SELECT * FROM customers WHERE id = " . $v_info['customer_id'], true);
            if ($c_info) {
                $client_name = $c_info['name'];
                $rem_balance = floatval($c_info['debt']);
                if (!empty($c_info['phone'])) {
                    $client_phone = preg_replace('/[^0-9]/', '', $c_info['phone']);
                }
            }
        }
    } else {
        $is_submitted = false;
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" style="direction: rtl; unicode-bidi: embed;">
<head>
<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><meta name='mobile-web-app-capable' content='yes'>
<meta name='apple-mobile-web-app-capable' content='yes'>

    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>سندات القبض والصرف - مؤسسة أبو ضياء</title>
    <?php include 'global_style.php'; ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        * { direction: rtl; text-align: right; box-sizing: border-box; font-family: 'Tahoma', Arial, sans-serif; }
        .lux-wrapper { padding: 8px; max-width: 600px; margin: 0 auto 75px auto; }
        
        .voucher-card { background: #FFF; border: 1px solid var(--border-gray); border-radius: 8px; padding: 12px; margin-bottom: 10px; display: <?php echo $is_submitted ? 'none' : 'block'; ?>; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
        .lux-input { width: 100%; padding: 8px; border: 1px solid #CBD5E1; border-radius: 6px; font-size: 12px; margin-bottom: 8px; background: #FFF; }
        .lux-select { width: 100%; padding: 8px; border: 1px solid #CBD5E1; border-radius: 6px; font-size: 12px; margin-bottom: 8px; background: #FFF; }
        .lux-textarea { width: 100%; padding: 8px; border: 1px solid #CBD5E1; border-radius: 6px; font-size: 12px; margin-bottom: 8px; background: #FFF; height: 60px; resize: vertical; }
        .btn-submit { background: #16A34A; color: #FFF; border: none; padding: 10px; border-radius: 6px; font-size: 13px; font-weight: bold; cursor: pointer; width: 100%; margin-top: 5px; }

        /* ورقة السند المطابقة تماماً لتصميم الفاتورة في الصورة */
        .invoice-sheet { background: #FFFFFF; border-radius: 10px; border: 1px solid #CBD5E1; box-shadow: 0 4px 15px rgba(0,0,0,0.08); overflow: hidden; margin-bottom: 12px; display: <?php echo $is_submitted ? 'block' : 'none'; ?>; }
        
        .inv-header-bg { background: #1E3A8A; color: #FFF; padding: 12px; display: flex; justify-content: space-between; align-items: center; }
        .inv-company-title { font-size: 15px; font-weight: bold; color: #FBBF24; margin-bottom: 2px; }
        .inv-company-sub { font-size: 10px; color: #CBD5E1; line-height: 1.4; }
        .inv-badge-box { background: #172554; border: 1px solid #3B82F6; border-radius: 6px; padding: 6px 10px; text-align: center; font-size: 10.5px; }
        
        .inv-customer-box { margin: 10px; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 6px; padding: 8px 12px; }
        .inv-cust-label { font-size: 9.5px; color: #64748B; margin-bottom: 2px; }
        .inv-cust-name { font-size: 14px; font-weight: bold; color: #1E293B; }

        .inv-table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        .inv-table th { background: #1E3A8A; color: #FFF; padding: 6px; font-size: 11px; text-align: center; border: 1px solid #1E3A8A; }
        .inv-table td { padding: 8px; border: 1px solid #E2E8F0; font-size: 11.5px; text-align: center; color: #334155; }

        .inv-summary-container { display: flex; gap: 8px; margin: 10px; }
        .inv-totals-box { flex: 1; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 6px; padding: 8px; }
        .inv-total-row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 11px; border-bottom: 1px dashed #E2E8F0; }
        .inv-total-row.grand { font-weight: bold; font-size: 12.5px; color: #15803D; border-bottom: none; }
        .inv-notes-box { flex: 1; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 6px; padding: 8px; font-size: 10px; color: #475569; line-height: 1.5; }

        .inv-signatures { display: flex; justify-content: space-between; margin: 10px; border-top: 1px solid #E2E8F0; padding-top: 8px; text-align: center; }
        .sig-col { flex: 1; font-size: 10px; color: #475569; border-left: 1px solid #E2E8F0; }
        .sig-col:first-child { border-left: none; }
        .sig-title { font-weight: bold; margin-bottom: 15px; color: #1E293B; }
        .sig-dots { color: #94A3B8; }

        .action-bar { display: <?php echo $is_submitted ? 'flex' : 'none'; ?>; justify-content: center; gap: 5px; margin-top: 10px; flex-wrap: wrap; }
        .btn-action { padding: 8px 12px; border: none; border-radius: 6px; font-size: 11px; font-weight: bold; cursor: pointer; color: white; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 3px; }
        .btn-img { background: #D97706; }
        .btn-pdf { background: #0284C7; }
        .btn-edit { background: #EA580C; }
        .btn-whatsapp-full { background: #16A34A; width: 100%; margin-top: 6px; font-size: 12.5px; padding: 10px; }

        .vouchers-list-section { background: #FFF; border: 1px solid var(--border-gray); border-radius: 8px; padding: 12px; margin-top: 10px; display: <?php echo $is_submitted ? 'none' : 'block'; ?>; }
        .rec-table { width: 100%; border-collapse: collapse; font-size: 11px; margin-top: 6px; }
        .rec-table th, .rec-table td { border: 1px solid #E2E8F0; padding: 6px; text-align: center; }
        .rec-table th { background: #1E3A8A; color: #FFF; }
        .btn-sm { padding: 3px 6px; font-size: 10px; border-radius: 4px; text-decoration: none; font-weight: bold; color: #fff; display: inline-block; }
        .btn-table-edit { background: #D97706; }
        .btn-table-del { background: #DC2626; }

        @media print {
            body { background: #FFF !important; padding: 0 !important; }
            .voucher-card, .top-nav, .action-bar, .vouchers-list-section, nav, .nav-menu { display: none !important; }
            .invoice-sheet { border: none !important; box-shadow: none !important; margin: 0 !important; width: 100% !important; display: block !important; }
        }
    </style>
</head>
<body>
<div class="lux-wrapper">
    
    <!-- نموذج تحرير السند -->
    <div class="voucher-card">
        <h2 style="font-size: 14px; margin-top: 0; color: #1E3A8A; border-bottom: 2px solid #E2E8F0; padding-bottom: 6px;">
            🏦 <?php echo $edit_mode ? 'تعديل سند رقم #' . $edit_data['id'] : 'سند قبض / صرف رسمي جديد'; ?>
        </h2>

        <form method="POST">
            <input type="hidden" name="edit_id" value="<?php echo $edit_mode ? $edit_data['id'] : 0; ?>">
            
            <label style="font-size: 11px; font-weight: bold; color: #64748B;">نوع السند:</label>
            <select name="type" class="lux-select">
                <option value="receipt" <?php echo ($edit_mode && $edit_data['type']=='receipt')?'selected':''; ?>>📥 سند قبض (استلام أموال)</option>
                <option value="payment" <?php echo ($edit_mode && $edit_data['type']=='payment')?'selected':''; ?>>📤 سند صرف (دفع أموال)</option>
            </select>

            <label style="font-size: 11px; font-weight: bold; color: #64748B;">العميل / الطرف:</label>
            <select name="customer_id" class="lux-select" required>
                <option value="0">--- عميل عام / بدون تعيين ---</option>
                <?php foreach($customers as $c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php echo ($c['id'] == $get_cust_id) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c['name']); ?> (الديون: <?php echo number_format($c['debt'], 2); ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <label style="font-size: 11px; font-weight: bold; color: #64748B;">رقم الفاتورة المرتبطة (اختياري):</label>
            <input type="text" name="invoice_id" class="lux-input" value="<?php echo htmlspecialchars($edit_data['invoice_id'] ?? ''); ?>" placeholder="مثال: 94">

            <label style="font-size: 11px; font-weight: bold; color: #64748B;">المبلغ (ر.ي):</label>
            <input type="number" step="0.01" name="amount" class="lux-input" value="<?php echo htmlspecialchars($edit_data['amount'] ?? ''); ?>" placeholder="0.00" required>

            <label style="font-size: 11px; font-weight: bold; color: #64748B;">البيان / ملاحظات:</label>
            <textarea name="notes" class="lux-textarea"><?php echo htmlspecialchars($edit_data['notes'] ?? 'سداد قيمة مبيعات / سند رسمي'); ?></textarea>

            <button type="submit" name="save_voucher" class="btn-submit">💾 حفظ واعتماد وترحيل السند</button>
        </form>
    </div>

    <!-- ورقة السند المطابقة تماماً لتصميم الفاتورة في الصورة -->
    <?php if ($is_submitted): ?>
    <div class="invoice-sheet" id="receiptContent">
        <!-- ترويسة الفاتورة الزرقاء تماماً كالصورة -->
        <div class="inv-header-bg">
            <div>
                <div class="inv-company-title">مؤسسة أبو ضياء للتوزيع</div>
                <div class="inv-company-sub">سيئون - السوق العام | جوال: 771458609<br>للتواصل والطلبات المباشرة</div>
            </div>
            <div class="inv-badge-box">
                <div style="color: #4ADE80; font-weight: bold; font-size: 11px;">معتمد</div>
                <div style="font-size: 11px; margin-top: 2px;" dir="ltr">#<?php echo str_pad($saved_voucher['id'], 4, '0', STR_PAD_LEFT); ?></div>
                <div style="font-size: 9.5px; color: #93C5FD;"><?php echo $created_date; ?></div>
            </div>
        </div>

        <!-- صندوق اسم العميل -->
        <div class="inv-customer-box">
            <div class="inv-cust-label"><?php echo ($saved_voucher['type'] === 'receipt') ? 'سند قبض لحساب العميل' : 'سند صرف للطرف'; ?></div>
            <div class="inv-cust-name"><?php echo htmlspecialchars($client_name); ?></div>
        </div>

        <!-- جدول بيانات السند -->
        <div style="margin: 0 10px;">
            <table class="inv-table">
                <thead>
                    <tr>
                        <th>نوع السند</th>
                        <th>التفاصيل</th>
                        <th>المبلغ</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="font-weight: bold;"><?php echo ($saved_voucher['type'] === 'receipt') ? 'سند قبض نقدي' : 'سند صرف نقدي'; ?></td>
                        <td>عملية نقدية معتمدة</td>
                        <td style="font-weight: bold; color: <?php echo ($saved_voucher['type'] === 'receipt') ? '#15803D' : '#DC2626'; ?>;" dir="ltr"><?php echo number_format($saved_voucher['amount'], 2); ?> ر.ي</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- صندوق الإجماليات والملاحظات -->
        <div class="inv-summary-container">
            <div class="inv-totals-box">
                <div class="inv-total-row">
                    <span>المبلغ:</span>
                    <span dir="ltr"><b><?php echo number_format($saved_voucher['amount'], 2); ?></b> ر.ي</span>
                </div>
                <div class="inv-total-row grand">
                    <span>الرصيد المتبقي عليكم:</span>
                    <span dir="ltr" style="color: #DC2626;"><?php echo number_format($rem_balance, 2); ?> ر.ي</span>
                </div>
            </div>
            <div class="inv-notes-box">
                <strong style="color: #1E3A8A;">📌 البيان:</strong><br>
                <?php echo htmlspecialchars($saved_voucher['notes'] ?: 'لا توجد ملاحظات إضافية.'); ?>
                <?php if(!empty($saved_voucher['invoice_id'])): ?>
                    <br>🔗 مرتبط بالفاتورة: #<?php echo $saved_voucher['invoice_id']; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- خانات التوقيع الثلاثة تماماً مثل الفاتورة -->
        <div class="inv-signatures">
            <div class="sig-col">
                <div class="sig-title">الصندوق / المالك</div>
                <div class="sig-dots">التوقيع: ......</div>
            </div>
            <div class="sig-col">
                <div class="sig-title">المسؤول / البائع</div>
                <div style="font-weight: bold; color: #1E3A8A;">أبو ضياء</div>
            </div>
            <div class="sig-col">
                <div class="sig-title">استلام الطرف</div>
                <div class="sig-dots">التوقيع: ......</div>
            </div>
        </div>

        <!-- التوقيع البرمجي الخفيف -->
        <div style="text-align: center; padding: 6px; font-size: 9px; color: #94A3B8; background: #F8FAFC; border-top: 1px solid #E2E8F0;">
            إدارة وتطوير: أبو ضياء (771458609)
        </div>
    </div>

    <!-- شريط الأزرار الاحترافي مطابق للفاتورة تماماً -->
    <div class="action-bar">
        <button onclick="downloadImageFile()" class="btn-action btn-img">📥 حفظ صورة HD</button>
        <button onclick="downloadPDFFile()" class="btn-action btn-pdf">📑 تنزيل PDF</button>
        <a href="treasury.php?edit_id=<?php echo $saved_voucher['id']; ?>" class="btn-action btn-edit">✏️ تعديل</a>
        <a href="treasury.php" class="btn-action" style="background: #475569;">➕ سند جديد</a>
    </div>
    <div>
        <button onclick="sharePDFOrWhatsApp()" class="btn-action btn-whatsapp-full">💬 إرسال السند عبر واتساب (PDF / نص)</button>
    </div>

    <script>
    function downloadPDFFile() {
        const element = document.getElementById('receiptContent');
        const opt = {
            margin:       [5, 5, 5, 5],
            filename:     'سند_<?php echo $saved_voucher['id']; ?>.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2, useCORS: true },
            jsPDF:        { unit: 'mm', format: 'a5', orientation: 'portrait' }
        };
        html2pdf().set(opt).from(element).save();
    }

    function downloadImageFile() {
        const element = document.getElementById('receiptContent');
        html2canvas(element, { scale: 2, useCORS: true }).then(canvas => {
            let link = document.createElement('a');
            link.download = 'سند_<?php echo $saved_voucher['id']; ?>.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
        });
    }

    async function sharePDFOrWhatsApp() {
        const element = document.getElementById('receiptContent');
        const opt = {
            margin:       [5, 5, 5, 5],
            filename:     'سند_قبض_<?php echo $saved_voucher['id']; ?>.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2, useCORS: true },
            jsPDF:        { unit: 'mm', format: 'a5', orientation: 'portrait' }
        };

        var phone = "<?php echo $client_phone; ?>";
        var compName = "مؤسسة أبو ضياء للتوزيع";
        var recId = "<?php echo $saved_voucher['id']; ?>";
        var typeTitle = "<?php echo ($saved_voucher['type'] === 'receipt') ? 'سند قبض' : 'سند صرف'; ?>";
        var clientName = "<?php echo addslashes($client_name); ?>";
        var amountVal = "<?php echo number_format($saved_voucher['amount'], 2); ?>";
        var notesVal = "<?php echo addslashes($saved_voucher['notes']); ?>";
        var remDebt = "<?php echo number_format($rem_balance, 2); ?>";
        var dateVal = "<?php echo $created_date; ?>";

        var textMsg = "*🌟 " + compName + " 🌟*\n" +
                      "──────────────────\n" +
                      "📄 *" + typeTitle + " رسمي معتمد #" + recId + "*\n" +
                      "──────────────────\n" +
                      "👤 *الطرف:* " + clientName + "\n" +
                      "💰 *المبلغ:* " + amountVal + " ر.ي\n" +
                      "📝 *البيان:* " + notesVal + "\n" +
                      "──────────────────\n" +
                      "📊 *الرصيد المتبقي عليكم:* " + remDebt + " ر.ي\n" +
                      "📅 *التاريخ:* " + dateVal + "\n\n" +
                      "إدارة: أبو ضياء | 771458609";

        // محاولة استخدام نظام المشاركة المباشر للملف (PDF) إذا كان مدعوماً في متصفح الهاتف
        try {
            const pdfBlob = await html2pdf().set(opt).from(element).output('blob');
            const pdfFile = new File([pdfBlob], "سند_قبض_" + recId + ".pdf", { type: "application/pdf" });

            if (navigator.canShare && navigator.canShare({ files: [pdfFile] })) {
                await navigator.share({
                    files: [pdfFile],
                    title: typeTitle + ' #' + recId,
                    text: textMsg
                });
                return;
            }
        } catch (e) {
            console.log("Web share not supported, falling back to WhatsApp link.");
        }

        // الطريقة البديلة المضمونة: تحميل الـ PDF وفتح واتساب بالنص
        downloadPDFFile();
        setTimeout(function() {
            window.open("https://wa.me/" + phone + "?text=" + encodeURIComponent(textMsg), "_blank");
        }, 1200);
    }
    </script>
    <?php endif; ?>

    <!-- سجل السندات الأخيرة -->
    <div class="vouchers-list-section">
        <h3 style="font-size: 13px; margin: 0 0 8px 0; color: #1E3A8A; border-bottom: 1px solid #E2E8F0; padding-bottom: 5px;">📋 سجل السندات الأخيرة</h3>
        <table class="rec-table">
            <thead>
                <tr>
                    <th>رقم</th>
                    <th>النوع</th>
                    <th>العميل</th>
                    <th>المبلغ</th>
                    <th>التاريخ</th>
                    <th>الإدارة</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $v_list = [];
                $res_l = $db->query("SELECT v.*, c.name as customer_name FROM vouchers v LEFT JOIN customers c ON v.customer_id = c.id ORDER BY v.id DESC LIMIT 30");
                if ($res_l) { while ($rw = $res_l->fetchArray(SQLITE3_ASSOC)) { $v_list[] = $rw; } }
                
                if (empty($v_list)):
                ?>
                    <tr><td colspan="6" style="padding: 10px; color: #64748B; font-size: 11px;">لا توجد سندات مسجلة حتى الآن.</td></tr>
                <?php else: foreach ($v_list as $vl): ?>
                    <tr>
                        <td style="font-weight: bold;" dir="ltr">#<?php echo $vl['id']; ?></td>
                        <td><?php echo ($vl['type'] == 'receipt') ? '📥 قبض' : '📤 صرف'; ?></td>
                        <td><?php echo htmlspecialchars($vl['customer_name'] ?? 'عميل عام'); ?></td>
                        <td style="color: <?php echo ($vl['type'] == 'receipt') ? '#15803D' : '#DC2626'; ?>; font-weight: bold;" dir="ltr"><?php echo number_format($vl['amount'], 2); ?> ر.ي</td>
                        <td style="font-size: 10px; color: #64748B;"><?php echo $vl['date']; ?></td>
                        <td>
                            <a href="treasury.php?edit_id=<?php echo $vl['id']; ?>" class="btn-sm btn-table-edit" title="تعديل">✏️ تعديل</a>
                            <a href="treasury.php?delete_id=<?php echo $vl['id']; ?>" class="btn-sm btn-table-del" onclick="return confirm('هل أنت متأكد من حذف هذا السند؟')" title="حذف">🗑️ حذف</a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

</div>
<?php include 'nav_menu.php'; ?>
</body>
</html>
