<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_role'])) { header("Location: login.php"); exit; }
$db = new SQLite3('/data/data/com.termux/files/home/secure_dbs/vmax.db');
$db->exec("CREATE TABLE IF NOT EXISTS fish_products (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, unit TEXT DEFAULT 'كيلو', price REAL, stock REAL, supplier TEXT);");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_fish'])) {
    $name = SQLite3::escapeString(trim($_POST['name']));
    $unit = SQLite3::escapeString(trim($_POST['unit']));
    $price = floatval($_POST['price']);
    $stock = floatval($_POST['stock']);
    $supplier = SQLite3::escapeString(trim($_POST['supplier']));
    if (!empty($name)) {
        $db->exec("INSERT INTO fish_products (name, unit, price, stock, supplier) VALUES ('$name', '$unit', $price, $stock, '$supplier')");
    }
    header("Location: fish_products.php"); exit;
}
if (isset($_GET['delete_id'])) {
    $db->exec("DELETE FROM fish_products WHERE id = " . intval($_GET['delete_id']));
    header("Location: fish_products.php"); exit;
}
$res = $db->query("SELECT * FROM fish_products ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <link rel="manifest" href="manifest.json">
    <meta name='mobile-web-app-capable' content='yes'>
    <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js");}</script>
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>أنواع الأسماك (أبو ضياء)</title>
    <style>
        body { font-family: Tahoma, sans-serif; background: #FFFFFF; color: #f8fafc; margin: 0; padding: 10px; direction: rtl; }
        .header { text-align: center; font-size: 16px; font-weight: bold; color: #38bdf8; margin-bottom: 12px; }
        .card-box { background: #FFFFFF; border: 1px solid #0e7490; border-radius: 12px; padding: 12px; margin-bottom: 12px; }
        .input-box { width: 100%; padding: 10px; background: #FFFFFF; color: #fff; border: 1px solid #38bdf8; border-radius: 8px; font-size: 13px; margin-bottom: 8px; box-sizing: border-box; }
        .btn { background: #FFFFFF; color: #fff; border: none; padding: 10px; border-radius: 8px; font-weight: bold; width: 100%; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 11px; }
        th, td { padding: 8px 4px; text-align: center; border-bottom: 1px solid #1e293b; }
        th { background: #FFFFFF; color: #fff; }
        td { background: #FFFFFF; color: #f8fafc; }
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
    <div class="header">🐟 إدارة أنواع الأسماك (ثمد، حومر، ديرك...)</div>
    <div class="card-box">
        <form method="POST">
            <input type="text" name="name" class="input-box" required placeholder="اسم السمك (مثال: سمك ثمد طازج)...">
            <div style="display: flex; gap: 6px;">
                <input type="number" step="any" name="price" class="input-box" required placeholder="سعر الكيلو/الحبة..." style="color:#4ade80; font-weight:bold;">
                <input type="number" step="any" name="stock" class="input-box" required placeholder="الكمية المتوفرة..." value="100">
            </div>
            <select name="unit" class="input-box">
                <option value="كيلو" selected>كيلوجرام (KG)</option>
                <option value="حبة">حبة / قطعة</option>
                <option value="كرتون">كرتون</option>
            </select>
            <input type="text" name="supplier" class="input-box" placeholder="اسم المورد / البحار (اختياري)...">
            <button type="submit" name="add_fish" class="btn">➕ إضافة نوع السمك</button>
        </form>
    </div>
    <div class="card-box">
        <table>
            <thead><tr><th>نوع السمك</th><th>السعر</th><th>المتخزن</th><th>حذف</th></tr></thead>
            <tbody>
                <?php while($p = $res->fetchArray(SQLITE3_ASSOC)): ?>
                <tr>
                    <td style="font-weight: bold; text-align:right;"><?php echo htmlspecialchars($p['name']); ?></td>
                    <td style="color: #4ade80; font-weight: bold;"><?php echo $p['price']; ?></td>
                    <td><?php echo $p['stock'].' '.$p['unit']; ?></td>
                    <td><a href="fish_products.php?delete_id=<?php echo $p['id']; ?>" style="color: #ef4444; text-decoration: none;">🗑️</a></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <div style="text-align: center; margin-top: 15px;"><a href="fish_index.php" style="color: #38bdf8; text-decoration: none; font-weight: bold;">🔙 العودة للوحة تحكم الأسماك</a></div>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>
</html>

