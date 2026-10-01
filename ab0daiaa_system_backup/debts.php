<?php
// debts.php - دفتر الديون وحسابات العملاء الذكي - V-MAX PRO
require_once 'auth.php';
require_admin();
$db_file = __DIR__ . '/vmax.db';
$db = new SQLite3($db_file);

// التأكد من وجود الجداول الأساسية
$db->exec("CREATE TABLE IF NOT EXISTS customers (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, phone TEXT, address TEXT)");
$db->exec("CREATE TABLE IF NOT EXISTS invoices (id INTEGER PRIMARY KEY AUTOINCREMENT, customer_id INTEGER, grand_total REAL, paid_amount REAL DEFAULT 0, payment_type TEXT DEFAULT 'cash', date DATETIME DEFAULT CURRENT_TIMESTAMP)");                          
$db->exec("CREATE TABLE IF NOT EXISTS receipts (id INTEGER PRIMARY KEY AUTOINCREMENT, customer_id INTEGER, amount REAL)");

if (isset($_POST['action']) && $_POST['action'] === 'add_receipt') {
    $c_id = intval($_POST['customer_id']);
    $amount = floatval($_POST['amount']);
    $note = trim($_POST['note'] ?? 'دفعة من الحساب');

    if ($amount > 0 && $c_id > 0) {
        $stmt = $db->prepare("INSERT INTO receipts (customer_id, amount, note) VALUES (:cid, :amt, :note)");
        $stmt->bindValue(':cid', $c_id, SQLITE3_INTEGER);
        $stmt->bindValue(':amt', $amount, SQLITE3_FLOAT);
        $stmt->bindValue(':note', $note, SQLITE3_TEXT);
        $stmt->execute();
        $receipt_id = $db->lastInsertRowID();
        echo "<script>alert('✅ تم تسجيل الدفعة بنجاح!'); window.location.href='view_receipt.php?id=$receipt_id';</script>";
        exit;
    }
}

$customers = [];
$total_market_debts = 0;

// جلب كافة العملاء وحساب الديون المتبقية الفعلي لكل عميل
$res = $db->query("SELECT * FROM customers ORDER BY name ASC");
if ($res) {
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $c_id = $row['id'];
        
        // حساب إجمالي المتبقي الفعلي من فواتير هذا العميل
        $inv_res = $db->query("SELECT SUM(grand_total - paid_amount) as total_rem FROM invoices WHERE customer_id = $c_id");
        $inv_row = $inv_res ? $inv_res->fetchArray(SQLITE3_ASSOC) : null;
        $real_balance = floatval($inv_row['total_rem'] ?? 0);
        
        // إذا كان هناك رصيد مخزون أو حقل قديم نتحقق منه
        if ($real_balance <= 0 && isset($row['balance'])) {
            $real_balance = floatval($row['balance']);
        }

        // إضافة العميل إذا كان عليه دين أكبر من الصفر
        if ($real_balance > 0.01) {
            $row['balance'] = $real_balance;
            $total_market_debts += $real_balance;
            $customers[] = $row;
        }
    }
}
$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'>
<meta name='mobile-web-app-capable' content='yes'>
<meta name='apple-mobile-web-app-capable' content='yes'>
    <meta charset="UTF-8">
    <title>إدارة الديون - نظام أبوضياء</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Tahoma', sans-serif; background: #0f172a; margin: 0; padding: 10px; padding-bottom: 75px; color: #1e293b; }
        .main-container { max-width: 650px; margin: 0 auto; background: #fff; border-radius: 12px; padding: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #fbbf24; padding-bottom: 10px; margin-bottom: 15px; background: #1d4ed8; color: #fff; padding: 12px; border-radius: 8px; }
        .header h2 { margin: 0; font-size: 15px; }
        .total-box { background: #dc2626; color: #fff; border-radius: 8px; padding: 15px; display: flex; justify-content: space-between; align-items: center; font-size: 15px; font-weight: bold; margin-bottom: 15px; }
        .table-responsive { width: 100%; overflow-x: auto; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; font-size: 11.5px; border-radius: 8px; overflow: hidden; }
        th { background: #1e293b; color: #fff; padding: 10px 6px; text-align: center; }
        td { padding: 10px 6px; text-align: center; border-bottom: 1px solid #e2e8f0; font-weight: bold; background: #f8fafc; }
        .cust-name { text-align: right; color: #1d4ed8; }
        .debt-amt { color: #dc2626; font-size: 13px; }
        .btn { padding: 6px 10px; border: none; border-radius: 6px; font-weight: bold; font-size: 10.5px; cursor: pointer; color: #fff; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 3px; }
        .btn-success { background: #16a34a; }
        .btn-info { background: #0284c7; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(4px); z-index: 1000; align-items: center; justify-content: center; }
        .modal-content { background: #1e293b; padding: 20px; border-radius: 12px; width: 90%; max-width: 350px; border: 1px solid #38bdf8; color: #fff; text-align: right; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #64748b; border-radius: 6px; font-size: 14px; background: #0f172a; color: #fff; outline: none; margin-bottom: 12px; font-weight: bold; }
        .bottom-nav { position: fixed; bottom: 0; left: 0; width: 100%; background: #1e293b; border-top: 2px solid #fbbf24; display: flex; justify-content: space-around; padding: 8px 0; z-index: 9999; box-shadow: 0 -5px 15px rgba(0,0,0,0.3); }
        .nav-item { color: #94a3b8; text-decoration: none; font-size: 10px; font-weight: bold; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 2px; flex: 1; }
        .nav-item span { font-size: 16px; }
        .nav-item.active, .nav-item:hover { color: #fbbf24; }
    </style>
</head>
<body>

<div class="main-container">
    <div class="header">
        <h2>📒 دفتر الديون وحسابات العملاء</h2>
    </div>

    <div class="total-box">
        <span>إجمالي الديون في السوق:</span>
        <span dir="ltr"><?php echo number_format($total_market_debts, 2); ?> ر.ي</span>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th style="width: 35%;">اسم العميل</th>
                    <th>الرصيد المتبقي</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($customers)): ?>
                <tr>
                    <td colspan="3" style="padding: 20px; color: #16a34a;">لا توجد ديون مسجلة في السوق!</td>
                </tr>
                <?php else: ?>
                    <?php foreach($customers as $c): ?>
                    <tr>
                        <td class="cust-name"><?php echo htmlspecialchars($c['name']); ?></td>
                        <td class="debt-amt" dir="ltr"><?php echo number_format($c['balance'], 2); ?> ر.ي</td>
                        <td style="display: flex; gap: 4px; justify-content: center;">
                            <a href="customer_statement.php?customer_id=<?php echo $c['id']; ?>" class="btn btn-info" title="كشف الحساب">📊 كشف</a>
                            <button class="btn btn-success" onclick="openReceiptModal(<?php echo $c['id']; ?>, '<?php echo htmlspecialchars($c['name'], ENT_QUOTES); ?>', <?php echo $c['balance']; ?>)">💵 سداد</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal" id="receiptModal">
    <div class="modal-content">
        <h3 style="color:#38bdf8; margin-top:0;">💵 سند قبض</h3>
        <p style="font-size: 12px; color: #cbd5e1; margin-bottom: 15px;">العميل: <span id="modalCustName" style="color:#fbbf24; font-weight:bold;"></span></p>
        <form method="POST">
            <input type="hidden" name="action" value="add_receipt">
            <input type="hidden" name="customer_id" id="modalCustId">
            <label style="font-size:11px; color:#94a3b8; display:block; margin-bottom:4px;">المبلغ المستلم (ر.ي)</label>
            <input type="text" inputmode="decimal" name="amount" id="modalAmount" class="form-control" required autofocus autocomplete="off" onfocus="this.select()">
            <label style="font-size:11px; color:#94a3b8; display:block; margin-bottom:4px;">ملاحظة</label>
            <input type="text" name="note" class="form-control" value="دفعة نقدية تسديد دين" autocomplete="off">
            <div style="display: flex; gap: 8px; margin-top: 10px;">
                <button type="submit" class="btn btn-success" style="flex:1; padding:10px; font-size:12px;">حفظ وطباعة السند</button>
                <button type="button" class="btn" style="flex:1; padding:10px; background:#475569; font-size:12px;" onclick="closeReceiptModal()">إلغاء</button>
            </div>
        </form>
    </div>
</div>

<div class="bottom-nav">
    <a href="pos.php" class="nav-item"><span>🛒</span>الكاشير</a>
    <a href="inventory.php" class="nav-item"><span>📦</span>المستودع</a>
    <a href="debts.php" class="nav-item active"><span>📒</span>الديون</a>
    <a href="daily_report.php" class="nav-item"><span>📊</span>اليومية</a>
    <a href="dashboard.php" class="nav-item"><span>⚙️</span>الرئيسية</a>
</div>

<script>
function openReceiptModal(id, name, balance) {
    document.getElementById('modalCustId').value = id;
    document.getElementById('modalCustName').innerText = name + ' (عليه ' + balance.toLocaleString() + ' ر.ي)';
    document.getElementById('modalAmount').value = balance;
    document.getElementById('receiptModal').style.display = 'flex';
}
function closeReceiptModal() {
    document.getElementById('receiptModal').style.display = 'none';
}
</script>
<?php if(file_exists('nav_menu.php')) { include 'nav_menu.php'; } ?>
</body>
</html>
