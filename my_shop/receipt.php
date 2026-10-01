<?php
error_reporting(E_ALL & ~E_WARNING & ~E_DEPRECATED);
ini_set('display_errors', 0);

$conn = null;
if (file_exists('db.php')) { include_once 'db.php'; }
if (!isset($conn) || !$conn) { $conn = @new mysqli('localhost', 'root', '', 'vmax_db'); }

if ($conn && !$conn->connect_error) {
    $conn->query("CREATE TABLE IF NOT EXISTS receipts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        invoice_id INT NULL,
        amount DECIMAL(10,2) NOT NULL,
        payment_method VARCHAR(50) NOT NULL,
        notes TEXT,
        created_at DATETIME NOT NULL
    )");
}

$settings = [];
if ($conn && !$conn->connect_error) {
    $res_s = $conn->query("SELECT setting_key, setting_value FROM settings");
    if ($res_s) { while ($row = $res_s->fetch_assoc()) { $settings[$row['setting_key']] = $row['setting_value']; } }
}

$clients = [];
if ($conn && !$conn->connect_error) {
    $res = $conn->query("SELECT * FROM clients");
    if ($res) { while ($row = $res->fetch_assoc()) { $clients[] = $row; } }
}

// معالجة الحذف
if (isset($_GET['delete_id']) && $conn) {
    $del_id = intval($_GET['delete_id']);
    $conn->query("DELETE FROM receipts WHERE id = $del_id");
    header("Location: receipt.php");
    exit;
}

// معالجة التعديل
$edit_mode = false;
$edit_data = ['id' => '', 'client_id' => 1, 'invoice_id' => '', 'amount' => '', 'payment_method' => 'نقداً للصندوق', 'notes' => ''];
if (isset($_GET['edit_id']) && $conn) {
    $edit_id = intval($_GET['edit_id']);
    $res_e = $conn->query("SELECT * FROM receipts WHERE id = $edit_id");
    if ($res_e && $row_e = $res_e->fetch_assoc()) {
        $edit_mode = true;
        $edit_data = $row_e;
    }
}

$get_client_id = $edit_mode ? $edit_data['client_id'] : ($_GET['client_id'] ?? ($clients[0]['id'] ?? 1));
$get_invoice_id = $edit_mode ? $edit_data['invoice_id'] : ($_GET['invoice_id'] ?? '');
$get_amount = $edit_mode ? $edit_data['amount'] : ($_GET['amount'] ?? '');

$current_client_balance = 0;
if ($conn && !$conn->connect_error) {
    $sum_inv = 0;
    $res_inv = $conn->query("SELECT SUM(total_amount) as total_inv FROM invoices WHERE client_id = $get_client_id");
    if ($res_inv && $row_inv = $res_inv->fetch_assoc()) { $sum_inv = floatval($row_inv['total_inv'] ?? 0); }

    $sum_rec = 0;
    $exclude_sql = $edit_mode ? " AND id != " . $edit_data['id'] : "";
    $res_rec = $conn->query("SELECT SUM(amount) as total_rec FROM receipts WHERE client_id = $get_client_id $exclude_sql");
    if ($res_rec && $row_rec = $res_rec->fetch_assoc()) { $sum_rec = floatval($row_rec['total_rec'] ?? 0); }

    $current_client_balance = $sum_inv - $sum_rec;
}

$is_submitted = ($_SERVER['REQUEST_METHOD'] === 'POST');
$receipt_id = 0;
$client_name = "عميل نقدي";
$saved_amount = 0;
$saved_method = "";
$saved_notes = "";
$saved_inv = "";
$created_at = date('Y-m-d H:i:s');
$new_balance = $current_client_balance;

if ($is_submitted) {
    $client_id = intval($_POST['client_id']);
    $invoice_id_val = !empty($_POST['invoice_id']) ? intval($_POST['invoice_id']) : NULL;
    $invoice_id_sql = $invoice_id_val !== NULL ? $invoice_id_val : "NULL";
    $amount = floatval($_POST['amount']);
    $payment_method = $conn->real_escape_string($_POST['payment_method']);
    $notes = $conn->real_escape_string($_POST['notes']);
    $post_edit_id = intval($_POST['edit_id'] ?? 0);
    
    if ($conn && !$conn->connect_error) {
        if ($post_edit_id > 0) {
            $sql = "UPDATE receipts SET client_id = $client_id, invoice_id = $invoice_id_sql, amount = $amount, payment_method = '$payment_method', notes = '$notes' WHERE id = $post_edit_id";
            $conn->query($sql);
            $receipt_id = $post_edit_id;
        } else {
            $sql = "INSERT INTO receipts (client_id, invoice_id, amount, payment_method, notes, created_at) VALUES ($client_id, $invoice_id_sql, $amount, '$payment_method', '$notes', NOW())";
            $conn->query($sql);
            $receipt_id = $conn->insert_id;
        }

        $saved_amount = $amount;
        $saved_method = $payment_method;
        $saved_notes = $notes;
        $saved_inv = $invoice_id_val;

        $res_c = $conn->query("SELECT * FROM clients WHERE id = $client_id");
        if ($res_c && $row_c = $res_c->fetch_assoc()) {
            $client_name = $row_c['name'];
            if (!empty($row_c['phone'])) { $client_phone = preg_replace('/[^0-9]/', '', $row_c['phone']); }
        }

        $sum_inv_post = 0;
        $res_inv_p = $conn->query("SELECT SUM(total_amount) as total_inv FROM invoices WHERE client_id = $client_id");
        if ($res_inv_p && $row_ip = $res_inv_p->fetch_assoc()) { $sum_inv_post = floatval($row_ip['total_inv'] ?? 0); }

        $sum_rec_post = 0;
        $res_rec_p = $conn->query("SELECT SUM(amount) as total_rec FROM receipts WHERE client_id = $client_id");
        if ($res_rec_p && $row_rp = $res_rec_p->fetch_assoc()) { $sum_rec_post = floatval($row_rp['total_rec'] ?? 0); }
        
        $new_balance = $sum_inv_post - $sum_rec_post;
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <link rel="manifest" href="manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js");}</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سند قبض رسمي - أبو ضياء</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        * { box-sizing: border-box; font-family: 'Tahoma', Arial, sans-serif; }
        body { background: #e2e8f0; margin: 0; padding: 10px; display: flex; justify-content: center; flex-direction: column; align-items: center; }
        .top-nav { width: 100%; max-width: 650px; background: #000; padding: 8px 12px; border-radius: 6px; display: flex; justify-content: space-between; gap: 5px; margin-bottom: 10px; flex-wrap: wrap; }
        .nav-link { color: #fff; text-decoration: none; font-size: 11px; font-weight: bold; padding: 6px 10px; border-radius: 4px; background: #333; }
        .nav-link.active { background: #2563eb; }
        
        .voucher-card { background: #fff; width: 100%; max-width: 650px; padding: 20px; border: 2px solid #000; border-radius: 6px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); display: <?php echo $is_submitted ? 'none' : 'block'; ?>; }
        h2 { text-align: center; color: #000; border-bottom: 2px solid #000; padding-bottom: 8px; margin-top: 0; font-size: 16px; }
        .balance-box { background: #fef08a; border: 2px solid #ca8a04; padding: 10px; border-radius: 4px; text-align: center; font-weight: bold; margin-bottom: 15px; font-size: 14px; color: #713f12; }
        .form-group { margin-bottom: 12px; }
        label { display: block; font-weight: bold; margin-bottom: 3px; color: #000; font-size: 12px; }
        select, input, textarea { width: 100%; padding: 8px; border: 2px solid #000; border-radius: 4px; font-size: 13px; font-weight: bold; background: #fff; }
        textarea { resize: vertical; height: 60px; }
        .btn-submit { background: #000; color: #fff; border: none; width: 100%; padding: 12px; font-size: 15px; font-weight: bold; border-radius: 4px; cursor: pointer; margin-top: 5px; }
        .btn-back { display: block; background: #475569; color: #fff; text-align: center; padding: 10px; border-radius: 4px; text-decoration: none; font-size: 13px; font-weight: bold; margin-top: 8px; }

        .receipt-sheet { background: #ffffff; width: 100%; max-width: 650px; padding: 18px; border: 2px solid #000; box-shadow: 0 4px 15px rgba(0,0,0,0.1); display: <?php echo $is_submitted ? 'block' : 'none'; ?>; }
        .header-top { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #000; padding-bottom: 5px; margin-bottom: 8px; font-size: 11px; color: #000; font-weight: bold; }
        .receipt-type { background: #000; color: #fff; text-align: center; padding: 4px 15px; font-size: 14px; font-weight: bold; display: inline-block; margin: 0 auto 10px auto; border-radius: 3px; }
        .center-wrapper { text-align: center; }
        .receipt-info { font-size: 13px; font-weight: bold; line-height: 1.9; border: 2px solid #000; padding: 12px; margin-bottom: 15px; background: #fafafa; }
        .receipt-info span { color: #b91c1c; }
        
        .action-bar { display: flex; justify-content: center; gap: 8px; margin-top: 15px; flex-wrap: wrap; }
        .btn { padding: 8px 14px; border: none; border-radius: 4px; font-size: 12px; font-weight: bold; cursor: pointer; color: white; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 5px; }
        .btn-print { background: #000; }
        .btn-pdf { background: #dc2626; }
        .btn-whatsapp { background: #128c7e; }
        .btn-new { background: #475569; }

        .receipts-list-container { background: #fff; width: 100%; max-width: 650px; padding: 15px; border: 2px solid #000; border-radius: 6px; margin-top: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); display: <?php echo $is_submitted ? 'none' : 'block'; ?>; }
        .rec-table { width: 100%; border-collapse: collapse; font-size: 11px; margin-top: 8px; }
        .rec-table th, .rec-table td { border: 1px solid #000; padding: 6px; text-align: center; }
        .rec-table th { background: #000; color: #fff; }
        .btn-sm { padding: 4px 8px; font-size: 10px; border-radius: 3px; text-decoration: none; font-weight: bold; color: #fff; display: inline-block; }
        .btn-edit { background: #ca8a04; }
        .btn-del { background: #dc2626; }
        .btn-remind { background: #0284c7; }

        @media print {
            body { background: #fff !important; padding: 0 !important; margin: 0 !important; }
            .receipt-sheet { border: 2px solid #000 !important; box-shadow: none !important; width: 100% !important; display: block !important; }
            .voucher-card, .top-nav, .action-bar, .receipts-list-container { display: none !important; }
            * { color: #000 !important; font-weight: bold !important; }
            .receipt-type { background: #000 !important; color: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

<div class="top-nav">
    <a href="index.php" class="nav-link">📄 فاتورة مبيعات</a>
    <a href="client_ledger.php" class="nav-link">👥 العملاء والفواتير</a>
    <a href="receipt.php" class="nav-link active">📥 سند قبض</a>
    <a href="payment.php" class="nav-link">📤 سند صرف</a>
    <a href="journal.php" class="nav-link">⚖️ قيد يومية</a>
    <a href="settings.php" class="nav-link">⚙️ الإعدادات</a>
</div>

<div class="voucher-card">
    <h2>📥 <?php echo $edit_mode ? 'تعديل سند قبض رقم #' . $edit_data['id'] : 'سند قبض نقدي / صرافة للعميل'; ?></h2>
    
    <div class="balance-box">
        💰 إجمالي المديونية الحالية على العميل: <span style="color: #b91c1c; font-size: 16px;"><?php echo number_format($current_client_balance, 2); ?> ر.ي</span>
    </div>

    <form method="POST">
        <input type="hidden" name="edit_id" value="<?php echo $edit_mode ? $edit_data['id'] : 0; ?>">
        <div class="form-group">
            <label>اختر العميل:</label>
            <select name="client_id" onchange="window.location.href='receipt.php?client_id='+this.value" required>
                <?php foreach ($clients as $c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php echo ($c['id'] == $get_client_id) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>رقم الفاتورة المرتبطة (اختياري):</label>
            <input type="text" name="invoice_id" value="<?php echo htmlspecialchars($get_invoice_id); ?>" placeholder="مثال: 38">
        </div>

        <div class="form-group">
            <label>المبلغ المقبوض (ريال يمني):</label>
            <input type="number" step="0.01" name="amount" value="<?php echo htmlspecialchars($get_amount); ?>" placeholder="0.00" required>
        </div>

        <div class="form-group">
            <label>طريقة القبض:</label>
            <select name="payment_method">
                <option value="نقداً للصندوق" <?php echo ($edit_mode && $edit_data['payment_method']=='نقداً للصندوق')?'selected':''; ?>>نقداً (الصندوق)</option>
                <option value="تحويل عبر العمقي" <?php echo ($edit_mode && $edit_data['payment_method']=='تحويل عبر العمقي')?'selected':''; ?>>تحويل عبر شبكة العمقي</option>
                <option value="حوالة بنكية" <?php echo ($edit_mode && $edit_data['payment_method']=='حوالة بنكية')?'selected':''; ?>>حوالة بنكية</option>
            </select>
        </div>

        <div class="form-group">
            <label>البيان / ملاحظات السند:</label>
            <textarea name="notes"><?php echo $edit_mode ? htmlspecialchars($edit_data['notes']) : 'سداد قيمة الفاتورة رقم #' . htmlspecialchars($get_invoice_id ?: 'عام'); ?></textarea>
        </div>

        <button type="submit" class="btn-submit">💾 حفظ واعتماد سند القبض وخصم الرصيد</button>
        <a href="client_ledger.php?client_id=<?php echo $get_client_id; ?>" class="btn-back">⬅ العودة إلى سجل فواتير العميل</a>
    </form>
</div>

<!-- جدول إدارة سندات القبض السابقة -->
<div class="receipts-list-container">
    <h3 style="font-size: 14px; margin: 0 0 8px 0; border-bottom: 1px solid #000; padding-bottom: 5px;">📋 سجل سندات القبض السابقة للعميل</h3>
    <table class="rec-table">
        <thead>
            <tr>
                <th>رقم</th>
                <th>المبلغ</th>
                <th>الطريقة</th>
                <th>البيان</th>
                <th>التاريخ</th>
                <th>إدارة</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $rec_list = [];
            if ($conn && !$conn->connect_error) {
                $res_l = $conn->query("SELECT * FROM receipts WHERE client_id = $get_client_id ORDER BY id DESC");
                if ($res_l) { while ($rw = $res_l->fetch_assoc()) { $rec_list[] = $rw; } }
            }
            if (empty($rec_list)):
            ?>
            <tr><td colspan="6" style="padding: 10px; color: #666;">لا توجد سندات قبض مسجلة لهذا العميل.</td></tr>
            <?php else: foreach ($rec_list as $rl): ?>
            <tr>
                <td>#<?php echo $rl['id']; ?></td>
                <td style="color: #15803d; font-weight: bold;"><?php echo number_format($rl['amount'], 2); ?> ر.ي</td>
                <td><?php echo htmlspecialchars($rl['payment_method']); ?></td>
                <td style="text-align: right; padding-right: 4px;"><?php echo htmlspecialchars($rl['notes']); ?></td>
                <td><?php echo $rl['created_at']; ?></td>
                <td>
                    <a href="receipt.php?edit_id=<?php echo $rl['id']; ?>" class="btn-sm btn-edit" title="تعديل">✏️</a>
                    <a href="receipt.php?delete_id=<?php echo $rl['id']; ?>" class="btn-sm btn-del" onclick="return confirm('هل أنت متأكد من حذف هذا السند؟')" title="حذف">🗑️</a>
                    <a href="https://wa.me/<?php echo $client_phone; ?>?text=<?php echo urlencode('تذكير بسند قبض رقم #' . $rl['id'] . ' بمبلغ ' . number_format($rl['amount'], 2) . ' ر.ي - شركة أبو ضياء للتوزيع'); ?>" target="_blank" class="btn-sm btn-remind" title="تذكير واتساب">🔔</a>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<?php if ($is_submitted): ?>
    <div class="receipt-sheet" id="receiptContent">
        <div class="header-top">
            <div>
                <div style="font-size: 15px;"><?php echo htmlspecialchars($settings['company_name'] ?? 'أبو ضياء للتوزيع'); ?></div>
                <div>حسابنا لدى <?php echo htmlspecialchars($settings['bank_name'] ?? 'العمقي'); ?> / <?php echo htmlspecialchars($settings['manager_name'] ?? ''); ?></div>
            </div>
            <div style="text-align: left;">
                <div><?php echo htmlspecialchars($settings['city'] ?? ''); ?></div>
                <div>تلفون: <?php echo htmlspecialchars($settings['phone'] ?? ''); ?></div>
            </div>
        </div>

        <div class="center-wrapper">
            <div class="receipt-type">سند قبض رسمي معتمد</div>
        </div>

        <div class="receipt-info">
            <div>📄 رقم السند: <span id="recIdText">#<?php echo $receipt_id; ?></span></div>
            <div>📅 التاريخ والوقت: <?php echo $created_at; ?></div>
            <div>👤 استلمنا من العميل: <span id="clientNameText"><?php echo htmlspecialchars($client_name); ?></span></div>
            <?php if (!empty($saved_inv)): ?>
                <div>🔗 مرتبط بالفاتورة رقم: <span>#<?php echo $saved_inv; ?></span></div>
            <?php endif; ?>
            <div>💰 المبلغ المقبوض: <span id="amountText" style="font-size: 16px; font-weight: 900;"><?php echo number_format($saved_amount, 2); ?> ر.ي</span></div>
            <div>💳 طريقة القبض: <?php echo htmlspecialchars($saved_method); ?></div>
            <div>📝 البيان: <?php echo htmlspecialchars($saved_notes); ?></div>
            <div style="margin-top: 5px; border-top: 1px dashed #000; padding-top: 5px;">📊 الرصيد المتبقي على العميل: <span style="color: #b91c1c;"><?php echo number_format($new_balance, 2); ?> ر.ي</span></div>
        </div>

        <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 12px; margin-top: 20px; padding: 0 20px;">
            <div>توقيع المستلم: <?php echo htmlspecialchars($settings['manager_name'] ?? ''); ?></div>
            <div>ختم الصندوق / معتمد</div>
        </div>

        <div class="action-bar">
            <button onclick="window.print()" class="btn btn-print">🖨️ طباعة / حفظ PDF</button>
            <button onclick="downloadPDFFile()" class="btn btn-pdf">📑 تنزيل ملف PDF</button>
            <button onclick="sendWhatsAppReceiptSafe()" class="btn btn-whatsapp">💬 واتساب</button>
            <a href="receipt.php?client_id=<?php echo $client_id; ?>" class="btn btn-new">➕ سند جديد</a>
        </div>
    </div>

    <script>
        // تنزيل ملف PDF بشكل مباشر وآمن بدون استخدام واجهة المشاركة التي تسبب الاختفاء
        function downloadPDFFile() {
            const element = document.getElementById('receiptContent');
            const opt = {
                margin:       [8, 8, 8, 8],
                filename:     'سند_قبض_<?php echo $receipt_id; ?>.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true },
                jsPDF:        { unit: 'mm', format: 'a5', orientation: 'portrait' }
            };

            html2pdf().set(opt).from(element).save();
        }

        function sendWhatsAppReceiptSafe() {
            var phone = "<?php echo $client_phone; ?>";
            var compName = "<?php echo addslashes($settings['company_name'] ?? 'أبو ضياء للتوزيع'); ?>";
            var recId = "<?php echo $receipt_id; ?>";
            var clientName = "<?php echo addslashes($client_name); ?>";
            var amountVal = "<?php echo number_format($saved_amount, 2); ?>";
            var methodVal = "<?php echo addslashes($saved_method); ?>";
            var invVal = "<?php echo $saved_inv; ?>";
            var notesVal = "<?php echo addslashes($saved_notes); ?>";
            var balanceVal = "<?php echo number_format($new_balance, 2); ?>";
            var dateVal = "<?php echo $created_at; ?>";

            var msg = "*🌟 " + compName + " 🌟*\n" +
                      "──────────────────\n" +
                      "📥 *سند قبض رسمي معتمد #" + recId + "*\n" +
                      "──────────────────\n" +
                      "👤 *العميل:* " + clientName + "\n" +
                      "💰 *المبلغ المقبوض:* " + amountVal + " ر.ي\n" +
                      "💳 *طريقة القبض:* " + methodVal + "\n";

            if (invVal) {
                msg += "🔗 *مرتبط بالفاتورة:* #" + invVal + "\n";
            }

            msg += "📝 *البيان:* " + notesVal + "\n" +
                   "──────────────────\n" +
                   "📊 *الرصيد المتبقي عليكم:* " + balanceVal + " ر.ي\n" +
                   "📅 *التاريخ:* " + dateVal;

            window.open("https://wa.me/" + phone + "?text=" + encodeURIComponent(msg), "_blank");
        }
    </script>
<?php endif; ?>


<?php include 'nav_bar.php'; ?>
<?php include "nav_menu.php"; ?>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>
</html>

