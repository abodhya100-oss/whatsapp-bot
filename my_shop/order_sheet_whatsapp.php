<?php

if (isset($_POST['fast_edit_id'])) {
    $f_id = $_POST['fast_edit_id'];
    $f_name = $_POST['fast_edit_name'];
    $f_price = $_POST['fast_edit_price'];
    try {
        $db->exec("UPDATE products SET name='" . SQLite3::escapeString($f_name) . "', price='" . SQLite3::escapeString($f_price) . "' WHERE id='" . SQLite3::escapeString($f_id) . "'");
    } catch (Exception $e) {}
}

ini_set('display_errors', 0);
error_reporting(E_ALL);
if(session_status()===PHP_SESSION_NONE){session_start();} if(!isset($_SESSION["user_role"])){header("Location: login.php");exit;}
$db = new SQLite3(__DIR__ . '/vmax.db');
$db->exec("CREATE TABLE IF NOT EXISTS products (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, buy_price REAL DEFAULT 0, sell_price REAL DEFAULT 0, stock REAL DEFAULT 0, unit TEXT DEFAULT 'قطعة', barcode TEXT, category TEXT DEFAULT 'عام');");

$products = [];
$res = $db->query("SELECT * FROM products ORDER BY name ASC");
while($p = $res->fetchArray(SQLITE3_ASSOC)) { $products[] = $p; }
$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <link rel="manifest" href="manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js");}</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مُجهّز طلبيات الواتساب - نظام ابوضياء العالمي</title>
    <style>
        body { font-family: 'Tahoma', sans-serif; background: #0f2027; color: #fff; margin: 0; padding: 10px; padding-bottom: 90px; }
        .container { max-width: 680px; margin: auto; }
        h1 { text-align: center; color: #ffd200; font-size: 19px; margin-bottom: 3px; }
        p.sub-title { text-align: center; color: #aaa; font-size: 11px; margin-bottom: 12px; }
        .card { background: rgba(255,255,255,0.08); padding: 12px; border-radius: 12px; margin-bottom: 10px; border: 1px solid rgba(255,255,255,0.15); box-shadow: 0 4px 15px rgba(0,0,0,0.3); }
        label { display: block; font-size: 11px; color: #ffd200; margin-bottom: 4px; font-weight: bold; }
        input { width: 100%; padding: 9px; margin-bottom: 8px; background: rgba(0,0,0,0.6); color: #fff; border: 1px solid #555; border-radius: 8px; box-sizing: border-box; font-size: 12px; }
        
        .table { width: 100%; border-collapse: collapse; font-size: 11px; background: rgba(0,0,0,0.4); border-radius: 8px; overflow: hidden; margin-top: 6px; }
        .table th, .table td { padding: 8px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .table th { background: rgba(255,210,0,0.2); color: #ffd200; font-weight: bold; }
        
        .qty-input { width: 65px; padding: 5px; text-align: center; background: rgba(0,255,0,0.1); border: 1px solid #38ef7d; color: #38ef7d; font-weight: bold; border-radius: 6px; font-size: 12px; }
        
        .btn-wa { display: block; width: 100%; background: #25d366; color: #fff; text-align: center; padding: 12px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 13px; margin-top: 10px; box-shadow: 0 4px 15px rgba(37,211,102,0.4); cursor: pointer; border: none; }
        .btn-back { display: block; text-align: center; color: #ffd200; text-decoration: none; font-size: 11px; margin-top: 15px; }
    </style>
</head>
<body>

<div class="container">
    <h1>📦 مُجهّز طلبيات الواتساب</h1>
    <p class="sub-title">إرسال قائمة الأصناف للعميل لتحديد الكميات - نظام ابوضياء العالمي | نظام ابوضياء العالمي</p>

    <div class="card">
        <label>📞 رقم هاتف العميل (اختياري للإرسال المباشر):</label>
        <p style="font-size:10px; color:#aaa; margin-top:2px;">💡 إذا تركت الرقم فارغاً، سيتم تجهيز الرسالة وفتح واتساب لاختيار العميل أو المجموعة التي تريد إرسالها إليها.</p>
    </div>

    <div class="card">
        <b style="color:#ffd200; font-size:12px; display:block; margin-bottom:6px;">📋 حدد الكميات المطلوبة لكل صنف:</b>
        
        <table class="table">
            <thead>
                <tr>
                    <th>الصنف</th>
                    <th>السعر</th>
                    <th>المتوفر</th>
                    <th>الكمية المطلوبة</th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($products)): ?>
                    <?php foreach($products as $p): ?>
                        <tr class="product-row" data-name="<?php echo htmlspecialchars($p['name']); ?>" data-price="<?php echo floatval($p['sell_price']); ?>">
                            <td style="text-align:right; font-weight:bold;"><?php echo htmlspecialchars($p['name']); ?></td>
                            <td style="color:#38ef7d;"><?php echo number_format($p['sell_price'], 2); ?></td>
                            <td><span style="color:#aaa;"><?php echo $p['stock']; ?></span></td>
                            <td>
                                <input type="number" step="0.01" class="qty-input" value="0" min="0">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="color:#aaa; padding:10px;">لا توجد منتجات مسجلة في المستودع حالياً.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <button type="button" class="btn-wa" onclick="sendOrderViaWhatsApp()">💬 إرسال طلبية الأصناف المنسقة عبر واتساب</button>
    </div>

    <a href="main.php" class="btn-back">⬅️ العودة للرئيسية</a>
</div>

<script>
function sendOrderViaWhatsApp() {
    let text = "📦 *طلب بضاعة جديد - نظام ابوضياء العالمي (سيئون)*\n";
    text += "👤 *المرسل:* طلبية عميل معتمدة\n";
    text += "----------------------------------\n\n";

    let rows = document.querySelectorAll('.product-row');
    let count = 0;
    let grandTotal = 0;

    rows.forEach(row => {
        let qtyInput = row.querySelector('.qty-input');
        let qty = parseFloat(qtyInput.value) || 0;
        if (qty > 0) {
            let name = row.getAttribute('data-name');
            let price = parseFloat(row.getAttribute('data-price'));
            let lineTotal = qty * price;
            grandTotal += lineTotal;
            text += `▪️ *${name}*\n  الكمية: *${qty}* | السعر: ${price.toFixed(2)} | الإجمالي: *${lineTotal.toFixed(2)} ر.ي*\n\n`;
            count++;
        }
    });

    if (count === 0) {
        alert("⚠️ يرجى تحديد كمية (أكبر من صفر) لصنف واحد على الأقل!");
        return;
    }

    text += "----------------------------------\n";
    text += `💰 *إجمالي قيمة الطلبية المتوقع:* *${grandTotal.toFixed(2)} ر.ي*\n`;

    let phone = document.getElementById('targetPhone').value.trim();
    let url = "";
    if (phone) {
        let cleanPhone = phone.replace(/^0/, '');
        if(!cleanPhone.startsWith('967')) cleanPhone = '967' + cleanPhone;
        url = `https://wa.me/${cleanPhone}?text=` + encodeURIComponent(text);
    } else {
        url = `https://wa.me/?text=` + encodeURIComponent(text);
    }

    window.open(url, '_blank');
}
</script>


<?php include 'nav_bar.php'; ?>

<script>
function editProduct(id, currentName, currentPrice) {
    let newName = prompt("تعديل اسم المنتج:", currentName);
    if (newName === null) return;
    let newPrice = prompt("تعديل سعر الحبة:", currentPrice);
    if (newPrice === null) return;
    
    // إرسال التعديل لنفس الصفحة أو ملف التحديث
    window.location.href = window.location.pathname + '?edit_id=' + id + '&name=' + encodeURIComponent(newName) + '&price=' + encodeURIComponent(newPrice);
}
</script>

<script>
function fastEdit(id, name, price) {
    let n = prompt("تعديل اسم المنتج:", name);
    if (n === null) return;
    let p = prompt("تعديل سعر الحبة:", price);
    if (p === null) return;
    
    let f = document.createElement('form');
    f.method = 'POST';
    f.innerHTML = '<input type="hidden" name="fast_edit_id" value="'+id+'"><input type="hidden" name="fast_edit_name" value="'+n+'\><input type="hidden" name="fast_edit_price" value="'+p+'">';
    document.body.appendChild(f);
    f.submit();
}
</script>
<?php include "nav_menu.php"; ?>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>

</html>

