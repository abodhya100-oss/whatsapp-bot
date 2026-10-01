<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$db = new SQLite3('vmax.db', SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
$db->busyTimeout(5000);

$db->exec("CREATE TABLE IF NOT EXISTS client_accounts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    phone TEXT,
    pay_type TEXT,
    amount REAL
)");

$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_client'])) {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $pay_type = $_POST['pay_type'] ?? 'cash';
    $amount = floatval($_POST['amount'] ?? 0);

    if (!empty($name)) {
        $stmt = $db->prepare("INSERT INTO client_accounts (name, phone, pay_type, amount) VALUES (:name, :phone, :pay_type, :amount)");
        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
        $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
        $stmt->bindValue(':pay_type', $pay_type, SQLITE3_TEXT);
        $stmt->bindValue(':amount', $amount, SQLITE3_FLOAT);
        $stmt->execute();
        
        $msg = "✅ تم حفظ العميل بنجاح!";
    } else {
        $msg = "❌ يرجى إدخال اسم العميل.";
    }
}

if (isset($_GET['delete'])) {
    $db->exec("DELETE FROM client_accounts WHERE id = " . intval($_GET['delete']));
    header("Location: client_accounts.php");
    exit;
}

$list = [];
$res = $db->query("SELECT * FROM client_accounts ORDER BY id DESC");
while ($row = $res->fetchArray(SQLITE3_ASSOC)) { $list[] = $row; }
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><meta name='mobile-web-app-capable' content='yes'>
<meta name='apple-mobile-web-app-capable' content='yes'>

    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>حسابات العملاء - V-MAX PRO</title>
    <style>
        body { font-family: Tahoma, sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 10px; padding-bottom: 110px; }
        .header { text-align: center; font-size: 16px; font-weight: 900; color: #38bdf8; margin-bottom: 12px; }
        .card { background: #1e293b; border: 1px solid #38bdf8; border-radius: 10px; padding: 14px; margin-bottom: 12px; }
        .form-control { width: 100%; padding: 10px; background: #0f172a; color: #fff; border: 1px solid #38bdf8; border-radius: 8px; box-sizing: border-box; margin-bottom: 8px; font-size: 12px; }
        .btn-submit { background: #22c55e; color: #fff; border: none; padding: 12px; border-radius: 8px; font-weight: bold; width: 100%; font-size: 13px; cursor: pointer; }
        .table { width: 100%; border-collapse: collapse; font-size: 11px; margin-top: 8px; }
        .table th, .table td { border: 1px solid #334155; padding: 6px; text-align: center; }
        .table th { background: #1e3a8a; color: #fff; }
    </style>
</head>
<body>
    <div class="header">👥 حسابات العملاء (نقدي / آجل)</div>

    <?php if($msg): ?>
    <div style="background: #16a34a; color: #fff; padding: 8px; text-align: center; font-size: 11px; border-radius: 6px; margin-bottom: 10px;"><?php echo $msg; ?></div>
    <?php endif; ?>

    <div class="card">
        <form method="POST">
            <label style="font-size: 11px; color: #38bdf8; font-weight: bold;">اسم العميل:</label>
            <input type="text" name="name" class="form-control" required placeholder="اسم العميل...">

            <label style="font-size: 11px; color: #38bdf8; font-weight: bold;">رقم الهاتف:</label>
            <input type="text" name="phone" class="form-control" placeholder="رقم الهاتف...">

            <label style="font-size: 11px; color: #38bdf8; font-weight: bold; color: #fbbf24;">⚡ طريقة الدفع:</label>
            <select name="pay_type" class="form-control" style="border-color: #fbbf24; font-weight: bold; background: #1e293b; color: #fff;">
                <option value="cash">💵 نقدي</option>
                <option value="credit">📝 آجل (دين)</option>
            </select>

            <label style="font-size: 11px; color: #38bdf8; font-weight: bold;">المبلغ (ر.ي):</label>
            <input type="number" step="any" name="amount" class="form-control" placeholder="0" required>

            <button type="submit" name="save_client" value="1" class="btn-submit">💾 حفظ المعاملة</button>
        </form>
    </div>

    <div class="card">
        <h3 style="margin-top: 0; color: #38bdf8; font-size: 13px;">📂 السجلات المسجلة</h3>
        <?php if(empty($list)): ?>
            <p style="font-size: 11px; color: #94a3b8; text-align: center;">لا توجد سجلات حتى الآن.</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>الاسم</th>
                        <th>الدفع</th>
                        <th>المبلغ</th>
                        <th>حذف</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($list as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td>
                            <?php if($row['pay_type'] === 'credit'): ?>
                                <span style="color: #f87171; font-weight: bold;">آجل</span>
                            <?php else: ?>
                                <span style="color: #4ade80; font-weight: bold;">نقدي</span>
                            <?php endif; ?>
                        </td>
                        <td style="color: #38bdf8; font-weight: bold;"><?php echo number_format($row['amount'], 0); ?></td>
                        <td>
                            <a href="client_accounts.php?delete=<?php echo $row['id']; ?>" onclick="return confirm('حذف؟')" style="background: #ef4444; color: #fff; padding: 2px 6px; border-radius: 4px; text-decoration: none; font-size: 10px;">حذف</a>
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

