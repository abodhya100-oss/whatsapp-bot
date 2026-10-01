<?php
// bulk_whatsapp.php - الرسائل الجماعية لـ نظام ابوضياء العالمي
require_once 'auth.php';

$db_file = __DIR__ . '/vmax.db';
if(session_status()===PHP_SESSION_NONE){session_start();} if(!isset($_SESSION["user_role"])){header("Location: login.php");exit;}
$db = new SQLite3($db_file);

$settings = [];
$s_res = $db->query("SELECT * FROM settings");
while ($row = $s_res->fetchArray(SQLITE3_ASSOC)) {
    $settings[$row['key']] = $row['value'];
}
$store_name = $settings['store_name'] ?? 'نظام ابوضياء العالمي لبيع وصيانة الهواتف';
$kuraimi = $settings['kuraimi_account'] ?? '30xxxxxxxxx';

$customers_res = $db->query("SELECT id, name, phone, region, balance FROM customers WHERE phone IS NOT NULL AND phone != '' ORDER BY name ASC");
$customers_list = [];
while($c = $customers_res->fetchArray(SQLITE3_ASSOC)) {
    $customers_list[] = $c;
}
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
    <title>نظام ابوضياء العالمي - رسائل نظام ابوضياء العالمي</title>
    <style>
        body { font-family: 'Tahoma', sans-serif; background: linear-gradient(135deg, #0f2027, #203a43, #2c5364); color: #fff; margin: 0; padding: 0; padding-bottom: 90px; }
        .container { max-width: 680px; margin: 15px auto; padding: 15px; }
        h1 { text-align: center; font-size: 20px; margin-bottom: 4px; color: #25d366; }
        p.sub-title { text-align: center; color: #bbb; font-size: 12px; margin-bottom: 12px; }
        .card { background: rgba(255, 255, 255, 0.08); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.15); border-radius: 14px; padding: 12px; margin-bottom: 12px; box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37); }
        label { display: block; font-size: 11px; color: #25d366; margin-bottom: 3px; font-weight: bold; }
        textarea { width: 100%; height: 130px; padding: 9px; border: 1px solid rgba(255, 255, 255, 0.25); background: rgba(0, 0, 0, 0.5); color: #fff; border-radius: 8px; box-sizing: border-box; font-size: 13px; resize: vertical; }
        .templates-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; margin-bottom: 10px; }
        .tmpl-btn { background: rgba(37, 211, 102, 0.2); border: 1px solid #25d366; color: #fff; padding: 8px; border-radius: 8px; font-size: 11px; font-weight: bold; cursor: pointer; text-align: center; transition: 0.2s; }
        .tmpl-btn:hover { background: rgba(37, 211, 102, 0.4); }
        .cust-row { background: rgba(0, 0, 0, 0.3); padding: 10px; border-radius: 8px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center; border-right: 4px solid #25d366; font-size: 12px; }
        .btn-wa { background: #25d366; color: #fff; padding: 6px 12px; border-radius: 6px; font-size: 11px; text-decoration: none; font-weight: bold; white-space: nowrap; }
        .nav-bar { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(15, 32, 39, 0.95); backdrop-filter: blur(10px); display: flex; justify-content: space-around; padding: 10px 0; border-top: 1px solid rgba(255, 255, 255, 0.1); z-index: 1000; }
        .nav-item { text-align: center; color: #aaa; text-decoration: none; font-size: 12px; }
        .nav-item.active, .nav-item:hover { color: #00f2fe; }
        .nav-item div { font-size: 20px; margin-bottom: 2px; }
    </style>
</head>
<body>

<div class="container">
    <h1>💬 قوالب ورسائل نظام ابوضياء العالمي للواتساب</h1>
    <p class="sub-title">إرسال تذكيرات الديون والسداد والعروض للعملاء</p>

    <div class="card">
        <label>اختر قالب جاهز:</label>
        <div class="templates-grid">
            <button type="button" class="tmpl-btn" onclick="setTemplate(1)">🚨 تذكير بالسداد</button>
            <button type="button" class="tmpl-btn" onclick="setTemplate(2)">📱 عروض الهواتف</button>
            <button type="button" class="tmpl-btn" onclick="setTemplate(3)">🌟 شكر وتقدير</button>
        </div>

        <label>نص الرسالة:</label>
نشكر تعاملك الراقي مع <?php echo htmlspecialchars($store_name); ?> 📱
نود تذكيركم بوجود مبالغ مالية مستحقة لصالح الحساب. ولتسهيل السداد، يمكنكم التحويل عبر حساباتنا المعتمدة:
- حساب الكريمي: <?php echo htmlspecialchars($kuraimi); ?>
- خدمة جوالي / موبايل موني: (<?php echo htmlspecialchars($jawali); ?>)
شاكرين حسن تعاونكم معنا ودعمكم المستمر 🙏</textarea>
    </div>

    <div class="card">
        <h3 style="margin-top:0; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:6px; font-size: 13px; color: #25d366;">
            👥 قائمة العملاء (<?php echo count($customers_list); ?> عميل برقم هاتف)
        </h3>

        <?php if (count($customers_list) > 0): ?>
            <?php foreach ($customers_list as $c): 
                $phone_clean = preg_replace('/[^0-9]/', '', $c['phone']);
            ?>
                <div class="cust-row">
                    <div>
                        <strong style="color: #fff; font-size: 13px;" data-name="<?php echo htmlspecialchars($c['name']); ?>"><?php echo htmlspecialchars($c['name']); ?></strong>
                        <div style="color: #bbb; font-size: 11px; margin-top: 2px;">الهاتف: <b><?php echo htmlspecialchars($c['phone']); ?></b> | المديونية: <span style="color:<?php echo ($c['balance']>0 ? '#ff4d4d':'#38ef7d'); ?>; font-weight:bold;"><?php echo number_format($c['balance'],2); ?> ر.ي</span></div>
                    </div>
                    <div>
                        <a href="#" onclick="sendWhatsApp('<?php echo $phone_clean; ?>', this); return false;" class="btn-wa">💬 مراسلة واتساب</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align: center; color: #aaa; font-size: 11px;">لا يوجد عملاء مسجلين بأرقام هواتف حالياً.</p>
        <?php endif; ?>
    </div>
</div>

<script>
نشكر تعاملك الراقي مع <?php echo htmlspecialchars($store_name); ?> 📱
نود تذكيركم بوجود مبالغ مالية مستحقة لصالح الحساب. ولتسهيل السداد، يمكنكم التحويل عبر حساباتنا المعتمدة:
- حساب الكريمي: <?php echo htmlspecialchars($kuraimi); ?>
- خدمة جوالي / موبايل موني: (<?php echo htmlspecialchars($jawali); ?>)
شاكرين حسن تعاونكم معنا ودعمكم المستمر 🙏`;

يسعدنا إبلاغكم بوصول تشكيلة جديدة ومميزة من أحدث الهواتف الذكية والإكسسوارات الأصلية بأسعار خاصة لعملائنا المميزين في <?php echo htmlspecialchars($store_name); ?>.
نتشرف بزيارتكم دائماً!`;

نشكر ثقتكم المستمرة بـ <?php echo htmlspecialchars($store_name); ?>. نحن دائماً في خدمتكم لأي استفسار، صيانة، أو احتياجات تقنية. دمتم بخير وصحة وعافية!`;

function setTemplate(id) {
    const box = document.getElementById('msgBox');
    if(id === 1) box.value = t1;
    if(id === 2) box.value = t2;
    if(id === 3) box.value = t3;
}

function sendWhatsApp(phone, el) {
    const custRow = el.closest('.cust-row');
    const customerName = custRow.querySelector('strong').getAttribute('data-name');
    let msg = document.getElementById('msgBox').value;
    msg = msg.replace(/\[اسم العميل\]/g, customerName);
    const url = "https://wa.me/" + phone + "?text=" + encodeURIComponent(msg);
    window.open(url, '_blank');
}
</script>


<?php include 'nav_bar.php'; ?>

<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
