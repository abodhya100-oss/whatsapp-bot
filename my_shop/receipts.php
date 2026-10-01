<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$db = new SQLite3('vmax.db', SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
$db->busyTimeout(5000);

// إنشاء جدول سندات القبض إن لم يكن موجوداً
$db->exec("CREATE TABLE IF NOT EXISTS receipts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_id INTEGER,
    customer_name TEXT,
    amount REAL,
    note TEXT,
    date TEXT,
    user_name TEXT
);");

$settings = [];
$s_res = @$db->query("SELECT key, value FROM settings");
if ($s_res) { while ($s_row = $s_res->fetchArray(SQLITE3_ASSOC)) { $settings[$s_row['key']] = $s_row['value']; } }

$store_name = $settings['store_name'] ?? 'محل هوت فون';
$store_tagline = $settings['store_tagline'] ?? 'جوالات إكسسوارات وصيانة شاملة';
$store_address = $settings['store_address'] ?? 'سيئون - السوق العام';

$customers = [];
$cust_res = @$db->query("SELECT id, name, balance FROM customers ORDER BY name ASC");
if ($cust_res) { while ($row = $cust_res->fetchArray(SQLITE3_ASSOC)) { $customers[] = $row; } }

$success_receipt = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_receipt'])) {
    $customer_id = intval($_POST['customer_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $note = trim($_POST['note'] ?? 'سند قبض نقدى');
    $date = date('Y-m-d H:i:s');
    $user_name = $_SESSION['username'] ?? 'مدير النظام';

    $customer_name = 'عميل نقدي';
    if ($customer_id > 0) {
        $c_info = $db->querySingle("SELECT name FROM customers WHERE id = $customer_id", true);
        if ($c_info) { $customer_name = $c_info['name']; }
    }

    if ($amount > 0) {
        $db->exec("BEGIN TRANSACTION;");
        
        $stmt = $db->prepare("INSERT INTO receipts (customer_id, customer_name, amount, note, date, user_name) VALUES (:cid, :cname, :amt, :note, :date, :user)");
        $stmt->bindValue(':cid', $customer_id, SQLITE3_INTEGER);
        $stmt->bindValue(':cname', $customer_name, SQLITE3_TEXT);
        $stmt->bindValue(':amt', $amount, SQLITE3_FLOAT);
        $stmt->bindValue(':note', $note, SQLITE3_TEXT);
        $stmt->bindValue(':date', $date, SQLITE3_TEXT);
        $stmt->bindValue(':user', $user_name, SQLITE3_TEXT);
        $stmt->execute();
        $receipt_id = $db->lastInsertRowID();

        $old_balance = 0; $new_balance = 0;
        if ($customer_id > 0) {
            $cust_info = $db->querySingle("SELECT balance FROM customers WHERE id = $customer_id", true);
            $old_balance = $cust_info ? $cust_info['balance'] : 0;
            $new_balance = $old_balance - $amount; // القبض يخصم من الدين
            $db->exec("UPDATE customers SET balance = $new_balance WHERE id = $customer_id");
            $db->exec("INSERT INTO vouchers (customer_id, type, amount, note, new_balance, date) VALUES ($customer_id, 'سند قبض', $amount, '$note', $new_balance, '$date')");
        }
        
        $db->exec("COMMIT;");

        $success_receipt = [
            'id' => $receipt_id, 'customer' => $customer_name, 'amount' => $amount,
            'note' => $note, 'old_balance' => $old_balance, 'new_balance' => $new_balance,
            'date' => date('Y-m-d H:i'), 'user_name' => $user_name
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>سند قبض - نظام هوت فون</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        body { font-family: Tahoma, sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 10px; padding-bottom: 110px; }
        .header { text-align: center; font-size: 15px; font-weight: 900; color: #38bdf8; margin-bottom: 10px; }
        .glass-panel { background: #1e293b; border: 1px solid #38bdf8; border-radius: 10px; padding: 12px; margin-bottom: 10px; }
        .form-control { width: 100%; padding: 10px; background: #0f172a; color: #fff; border: 1px solid #38bdf8; border-radius: 8px; box-sizing: border-box; margin-bottom: 8px; font-size: 12px;}
        .btn-submit { background: #2563eb; color: #fff; border: none; padding: 12px; border-radius: 8px; font-weight: bold; width: 100%; font-size: 13px; cursor: pointer; }
        
        .modal-overlay { position: fixed; inset: 0; background: rgba(15,23,42,0.96); z-index: 99999; padding: 10px; overflow-y: auto; display: flex; align-items: center; justify-content: center; }
        .modal-wrapper { width: 100%; max-width: 360px; margin: auto; }
        .receipt-card { background: #ffffff !important; color: #000000 !important; width: 100%; border-radius: 14px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); direction: rtl; border: 1px solid #cbd5e1; padding: 16px; box-sizing: border-box; margin-bottom: 15px; font-family: Tahoma, sans-serif !important; }
        .actions-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; margin-top: 10px; width: 100%; }
        .btn-act { border: none; padding: 12px; border-radius: 8px; font-weight: bold; font-size: 11px; color: #fff; cursor: pointer; text-align:center; text-decoration:none; display:flex; align-items:center; justify-content:center; }
    </style>
</head>
<body>
    <div class="header">💵 سند قبض نقدى - أبو ضياء</div>

    <?php if ($success_receipt): ?>
    <div class="modal-overlay">
        <div class="modal-wrapper">
            <div class="receipt-card" id="receiptCard" dir="rtl" translate="no">
                <table style="width: 100%; border-collapse: collapse; background: #1d4ed8 !important; color: #ffffff !important; border-radius: 10px; overflow: hidden; margin-bottom: 12px; direction: rtl;">
                    <tr>
                        <td style="padding: 12px 14px; text-align: right; vertical-align: middle; width: 62%;">
                            <div style="font-size: 14px; font-weight: 900; color: #ffffff !important;"><?php echo htmlspecialchars((string)$store_name); ?></div>
                            <div style="font-size: 9px; opacity: 0.95; margin-top: 2px; color: #e2e8f0 !important;"><?php echo htmlspecialchars((string)$store_tagline); ?></div>
                            <div style="font-size: 8.5px; opacity: 0.90; margin-top: 2px; color: #e2e8f0 !important;"><?php echo htmlspecialchars((string)$store_address); ?> - <span style="direction:ltr; display:inline-block;"><?php echo htmlspecialchars((string)$store_phone); ?></span></div>
                        </td>
                        <td style="padding: 12px 10px; text-align: center; vertical-align: middle; width: 38%; border-right: 1.5px solid rgba(255,255,255,0.3);">
                            <div style="font-size: 11px; font-weight: bold; color: #ffffff !important;">سند قبض</div>
                            <div style="font-size: 10px; font-weight: bold; margin-top: 3px; color: #93c5fd !important;">رقم: <span style="direction:ltr; display:inline-block;"><?php echo $success_receipt['id']; ?></span></div>
                        </td>
                    </tr>
                </table>

                <div style="background: #eab308 !important; height: 3.5px; width: 100%; border-radius: 2px; margin-bottom: 12px;"></div>

                <div style="padding: 2px; font-size: 11px;">
                    <div style="margin-bottom: 8px; font-weight: bold; color: #000;">
                        التاريخ: <span style="direction:ltr; display:inline-block;"><?php echo $success_receipt['date']; ?></span>
                    </div>

                    <div style="border: 1.5px solid #0284c7; border-radius: 8px; padding: 8px; margin-bottom: 10px; background: #f0f9ff !important;">
                        استلمنا من الأخ / العميل: <b style="color: #0369a1; font-size: 12px;"><?php echo htmlspecialchars((string)$success_receipt['customer']); ?></b>
                    </div>

                    <div style="border: 1.5px solid #16a34a; border-radius: 8px; padding: 10px; margin-bottom: 10px; background: #f0fdf4 !important; text-align: center;">
                        <div style="font-size: 10px; color: #166534; font-weight: bold; margin-bottom: 2px;">المبلغ القبض:</div>
                        <div style="font-size: 18px; font-weight: 900; color: #15803d; direction: ltr;"><?php echo number_format($success_receipt['amount'], 0); ?> ر.ي</div>
                    </div>

                    <div style="border: 1.5px solid #cbd5e1; border-radius: 8px; padding: 8px; margin-bottom: 10px; background: #f8fafc !important;">
                        <b>البيان / الوقف:</b> <?php echo htmlspecialchars((string)$success_receipt['note']); ?>
                    </div>

                    <div style="border: 1.5px solid #cbd5e1; border-radius: 6px; padding: 6px 10px; font-size: 10px; margin-bottom: 12px; background: #fffbeb !important;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="text-align: right; color: #b45309; font-weight: bold;">إجمالي الرصيد المتبقي بالحساب:</td>
                                <td style="text-align: left; font-weight: bold; color: #b45309;"><span style="direction:ltr; display:inline-block;"><?php echo number_format($success_receipt['new_balance'], 0); ?> ر.ي</span></td>
                            </tr>
                        </table>
                    </div>

                    <div style="margin-top: 15px; padding: 12px; border-radius: 10px; background: #f8fafc !important; border: 1px solid #e2e8f0; position: relative;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-size: 9px; color: #64748b; font-weight: bold;">تطوير وتصميم الأنظمة</div>
                                <div style="font-size: 12px; font-weight: 900; color: #0f172a;">أبو ضياء للبرمجيات 💎</div>
                            </div>
                            <div style="background: #1e293b; border-radius: 8px; padding: 4px 10px; text-align: center; border: 1px solid #334155;">
                                <div style="font-size: 7px; color: #94a3b8; font-weight: 900;">VERIFIED</div>
                                <div style="font-size: 9px; color: #38bdf8; font-weight: 900;">ABU DHIA</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="actions-grid">
                <button onclick="downloadPDF()" id="btnPdf" class="btn-act" style="background:#16a34a;">📥 تحميل PDF</button>
                <button onclick="window.print()" class="btn-act" style="background:#2563eb;">🖨️ طباعة السند</button>
                <a href="receipts.php" class="btn-act" style="background:#8b5cf6; grid-column: span 2;">➕ سند قبض جديد</a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="glass-panel">
        <form method="POST">
            <input type="hidden" name="save_receipt" value="1">
            
            <label style="font-size: 11px; color: #38bdf8; font-weight: bold;">اختر العميل:</label>
            <select name="customer_id" class="form-control" required>
                <option value="">-- اختر العميل --</option>
                <?php foreach($customers as $c): ?>
                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?> (رصيد الدين: <?php echo number_format($c['balance']); ?>)</option>
                <?php endforeach; ?>
            </select>

            <label style="font-size: 11px; color: #38bdf8; font-weight: bold;">المبلغ المستلم (ر.ي):</label>
            <input type="number" step="any" name="amount" class="form-control" required placeholder="0">

            <label style="font-size: 11px; color: #38bdf8; font-weight: bold;">البيان / ملاحظات السند:</label>
            <input type="text" name="note" class="form-control" value="سند قبض نقدى" placeholder="البيان...">

            <button type="submit" class="btn-submit">💾 حفظ وطباعة سند القبض</button>
        </form>
    </div>

    <script>
    function downloadPDF() {
        const element = document.getElementById('receiptCard');
        const opt = {
            margin: [2, 0, 2, 0],
            filename: 'Receipt_<?php echo $success_receipt['id'] ?? 1; ?>.pdf',
            image: { type: 'jpeg', quality: 1.0 },
            html2canvas: { scale: 4, useCORS: true },
            jsPDF: { unit: 'mm', format: [80, 150], orientation: 'portrait' }
        };
        html2pdf().set(opt).from(element).save();
    }
    </script>
    <?php if(file_exists('nav_menu.php')) { include 'nav_menu.php'; } ?>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>
</html>

