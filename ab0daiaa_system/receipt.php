<?php
$c_id = $_GET['customer_id'] ?? $_GET['c_id'] ?? 0;
$customer_data = ['name' => '', 'balance' => 0, 'debt' => 0, 'phone' => ''];
if ($c_id > 0 && file_exists('/data/data/com.termux/files/home/secure_dbs/vmax.db')) {
    $db = new SQLite3('/data/data/com.termux/files/home/secure_dbs/vmax.db');
    $stmt = $db->prepare('SELECT * FROM customers WHERE id = :id');
    $stmt->bindValue(':id', $c_id, SQLITE3_INTEGER);
    $res = $stmt->execute();
    if ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $customer_data = $row;
    }
}
?>
<?php
// تحديث أرصدة العملاء وتفاصيل الحساب بدقة عند حفظ السند
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_file = file_exists('/data/data/com.termux/files/home/secure_dbs/vmax.db') ? '/data/data/com.termux/files/home/secure_dbs/vmax.db' : 'database.db';
    if (file_exists($db_file)) {
        $db = new SQLite3($db_file);
        $c_id = $_POST['customer_id'] ?? $_POST['c_id'] ?? $_POST['client_id'] ?? 0;
        $amount = floatval($_POST['amount'] ?? $_POST['val'] ?? $_POST['sum'] ?? 0);
        $type = $_POST['type'] ?? $_POST['payment_type'] ?? 'receipt';
        
        if ($c_id > 0 && $amount > 0) {
            // تحديث الحقلين معاً (balance و debt) لضمان انعكاس العملية فوراً في تفاصيل العميل
            $db->exec("UPDATE customers SET balance = balance + $amount, debt = debt - $amount WHERE id = $c_id");
        }
    }
}
?>
<?php
// تعديل ذكي ومحمي لتحديث رصيد العميل في قاعدة البيانات vmax.db
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_file = file_exists('/data/data/com.termux/files/home/secure_dbs/vmax.db') ? '/data/data/com.termux/files/home/secure_dbs/vmax.db' : 'database.db';
    if (file_exists($db_file)) {
        $db = new SQLite3($db_file);
        $c_id = $_POST['customer_id'] ?? $_POST['c_id'] ?? $_POST['client_id'] ?? 0;
        $amount = floatval($_POST['amount'] ?? $_POST['val'] ?? $_POST['sum'] ?? 0);
        $type = $_POST['type'] ?? $_POST['payment_type'] ?? 'receipt'; // قبض أو صرف
        
        if ($c_id > 0 && $amount > 0) {
            // إذا كان سند قبض، يتم خصم المبلغ من مديونية العميل
            // إذا كان سند صرف، يتم إضافته أو التعامل معه حسب طبيعة الحساب
            $db->exec("UPDATE customers SET debt = debt - $amount WHERE id = $c_id");
        }
    }
}
?>
<?php
// receipt.php - سند القبض الرسمي المنبثق مع معاينة ومشاركة الواتساب (متوافق مع SQLite)
error_reporting(E_ALL);
ini_set('display_errors', 1);

$db = new SQLite3('/data/data/com.termux/files/home/secure_dbs/vmax.db');

// إنشاء جدول السندات وقواعد البيانات بأمان تام دون المساس بالمتاجر أو الجداول الأخرى
$db->exec("CREATE TABLE IF NOT EXISTS receipts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_name TEXT,
    amount REAL,
    payment_method TEXT,
    notes TEXT,
    created_at TEXT
);");

$success = false;
$receipt_data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = trim($_POST['customer_name'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $payment_method = trim($_POST['payment_method'] ?? 'نقداً');
    $notes = trim($_POST['notes'] ?? '');
    $date = date('Y-m-d H:i:s');

    if (!empty($customer_name) && $amount > 0) {
        $stmt = $db->prepare("INSERT INTO receipts (customer_name, amount, payment_method, notes, created_at) VALUES (:c, :amt, :m, :notes, :date)");
        $stmt->bindValue(':c', $customer_name, SQLITE3_TEXT);
        $stmt->bindValue(':amt', $amount, SQLITE3_FLOAT);
        $stmt->bindValue(':m', $payment_method, SQLITE3_TEXT);
        $stmt->bindValue(':notes', $notes, SQLITE3_TEXT);
        $stmt->bindValue(':date', $date, SQLITE3_TEXT);
        
        if ($stmt->execute()) {
            $success = true;
            $receipt_data = [
                'id' => $db->lastInsertRowID(),
                'customer_name' => $customer_name,
                'amount' => $amount,
                'payment_method' => $payment_method,
                'notes' => $notes,
                'created_at' => $date
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <link rel="manifest" href="manifest.json">
    <meta name='mobile-web-app-capable' content='yes'>
    <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js");}</script>
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>سند قبض رسمي - أبو ضياء</title>
    <style>
        * { box-sizing: border-box; font-family: 'Tahoma', Arial, sans-serif; }
        body { background: #FFFFFF; margin: 0; padding: 10px; display: flex; justify-content: center; flex-direction: column; align-items: center; padding-bottom: 90px; }
        .top-nav { width: 100%; max-width: 650px; background: #FFFFFF; padding: 8px 12px; border-radius: 6px; display: flex; justify-content: space-between; gap: 5px; margin-bottom: 10px; flex-wrap: wrap; }
        .nav-link { color: #fff; text-decoration: none; font-size: 11px; font-weight: bold; padding: 6px 10px; border-radius: 4px; background: #FFFFFF; }
        .nav-link.active { background: #FFFFFF; }
        .voucher-card { background: #FFFFFF; width: 100%; max-width: 650px; padding: 20px; border: 2px solid #000; border-radius: 6px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #000; border-bottom: 2px solid #000; padding-bottom: 8px; margin-top: 0; font-size: 16px; }
        .form-group { margin-bottom: 12px; }
        label { display: block; font-weight: bold; margin-bottom: 3px; color: #000; font-size: 12px; }
        select, input, textarea { width: 100%; padding: 8px; border: 2px solid #000; border-radius: 4px; font-size: 13px; font-weight: bold; background: #FFFFFF; }
        textarea { resize: vertical; height: 60px; }
        .btn-submit { background: #FFFFFF; color: #fff; border: none; width: 100%; padding: 12px; font-size: 15px; font-weight: bold; border-radius: 4px; cursor: pointer; margin-top: 5px; }
        
        /* تنسيق نافذة سند القبض الرسمي المنبثق (بنفس شكل الفاتورة) */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; z-index: 99999; padding: 15px; }
        .official-receipt { background: #FFFFFF; border: 3px solid #000; width: 100%; max-width: 450px; padding: 20px; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); text-align: right; direction: rtl; }
        .receipt-header { text-align: center; border-bottom: 2px dashed #000; padding-bottom: 10px; margin-bottom: 15px; }
        .receipt-row { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 8px; border-bottom: 1px solid #eee; padding-bottom: 4px; }
        .btn-whatsapp { background: #FFFFFF; color: #fff; border: none; width: 100%; padding: 10px; font-size: 13px; font-weight: bold; border-radius: 4px; cursor: pointer; margin-top: 10px; text-decoration: none; display: block; text-align: center; }
        .btn-close { background: #FFFFFF; color: #fff; border: none; width: 100%; padding: 8px; font-size: 12px; font-weight: bold; border-radius: 4px; cursor: pointer; margin-top: 6px; }
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
<!-- بطاقة تفاصيل العميل المضافة حديثاً -->
<div id="vmax-customer-card" style="background: linear-gradient(135deg, #0284c7, #0369a1); color: white; padding: 16px; border-radius: 12px; margin: 15px auto; max-width: 600px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); direction: rtl; text-align: right; font-family: Arial, sans-serif;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
        <h4 style="margin: 0; font-size: 16px;">👤 بيانات العميل المحدد</h4>
        <span id="card-client-id" style="background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 4px; font-size: 12px;">مفعل</span>
    </div>
    <div style="font-size: 15px; font-weight: bold; margin-bottom: 4px;" id="card-client-name">جاري تحميل اسم العميل...</div>
    <div style="font-size: 13px; opacity: 0.9;" id="card-client-balance">الرصيد / المديونية: يتم التحديث...</div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const urlParams = new URLSearchParams(window.location.search);
    const name = urlParams.get('name') || urlParams.get('customer');
    if (name) {
        const decodedName = decodeURIComponent(name);
        document.getElementById('card-client-name').innerText = "العميل: " + decodedName;
        
        // محاولة تعبئة حقل الاسم المخفي أو الظاهر في السند تلقائياً
        const nameInputs = document.querySelectorAll('input[name*="name"], input[name*="customer"], select[name*="customer"]');
        nameInputs.forEach(input => {
            if(input.tagName === 'INPUT') input.value = decodedName;
        });
    } else {
        document.getElementById('card-client-name').innerText = "تم فتح السند بدون تحديد عميل محدد من القائمة";
    }
});
</script>

    <div class="top-nav">
        <a href="index.php" class="nav-link">📄 فاتورة مبيعات</a>
        <a href="receipt.php" class="nav-link active">📥 سند قبض</a>
        <a href="payment.php" class="nav-link">📤 سند صرف</a>
        <a href="journal.php" class="nav-link">⚖️ قيد يومية</a>
        <a href="settings.php" class="nav-link">⚙️ الإعدادات</a>
    </div>

    <div class="voucher-card">
        <h2>📥 سند قبض نقدي رسمي</h2>

        <form method="POST">
            <div class="form-group">
                <label>اسم العميل / الدافع:</label>
                <input type="text" name="customer_name" placeholder="اسم المحل أو العميل..." required>
            </div>

            <div class="form-group">
                <label>المبلغ المقبوض (ريال يمني):</label>
                <input type="number" step="0.01" name="amount" placeholder="0.00" required>
            </div>

            <div class="form-group">
                <label>طريقة القبض:</label>
                <select name="payment_method">
                    <option value="نقداً">نقداً (صندوق)</option>
                    <option value="تحويل بنكي">تحويل بنكي / شبكة</option>
                    <option value="شيك">شيك</option>
                </select>
            </div>

            <div class="form-group">
                <label>البيان / تفاصيل السند:</label>
                <textarea name="notes">سداد قيمة حساب / فواتير مستحقة</textarea>
            </div>

            <button type="submit" class="btn-submit">💾 حفظ واعتماد سند القبض</button>
        </form>
    </div>

    <!-- نافذة سند القبض الرسمي المنبثق تلقائياً بعد الحفظ -->
    <?php if ($success && !empty($receipt_data)): ?>
    <div class="modal-overlay" id="receiptModal">
        <div class="official-receipt">
            <div class="receipt-header">
                <h3 style="margin: 0; font-size: 16px; color: #000;">مؤسسة أبو ضياء للتوزيع</h3>
                <p style="margin: 3px 0 0; font-size: 11px; color: #555;">سند قبض رسمي معتمد #<?php echo $receipt_data['id']; ?></p>
            </div>
            
            <div class="receipt-row">
                <span><b>التاريخ والوقت:</b></span>
                <span><?php echo $receipt_data['created_at']; ?></span>
            </div>
            <div class="receipt-row">
                <span><b>اسم العميل:</b></span>
                <span><b><?php echo htmlspecialchars($receipt_data['customer_name']); ?></b></span>
            </div>
            <div class="receipt-row">
                <span><b>المبلغ المقبوض:</b></span>
                <span style="color: #16a34a; font-weight: bold;"><?php echo number_format($receipt_data['amount'], 2); ?> ر.ي</span>
            </div>
            <div class="receipt-row">
                <span><b>طريقة الدفع:</b></span>
                <span><?php echo htmlspecialchars($receipt_data['payment_method']); ?></span>
            </div>
            <div class="receipt-row" style="flex-direction: column; gap: 2px;">
                <span><b>البيان:</b></span>
                <span style="color: #445566; font-size: 12px; background: #FFFFFF; padding: 5px; border-radius: 4px;"><?php echo htmlspecialchars($receipt_data['notes']); ?></span>
            </div>

            <button onclick="shareReceiptWhatsApp()" class="btn-whatsapp">💬 مشاركة السند (PDF/نص) عبر الواتساب</button>
        </div>
    </div>

    <script>
        function shareReceiptWhatsApp() {
            const text = "📥 *سند قبض رسمي - مؤسسة أبو ضياء*\n\n" +
                         "🔢 *رقم السند:* #<?php echo $receipt_data['id']; ?>\n" +
                         "👤 *اسم العميل:* <?php echo htmlspecialchars($receipt_data['customer_name'], ENT_QUOTES); ?>\n" +
                         "💰 *المبلغ المقبوض:* <?php echo number_format($receipt_data['amount'], 2); ?> ر.ي\n" +
                         "💳 *طريقة الدفع:* <?php echo htmlspecialchars($receipt_data['payment_method'], ENT_QUOTES); ?>\n" +
                         "📝 *البيان:* <?php echo htmlspecialchars($receipt_data['notes'], ENT_QUOTES); ?>\n" +
                         "📅 *التاريخ:* <?php echo $receipt_data['created_at']; ?>\n\n" +
                         "صادر عن: نظام الإدارة الشامل 🌟";
            window.open("https://wa.me/?text=" + encodeURIComponent(text), "_blank");
        }
    </script>
    <?php endif; ?>

    <?php if (file_exists('nav_bar.php')) include 'nav_bar.php'; ?>
    <?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); if(file_exists("global_footer.php")) include "global_footer.php"; } ?>
<!-- واجهة الطباعة والمشاركة الفورية للسندات -->
<div id="vmax-print-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:99999; justify-content:center; align-items:center; direction:rtl;">
    <div style="background:white; padding:25px; border-radius:16px; width:92%; max-width:420px; box-shadow:0 10px 30px rgba(0,0,0,0.2); text-align:right;">
        <div style="text-align:center; border-bottom:2px dashed #cbd5e1; padding-bottom:12px; margin-bottom:15px;">
            <h3 style="margin:0; color:#0f172a; font-size:18px;">🧾 سند معتمد - V-MAX PRO</h3>
            <p style="margin:4px 0 0; color:#64748b; font-size:12px;">تم حفظ السند بنجاح وجاهز للطباعة والمشاركة</p>
        </div>
        <div style="display:flex; flex-direction:column; gap:10px; margin-top:20px;">
            <button onclick="window.print()" style="background: #FFFFFF; color:white; border:none; padding:12px; border-radius:8px; font-weight:bold; font-size:14px; cursor:pointer;">🖨️ طباعة السند</button>
            <button onclick="window.print()" style="background: #FFFFFF; color:white; border:none; padding:12px; border-radius:8px; font-weight:bold; font-size:14px; cursor:pointer;">📥 حفظ PDF / طباعة</button>
        </div>
    </div>
</div>
<script>
// إظهار نافذة السند تلقائياً عند نجاح الحفظ أو وجود مؤشر في الصفحة
if (window.location.search.includes('success') || window.location.search.includes('saved') || window.location.search.includes('id=') || document.body.innerText.includes('تم الحفظ') || document.body.innerText.includes('بنجاح')) {
    window.addEventListener('load', function() {
        const modal = document.getElementById('vmax-print-modal');
        if(modal) {
            // إظهار النافذة بعد ثزء من الثانية لضمان الاستقرار
            setTimeout(() => { modal.style.display = 'flex'; }, 300);
        }
    });
}
</script>
<style>
@media print {
    .no-print { display: none !important; }
    body { background: white !important; }
}
.vmax-float-print {
    position: fixed; bottom: 25px; left: 25px; background: #FFFFFF; color: white;
    padding: 14px 22px; border-radius: 50px; font-weight: bold; box-shadow: 0 6px 20px rgba(2,132,199,0.4);
    z-index: 99999; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 15px; border: none;
}
.vmax-float-print:hover { background: #FFFFFF; }
</style>
<button onclick="window.print()" class="vmax-float-print no-print">
    🖨️ طباعة السند / حفظ PDF
</button>
</body>
</html>
