<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_role'])) { header("Location: login.php"); exit; }
$db = new SQLite3('vmax.db');
$items = [];
$res = $db->query("SELECT id, item_name, barcode, sell_price FROM inventory WHERE barcode != '' AND barcode IS NOT NULL");
while ($row = $res->fetchArray(SQLITE3_ASSOC)) { $items[] = $row; }

$print_item = null;
$copies = 1;
if (isset($_GET['item_id'])) {
    $id = intval($_GET['item_id']);
    $copies = intval($_GET['copies'] ?? 1);
    $print_item = $db->querySingle("SELECT * FROM inventory WHERE id = $id", true);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl"><head>
    <link rel="manifest" href="manifest.json">
    <meta name='mobile-web-app-capable' content='yes'>
    <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js");}</script><meta charset="UTF-8"><meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>طباعة الباركود</title>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.0/dist/JsBarcode.all.min.js"></script>
<style>
    body { font-family: 'Cairo', sans-serif; background: #0f172a; color: #fff; padding: 20px; }
    .form-control { padding: 8px; border-radius: 6px; border: none; background: #1e293b; color: #fff; border: 1px solid #a855f7; }
    .btn { padding: 8px 15px; background: #a855f7; border: none; color: #fff; border-radius: 6px; cursor: pointer; font-weight: bold; }
    .label-sheet { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 20px; background: #fff; padding: 10px; border-radius: 8px; color: #000; }
    .barcode-label { width: 38mm; height: 25mm; border: 1px dashed #ccc; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 2px; box-sizing: border-box; text-align: center; }
    .label-title { font-size: 10px; font-weight: bold; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; }
    .label-price { font-size: 12px; font-weight: 900; }
    @media print { body * { visibility: hidden; } .label-sheet, .label-sheet * { visibility: visible; } .label-sheet { position: absolute; left: 0; top: 0; background: transparent; padding: 0; margin: 0; } .barcode-label { border: none; } }
</style></head><body>
    <h2 class="no-print" style="color: #a855f7; text-align:center;">🏷️ مولد وطباعة الباركود</h2>
    <form class="no-print" method="GET" style="background:#1e293b; padding:15px; border-radius:10px; text-align:center;">
        <select name="item_id" class="form-control" required>
            <option value="">-- اختر الصنف --</option>
            <?php foreach($items as $i): ?>
                <option value="<?php echo $i['id']; ?>" <?php if(isset($print_item) && $print_item['id'] == $i['id']) echo 'selected'; ?>><?php echo $i['item_name']; ?> (<?php echo $i['barcode']; ?>)</option>
            <?php endforeach; ?>
        </select>
        <input type="number" name="copies" class="form-control" value="<?php echo $copies; ?>" min="1" max="100" style="width:80px;" placeholder="عدد النسخ">
        <button type="submit" class="btn">معاينة</button>
        <?php if($print_item): ?> <button type="button" onclick="window.print()" class="btn" style="background:#22c55e;">🖨️ طباعة الملصقات</button> <?php endif; ?>
    </form>
    
    <?php if($print_item): ?>
    <div class="label-sheet">
        <?php for($k=0; $k<$copies; $k++): ?>
        <div class="barcode-label">
            <div class="label-title"><?php echo mb_strimwidth($print_item['item_name'], 0, 18, '...'); ?></div>
            <svg class="barcode" jsbarcode-value="<?php echo $print_item['barcode']; ?>" jsbarcode-width="1.2" jsbarcode-height="25" jsbarcode-fontsize="10" jsbarcode-margin="0"></svg>
            <div class="label-price"><?php echo number_format($print_item['sell_price']); ?> ر.ي</div>
        </div>
        <?php endfor; ?>
    </div>
    <script>JsBarcode(".barcode").init();</script>
    <?php endif; ?>
    <br><a href="dashboard.php" class="btn no-print" style="background:#475569;">عودة</a>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
<?php if(file_exists('nav_menu.php')) { include 'nav_menu.php'; } ?>
</body></html>

