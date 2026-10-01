<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$db = new SQLite3('vmax.db', SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
$db->busyTimeout(5000);

$db->exec("CREATE TABLE IF NOT EXISTS customers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    phone TEXT,
    address TEXT,
    pay_type TEXT DEFAULT 'cash',
    balance REAL DEFAULT 0
)");

$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_customer'])) {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $pay_type = $_POST['pay_type'] ?? 'cash';
    $initial_balance = floatval($_POST['initial_balance'] ?? 0);

    if (!empty($name)) {
        $balance_to_save = ($pay_type === 'credit') ? $initial_balance : 0;
        $stmt = $db->prepare("INSERT INTO customers (name, phone, address, pay_type, balance) VALUES (:name, :phone, :address, :pay_type, :balance)");
        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
        $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
        $stmt->bindValue(':address', $address, SQLITE3_TEXT);
        $stmt->bindValue(':pay_type', $pay_type, SQLITE3_TEXT);
        $stmt->bindValue(':balance', $balance_to_save, SQLITE3_FLOAT);
        $stmt->execute();
        $msg = "✅ تم إضافة العميل بنجاح!";
    } else {
        $msg = "❌ يرجى إدخال اسم العميل.";
    }
}

if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $db->exec("DELETE FROM customers WHERE id = $del_id");
    header("Location: customers.php");
    exit;
}

$customers_list = [];
$res = $db->query("SELECT * FROM customers ORDER BY id DESC");
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $customers_list[] = $row;
}

$page_title = "إدارة العملاء - V-MAX PRO";
include 'layout.php';
?>

    <div class="app-header">👥 إدارة العملاء والحسابات الذكية</div>

    <?php if($msg): ?>
    <div style="background: rgba(16, 185, 129, 0.2); border: 1px solid var(--accent-green); color: #34d399; padding: 12px; border-radius: 12px; text-align: center; font-size: 12px; margin-bottom: 14px; font-weight: bold;"><?php echo $msg; ?></div>
    <?php endif; ?>

    <div class="card">
        <h3 style="margin-top: 0; color: var(--accent-cyan); font-size: 14px; font-weight: bold;">➕ إضافة عميل جديد وتحديد نوع التعامل</h3>
        <form method="POST">
            <label style="font-size: 11px; color: var(--accent-cyan); font-weight: bold;">اسم العميل:</label>
            <input type="text" name="name" class="form-control" required placeholder="أدخل اسم العميل...">

            <label style="font-size: 11px; color: var(--accent-cyan); font-weight: bold;">رقم الهاتف:</label>
            <input type="text" name="phone" class="form-control" placeholder="رقم الهاتف...">

            <label style="font-size: 11px; color: var(--accent-cyan); font-weight: bold;">العنوان:</label>
            <input type="text" name="address" class="form-control" placeholder="المنطقة / الشارع...">

            <label style="font-size: 11px; color: var(--accent-gold); font-weight: bold;">⚡ طريقة الدفع / نوع المعاملة:</label>
            <select name="pay_type" class="form-control" style="border-color: var(--accent-gold); font-weight: bold;">
                <option value="cash">💵 نقدي (مسدد بالكامل)</option>
                <option value="credit">📝 آجل (تسجيل كدين على العميل)</option>
                <option value="transfer">🏦 تحويل شبكة / بنكي</option>
            </select>

            <label style="font-size: 11px; color: var(--accent-cyan); font-weight: bold;">المبلغ أو الدين الافتتاحي (ر.ي):</label>
            <input type="number" step="any" name="initial_balance" class="form-control" placeholder="0">

            <button type="submit" name="add_customer" value="1" class="btn-main">💾 حفظ العميل بأمان تام</button>
        </form>
    </div>

    <div class="card">
        <h3 style="margin-top: 0; color: var(--accent-cyan); font-size: 14px; font-weight: bold;">📂 قائمة العملاء المسجلين</h3>
        <?php if(empty($customers_list)): ?>
            <p style="font-size: 12px; color: var(--text-muted); text-align: center; margin: 15px 0;">لا يوجد عملاء مسجلون حتى الآن.</p>
        <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>الاسم ورقم الهاتف</th>
                            <th>النوع</th>
                            <th>المبلغ / الدين</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($customers_list as $cust): ?>
                        <tr>
                            <td style="text-align: right; font-weight: bold;">
                                <?php echo htmlspecialchars($cust['name']); ?><br>
                                <span style="font-size: 10px; color: var(--text-muted);"><?php echo htmlspecialchars($cust['phone']); ?></span>
                            </td>
                            <td>
                                <?php 
                                    $pt = $cust['pay_type'] ?? 'cash';
                                    if($pt === 'credit') echo '<span style="color: #f87171; font-weight: bold;">آجل</span>';
                                    elseif($pt === 'transfer') echo '<span style="color: #38bdf8; font-weight: bold;">تحويل</span>';
                                    else echo '<span style="color: #34d399; font-weight: bold;">نقدي</span>';
                                ?>
                            </td>
                            <td style="direction: ltr; font-weight: bold; color: <?php echo $cust['balance'] > 0 ? '#f87171' : '#34d399'; ?>;">
                                <?php echo number_format($cust['balance'], 0); ?> ر.ي
                            </td>
                            <td>
                                <a href="customers.php?delete=<?php echo $cust['id']; ?>" onclick="return confirm('هل أنت متأكد من حذف هذا العميل؟')" style="background: rgba(239,68,68,0.2); color: #f87171; border: 1px solid #ef4444; padding: 5px 9px; border-radius: 8px; text-decoration: none; font-size: 10px; font-weight: bold;">حذف</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- البصمة العالمية المحمية ضد التكرار -->
    <?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
    <?php include 'nav_menu.php'; ?>
