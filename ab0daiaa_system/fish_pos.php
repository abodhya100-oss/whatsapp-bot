<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_role'])) { header("Location: login.php"); exit; }
$db = new SQLite3('/data/data/com.termux/files/home/secure_dbs/vmax.db');
$db->exec("CREATE TABLE IF NOT EXISTS fish_products (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, unit TEXT DEFAULT 'كيلو', price REAL, stock REAL, supplier TEXT);");
$db->exec("CREATE TABLE IF NOT EXISTS fish_invoices (id INTEGER PRIMARY KEY AUTOINCREMENT, customer_name TEXT, total REAL, weight_total REAL, date TEXT, payment_method TEXT);");

// معالجة بيع السمك
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_fish_invoice'])) {
    $cust = SQLite3::escapeString(trim($_POST['customer_name'] ?? 'زبون أسماك نقدي'));
    $fish_id = intval($_POST['fish_id']);
    $weight = floatval($_POST['weight']); // الوزن بالكيلو أو الكمية
    $pay = SQLite3::escapeString($_POST['payment_method'] ?? 'cash');
    
    if ($fish_id > 0 && $weight > 0) {
        $f = $db->querySingle("SELECT name, price FROM fish_products WHERE id = $fish_id", true);
        if ($f) {
            $total = $weight * floatval($f['price']);
            $date = date('Y-m-d H:i:s');
            
            $db->exec("INSERT INTO fish_invoices (customer_name, total, weight_total, date, payment_method) VALUES ('$cust', $total, $weight, '$date', '$pay')");
            $db->exec("UPDATE fish_products SET stock = stock - $weight WHERE id = $fish_id");
            
            echo "<script>alert('✅ تم حفظ فاتورة بيع السمك بنجاح! الإجمالي: $total ر.ي'); window.location.href='fish_pos.php';</script>";
            exit;
        }
    }
}
$products = $db->query("SELECT * FROM fish_products ORDER BY name ASC");
$all_f = [];
while($row = $products->fetchArray(SQLITE3_ASSOC)) { $all_f[] = $row; }
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <link rel="manifest" href="manifest.json">
    <meta name='mobile-web-app-capable' content='yes'>
    <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js");}</script>
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>كاشير الأسماك (أبو ضياء)</title>
    <style>
        body { font-family: Tahoma, sans-serif; background: #FFFFFF; color: #f8fafc; margin: 0; padding: 12px; direction: rtl; }
        .header { text-align: center; font-size: 16px; font-weight: bold; color: #06b6d4; margin-bottom: 12px; }
        .card-box { background: #FFFFFF; border: 1px solid #0e7490; border-radius: 14px; padding: 15px; margin-bottom: 12px; }
        .input-box { width: 100%; padding: 12px; background: #FFFFFF; color: #fff; border: 1px solid #38bdf8; border-radius: 8px; font-size: 14px; margin-bottom: 10px; box-sizing: border-box; }
        .btn-pay { background: #FFFFFF; color: #fff; border: none; padding: 14px; border-radius: 10px; font-weight: bold; font-size: 15px; width: 100%; cursor: pointer; }
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
    <div class="header">⚖️ نقطة بيع الأسماك السريعة (بالوزن / الحبة)</div>
    
    <div class="card-box">
        <form method="POST">
            <div style="font-size: 12px; color: #38bdf8; font-weight: bold; margin-bottom: 5px;">👤 اسم الزبون:</div>
            <input type="text" name="customer_name" class="input-box" required placeholder="اسم المشتري..." value="زبون أسماك عام">

            <div style="font-size: 12px; color: #38bdf8; font-weight: bold; margin-bottom: 5px;">🐟 اختر نوع السمك:</div>
            <select name="fish_id" class="input-box" required>
                <option value="">-- اختر السمك --</option>
                <?php foreach($all_f as $item): ?>
                    <option value="<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['name']); ?> (السعر: <?php echo $item['price']; ?> ر.ي / <?php echo $item['unit']; ?>)</option>
                <?php endforeach; ?>
            </select>

            <div style="font-size: 12px; color: #38bdf8; font-weight: bold; margin-bottom: 5px;">⚖️ الوزن بالكيلو (أو الكمية بالحبة):</div>
            <input type="number" step="any" name="weight" class="input-box" required placeholder="مثال: 3.5 كيلو..." style="font-size: 20px; font-weight: bold; color: #4ade80; text-align: center;">

            <div style="font-size: 12px; color: #38bdf8; font-weight: bold; margin-bottom: 5px;">💳 طريقة الدفع:</div>
            <select name="payment_method" class="input-box">
                <option value="cash">نقدي (كاش)</option>
                <option value="credit">آجل (على حساب الزبون)</option>
            </select>

            <button type="submit" name="save_fish_invoice" class="btn-pay">💾 إصدار فاتورة بيع السمك</button>
        </form>
    </div>

    <div style="text-align: center; margin-top: 15px;"><a href="fish_index.php" style="color: #38bdf8; text-decoration: none; font-weight: bold;">🔙 العودة للوحة تحكم الأسماك</a></div>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>
</html>

