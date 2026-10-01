<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_role'])) { header("Location: login.php"); exit; }
$db = new SQLite3('vmax.db');

// إنشاء جدول المرتجعات إذا لم يكن موجوداً
$db->exec("CREATE TABLE IF NOT EXISTS returns (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    invoice_id INTEGER,
    item_name TEXT,
    qty INTEGER,
    price REAL,
    total_refund REAL,
    date TEXT,
    user_name TEXT
);");

$msg = "";
$invoice_data = null;
$invoice_items = [];
$search_id = isset($_GET['inv_id']) ? intval($_GET['inv_id']) : 0;

// البحث عن فاتورة
if ($search_id > 0) {
    $invoice_data = $db->querySingle("SELECT * FROM invoices WHERE id = $search_id", true);
    if (!$invoice_data) {
        $msg = "<div class='alert-error'>⚠️ لم يتم العثور على فاتورة بهذا الرقم.</div>";
    }
}

// معالجة طلب إرجاع صنف
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_return'])) {
    $inv_id = intval($_POST['invoice_id']);
    $item_name = SQLite3::escapeString(trim($_POST['item_name']));
    $qty_to_return = intval($_POST['qty']);
    $price = floatval($_POST['price']);
    $total_refund = $qty_to_return * $price;
    $date = date('Y-m-d H:i:s');
    $user = $_SESSION['username'] ?? 'مدير النظام';

    // 1. إعادة الكمية للمخزون
    $db->exec("UPDATE inventory SET stock = stock + $qty_to_return WHERE item_name = '$item_name'");
    
    // 2. تسجيل عملية المرتجع
    $stmt = $db->prepare("INSERT INTO returns (invoice_id, item_name, qty, price, total_refund, date, user_name) VALUES (:inv, :itm, :q, :p, :tot, :d, :u)");
    $stmt->bindValue(':inv', $inv_id, SQLITE3_INTEGER);
    $stmt->bindValue(':itm', $item_name, SQLITE3_TEXT);
    $stmt->bindValue(':q', $qty_to_return, SQLITE3_INTEGER);
    $stmt->bindValue(':p', $price, SQLITE3_FLOAT);
    $stmt->bindValue(':tot', $total_refund, SQLITE3_FLOAT);
    $stmt->bindValue(':d', $date, SQLITE3_TEXT);
    $stmt->bindValue(':u', $user, SQLITE3_TEXT);
    $stmt->execute();

    // 3. معالجة حساب العميل إذا كانت الفاتورة آجلة أو مسجلة باسمه
    $cust_id = intval($_POST['customer_id']);
    if ($cust_id > 0) {
        $cust_info = $db->querySingle("SELECT balance FROM customers WHERE id = $cust_id", true);
        $old_bal = $cust_info ? $cust_info['balance'] : 0;
        $new_bal = $old_bal - $total_refund;
        if($new_bal < 0) $new_bal = 0;
        
        $db->exec("UPDATE customers SET balance = $new_bal WHERE id = $cust_id");
        
        // تسجيل الإشعار الدائن
        $v_note = "إشعار دائن (مرتجع مبيعات) لفاتورة رقم #$inv_id";
        $v_stmt = $db->prepare("INSERT INTO vouchers (customer_id, type, amount, note, new_balance, date) VALUES (:cid, 'مرتجع', :amt, :note, :nbal, :date)");
        $v_stmt->bindValue(':cid', $cust_id, SQLITE3_INTEGER);
        $v_stmt->bindValue(':amt', $total_refund, SQLITE3_FLOAT);
        $v_stmt->bindValue(':note', $v_note, SQLITE3_TEXT);
        $v_stmt->bindValue(':nbal', $new_bal, SQLITE3_TEXT);
        $v_stmt->bindValue(':date', $date, SQLITE3_TEXT);
        $v_stmt->execute();
    }
    
    // 4. تسجيل كـ "مصروف / خروج نقد" لخصمه من درج الكاش اليومي
    $exp_note = "إرجاع نقدي للعميل (مرتجع من فاتورة #$inv_id)";
    $db->exec("INSERT INTO expenses (title, amount, date, user) VALUES ('$exp_note', $total_refund, '$date', '$user')");

    $msg = "<div class='alert-success'>✅ تم إرجاع ($qty_to_return) من الصنف ($item_name)، ورد المبلغ وتسويته محاسبياً.</div>";
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
    <title>المرتجعات - أبو ضياء</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 10px; padding-bottom: 80px; }
        .header { text-align: center; font-size: 16px; font-weight: 900; color: #f59e0b; margin-bottom: 15px; }
        .glass-panel { background: linear-gradient(135deg, rgba(30, 41, 59, 0.95), rgba(15, 23, 42, 0.98)); border: 1px solid rgba(245, 158, 11, 0.4); border-radius: 12px; padding: 15px; margin-bottom: 15px; }
        .form-control { width: 100%; padding: 10px; background: #1e293b; color: #fff; border: 1px solid rgba(245, 158, 11, 0.4); border-radius: 8px; margin-bottom: 10px; box-sizing: border-box; }
        .btn-submit { background: linear-gradient(135deg, #d97706, #b45309); color: white; padding: 12px; border: none; width: 100%; font-weight: bold; border-radius: 8px; cursor: pointer; box-shadow: 0 4px 10px rgba(217, 119, 6, 0.4); }
        .btn-search { background: #38bdf8; color: #0f172a; border: none; padding: 10px 15px; border-radius: 8px; font-weight: bold; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; margin-bottom: 15px; }
        th { background: rgba(245, 158, 11, 0.2); color: #fcd34d; padding: 8px; border: 1px solid rgba(245, 158, 11, 0.3); }
        td { padding: 8px; border: 1px solid rgba(148, 163, 184, 0.2); background: rgba(30, 41, 59, 0.5); text-align: center; }
        .alert-success { background: rgba(34, 197, 94, 0.2); color: #4ade80; border: 1px solid #22c55e; padding: 10px; border-radius: 8px; text-align: center; margin-bottom: 10px; font-weight: bold; }
        .alert-error { background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid #ef4444; padding: 10px; border-radius: 8px; text-align: center; margin-bottom: 10px; font-weight: bold; }
        
        .modal { display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.9); justify-content: center; align-items: center; z-index: 2000; padding: 15px; }
        .modal-content { background: #1e293b; border: 2px solid #f59e0b; border-radius: 12px; padding: 20px; width: 100%; max-width: 350px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">↩️ مرتجعات المبيعات</div>
    
    <?php echo $msg; ?>

    <div class="glass-panel">
        <form method="GET" style="display: flex; gap: 8px;">
            <input type="number" name="inv_id" class="form-control" style="margin: 0;" placeholder="أدخل رقم الفاتورة..." required value="<?php echo $search_id > 0 ? $search_id : ''; ?>">
            <button type="submit" class="btn-search">🔍 بحث</button>
        </form>
    </div>

    <?php if($invoice_data): ?>
    <div class="glass-panel">
        <div style="font-size: 13px; font-weight: bold; color: #fcd34d; margin-bottom: 10px;">📄 بيانات الفاتورة رقم: #<?php echo $invoice_data['id']; ?></div>
        <div style="font-size: 11px; color: #cbd5e1; margin-bottom: 10px; display: grid; grid-template-columns: 1fr 1fr; gap: 5px;">
            <div>العميل: <b style="color:#fff;"><?php echo htmlspecialchars($invoice_data['customer_name']); ?></b></div>
            <div>التاريخ: <b style="color:#fff; direction:ltr;"><?php echo date('Y-m-d', strtotime($invoice_data['date'])); ?></b></div>
            <div>الإجمالي: <b style="color:#4ade80; direction:ltr;"><?php echo number_format($invoice_data['net_amount'],0); ?></b></div>
        </div>

        <div style="color: #f87171; font-size: 10px; margin-bottom: 5px; font-weight:bold;">⚠️ الترجيع يتم بإدخال اسم الصنف المرتجع، وقيمته، والكمية المستردة.</div>
        
        <form method="POST">
            <input type="hidden" name="process_return" value="1">
            <input type="hidden" name="invoice_id" value="<?php echo $invoice_data['id']; ?>">
            <input type="hidden" name="customer_id" value="<?php echo $invoice_data['customer_id']; ?>">
            
            <label style="font-size: 10px; color: #94a3b8;">اسم الصنف المرتجع:</label>
            <input type="text" name="item_name" class="form-control" required placeholder="مثال: سماعة سلك...">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                <div>
                    <label style="font-size: 10px; color: #94a3b8;">الكمية المستردة:</label>
                    <input type="number" name="qty" class="form-control" required min="1" value="1">
                </div>
                <div>
                    <label style="font-size: 10px; color: #94a3b8;">سعر بيع الحبة:</label>
                    <input type="number" name="price" class="form-control" required placeholder="المبلغ المسترد...">
                </div>
            </div>
            
            <button type="submit" class="btn-submit" onclick="return confirm('هل أنت متأكد من ترجيع هذا الصنف؟ سيتم خصم المبلغ من درج اليوم وإعادة البضاعة للمخزون.')">♻️ تنفيذ المرتجع</button>
        </form>
    </div>
    <?php endif; ?>

    <?php include "nav_menu.php"; ?>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>
</html>

