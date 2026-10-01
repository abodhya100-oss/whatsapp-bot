<?php
// inventory.php - إدارة المستودع والمخزون مع إضافة وحدة الطبق
require_once 'auth.php';
require_admin();
$db_file = '/data/data/com.termux/files/home/secure_dbs/vmax.db';
$db = new SQLite3($db_file);

$db->exec("CREATE TABLE IF NOT EXISTS products (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, unit TEXT DEFAULT 'كرتون', price REAL NOT NULL, qty REAL DEFAULT 0, image TEXT)");

// معالجة إضافة صنف جديد
if (isset($_POST['action']) && $_POST['action'] === 'add_new_product') {
    $p_name = trim($_POST['p_name'] ?? '');
    $p_unit = trim($_POST['p_unit'] ?? 'كرتون');
    $p_price = floatval($_POST['p_price'] ?? 0);
    $p_qty = floatval($_POST['p_qty'] ?? 0);

    // دمج اسم الصنف مع الوحدة لسهولة تمييزه في الكاشير
    $full_name = $p_name . ' (' . $p_unit . ')';

    if (!empty($p_name) && $p_price > 0) {
        $stmt = $db->prepare("INSERT INTO products (name, unit, price, qty) VALUES (:name, :unit, :price, :qty)");
        $stmt->bindValue(':name', $full_name, SQLITE3_TEXT);
        $stmt->bindValue(':unit', $p_unit, SQLITE3_TEXT);
        $stmt->bindValue(':price', $p_price, SQLITE3_FLOAT);
        $stmt->bindValue(':qty', $p_qty, SQLITE3_FLOAT);
        $stmt->execute();
        echo "<script>alert('✅ تم إضافة الصنف بنجاح!'); window.location.href='inventory.php';</script>";
        exit;
    }
}

// معالجة تزويد كمية صنف حالي
if (isset($_POST['action']) && $_POST['action'] === 'add_stock') {
    $p_id = intval($_POST['product_id']);
    $add_qty = floatval($_POST['add_qty']);
    if ($p_id > 0 && $add_qty > 0) {
        $db->exec("UPDATE products SET qty = qty + $add_qty WHERE id = $p_id");
        echo "<script>alert('✅ تم تزويد المخزون بنجاح!'); window.location.href='inventory.php';</script>";
        exit;
    }
}

$products = [];
$total_inventory_value = 0;
$res = $db->query("SELECT * FROM products ORDER BY id DESC");
if ($res) {
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $products[] = $row;
        $total_inventory_value += (floatval($row['qty']) * floatval($row['price']));
    }
}
$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><meta name='mobile-web-app-capable' content='yes'>
<meta name='apple-mobile-web-app-capable' content='yes'>

    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>المستودع والمخزون - نظام أبوضياء</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Tahoma', sans-serif; background: #FFFFFF; margin: 0; padding: 10px; padding-bottom: 75px; color: #1e293b; }
        .main-container { max-width: 650px; margin: 0 auto; background: #FFFFFF; border-radius: 12px; padding: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #fbbf24; padding-bottom: 10px; margin-bottom: 15px; background: #FFFFFF; color: #fff; padding: 12px; border-radius: 8px; }
        .header h2 { margin: 0; font-size: 15px; }
        .total-box { background: #FFFFFF; border: 1px solid #cbd5e1; color: #0f172a; padding: 12px; border-radius: 8px; font-weight: bold; display: flex; justify-content: space-between; margin-bottom: 15px; align-items: center; }
        .table-responsive { width: 100%; overflow-x: auto; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; font-size: 11.5px; border-radius: 8px; overflow: hidden; }
        th { background: #FFFFFF; color: #fff; padding: 10px 4px; text-align: center; }
        td { padding: 10px 4px; text-align: center; border-bottom: 1px solid #e2e8f0; font-weight: bold; background: #FFFFFF; }
        .t-name { text-align: right; color: #1d4ed8; }
        .btn { padding: 8px 12px; border: none; border-radius: 6px; font-weight: bold; font-size: 11px; cursor: pointer; color: #fff; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; }
        .btn-success { background: #FFFFFF; }
        .btn-primary { background: #FFFFFF; }
        .btn-dark { background: #FFFFFF; }
        .low-stock { color: #dc2626; background: #FFFFFF; padding: 2px 6px; border-radius: 4px; font-size: 10px; }
        .good-stock { color: #15803d; background: #FFFFFF; padding: 2px 6px; border-radius: 4px; font-size: 10px; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(4px); z-index: 1000; align-items: center; justify-content: center; }
        .modal-content { background: #FFFFFF; padding: 20px; border-radius: 12px; width: 90%; max-width: 350px; border: 1px solid #38bdf8; color: #fff; text-align: right; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #64748b; border-radius: 6px; font-size: 13px; background: #FFFFFF; color: #fff; outline: none; margin-bottom: 10px; font-weight: bold; }
        select.form-control { appearance: none; background-image: url('data:image/svg+xml;utf8,<svg fill="%2338bdf8" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/></svg>'); background-repeat: no-repeat; background-position: left 10px center; }
        .bottom-nav { position: fixed; bottom: 0; left: 0; width: 100%; background: #FFFFFF; border-top: 2px solid #fbbf24; display: flex; justify-content: space-around; padding: 8px 0; z-index: 9999; box-shadow: 0 -5px 15px rgba(0,0,0,0.3); }
        .nav-item { color: #94a3b8; text-decoration: none; font-size: 10px; font-weight: bold; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 2px; flex: 1; }
        .nav-item span { font-size: 16px; }
        .nav-item.active, .nav-item:hover { color: #fbbf24; }
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

<div class="main-container">
    <div class="header">
        <h2>📦 إدارة المستودع والمخزون</h2>
        <button class="btn btn-primary" onclick="openNewProductModal()">➕ صنف جديد</button>
    </div>

    <div class="total-box">
        <span>إجمالي قيمة البضاعة:</span>
        <span style="color:#16a34a;"><?php echo number_format($total_inventory_value, 0); ?> ر.ي</span>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th style="width: 45%;">الصنف والوحدة</th>
                    <th>السعر</th>
                    <th>الكمية</th>
                    <th>إجراء</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($products as $p): ?>
                <tr>
                    <td class="t-name"><?php echo htmlspecialchars($p['name']); ?></td>
                    <td><?php echo number_format($p['price'], 0); ?></td>
                    <td>
                        <?php if($p['qty'] <= 10): ?>
                            <span class="low-stock">⚠️ <?php echo floatval($p['qty']); ?></span>
                        <?php else: ?>
                            <span class="good-stock"><?php echo floatval($p['qty']); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="btn btn-success" style="padding:4px 8px; font-size:10px;" onclick="openStockModal(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars($p['name'], ENT_QUOTES); ?>')">➕ تزويد</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- نافذة إضافة صنف جديد مع خيار الطبق والكرتون -->
<div class="modal" id="newProductModal">
    <div class="modal-content">
        <h3 style="color:#38bdf8; margin-top:0;">➕ إضافة صنف أو وحدة جديدة</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add_new_product">

            <label style="font-size:11px; color:#94a3b8; display:block; margin-bottom:4px;">اسم المنتج الأساسي</label>
            <input type="text" name="p_name" class="form-control" placeholder="مثال: بفك رنجو" required autocomplete="off">

            <label style="font-size:11px; color:#94a3b8; display:block; margin-bottom:4px;">اختر الوحدة</label>
            <select name="p_unit" class="form-control" required>
                <option value="كرتون">📦 كرتون</option>
                <option value="باكت">🧃 باكت</option>
                <option value="طبق">🍽️ طبق</option>
                <option value="شدة">📚 شدة</option>
                <option value="كيس">🛍️ كيس</option>
                <option value="حبة">🍬 حبة</option>
                <option value="قطعة">🧩 قطعة</option>
                <option value="علبة">🥫 علبة</option>
                <option value="درزن">🔢 درزن</option>
                <option value="كيلو">⚖️ كيلو</option>
                <option value="لتر">💧 لتر</option>
                <option value="طرد">📦 طرد</option>
            </select>

            <label style="font-size:11px; color:#94a3b8; display:block; margin-bottom:4px;">السعر لهذه الوحدة (ر.ي)</label>
            <input type="text" inputmode="decimal" name="p_price" class="form-control" placeholder="0" required autocomplete="off">

            <label style="font-size:11px; color:#94a3b8; display:block; margin-bottom:4px;">الكمية الأولية</label>
            <input type="text" inputmode="decimal" name="p_qty" class="form-control" placeholder="0" value="0" required autocomplete="off">

            <div style="display: flex; gap: 8px; margin-top: 10px;">
                <button type="submit" class="btn btn-success" style="flex:1; padding:10px;">حفظ وإضافة</button>
                <button type="button" class="btn" style="flex:1; padding:10px; background: #FFFFFF;" onclick="closeNewProductModal()">إلغاء</button>
            </div>
        </form>
    </div>
</div>

<!-- نافذة تزويد الكمية -->
<div class="modal" id="stockModal">
    <div class="modal-content">
        <h3 style="color:#38bdf8; margin-top:0;">📦 تزويد كمية للمستودع</h3>
        <p style="font-size: 12px; color: #cbd5e1; margin-bottom: 15px;">الصنف: <span id="modalProductName" style="color:#fbbf24; font-weight:bold;"></span></p>
        <form method="POST">
            <input type="hidden" name="action" value="add_stock">
            <input type="hidden" name="product_id" id="modalProductId">
            <label style="font-size: 11px; color: #94a3b8; font-weight: bold; margin-bottom: 4px; display: block;">الكمية الجديدة المضافة</label>
            <input type="text" inputmode="decimal" name="add_qty" class="form-control" required autofocus autocomplete="off">
            <div style="display: flex; gap: 8px; margin-top: 10px;">
                <button type="submit" class="btn btn-success" style="flex:1; padding:10px;">حفظ</button>
                <button type="button" class="btn btn-dark" style="flex:1; padding:10px; background: #FFFFFF;" onclick="closeStockModal()">إلغاء</button>
            </div>
        </form>
    </div>
</div>

<div class="bottom-nav">
    <a href="pos.php" class="nav-item"><span> 🛒</span>الكاشير</a>
    <a href="inventory.php" class="nav-item active"><span>📦</span>المستودع</a>
    <a href="debts.php" class="nav-item"><span>📒</span>الديون</a>
    <a href="daily_report.php" class="nav-item"><span>📊</span>اليومية</a>
    <a href="dashboard.php" class="nav-item"><span>⚙️</span>الرئيسية</a>
</div>

<script>
function openNewProductModal() { document.getElementById('newProductModal').style.display = 'flex'; }
function closeNewProductModal() { document.getElementById('newProductModal').style.display = 'none'; }
function openStockModal(id, name) {
    document.getElementById('modalProductId').value = id;
    document.getElementById('modalProductName').innerText = name;
    document.getElementById('stockModal').style.display = 'flex';
}
function closeStockModal() { document.getElementById('stockModal').style.display = 'none'; }
</script>
</body>
</html>
