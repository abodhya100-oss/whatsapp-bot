<?php
require_once 'auth.php';
$db = new SQLite3('/data/data/com.termux/files/home/secure_dbs/vmax.db');
$msg = "";
if (isset($_POST['process_return'])) {
    $p_id = intval($_POST['product_id']);
    $qty = intval($_POST['qty']);
    $amount = floatval($_POST['amount']);
    // إعادة الكمية للمخزون
    $db->exec("UPDATE products SET qty = qty + $qty WHERE id = $p_id");
    // تسجيل المبلغ كمصروف (مرتجع مبيعات) لخصمه من الخزنة
    $date = date('Y-m-d H:i:s');
    $stmt = $db->prepare("INSERT INTO expenses (title, amount, date) VALUES ('مرتجع مبيعات', :a, :d)");
    $stmt->bindValue(':a', $amount);
    $stmt->bindValue(':d', $date);
    $stmt->execute();
    $msg = "تم استلام المرتجع، إضافة ($qty) للمخزون، وخصم ($amount) من الخزنة بنجاح!";
}
$products = [];
$res = $db->query("SELECT * FROM products ORDER BY name ASC");
while($row = $res->fetchArray(SQLITE3_ASSOC)) { $products[] = $row; }
$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl"><head>
<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><meta name='mobile-web-app-capable' content='yes'>
<meta name='apple-mobile-web-app-capable' content='yes'>
<meta charset="UTF-8"><meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>نظام المرتجعات</title><?php if(file_exists('global_style.php')) include 'global_style.php'; ?></head>
<body><div class="lux-wrapper"><div class="lux-card"><h2 class="lux-title" style="color:#ef4444; border-right-color:#ef4444;">🔄 قسم المرتجعات والتبديل</h2>
    <div style="display: flex; gap: 5px;">
    </div>
    <div style="display: flex; gap: 4px; align-items: center;">
        <span style="color: #94a3b8; font-size: 10px; font-weight: bold;">تكبير الشاشة:</span>
    </div>
</div>
</div>
<?php if($msg) echo "<div style='background: #FFFFFF; color:#fca5a5; padding:10px; border-radius:8px; margin-bottom:10px; font-size:12px;'>$msg</div>"; ?>
<form method="POST"><input type="hidden" name="process_return" value="1">
<label style="font-size:11px; color:#94a3b8;">الصنف المرتجع:</label>
<select name="product_id" class="lux-input" required>
<?php foreach($products as $p) echo "<option value='{$p['id']}'>{$p['name']} (مخزون: {$p['qty']})</option>"; ?>
</select>
<div style="display:flex; gap:10px;">
<input type="number" name="qty" class="lux-input" placeholder="الكمية المرتجعة" required style="flex:1;">
<input type="number" step="0.01" name="amount" class="lux-input" placeholder="المبلغ المردود (ر.ي)" required style="flex:1;">
</div>
<button type="submit" class="lux-btn" style="background: #FFFFFF; color:#fff; box-shadow:0 0 15px rgba(239,68,68,0.4);">إرجاع الصنف وخصم المبلغ 💵</button>
</form></div>
<a href="index.php" class="lux-btn" style="background: #FFFFFF; color:#38bdf8; text-decoration:none;">🏠 العودة للرئيسية</a>
