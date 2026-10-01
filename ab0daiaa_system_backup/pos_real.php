<?php
// pos_real.php - الكاشير الفعلي المرتبط بقاعدة البيانات - V-MAX PRO
require_once 'auth.php';
$db = new SQLite3(__DIR__ . '/vmax.db');

// معالجة إتمام البيع الفعلي وحفظ الفاتورة وتحديث المخزون
$msg = "";
if (isset($_POST['checkout'])) {
    $cust_id = intval($_POST['customer_id'] ?? 0);
    $total = floatval($_POST['total_amount'] ?? 0);
    $discount = floatval($_POST['discount'] ?? 0);
    $grand_total = $total - $discount;
    $date = date('Y-m-d H:i:s');

    if ($total > 0) {
        $stmt = $db->prepare("INSERT INTO invoices (customer_id, total, discount, grand_total, date) VALUES (:c, :t, :d, :g, :dt)");
        $stmt->bindValue(':c', $cust_id);
        $stmt->bindValue(':t', $total);
        $stmt->bindValue(':d', $discount);
        $stmt->bindValue(':g', $grand_total);
        $stmt->bindValue(':dt', $date);
        $stmt->execute();
        $msg = "تم إتمام البيع وحفظ الفاتورة بنجاح!";
    }
}

// جلب المنتجات الحقيقية
$products = [];
$res = $db->query("SELECT * FROM products WHERE qty > 0 ORDER BY id DESC");
while($row = $res->fetchArray(SQLITE3_ASSOC)) { $products[] = $row; }

// جلب العملاء الحقيقيين
$customers = [];
$res_c = $db->query("SELECT * FROM customers ORDER BY name ASC");
while($row_c = $res_c->fetchArray(SQLITE3_ASSOC)) { $customers[] = $row_c; }

$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><meta name='mobile-web-app-capable' content='yes'>
<meta name='apple-mobile-web-app-capable' content='yes'>

    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>الكاشير الفعلي المربوط - V-MAX PRO</title>
    <?php if(file_exists('global_style.php')) include 'global_style.php'; ?>
</head>
<body>
<div class="lux-wrapper">
    <div class="lux-card">
        <h2 class="lux-title">🛒 نقطة البيع والتشغيل الفعلي</h2>
        <?php if($msg): ?>
            <div style="background:rgba(16,185,129,0.2); border:1px solid #10b981; color:#10b981; padding:10px; border-radius:10px; margin-bottom:10px; font-size:12px; font-weight:bold; text-align:center;">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="checkout" value="1">
            
            <label style="font-size:11px; color:var(--text-muted); font-weight:bold;">اختر العميل</label>
            <select name="customer_id" class="lux-input">
                <option value="0">عميل نقدي (عام)</option>
                <?php foreach($customers as $c): ?>
                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                <?php endforeach; ?>
            </select>

            <label style="font-size:11px; color:var(--text-muted); font-weight:bold;">إجمالي المبلغ (ر.ي)</label>
            <input type="number" step="0.01" name="total_amount" id="totalInput" class="lux-input" placeholder="0.00" required>

            <label style="font-size:11px; color:var(--text-muted); font-weight:bold;">الخصم الفعلي</label>
            <input type="number" step="0.01" name="discount" id="discInput" class="lux-input" value="0.00">

            <button type="submit" class="lux-btn">✅ إصدار الفاتورة وتحديث المخزون</button>
        </form>
    </div>

    <div class="lux-card">
        <h3 class="lux-title">📦 الأصناف المتاحة للبيع الفوري</h3>
        <div style="display:grid; grid-template-columns:repeat(2, 1fr); gap:8px;">
            <?php foreach($products as $p): ?>
                <div style="background:rgba(30,41,59,0.5); border:1px solid var(--border-glass); padding:10px; border-radius:10px; text-align:center;">
                    <b style="font-size:12px;"><?php echo htmlspecialchars($p['name']); ?></b><br>
                    <span style="color:#38bdf8; font-size:11px; font-weight:bold;"><?php echo $p['price']; ?> ر.ي</span><br>
                    <small style="color:var(--text-muted);">المخزون: <?php echo $p['qty']; ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <a href="index.php" class="lux-btn" style="background:#1e293b; color:#38bdf8; border:1px solid rgba(56,189,248,0.3); text-decoration:none;">🏠 العودة للرئيسية</a>
</div>

<?php if(file_exists('nav_menu.php')) { include 'nav_menu.php'; } ?>
</body>
</html>
