<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$db = new SQLite3('vmax.db', SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
$db->busyTimeout(5000);

$db->exec("CREATE TABLE IF NOT EXISTS payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_name TEXT,
    amount REAL,
    note TEXT,
    date TEXT
)");

$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_payment'])) {
    $client_name = trim($_POST['client_name'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $note = trim($_POST['note'] ?? '');
    $date = date('Y-m-d H:i:s');

    if (!empty($client_name) && $amount > 0) {
        $stmt = $db->prepare("INSERT INTO payments (client_name, amount, note, date) VALUES (:name, :amount, :note, :date)");
        $stmt->bindValue(':name', $client_name, SQLITE3_TEXT);
        $stmt->bindValue(':amount', $amount, SQLITE3_FLOAT);
        $stmt->bindValue(':note', $note, SQLITE3_TEXT);
        $stmt->bindValue(':date', $date, SQLITE3_TEXT);
        $stmt->execute();
        
        $msg = "✅ تم تسجيل السداد بنجاح بقيمة " . number_format($amount, 0) . " ر.ي!";
    } else {
        $msg = "❌ يرجى إدخال اسم العميل والمبلغ بشكل صحيح.";
    }
}

if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $db->exec("DELETE FROM payments WHERE id = $del_id");
    header("Location: payments.php");
    exit;
}

$payments_list = [];
$res = $db->query("SELECT * FROM payments ORDER BY id DESC");
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $payments_list[] = $row;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة السداد - V-MAX PRO</title>
    <style>
        body { font-family: Tahoma, sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 10px; padding-bottom: 110px; }
        .header { text-align: center; font-size: 16px; font-weight: 900; color: #38bdf8; margin-bottom: 12px; }
        .card { background: #1e293b; border: 1px solid #38bdf8; border-radius: 10px; padding: 14px; margin-bottom: 12px; box-shadow: 0 0 15px rgba(56,189,248,0.2); }
        .form-control { width: 100%; padding: 10px; background: #0f172a; color: #fff; border: 1px solid #38bdf8; border-radius: 8px; box-sizing: border-box; margin-bottom: 8px; font-size: 12px; }
        .btn-submit { background: #22c55e; color: #fff; border: none; padding: 12px; border-radius: 8px; font-weight: bold; width: 100%; font-size: 13px; cursor: pointer; margin-top: 6px; }
        .alert { padding: 10px; border-radius: 6px; text-align: center; font-size: 11px; font-weight: bold; margin-bottom: 10px; }
        .table { width: 100%; border-collapse: collapse; font-size: 11px; margin-top: 8px; }
        .table th, .table td { border: 1px solid #334155; padding: 6px; text-align: center; }
        .table th { background: #1e3a8a; color: #fff; }
    </style>
</head>
<body>
    <div class="header">💰 إدارة السداد والقبض</div>

    <?php if($msg): ?>
    <div class="alert" style="background: <?php echo strpos($msg, '✅') !== false ? '#16a34a' : '#dc2626'; ?>; color: #fff;"><?php echo $msg; ?></div>
    <?php endif; ?>

    <div class="card">
        <h3 style="margin-top: 0; color: #38bdf8; font-size: 13px;">➕ تسجيل سند سداد جديد</h3>
        <form method="POST">
            <label style="font-size: 11px; color: #38bdf8; font-weight: bold;">اسم العميل / الجهة:</label>
            <input type="text" name="client_name" class="form-control" required placeholder="اكتب اسم العميل...">

            <label style="font-size: 11px; color: #38bdf8; font-weight: bold;">المبلغ (ر.ي):</label>
            <input type="number" step="any" name="amount" class="form-control" required placeholder="0">

            <label style="font-size: 11px; color: #38bdf8; font-weight: bold;">ملاحظات / البيان:</label>
            <input type="text" name="note" class="form-control" placeholder="سبب السداد...">

            <button type="submit" name="save_payment" value="1" class="btn-submit">💾 حفظ وحفظ السند</button>
        </form>
    </div>

    <div class="card">
        <h3 style="margin-top: 0; color: #38bdf8; font-size: 13px;">📂 سجل السدادات السابقة</h3>
        <?php if(empty($payments_list)): ?>
            <p style="font-size: 11px; color: #94a3b8; text-align: center; margin: 5px 0;">لا توجد سندات سداد مسجلة حتى الآن.</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>العميل</th>
                        <th>المبلغ</th>
                        <th>ملاحظات</th>
                        <th>حذف</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($payments_list as $pay): ?>
                    <tr>
                        <td style="text-align: right;"><?php echo htmlspecialchars($pay['client_name']); ?></td>
                        <td style="direction: ltr; font-weight: bold; color: #4ade80;"><?php echo number_format($pay['amount'], 0); ?></td>
                        <td style="font-size: 10px; color: #94a3b8;"><?php echo htmlspecialchars($pay['note']); ?></td>
                        <td>
                            <a href="payments.php?delete=<?php echo $pay['id']; ?>" onclick="return confirm('هل تريد حذف السند؟')" style="background: #ef4444; color: #fff; padding: 2px 6px; border-radius: 4px; text-decoration: none; font-size: 10px;">حذف</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <?php if(file_exists('nav_menu.php')) { include 'nav_menu.php'; } ?>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>
</html>

