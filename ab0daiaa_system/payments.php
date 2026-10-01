<?php
// payments.php - نظام سندات القبض والصرف الشامل - V-MAX PRO
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'auth.php';

$db_file = '/data/data/com.termux/files/home/secure_dbs/vmax.db';
if(!file_exists($db_file)) { $db_file = 'database.db'; }
$db = new SQLite3($db_file);
$db->busyTimeout(5000);

// التأكد من وجود جدول السندات
$db->exec("CREATE TABLE IF NOT EXISTS vouchers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_id INTEGER,
    type TEXT,
    amount REAL,
    note TEXT,
    new_balance REAL,
    date TEXT
);");

$settings = [];
$s_res = @$db->query("SELECT key, value FROM settings");
if ($s_res) { while ($s_row = $s_res->fetchArray(SQLITE3_ASSOC)) { if(isset($s_row['key'])) { $settings[$s_row['key']] = $s_row['value']; } } }

$store_name = $settings['store_name'] ?? 'محلات أبوضياء للتوزيع';
$store_tagline = $settings['store_tagline'] ?? 'جوالات إكسسوارات وصيانة شاملة';

// استقبال معرف العميل من الرابط إن وجد
$selected_customer_id = intval($_GET['customer_id'] ?? $_GET['c_id'] ?? 0);

$customers = [];
$cust_res = @$db->query("SELECT id, name, balance, debt FROM customers ORDER BY name ASC");
if ($cust_res) { while ($row = $cust_res->fetchArray(SQLITE3_ASSOC)) { $customers[] = $row; } }

$success_msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_voucher'])) {
    $customer_id = intval($_POST['customer_id'] ?? 0);
    $type = trim($_POST['type'] ?? 'receipt'); // receipt أو payment
    $amount = floatval($_POST['amount'] ?? 0);
    $note = trim($_POST['note'] ?? 'سند قبض / صرف');
    $date = date('Y-m-d H:i:s');

    if ($amount > 0 && $customer_id > 0) {
        $db->exec("BEGIN TRANSACTION;");
        
        $cust_info = @$db->querySingle("SELECT balance, debt FROM customers WHERE id = $customer_id", true);
        $current_debt = $cust_info ? floatval($cust_info['debt']) : 0;
        $current_balance = $cust_info ? floatval($cust_info['balance']) : 0;

        if ($type === 'receipt') {
            // سند قبض (يقلل الدين)
            $new_debt = $current_debt - $amount;
            $new_balance = $current_balance - $amount;
            $db->exec("UPDATE customers SET debt = $new_debt, balance = $new_balance WHERE id = $customer_id");
            $db->exec("INSERT INTO vouchers (customer_id, type, amount, note, new_balance, date) VALUES ($customer_id, 'سند قبض', $amount, '$note', $new_balance, '$date')");
        } else {
            // سند صرف (يزيد الدين أو يدفع للمورد)
            $new_debt = $current_debt + $amount;
            $new_balance = $current_balance + $amount;
            $db->exec("UPDATE customers SET debt = $new_debt, balance = $new_balance WHERE id = $customer_id");
            $db->exec("INSERT INTO vouchers (customer_id, type, amount, note, new_balance, date) VALUES ($customer_id, 'سند صرف', $amount, '$note', $new_balance, '$date')");
        }

        $db->exec("COMMIT;");
        $success_msg = "تم حفظ وإصدار السند بنجاح! المبلغ: " . number_format($amount, 2) . " ر.ي";
    }
}
$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'>
    <title>سندات القبض والصرف - V-MAX PRO</title>
    <style>
        :root {
            --bg-deep: #F1F5F9;
            --card-bg: #FFFFFF;
            --text-main: #0F172A;
            --text-muted: #64748B;
            --primary-blue: #2563EB;
            --success: #10B981;
            --danger: #E11D48;
            --border-color: #CBD5E1;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Tahoma', sans-serif;
            background: #FFFFFF;
            color: var(--text-main);
            margin: 0;
            padding: 15px;
            padding-bottom: 110px;
        }
        .header-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(241, 245, 249, 0.9));
            border: 1.5px solid var(--primary-blue);
            border-radius: 20px;
            padding: 18px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.06);
            margin-bottom: 15px;
        }
        .header-card h1 { margin: 0 0 5px 0; font-size: 18px; color: var(--primary-blue); }
        .header-card p { margin: 0; font-size: 11px; color: var(--text-muted); }

        .glass-panel {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
        }
        .form-control {
            width: 100%;
            padding: 11px;
            background: #FFFFFF;
            border: 1px solid var(--border-color);
            color: var(--text-main);
            border-radius: 10px;
            font-size: 12px;
            box-sizing: border-box;
            margin-bottom: 10px;
            outline: none;
        }
        .form-control:focus { border-color: var(--primary-blue); background: #FFFFFF; }
        .btn-submit {
            background: linear-gradient(135deg, var(--primary-blue), #1D4ED8);
            color: #fff;
            border: none;
            padding: 12px;
            border-radius: 10px;
            font-weight: bold;
            width: 100%;
            font-size: 13px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(37,99,235,0.25);
        }
        .alert-success {
            background: #FFFFFF;
            color: #15803D;
            border: 1px solid #BBF7D0;
            padding: 12px;
            border-radius: 10px;
            text-align: center;
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 15px;
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

<div class="header-card">
    <h1>💰 سندات القبض والصرف</h1>
    <p>إدارة حركة النقدية والتحويلات والمدفوعات بدقة عالية</p>
</div>

<?php if ($success_msg): ?>
<div class="alert-success">
    <?php echo $success_msg; ?>
</div>
<?php endif; ?>

<div class="glass-panel">
    <form method="POST">
        <input type="hidden" name="save_voucher" value="1">
        
        <label style="font-size: 11px; color: var(--primary-blue); font-weight: bold; display: block; margin-bottom: 4px;">نوع السند:</label>
        <select name="type" class="form-control" id="voucherType" onchange="updateDebtDisplay()">
            <option value="receipt">🟢 سند قبض (استلام من عميل)</option>
            <option value="payment">🔴 سند صرف (دفع / سحب)</option>
        </select>

        <label style="font-size: 11px; color: var(--text-muted); font-weight: bold; display: block; margin-bottom: 4px;">الطرف (العميل):</label>
        <select name="customer_id" class="form-control" id="customerSelect" onchange="updateDebtDisplay()" required>
            <option value="">-- اختر العميل --</option>
            <?php foreach($customers as $c): 
                $selected = ($selected_customer_id == $c['id']) ? 'selected' : '';
            ?>
            <option value="<?php echo $c['id']; ?>" data-debt="<?php echo $c['debt'] ?? 0; ?>" <?php echo $selected; ?>>
                <?php echo htmlspecialchars($c['name']); ?> (الديون: <?php echo number_format($c['debt'] ?? 0, 2); ?> ر.ي)
            </option>
            <?php endforeach; ?>
        </select>

        <div style="background: #FFFFFF; border: 1px solid #FECDD3; border-radius: 12px; padding: 12px; text-align: center; margin-bottom: 12px;">
            <div style="font-size: 11px; color: var(--danger); font-weight: bold;">إجمالي الدين الحالي للعميل:</div>
            <div id="customerDebtDisplay" style="font-size: 18px; font-weight: 900; color: var(--danger); margin-top: 3px;">0.00 ر.ي</div>
        </div>

        <label style="font-size: 11px; color: var(--primary-blue); font-weight: bold; display: block; margin-bottom: 4px;">المبلغ (ر.ي):</label>
        <input type="number" step="any" name="amount" class="form-control" required placeholder="0.00">

        <label style="font-size: 11px; color: var(--primary-blue); font-weight: bold; display: block; margin-bottom: 4px;">البيان / التفاصيل:</label>
        <input type="text" name="note" class="form-control" placeholder="سبب القبض أو الصرف...">

        <button type="submit" class="btn-submit">💾 حفظ وإصدار السند</button>
    </form>
</div>

<script>
function updateDebtDisplay() {
    const select = document.getElementById('customerSelect');
    const selectedOption = select.options[select.selectedIndex];
    const debt = selectedOption.getAttribute('data-debt') || 0;
    document.getElementById('customerDebtDisplay').innerText = Number(debt).toLocaleString('en-US', {minimumFractionDigits: 2}) + ' ر.ي';
}
// تحديث القيمة عند تحميل الصفحة إذا كان العميل محدداً مسبقاً
window.onload = function() {
    updateDebtDisplay();
};
</script>

</body>
</html>
