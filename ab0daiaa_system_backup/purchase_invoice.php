<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
if(session_status()===PHP_SESSION_NONE){session_start();} if(!isset($_SESSION["user_role"])){header("Location: login.php");exit;}
$db = new SQLite3(__DIR__ . '/vmax.db');
$db->exec("CREATE TABLE IF NOT EXISTS purchases (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    supplier_id INTEGER,
    product_id INTEGER,
    qty REAL,
    total REAL,
    date TEXT
)");

$msg = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_purchase'])) {
    $supplier_id = intval($_POST['supplier_id'] ?? 0);
    $product_id = intval($_POST['product_id'] ?? 0);
    $qty = floatval($_POST['qty'] ?? 0);
    $buy_price = floatval($_POST['buy_price'] ?? 0);
    $total = $qty * $buy_price;
    $date = date('Y-m-d H:i:s');

    if ($supplier_id > 0 && $product_id > 0 && $qty > 0) {
        $stmt = $db->prepare("INSERT INTO purchases (supplier_id, product_id, qty, total, date) VALUES (:sid, :pid, :q, :tot, :date)");
        $stmt->bindValue(':sid', $supplier_id, SQLITE3_INTEGER);
        $stmt->bindValue(':pid', $product_id, SQLITE3_INTEGER);
        $stmt->bindValue(':q', $qty, SQLITE3_FLOAT);
        $stmt->bindValue(':tot', $total, SQLITE3_FLOAT);
        $stmt->bindValue(':date', $date, SQLITE3_TEXT);
        $stmt->execute();
        $stmt->close();

        // زيادة كمية المخزون في المستودع وتحديث سعر الشراء إن أمكن
        $db->exec("UPDATE products SET stock = stock + $qty, buy_price = $buy_price WHERE id = $product_id");
        // زيادة مديونية المورد
        $db->exec("UPDATE suppliers SET balance = balance + $total WHERE id = $supplier_id");

        $msg = "<div style='background:rgba(56,239,125,0.2); color:#38ef7d; padding:8px; border-radius:8px; text-align:center; font-size:11px; margin-bottom:8px; font-weight:bold;'>✅ تم إدخال البضاعة للمستودع وتسجيل الفاتورة على المورد بنجاح!</div>";
    }
}

$suppliers = [];
$res_s = $db->query("SELECT * FROM suppliers ORDER BY name ASC");
while($s = $res_s->fetchArray(SQLITE3_ASSOC)) { $suppliers[] = $s; }

$products = [];
$res_p = $db->query("SELECT * FROM products ORDER BY name ASC");
while($p = $res_p->fetchArray(SQLITE3_ASSOC)) { $products[] = $p; }

$purchases = [];
$res_pur = $db->query("SELECT p.*, s.name as supplier_name, pr.name as product_name FROM purchases p LEFT JOIN suppliers s ON p.supplier_id = s.id LEFT JOIN products pr ON p.product_id = pr.id ORDER BY p.id DESC LIMIT 30");
while($pu = $res_pur->fetchArray(SQLITE3_ASSOC)) { $purchases[] = $pu; }
$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <link rel="manifest" href="manifest.json">
    <meta name='mobile-web-app-capable' content='yes'>
    <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js");}</script>
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>فواتير المشتريات - ابوضياء العالمي</title>
    <style>
        body { font-family: 'Tahoma', sans-serif; background: #0f2027; color: #fff; margin: 0; padding: 10px; padding-bottom: 90px; }
        .container { max-width: 680px; margin: auto; }
        h1 { text-align: center; color: #ffd200; font-size: 19px; margin-bottom: 3px; }
        p.sub-title { text-align: center; color: #aaa; font-size: 11px; margin-bottom: 10px; }
        .card { background: rgba(255,255,255,0.08); padding: 12px; border-radius: 12px; margin-bottom: 10px; border: 1px solid rgba(255,255,255,0.15); }
        label { display: block; font-size: 11px; color: #ffd200; margin-bottom: 3px; font-weight: bold; }
        input, select { width: 100%; padding: 9px; margin-bottom: 8px; background: rgba(0,0,0,0.6); color: #fff; border: 1px solid #555; border-radius: 8px; box-sizing: border-box; font-size: 12px; }
        select option { background: #203a43; color: #fff; }
        .btn { width: 100%; background: #ffd200; color: #000; padding: 11px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 13px; }
        .table { width: 100%; border-collapse: collapse; font-size: 11px; background: rgba(0,0,0,0.4); border-radius: 8px; overflow: hidden; margin-top: 6px; }
        .table th, .table td { padding: 8px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .table th { background: rgba(255,210,0,0.2); color: #ffd200; }
        .nav-bar { position: fixed; bottom: 0; left: 0; right: 0; background: #0f2027; border-top: 1px solid rgba(255,210,0,0.2); padding: 6px 0; text-align: center; z-index: 1000; }
        .nav-links { display: flex; justify-content: space-around; font-size: 12px; }
        .nav-links a { color: #aaa; text-decoration: none; font-weight: bold; text-align: center; }
        .nav-links a.active { color: #ffd200; }
    </style>
</head>
<body>
<div class="container">
    <h1>📥 فواتير المشتريات وإدخال البضاعة</h1>
    <p class="sub-title">إضافة بضاعة للمستودع وزيادة رصيد المورد</p>
    <?php echo $msg; ?>
    <div class="card">
        <form method="POST">
            <label>🏭 اختر المورد:</label>
            <select name="supplier_id" required>
                <option value="">-- اختر المورد --</option>
                <?php foreach($suppliers as $s): ?>
                    <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                <?php endforeach; ?>
            </select>

            <label>📦 اختر الصنف:</label>
            <select name="product_id" required>
                <option value="">-- اختر المنتج من المستودع --</option>
                <?php foreach($products as $p): ?>
                    <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?> (مخزون: <?php echo $p['stock']; ?>)</option>
                <?php endforeach; ?>
            </select>

            <div style="display: flex; gap: 8px;">
                <div style="flex:1;">
                    <label>🔢 الكمية الواردة:</label>
                    <input type="number" step="0.01" name="qty" placeholder="0" required>
                </div>
                <div style="flex:1;">
                    <label>💵 سعر الشراء للوحدة:</label>
                    <input type="number" step="0.01" name="buy_price" placeholder="0.00" required>
                </div>
            </div>

            <button type="submit" name="save_purchase" class="btn">💾 إدخال البضاعة للمستودع وتسجيل الفاتورة</button>
        </form>
    </div>

    <div class="card">
        <b style="color:#ffd200; font-size:12px; display:block; margin-bottom:6px;">📋 سجل المشتريات الأخيرة:</b>
        <table class="table">
            <thead><tr><th>المورد</th><th>الصنف</th><th>الكمية</th><th>الإجمالي</th></tr></thead>
            <tbody>
                <?php if(!empty($purchases)): foreach($purchases as $pu): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($pu['supplier_name']); ?></td>
                        <td><?php echo htmlspecialchars($pu['product_name']); ?></td>
                        <td><b><?php echo $pu['qty']; ?></b></td>
                        <td style="color:#38ef7d; font-weight:bold;"><?php echo number_format($pu['total'],2); ?></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="4" style="color:#aaa;">لا توجد مشتريات مسجلة.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php include 'signature.php'; ?>
    <a href="main.php" style="display:block; text-align:center; color:#ffd200; text-decoration:none; font-size:12px; margin-top:10px;">⬅️ العودة للرئيسية</a>
</div>

<?php include 'nav_bar.php'; ?>

<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
