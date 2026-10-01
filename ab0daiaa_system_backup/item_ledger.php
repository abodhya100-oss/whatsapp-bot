<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
if(session_status()===PHP_SESSION_NONE){session_start();} if(!isset($_SESSION["user_role"])){header("Location: login.php");exit;}
$db = new SQLite3(__DIR__ . '/vmax.db');
$db->exec("CREATE TABLE IF NOT EXISTS invoice_items (id INTEGER PRIMARY KEY AUTOINCREMENT, invoice_id INTEGER, product_id INTEGER, qty REAL, price REAL)");

$product_id = intval($_GET['product_id'] ?? 0);
$products = [];
$res_p = $db->query("SELECT * FROM products ORDER BY name ASC");
while($p = $res_p->fetchArray(SQLITE3_ASSOC)) { $products[] = $p; }

$selected_product = null;
$ledger = [];
if ($product_id > 0) {
    $selected_product = $db->querySingle("SELECT * FROM products WHERE id = $product_id", true);
    // المشتريات (وارد)
    $res_pur = $db->query("SELECT 'توريد مشتريات' as type, qty as q, total as val, date FROM purchases WHERE product_id = $product_id");
    while($r = $res_pur->fetchArray(SQLITE3_ASSOC)) { $ledger[] = $r; }
    // المرتجعات (وارد)
    $res_ret = $db->query("SELECT 'مرتجع مبيعات' as type, qty as q, total as val, date FROM returns WHERE product_id = $product_id");
    while($r = $res_ret->fetchArray(SQLITE3_ASSOC)) { $ledger[] = $r; }
}
$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <link rel="manifest" href="manifest.json">
    <meta name='mobile-web-app-capable' content='yes'>
    <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js");}</script>
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>كارت الصنف وحركة المخزون - ابوضياء العالمي</title>
    <style>
        body { font-family: 'Tahoma', sans-serif; background: #0f2027; color: #fff; margin: 0; padding: 10px; padding-bottom: 90px; }
        .container { max-width: 680px; margin: auto; }
        h1 { text-align: center; color: #ffd200; font-size: 19px; margin-bottom: 3px; }
        p.sub-title { text-align: center; color: #aaa; font-size: 11px; margin-bottom: 10px; }
        .card { background: rgba(255,255,255,0.08); padding: 12px; border-radius: 12px; margin-bottom: 10px; border: 1px solid rgba(255,255,255,0.15); }
        label { display: block; font-size: 11px; color: #ffd200; margin-bottom: 3px; font-weight: bold; }
        select { width: 100%; padding: 9px; margin-bottom: 8px; background: rgba(0,0,0,0.6); color: #fff; border: 1px solid #555; border-radius: 8px; font-size: 12px; }
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
    <h1>🔍 كارت الصنف وحركة المخزون</h1>
    <p class="sub-title">تتبع السجل التاريخي وحركة الأصناف بالمستودع</p>
    
    <div class="card">
        <form method="GET">
            <label>📦 اختر الصنف:</label>
            <select name="product_id" required>
                <option value="">-- اختر المنتج --</option>
                <?php foreach($products as $p): ?>
                    <option value="<?php echo $p['id']; ?>" <?php echo $product_id==$p['id']?'selected':''; ?>>
                        <?php echo htmlspecialchars($p['name']); ?> (المخزون الحالي: <?php echo $p['stock']; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn">🔍 عرض تفاصيل وتاريخ الصنف</button>
        </form>
    </div>

    <?php if($selected_product): ?>
        <div class="card" style="border-color:#ffd200;">
            <b style="color:#ffd200; font-size:13px; display:block; margin-bottom:5px;">📦 اسم الصنف: <?php echo htmlspecialchars($selected_product['name']); ?></b>
            <div style="font-size:11px; color:#aaa; margin-bottom:3px;">🏷️ التصنيف: <?php echo $selected_product['category']; ?> | الوحدة: <?php echo $selected_product['unit']; ?></div>
            <div style="font-size:12px; font-weight:bold; color:#38ef7d;">
                💎 المخزون الحالي بالمستودع: <?php echo $selected_product['stock']; ?> <?php echo $selected_product['unit']; ?>
            </div>
        </div>

        <div class="card">
            <b style="color:#ffd200; font-size:12px; display:block; margin-bottom:6px;">📋 سجل حركات الصنف الواردة:</b>
            <table class="table">
                <thead><tr><th>نوع الحركة</th><th>الكمية</th><th>القيمة</th><th>التاريخ</th></tr></thead>
                <tbody>
                    <?php if(!empty($ledger)): foreach($ledger as $row): ?>
                        <tr>
                            <td><?php echo $row['type']; ?></td>
                            <td style="color:#38ef7d; font-weight:bold;"><?php echo $row['q']; ?></td>
                            <td><?php echo number_format($row['val'],2); ?> ر.ي</td>
                            <td style="font-size:10px; color:#aaa;"><?php echo $row['date']; ?></td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="4" style="color:#aaa;">لا توجد حركات مسجلة لهذا الصنف حتى الآن.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php include 'signature.php'; ?>
    <a href="main.php" style="display:block; text-align:center; color:#ffd200; text-decoration:none; font-size:12px; margin-top:10px;">⬅️ العودة للرئيسية</a>
</div>

<?php include 'nav_bar.php'; ?>

<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
