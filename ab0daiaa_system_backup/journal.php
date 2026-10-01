<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$db = new SQLite3('vmax.db', SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
$db->busyTimeout(5000);

// إنشاء جدول القيود المحاسبية إن لم يكن موجوداً
$db->exec("CREATE TABLE IF NOT EXISTS journal_entries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ref_no TEXT,
    description TEXT,
    debit_account TEXT,
    credit_account TEXT,
    amount REAL,
    date TEXT,
    user_name TEXT
);");

$settings = [];
$s_res = @$db->query("SELECT key, value FROM settings");
if ($s_res) { while ($s_row = $s_res->fetchArray(SQLITE3_ASSOC)) { $settings[$s_row['key']] = $s_row['value']; } }

$store_name = $settings['store_name'] ?? 'محل أبو ضياء';
$store_tagline = $settings['store_tagline'] ?? 'جوالات إكسسوارات وصيانة شاملة';
$store_address = $settings['store_address'] ?? 'سيئون - السوق العام';

$success_entry = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_entry'])) {
    $ref_no = trim($_POST['ref_no'] ?? 'Q-' . rand(1000, 9999));
    $description = trim($_POST['description'] ?? 'قيد محاسبي عام');
    $debit_account = trim($_POST['debit_account'] ?? 'الصندوق');
    $credit_account = trim($_POST['credit_account'] ?? 'رأس المال');
    $amount = floatval($_POST['amount'] ?? 0);
    $date = date('Y-m-d H:i:s');
    $user_name = $_SESSION['username'] ?? 'مدير النظام';

    if ($amount > 0) {
        $stmt = $db->prepare("INSERT INTO journal_entries (ref_no, description, debit_account, credit_account, amount, date, user_name) VALUES (:ref, :desc, :deb, :cred, :amt, :date, :user)");
        $stmt->bindValue(':ref', $ref_no, SQLITE3_TEXT);
        $stmt->bindValue(':desc', $description, SQLITE3_TEXT);
        $stmt->bindValue(':deb', $debit_account, SQLITE3_TEXT);
        $stmt->bindValue(':cred', $credit_account, SQLITE3_TEXT);
        $stmt->bindValue(':amt', $amount, SQLITE3_FLOAT);
        $stmt->bindValue(':date', $date, SQLITE3_TEXT);
        $stmt->bindValue(':user', $user_name, SQLITE3_TEXT);
        $stmt->execute();
        $entry_id = $db->lastInsertRowID();

        $success_entry = [
            'id' => $entry_id, 'ref_no' => $ref_no, 'description' => $description,
            'debit' => $debit_account, 'credit' => $credit_account, 'amount' => $amount,
            'date' => date('Y-m-d H:i'), 'user_name' => $user_name
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><meta name='mobile-web-app-capable' content='yes'>
<meta name='apple-mobile-web-app-capable' content='yes'>

    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>قيود اليومية - نظام هوت فون</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        body { font-family: Tahoma, sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 10px; padding-bottom: 110px; }
        .header { text-align: center; font-size: 15px; font-weight: 900; color: #38bdf8; margin-bottom: 10px; }
        .glass-panel { background: #1e293b; border: 1px solid #38bdf8; border-radius: 10px; padding: 12px; margin-bottom: 10px; }
        .form-control { width: 100%; padding: 10px; background: #0f172a; color: #fff; border: 1px solid #38bdf8; border-radius: 8px; box-sizing: border-box; margin-bottom: 8px; font-size: 12px;}
        .btn-submit { background: #8b5cf6; color: #fff; border: none; padding: 12px; border-radius: 8px; font-weight: bold; width: 100%; font-size: 13px; cursor: pointer; }
        
        .modal-overlay { position: fixed; inset: 0; background: rgba(15,23,42,0.96); z-index: 99999; padding: 10px; overflow-y: auto; display: flex; align-items: center; justify-content: center; }
        .modal-wrapper { width: 100%; max-width: 360px; margin: auto; }
        .receipt-card { background: #ffffff !important; color: #000000 !important; width: 100%; border-radius: 14px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); direction: rtl; border: 1px solid #cbd5e1; padding: 16px; box-sizing: border-box; margin-bottom: 15px; font-family: Tahoma, sans-serif !important; }
        .actions-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; margin-top: 10px; width: 100%; }
        .btn-act { border: none; padding: 12px; border-radius: 8px; font-weight: bold; font-size: 11px; color: #fff; cursor: pointer; text-align:center; text-decoration:none; display:flex; align-items:center; justify-content:center; }
    </style>
</head>
<body>
    <div class="header">⚖️ قيود اليومية المحاسبية - أبو ضياء</div>

    <?php if ($success_entry): ?>
    <div class="modal-overlay">
        <div class="modal-wrapper">
            <div class="receipt-card" id="journalCard" dir="rtl" translate="no">
                <table style="width: 100%; border-collapse: collapse; background: #1d4ed8 !important; color: #ffffff !important; border-radius: 10px; overflow: hidden; margin-bottom: 12px; direction: rtl;">
                    <tr>
                        <td style="padding: 12px 14px; text-align: right; vertical-align: middle; width: 62%;">
                            <div style="font-size: 14px; font-weight: 900; color: #ffffff !important;"><?php echo htmlspecialchars((string)$store_name); ?></div>
                            <div style="font-size: 9px; opacity: 0.95; margin-top: 2px; color: #e2e8f0 !important;"><?php echo htmlspecialchars((string)$store_tagline); ?></div>
                            <div style="font-size: 8.5px; opacity: 0.90; margin-top: 2px; color: #e2e8f0 !important;"><?php echo htmlspecialchars((string)$store_address); ?> - <span style="direction:ltr; display:inline-block;"><?php echo htmlspecialchars((string)$store_phone); ?></span></div>
                        </td>
                        <td style="padding: 12px 10px; text-align: center; vertical-align: middle; width: 38%; border-right: 1.5px solid rgba(255,255,255,0.3);">
                            <div style="font-size: 11px; font-weight: bold; color: #ffffff !important;">قيد يومية</div>
                            <div style="font-size: 10px; font-weight: bold; margin-top: 3px; color: #93c5fd !important;">رقم: <span style="direction:ltr; display:inline-block;"><?php echo $success_entry['id']; ?></span></div>
                        </td>
                    </tr>
                </table>

                <div style="background: #eab308 !important; height: 3.5px; width: 100%; border-radius: 2px; margin-bottom: 12px;"></div>

                <div style="padding: 2px; font-size: 11px;">
                    <div style="margin-bottom: 8px; font-weight: bold; color: #000;">
                        التاريخ: <span style="direction:ltr; display:inline-block;"><?php echo $success_entry['date']; ?></span>
                    </div>

                    <div style="border: 1.5px solid #0284c7; border-radius: 8px; padding: 8px; margin-bottom: 10px; background: #f0f9ff !important;">
                        <b>البيان / الشرح:</b> <span style="color: #0369a1; font-weight: bold;"><?php echo htmlspecialchars((string)$success_entry['description']); ?></span>
                    </div>

                    <table style="width: 100%; border-collapse: collapse; font-size: 11px; margin-bottom: 10px; text-align: center; background: #ffffff !important;">
                        <thead>
                            <tr style="background: #1e293b !important; color: #ffffff !important;">
                                <th style="padding: 6px; border: 1px solid #334155; color: #ffffff !important;">الطرف المدين</th>
                                <th style="padding: 6px; border: 1px solid #334155; color: #ffffff !important;">الطرف الدائن</th>
                                <th style="padding: 6px; border: 1px solid #334155; color: #ffffff !important;">المبلغ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="padding: 8px; border: 1px solid #cbd5e1; font-weight: bold; color: #1d4ed8;"><?php echo htmlspecialchars((string)$success_entry['debit']); ?></td>
                                <td style="padding: 8px; border: 1px solid #cbd5e1; font-weight: bold; color: #dc2626;"><?php echo htmlspecialchars((string)$success_entry['credit']); ?></td>
                                <td style="padding: 8px; border: 1px solid #cbd5e1; font-weight: bold; color: #059669; direction: ltr;"><?php echo number_format($success_entry['amount'], 0); ?></td>
                            </tr>
                        </tbody>
                    </table>

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
                <button onclick="window.print()" class="btn-act" style="background:#2563eb;">🖨️ طباعة القيد</button>
                <a href="journal.php" class="btn-act" style="background:#8b5cf6; grid-column: span 2;">➕ قيد يومية جديد</a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="glass-panel">
        <form method="POST">
            <input type="hidden" name="save_entry" value="1">
            
            <label style="font-size: 11px; color: #38bdf8; font-weight: bold;">شرح القيد (البيان):</label>
            <input type="text" name="description" class="form-control" required placeholder="مثال: اثبات رأس المال، تسوية نقدية...">

            <div style="display: flex; gap: 6px;">
                <div style="flex:1;">
                    <label style="font-size: 11px; color: #38bdf8; font-weight: bold;">الطرف المدين:</label>
                    <input type="text" name="debit_account" class="form-control" required value="الصندوق">
                </div>
                <div style="flex:1;">
                    <label style="font-size: 11px; color: #38bdf8; font-weight: bold;">الطرف الدائن:</label>
                    <input type="text" name="credit_account" class="form-control" required value="رأس المال">
                </div>
            </div>

            <label style="font-size: 11px; color: #38bdf8; font-weight: bold;">المبلغ (ر.ي):</label>
            <input type="number" step="any" name="amount" class="form-control" required placeholder="0">

            <button type="submit" class="btn-submit">💾 حفظ وطباعة قيد اليومية</button>
        </form>
    </div>

    <script>
    function downloadPDF() {
        const element = document.getElementById('journalCard');
        const opt = {
            margin: [2, 0, 2, 0],
            filename: 'Journal_<?php echo $success_entry['id'] ?? 1; ?>.pdf',
            image: { type: 'jpeg', quality: 1.0 },
            html2canvas: { scale: 4, useCORS: true },
            jsPDF: { unit: 'mm', format: [80, 140], orientation: 'portrait' }
        };
        html2pdf().set(opt).from(element).save();
    }
    </script>
    <?php if(file_exists('nav_menu.php')) { include 'nav_menu.php'; } ?>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>
</html>

