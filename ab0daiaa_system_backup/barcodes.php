<?php
require_once 'auth.php';
$db = new SQLite3(__DIR__ . '/vmax.db');
$products = [];
$res = $db->query("SELECT * FROM products ORDER BY id DESC LIMIT 20");
while($row = $res->fetchArray(SQLITE3_ASSOC)) { $products[] = $row; }
$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl"><head>
<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><meta name='mobile-web-app-capable' content='yes'>
<meta name='apple-mobile-web-app-capable' content='yes'>
<meta charset="UTF-8"><title>توليد الباركود</title>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<?php if(file_exists('global_style.php')) include 'global_style.php'; ?>
<style>@media print { body { background:#fff; color:#000; } .no-print, .lux-wrapper, .global-nav { display:none!important; } .print-area { display:block!important; } }</style>
</head><body>
<div class="lux-wrapper">
    <div class="lux-card"><h2 class="lux-title">🏷️ صانع الباركود (الإكسسوارات)</h2>
    <p style="font-size:11px; color:#94a3b8;">سيتم توليد باركود لآخر 20 صنف لطباعتها ولصقها.</p>
    <button onclick="window.print()" class="lux-btn">🖨️ طباعة الملصقات</button>
    </div>
</div>
<div class="print-area" style="display:none; text-align:center; background:#fff; padding:10px;">
    <?php foreach($products as $p): ?>
        <div style="display:inline-block; border:1px solid #000; padding:10px; margin:5px; text-align:center; width:45%;">
            <div style="font-size:10px; font-weight:bold; color:#000;"><?php echo htmlspecialchars($p['name']); ?></div>
            <svg class="barcode" jsbarcode-format="CODE128" jsbarcode-value="HP-<?php echo $p['id']; ?>" jsbarcode-textmargin="0" jsbarcode-height="30" jsbarcode-width="1.5"></svg>
            <div style="font-size:12px; font-weight:bold; color:#000;">السعر: <?php echo $p['price']; ?> ر.ي</div>
        </div>
    <?php endforeach; ?>
</div>
<script>JsBarcode(".barcode").init();</script>
<?php if(file_exists('nav_menu.php')) include 'nav_menu.php'; ?></body></html>
