<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$db = new SQLite3('vmax.db', SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
$db->busyTimeout(5000);

// التأكد من إنشاء الجدول بالأعمدة الصحيحة
$db->exec("CREATE TABLE IF NOT EXISTS invoices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_name TEXT,
    total REAL,
    pay_type TEXT,
    date TEXT
)");

$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_invoice'])) {
    $customer = trim($_POST['customer_name'] ?? 'عميل نقدي');
    $total = floatval($_POST['total_amount'] ?? 0);
    $pay_type = $_POST['pay_type'] ?? 'cash';
    $date = date('Y-m-d H:i:s');

    if ($total > 0) {
        $stmt = $db->prepare("INSERT INTO invoices (customer_name, total, pay_type, date) VALUES (:cust, :total, :pay, :date)");
        $stmt->bindValue(':cust', $customer, SQLITE3_TEXT);
        $stmt->bindValue(':total', $total, SQLITE3_FLOAT);
        $stmt->bindValue(':pay', $pay_type, SQLITE3_TEXT);
        $stmt->bindValue(':date', $date, SQLITE3_TEXT);
        $stmt->execute();
        $msg = "✅ تم حفظ الفاتورة بنجاح!";
    } else {
        $msg = "❌ أدخل مبلغ صحيح.";
    }
}

if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $db->exec("DELETE FROM invoices WHERE id = $del_id");
    header("Location: pos.php");
    exit;
}

$invoices_list = [];
$res = $db->query("SELECT * FROM invoices ORDER BY id DESC");
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    // دعم الأسماء القديمة والجديدة للأعمدة تفادياً لأي خطأ
    $row['safe_total'] = $row['total'] ?? $row['total_amount'] ?? $row['amount'] ?? 0;
    $row['safe_cust'] = $row['customer_name'] ?? $row['name'] ?? 'عميل نقدي';
    $row['safe_pay'] = $row['pay_type'] ?? 'cash';
    $invoices_list[] = $row;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الكاشير - أبو ضياء</title>
    <style>
        body { font-family: Tahoma, sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 10px; padding-bottom: 120px; }
        .header { text-align: center; font-size: 15px; font-weight: bold; color: #38bdf8; margin-bottom: 10px; }
        .card { background: #1e293b; border: 1px solid #38bdf8; border-radius: 8px; padding: 12px; margin-bottom: 10px; }
        .form-control { width: 100%; padding: 8px; background: #0f172a; color: #fff; border: 1px solid #38bdf8; border-radius: 6px; box-sizing: border-box; margin-bottom: 6px; font-size: 11px; }
        .btn-submit { background: #22c55e; color: #fff; border: none; padding: 10px; border-radius: 6px; font-weight: bold; width: 100%; font-size: 12px; cursor: pointer; }
        .table { width: 100%; border-collapse: collapse; font-size: 11px; margin-top: 6px; }
        .table th, .table td { border: 1px solid #334155; padding: 5px; text-align: center; }
        .table th { background: #1e3a8a; color: #fff; }
    </style>
</head>
<body>
    <div class="header">🛒 نقطة البيع والكاشير</div>

    <?php if($msg): ?>
    <div style="background: #16a34a; color: #fff; padding: 8px; border-radius: 4px; text-align: center; font-size: 11px; margin-bottom: 8px;"><?php echo $msg; ?></div>
    <?php endif; ?>

    <div class="card">
        <h3 style="margin-top: 0; color: #38bdf8; font-size: 12px;">إصدار فاتورة جديدة</h3>
        <form method="POST">
            <input type="text" name="customer_name" class="form-control" placeholder="اسم العميل..." value="عميل نقدي">
            <input type="number" step="any" name="total_amount" class="form-control" required placeholder="إجمالي المبلغ (ر.ي)...">
            <select name="pay_type" class="form-control">
                <option value="cash">نقدي</option>
                <option value="credit">آجل (دين)</option>
            </select>
            <button type="submit" name="save_invoice" value="1" class="btn-submit">💾 حفظ الفاتورة</button>
        </form>
    </div>

    <div class="card">
        <h3 style="margin-top: 0; color: #38bdf8; font-size: 12px;">الفواتير المسجلة</h3>
        <?php if(empty($invoices_list)): ?>
            <p style="font-size: 11px; color: #94a3b8; text-align: center;">لا توجد فواتير مسجلة.</p>
        <?php else: ?>
            <table class="table">
                <thead><tr><th>العميل</th><th>المبلغ</th><th>الدفع</th><th>حذف</th></tr></thead>
                <tbody>
                    <?php foreach($invoices_list as $inv): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($inv['safe_cust']); ?></td>
                        <td><?php echo number_format(floatval($inv['safe_total']), 0); ?></td>
                        <td><?php echo htmlspecialchars($inv['safe_pay']); ?></td>
                        <td><a href="pos.php?delete=<?php echo $inv['id']; ?>" style="color: #f87171;">حذف</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- البصمة المعتمدة -->
    <?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
    <?php if(file_exists('nav_menu.php')) { include 'nav_menu.php'; } ?>
</body>
</html>
