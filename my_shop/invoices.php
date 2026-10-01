<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
if(session_status()===PHP_SESSION_NONE){session_start();} if(!isset($_SESSION["user_role"])){header("Location: login.php");exit;}
$db = new SQLite3(__DIR__ . '/vmax.db');

$db->exec("CREATE TABLE IF NOT EXISTS invoices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_id INTEGER,
    total REAL DEFAULT 0,
    paid REAL DEFAULT 0,
    type TEXT DEFAULT 'مبيعات',
    date TEXT
)");

$customers = [];
$res_c = $db->query("SELECT * FROM customers ORDER BY name ASC");
while($c = $res_c->fetchArray(SQLITE3_ASSOC)) { $customers[] = $c; }

$products = [];
$res_p = $db->query("SELECT * FROM products ORDER BY name ASC");
while($p = $res_p->fetchArray(SQLITE3_ASSOC)) { $products[] = $p; }

$msg = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_invoice'])) {
    $cust_id = intval($_POST['customer_id'] ?? 0);
    $prod_id = intval($_POST['product_id'] ?? 0);
    $qty = floatval($_POST['qty'] ?? 1);
    $unit_type = trim($_POST['unit_type'] ?? 'unit'); // unit, alt1, alt2

    $prod = $db->querySingle("SELECT * FROM products WHERE id = $prod_id", true);
    if ($prod) {
        $price = $prod['sell_price'];
        if($unit_type == 'alt1') { $price = $prod['alt1_price']; }
        if($unit_type == 'alt2') { $price = $prod['alt2_price']; }
        
        $total = $price * $qty;
        $date = date('Y-m-d H:i:s');

        // تسجيل الفاتورة
        $stmt = $db->prepare("INSERT INTO invoices (customer_id, total, paid, type, date) VALUES (:cid, :tot, :tot, 'مبيعات', :date)");
        $stmt->bindValue(':cid', $cust_id, SQLITE3_INTEGER);
        $stmt->bindValue(':tot', $total, SQLITE3_FLOAT);
        $stmt->bindValue(':date', $date, SQLITE3_TEXT);
        $stmt->execute();
        $stmt->close();

        // تحديث رصيد العميل والدين
        if($cust_id > 0) {
            $db->exec("UPDATE customers SET balance = balance + $total WHERE id = $cust_id");
        }

        // خصم المخزون
        $factor = 1;
        if($unit_type == 'alt1') { $factor = $prod['alt1_factor']; }
        if($unit_type == 'alt2') { $factor = $prod['alt2_factor']; }
        $total_qty_deduct = $qty * $factor;
        $db->exec("UPDATE products SET stock = stock - $total_qty_deduct WHERE id = $prod_id");

        $msg = "<div style='background:rgba(56,239,125,0.2); color:#38ef7d; padding:8px; border-radius:8px; text-align:center; font-size:11px; margin-bottom:8px; font-weight:bold;'>✅ تم إصدار الفاتورة وخصم المخزون وتجيل الدين بنجاح!</div>";
    }
}
$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <link rel="manifest" href="manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js");}</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فواتير المبيعات - نظام ابوضياء العالمي</title>
    <style>
        body { font-family: 'Tahoma', sans-serif; background: #0f2027; color: #fff; margin: 0; padding: 10px; padding-bottom: 100px; }
        .container { max-width: 680px; margin: auto; }
        h1 { text-align: center; color: #ffd200; font-size: 19px; margin-bottom: 3px; }
        p.sub-title { text-align: center; color: #aaa; font-size: 11px; margin-bottom: 10px; }
        .card { background: rgba(255,255,255,0.08); padding: 12px; border-radius: 12px; margin-bottom: 10px; border: 1px solid rgba(255,255,255,0.15); box-shadow: 0 4px 15px rgba(0,0,0,0.3); }
        label { display: block; font-size: 11px; color: #ffd200; margin-bottom: 3px; font-weight: bold; }
        input, select { width: 100%; padding: 9px; margin-bottom: 8px; background: rgba(0,0,0,0.6); color: #fff; border: 1px solid #555; border-radius: 8px; box-sizing: border-box; font-size: 12px; }
        select option { background: #203a43; color: #fff; }
        .btn { width: 100%; background: #ffd200; color: #000; padding: 11px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 13px; }
        .nav-bar { position: fixed; bottom: 0; left: 0; right: 0; background: #0f2027; border-top: 1px solid rgba(255,210,0,0.2); padding: 6px 0; text-align: center; z-index: 1000; }
        .nav-links { display: flex; justify-content: space-around; font-size: 12px; }
        .nav-links a { color: #aaa; text-decoration: none; font-weight: bold; text-align: center; }
        .nav-links a.active { color: #ffd200; }
    </style>
</head>
<body>
<div class="container">
    <h1>🧾 نقطة البيع وفواتير المبيعات</h1>
    <p class="sub-title">نظام ابوضياء العالمي - سيئون السوق العام</p>
    <?php echo $msg; ?>
    <div class="card">
        <form method="POST">
            <label>👥 اختر العميل:</label>
            <select name="customer_id" required>
                <option value="">-- اختر العميل --</option>
                <?php foreach($customers as $c): ?>
                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?> (رصيد: <?php echo $c['balance']; ?>)</option>
                <?php endforeach; ?>
            </select>

            <label>📦 اختر الصنف:</label>
            <select name="product_id" required>
                <option value="">-- اختر المنتج --</option>
                <?php foreach($products as $p): ?>
                    <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?> (مخزون: <?php echo $p['stock']; ?>)</option>
                <?php endforeach; ?>
            </select>

            <div style="display: flex; gap: 8px;">
                <div style="flex:1;">
                    <label>⚖️ وحدة البيع:</label>
                    <select name="unit_type">
                        <option value="unit">الوحدة الصغرى (حبة)</option>
                        <option value="alt1">الوحدة المتوسطة (طبق/باكت)</option>
                        <option value="alt2">الوحدة الكبرى (كرتون)</option>
                    </select>
                </div>
                <div style="flex:1;">
                    <label>🔢 الكمية:</label>
                    <input type="number" step="0.01" name="qty" value="1" required>
                </div>
            </div>

            <button type="submit" name="save_invoice" class="btn" style="margin-top:5px;">💾 إصدار الفاتورة وتسجيل الحساب</button>
        </form>
    </div>
    <a href="main.php" style="display:block; text-align:center; color:#ffd200; text-decoration:none; font-size:12px; margin-top:10px;">⬅️ العودة لوحة التحكم الرئيسية</a>
</div>

<?php include 'nav_bar.php'; ?>

<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
