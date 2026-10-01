<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_role'])) { header("Location: login.php"); exit; }
$db = new SQLite3('vmax.db');

// تحديث هيكل جداول الفواتير لدعم تتبع السداد الفردي
$db->exec("CREATE TABLE IF NOT EXISTS invoice_payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    invoice_id INTEGER,
    customer_id INTEGER,
    amount REAL,
    date TEXT,
    note TEXT
);");
@$db->exec("ALTER TABLE invoices ADD COLUMN paid_amount REAL DEFAULT 0;");
@$db->exec("ALTER TABLE invoices ADD COLUMN status TEXT DEFAULT 'غير مسدد';");

$success_msg = "";
$error_msg = "";

// معالجة عملية السداد لفاتورة معينة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_invoice'])) {
    $invoice_id = intval($_POST['invoice_id']);
    $customer_id = intval($_POST['customer_id']);
    $pay_amount = floatval($_POST['pay_amount']);
    $note = trim($_POST['note']);
    $date = date('Y-m-d H:i:s');

    if ($pay_amount > 0) {
        // جلب بيانات الفاتورة
        $inv = $db->querySingle("SELECT net_amount, paid_amount FROM invoices WHERE id = $invoice_id", true);
        if ($inv) {
            $net = floatval($inv['net_amount']);
            $already_paid = floatval($inv['paid_amount']);
            $remaining = $net - $already_paid;

            if ($pay_amount <= $remaining) {
                $new_paid = $already_paid + $pay_amount;
                $new_status = ($new_paid >= $net) ? 'مسدد بالكامل' : 'مسدد جزئياً';

                // 1. تحديث الفاتورة
                $db->exec("UPDATE invoices SET paid_amount = $new_paid, status = '$new_status' WHERE id = $invoice_id");

                // 2. تسجيل السداد في جدول الحركات
                $stmt = $db->prepare("INSERT INTO invoice_payments (invoice_id, customer_id, amount, date, note) VALUES (:inv, :cust, :amt, :date, :note)");
                $stmt->bindValue(':inv', $invoice_id, SQLITE3_INTEGER);
                $stmt->bindValue(':cust', $customer_id, SQLITE3_INTEGER);
                $stmt->bindValue(':amt', $pay_amount, SQLITE3_FLOAT);
                $stmt->bindValue(':date', $date, SQLITE3_TEXT);
                $stmt->bindValue(':note', $note, SQLITE3_TEXT);
                $stmt->execute();

                // 3. خصم المبلغ من رصيد العميل الإجمالي
                $old_bal = $db->querySingle("SELECT balance FROM customers WHERE id = $customer_id");
                $new_bal = $old_bal - $pay_amount;
                $db->exec("UPDATE customers SET balance = $new_bal WHERE id = $customer_id");

                // 4. تسجيل سند قبض في الصندوق
                $v_note = "سداد جزئي/كلي لفاتورة مبيعات رقم #$invoice_id " . (!empty($note) ? "[$note]" : "");
                $v_stmt = $db->prepare("INSERT INTO vouchers (customer_id, type, amount, note, new_balance, date) VALUES (:cid, 'سند قبض', :amt, :note, :nbal, :date)");
                $v_stmt->bindValue(':cid', $customer_id, SQLITE3_INTEGER);
                $v_stmt->bindValue(':amt', $pay_amount, SQLITE3_FLOAT);
                $v_stmt->bindValue(':note', $v_note, SQLITE3_TEXT);
                $v_stmt->bindValue(':nbal', $new_bal, SQLITE3_FLOAT);
                $v_stmt->bindValue(':date', $date, SQLITE3_TEXT);
                $v_stmt->execute();

                $success_msg = "✅ تم سداد مبلغ " . number_format($pay_amount, 0) . " ر.ي بنجاح للفاتورة #$invoice_id!";
            } else {
                $error_msg = "⚠️ مبلغ السداد أكبر من المبلغ المتبقي على الفاتورة!";
            }
        }
    } else {
        $error_msg = "⚠️ أدخل مبلغ سداد صحيح أكبر من الصفر!";
    }
}

// جلب العملاء الذين لديهم فواتير
$customers = [];
$c_res = $db->query("SELECT DISTINCT c.id, c.name, c.balance FROM customers c JOIN invoices i ON c.id = i.customer_id WHERE i.net_amount > IFNULL(i.paid_amount, 0) ORDER BY c.name ASC");
while ($r = $c_res->fetchArray(SQLITE3_ASSOC)) { $customers[] = $r; }

$selected_cust_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : (isset($customers[0]['id']) ? $customers[0]['id'] : 0);

// جلب الفواتير غير المسددة بالكامل للعميل المحدد
$customer_invoices = [];
if ($selected_cust_id > 0) {
    $i_res = $db->query("SELECT * FROM invoices WHERE customer_id = $selected_cust_id AND net_amount > IFNULL(paid_amount, 0) ORDER BY id DESC");
    while ($row = $i_res->fetchArray(SQLITE3_ASSOC)) { $customer_invoices[] = $row; }
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
    <title>سداد الفواتير الفردية - أبو ضياء</title>
    <style>
        body { font-family: Tahoma, sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 10px; padding-bottom: 90px; direction: rtl; }
        .header { text-align: center; font-size: 15px; font-weight: 900; color: #38bdf8; margin-bottom: 12px; text-shadow: 0 0 10px rgba(56,189,248,0.5); }
        .glass-panel { background: linear-gradient(135deg, rgba(30, 41, 59, 0.95), rgba(15, 23, 42, 0.98)); backdrop-filter: blur(20px); border: 1.5px solid rgba(56, 189, 248, 0.4); border-radius: 18px; padding: 12px; margin-bottom: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.6); }
        .form-control { width: 100%; padding: 10px; background: #0f172a; color: #fff; border: 1px solid rgba(56, 189, 248, 0.4); border-radius: 10px; font-size: 12px; box-sizing: border-box; margin-bottom: 8px; }
        .invoice-card { background: rgba(15, 23, 42, 0.85); border: 1px solid rgba(56, 189, 248, 0.3); border-radius: 14px; padding: 12px; margin-bottom: 10px; display: flex; flex-direction: column; gap: 6px; }
        .btn-pay { background: linear-gradient(135deg, #22c55e, #16a34a); color: #fff; border: none; padding: 8px 14px; border-radius: 10px; font-weight: bold; cursor: pointer; font-size: 11px; box-shadow: 0 4px 12px rgba(34,197,94,0.4); }
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.9); backdrop-filter: blur(12px); display: none; align-items: center; justify-content: center; z-index: 99999; padding: 10px; }
        .modal-card { background: linear-gradient(135deg, #1e293b, #0f172a); border: 2px solid #38bdf8; border-radius: 20px; padding: 20px; width: 100%; max-width: 350px; box-shadow: 0 25px 50px rgba(0,0,0,0.9); }
    </style>
</head>
<body>
    <div class="header">💎 نظام سداد الفواتير الفردية - أبو ضياء</div>

    <?php if(!empty($success_msg)): ?>
        <div style="background: rgba(34,197,94,0.2); border: 1px solid #22c55e; color: #4ade80; padding: 10px; border-radius: 10px; font-size: 11px; margin-bottom: 10px; text-align:center; font-weight:bold;"><?php echo $success_msg; ?></div>
    <?php endif; ?>
    <?php if(!empty($error_msg)): ?>
        <div style="background: rgba(239,68,68,0.2); border: 1px solid #ef4444; color: #ef4444; padding: 10px; border-radius: 10px; font-size: 11px; margin-bottom: 10px; text-align:center; font-weight:bold;"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <div class="glass-panel">
        <label style="font-size: 11px; color: #38bdf8; font-weight: bold; display: block; margin-bottom: 4px;">👥 اختر العميل لعرض فواتيره غير المسددة:</label>
        <select class="form-control" onchange="window.location.href='pay_invoices.php?customer_id='+this.value">
            <?php if(empty($customers)): ?>
                <option value="">-- لا توجد فواتير معلقة على أي عميل --</option>
            <?php else: foreach($customers as $c): ?>
                <option value="<?php echo $c['id']; ?>" <?php echo ($selected_cust_id == $c['id']) ? 'selected' : ''; ?>>
                    👤 <?php echo htmlspecialchars($c['name']); ?> (الدين الإجمالي: <?php echo number_format($c['balance'], 0); ?> ر.ي)
                </option>
            <?php endforeach; endif; ?>
        </select>
    </div>

    <div class="glass-panel">
        <div style="font-size: 12px; font-weight: bold; color: #38bdf8; margin-bottom: 8px;">📄 الفواتير المعلقة والجزئية للعميل:</div>

        <?php if(empty($customer_invoices)): ?>
            <div style="text-align: center; color: #94a3b8; padding: 20px; font-size: 11px;">✨ ممتاز! هذا العميل مسدد لكافة فواتيره ولا توجد مبالغ معلقة.</div>
        <?php else: foreach($customer_invoices as $inv): 
            $net = floatval($inv['net_amount']);
            $paid = floatval($inv['paid_amount']);
            $remaining = $net - $paid;
            $percent = ($net > 0) ? min(100, round(($paid / $net) * 100)) : 0;
        ?>
            <div class="invoice-card">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="font-size: 12px; font-weight: bold; color: #f8fafc;"> فاتورة رقم #<?php echo $inv['id']; ?></div>
                    <div style="font-size: 10px; color: #94a3b8;"><?php echo $inv['date']; ?></div>
                </div>
                
                <div style="display: flex; justify-content: space-between; font-size: 11px; color: #cbd5e1;">
                    <span>إجمالي الفاتورة: <b style="color: #38bdf8; direction: ltr; display:inline-block;"><?php echo number_format($net, 0); ?> ر.ي</b></span>
                    <span>المدفوع: <b style="color: #4ade80; direction: ltr; display:inline-block;"><?php echo number_format($paid, 0); ?> ر.ي</b></span>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed rgba(56,189,248,0.3); padding-top: 6px; margin-top: 2px;">
                    <div>
                        <span style="font-size: 10px; color: #94a3b8;">المتبقي على الفاتورة:</span><br>
                        <span style="font-size: 13px; font-weight: 900; color: #ef4444; direction: ltr; display:inline-block;"><?php echo number_format($remaining, 0); ?> ر.ي</span>
                    </div>
                    <button class="btn-pay" onclick="openPayModal(<?php echo $inv['id']; ?>, <?php echo $remaining; ?>)">💳 تسديد هذه الفاتورة</button>
                </div>

                <!-- شريط التقدم للسداد -->
                <div style="background: #0f172a; border-radius: 6px; height: 6px; width: 100%; overflow: hidden; margin-top: 4px; border: 1px solid rgba(56,189,248,0.2);">
                    <div style="background: linear-gradient(90deg, #22c55e, #38bdf8); height: 100%; width: <?php echo $percent; ?>%;"></div>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>

    <!-- نافذة السداد المنبثقة الفاخرة -->
    <div id="payModal" class="modal-overlay">
        <div class="modal-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <div style="font-weight: bold; color: #38bdf8; font-size: 13px;">💳 سداد مبلغ للفاتورة #<span id="modalInvId"></span></div>
                <button onclick="closePayModal()" style="background: transparent; border: none; color: #ef4444; font-size: 16px; font-weight: bold; cursor: pointer;">✕</button>
            </div>

            <form method="POST">
                <input type="hidden" name="pay_invoice" value="1">
                <input type="hidden" name="invoice_id" id="form_inv_id">
                <input type="hidden" name="customer_id" value="<?php echo $selected_cust_id; ?>">

                <label style="font-size: 10px; color: #94a3b8; display: block; margin-bottom: 4px;">المبلغ المراد سداده (ر.ي):</label>
                <input type="number" step="any" name="pay_amount" id="form_pay_amount" class="form-control" placeholder="0" required style="font-size: 16px; font-weight: bold; color: #4ade80; text-align: center;">

                <label style="font-size: 10px; color: #94a3b8; display: block; margin-bottom: 4px;">ملاحظة أو سند (اختياري):</label>
                <input type="text" name="note" class="form-control" placeholder="مثل: نقداً / تحويل بنكي...">

                <button type="submit" style="width: 100%; background: linear-gradient(135deg, #22c55e, #16a34a); color: #fff; border: none; padding: 12px; border-radius: 12px; font-weight: bold; cursor: pointer; font-size: 13px; margin-top: 10px; box-shadow: 0 4px 15px rgba(34,197,94,0.4);">
                    ✓ تأكيد عملية السداد الفوري
                </button>
            </form>
        </div>
    </div>

    <script>
    function openPayModal(invId, maxAmount) {
        document.getElementById('modalInvId').innerText = invId;
        document.getElementById('form_inv_id').value = invId;
        document.getElementById('form_pay_amount').value = maxAmount;
        document.getElementById('form_pay_amount').max = maxAmount;
        document.getElementById('payModal').style.display = 'flex';
        document.getElementById('form_pay_amount').focus();
    }
    function closePayModal() {
        document.getElementById('payModal').style.display = 'none';
    }
    </script>

    <?php include "nav_menu.php"; ?>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>
</html>

