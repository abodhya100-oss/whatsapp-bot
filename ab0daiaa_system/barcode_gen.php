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
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>مولد وطابع الباركود - ابوضياء العالمي</title>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <style>
        body { font-family: 'Tahoma', sans-serif; background: #FFFFFF; color: #fff; margin: 0; padding: 10px; padding-bottom: 90px; }
        .container { max-width: 680px; margin: auto; }
        h1 { text-align: center; color: #ffd200; font-size: 19px; margin-bottom: 3px; }
        p.sub-title { text-align: center; color: #aaa; font-size: 11px; margin-bottom: 10px; }
        .card { background: rgba(255,255,255,0.08); padding: 12px; border-radius: 12px; margin-bottom: 10px; border: 1px solid rgba(255,255,255,0.15); text-align: center; }
        select { width: 100%; padding: 9px; margin-bottom: 8px; background: rgba(0,0,0,0.6); color: #fff; border: 1px solid #555; border-radius: 8px; font-size: 12px; }
        select option { background: #FFFFFF; color: #fff; }
        .barcode-box { background: #FFFFFF; color: #000; padding: 15px; border-radius: 8px; display: inline-block; margin-top: 10px; }
        .btn-print { background: #FFFFFF; color: #fff; padding: 10px 15px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 12px; margin-top: 10px; display: block; width: 100%; }
        .nav-bar { position: fixed; bottom: 0; left: 0; right: 0; background: #FFFFFF; border-top: 1px solid rgba(255,210,0,0.2); padding: 6px 0; text-align: center; z-index: 1000; }
        .nav-links { display: flex; justify-content: space-around; font-size: 12px; }
        .nav-links a { color: #aaa; text-decoration: none; font-weight: bold; text-align: center; }
        .nav-links a.active { color: #ffd200; }
        @media print {
            body { background: #fff !important; color: #000 !important; }
            .container > *:not(.card) { display: none !important; }
            .card { border: none !important; background: #fff !important; }
            .nav-bar { display: none !important; }
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
    <h1>🏷️ مولد وطابع باركود المنتجات</h1>
    <p class="sub-title">توليد وطباعة ملصقات الباركود للأصناف</p>
    
    <div class="card">
        <label style="color:#ffd200; font-size:11px; font-weight:bold; text-align:right; display:block; margin-bottom:3px;">اختر الصنف:</label>
        <select id="prodSelect" onchange="generateBarcode()">
            <option value="" data-bc="">-- اختر المنتج --</option>
            <?php foreach($products as $p): 
                $bc = $p['barcode'] ?: ('PRD' . sprintf('%04d', $p['id']));
            ?>
                <option value="<?php echo htmlspecialchars($p['name']); ?>" data-bc="<?php echo $bc; ?>" data-price="<?php echo $p['sell_price']; ?>">
                    <?php echo htmlspecialchars($p['name']); ?> (<?php echo $p['sell_price']; ?> ر.ي)
                </option>
            <?php endforeach; ?>
        </select>

        <div class="barcode-box" id="barcodeContainer" style="display:none;">
            <div id="prodTitle" style="font-weight:bold; font-size:13px; margin-bottom:5px; color:#000;"></div>
            <svg id="barcodeSvg"></svg>
            <div id="prodPrice" style="font-size:12px; font-weight:bold; margin-top:5px; color:#000;"></div>
        </div>

        <button type="button" class="btn-print" id="printBtn" style="display:none;" onclick="window.print()">🖨️ طباعة ملصق الباركود</button>
    </div>

    <?php include 'signature.php'; ?>
    <a href="main.php" style="display:block; text-align:center; color:#ffd200; text-decoration:none; font-size:12px; margin-top:10px;">⬅️ العودة للرئيسية</a>
</div>

<?php include 'nav_bar.php'; ?>

<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
