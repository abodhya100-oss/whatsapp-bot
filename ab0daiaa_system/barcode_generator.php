<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
if(session_status()===PHP_SESSION_NONE){session_start();} if(!isset($_SESSION["user_role"])){header("Location: login.php");exit;}
$db = new SQLite3('/data/data/com.termux/files/home/secure_dbs/vmax.db');
$products = [];
$res = $db->query("SELECT * FROM products ORDER BY name ASC");
while($p = $res->fetchArray(SQLITE3_ASSOC)) { $products[] = $p; }
$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <link rel="manifest" href="manifest.json">
    <meta name='mobile-web-app-capable' content='yes'>
    <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js");}</script>
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>مولد ملصقات الباركود - نظام ابوضياء العالمي</title>
    <!-- JsBarcode مكتبة توليد الباركود العالمية -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.5/JsBarcode.all.min.js"></script>
    <style>
        body { font-family: 'Tahoma', sans-serif; background: #FFFFFF; color: #fff; margin: 0; padding: 10px; }
        .container { max-width: 680px; margin: auto; }
        h1 { text-align: center; color: #ffd200; font-size: 18px; margin-bottom: 5px; }
        .card { background: rgba(255,255,255,0.08); padding: 12px; border-radius: 12px; margin-bottom: 10px; border: 1px solid rgba(255,255,255,0.15); }
        label { display: block; font-size: 11px; color: #ffd200; margin-bottom: 4px; font-weight: bold; }
        select, input { width: 100%; padding: 9px; margin-bottom: 8px; background: rgba(0,0,0,0.6); color: #fff; border: 1px solid #555; border-radius: 8px; box-sizing: border-box; font-size: 12px; }
        .btn { width: 100%; background: #FFFFFF; color: #000; padding: 11px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 13px; }
        
        .print-sheet { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; background: #FFFFFF; color: #000; padding: 15px; border-radius: 8px; margin-top: 15px; }
        .barcode-tag { border: 1px dashed #000; padding: 8px; text-align: center; width: 160px; border-radius: 4px; background: #FFFFFF; }
        .barcode-tag b { font-size: 11px; display: block; margin-bottom: 2px; }
        .barcode-tag span { font-size: 10px; color: #333; display: block; font-weight: bold; }
        
        @media print {
            body { background: #FFFFFF; color: #000; padding: 0; }
            .card, h1, p, .btn, .nav-bar { display: none !important; }
            .print-sheet { border: none; padding: 0; margin: 0; width: 100%; }
        }
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
<div class="container">
    <h1>🏷️ مولد وطباعة ملصقات الباركود</h1>
    <p style="text-align:center; color:#aaa; font-size:11px; margin-bottom:10px;">نظام ابوضياء العالمي</p>

    <div class="card">
        <label>📦 اختر الصنف لتوليد باركوده:</label>
        <select id="productSelect">
            <?php foreach($products as $p): ?>
                <option value="<?php echo htmlspecialchars($p['barcode'] ?: $p['id']); ?>" data-name="<?php echo htmlspecialchars($p['name']); ?>" data-price="<?php echo floatval($p['sell_price']); ?>">
                    <?php echo htmlspecialchars($p['name']) . ' (باركود: ' . ($p['barcode'] ?: $p['id']) . ')'; ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <label>🔢 عدد الملصقات للطباعة:</label>
        <input type="number" id="labelCount" value="10" min="1" max="50">

        <button type="button" class="btn" onclick="generateBarcodeSheet()">🖨️ توليد ورقة الملصقات للطباعة</button>
    </div>

    <div class="print-sheet" id="printSheet">
        <p style="color:#aaa; text-align:center; width:100%;">اختر صنفاً واضغط توليد لعرض الملصقات هنا.</p>
    </div>

    <button onclick="window.print()" class="btn" style="margin-top:10px; background: #FFFFFF; color:#000;">🖨️ طباعة ورقة الملصقات الآن</button>
    <a href="main.php" style="display:block; text-align:center; margin-top:10px; color:#ffd200; text-decoration:none; font-size:11px;">⬅️ العودة للرئيسية</a>
</div>

<script>
function generateBarcodeSheet() {
    let select = document.getElementById('productSelect');
    let opt = select.options[select.selectedIndex];
    if (!opt) return;

    let code = opt.value;
    let name = opt.getAttribute('data-name');
    let price = opt.getAttribute('data-price');
    let count = parseInt(document.getElementById('labelCount').value) || 10;

    let sheet = document.getElementById('printSheet');
    sheet.innerHTML = '';

    for (let i = 0; i < count; i++) {
        let tagId = 'bc_' + i;
        let div = document.createElement('div');
        div.className = 'barcode-tag';
        div.innerHTML = `
            <b>نظام ابوضياء العالمي</b>
            <span>${name}</span>
            <svg id="${tagId}"></svg>
            <span>${parseFloat(price).toFixed(2)} ر.ي</span>
        `;
        sheet.appendChild(div);

        try {
            JsBarcode("#" + tagId, code, {
                format: "CODE128",
                width: 1.2,
                height: 35,
                displayValue: true,
                fontSize: 10
            });
        } catch(e) {}
    }
}
</script>

<?php include 'nav_bar.php'; ?>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>
</html>

