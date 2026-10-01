<?php
// location.php - شاشة تحديد وعرض موقع المؤسسة والاتصال (مستقلة تماماً)
error_reporting(E_ALL & ~E_WARNING & ~E_DEPRECATED);  
ini_set('display_errors', 0);

require_once 'auth.php';
// ملف العنوان والموقع (يمكن تحديث الإحداثيات والنصوص بسهولة من هنا أو حفظها)
$store_name = "مؤسسة أبو ضياء للتوزيع";
$city = "سيئون، السوق العام";
$country = "اليمن - حضرموت";
$phone = "771458609";
// رابط افتراضي لخرائط جوجل لمدينة سيئون (يمكن استبداله برابط موقعك الدقيق من خرائط جوجل)
$google_maps_link = "https://maps.app.goo.gl/..."; 
$notes = "مرحباً بكم في مقر المؤسسة الرئيسي. نسعد بزيارتكم وتلبية طلباتكم.";
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" style="direction: rtl; unicode-bidi: embed;">
<head>
<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><meta name='mobile-web-app-capable' content='yes'>
<meta name='apple-mobile-web-app-capable' content='yes'>

    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>موقع المؤسسة - مؤسسة أبو ضياء</title>
    <?php include 'global_style.php'; ?>
    <style>
        * { direction: rtl; text-align: right; box-sizing: border-box; font-family: 'Tahoma', Arial, sans-serif; }
        body { margin: 0; padding: 0; background: #F8FAFC; }
        .lux-wrapper { padding: 12px; max-width: 600px; margin: 0 auto 75px auto; }
        
        .loc-card { background: #FFF; border: 1px solid #CBD5E1; border-radius: 12px; padding: 20px; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.03); margin-top: 15px; }
        .loc-icon { font-size: 45px; margin-bottom: 10px; }
        .loc-title { font-size: 18px; font-weight: bold; color: #1E3A8A; margin-bottom: 5px; }
        .loc-address { font-size: 13px; color: #475569; margin-bottom: 15px; font-weight: bold; }
        
        .info-box { background: #F1F5F9; border-radius: 8px; padding: 12px; text-align: right; margin-bottom: 15px; font-size: 12px; color: #334155; line-height: 1.6; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 6px; border-bottom: 1px dashed #CBD5E1; padding-bottom: 4px; }
        .info-label { font-weight: bold; color: #1E3A8A; }

        .btn-action { display: flex; align-items: center; justify-content: center; gap: 6px; width: 100%; padding: 12px; border-radius: 8px; font-size: 13px; font-weight: bold; text-decoration: none; border: none; cursor: pointer; margin-bottom: 10px; }
        .btn-map { background: #0284C7; color: #FFF; }
        .btn-whatsapp { background: #16A34A; color: #FFF; }
    </style>
</head>
<body>
<div class="lux-wrapper">
    <h2 style="font-size: 15px; color: #1E3A8A; margin-bottom: 5px;">📍 موقع ومعلومات المؤسسة</h2>

    <div class="loc-card">
        <div class="loc-icon">📍</div>
        <div class="loc-title"><?php echo $store_name; ?></div>
        <div class="loc-address">الجمهورية اليمنية - حضرموت - <?php echo $city; ?></div>

        <div class="info-box">
            <div class="info-row">
                <span class="info-label">اسم المسؤول:</span>
                <span>أبو ضياء (محمد حيمد)</span>
            </div>
            <div class="info-row">
                <span class="info-label">رقم الهاتف:</span>
                <span dir="ltr"><?php echo $phone; ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">العنوان بالتفصيل:</span>
                <span><?php echo $city; ?>، بجوار السوق العام</span>
            </div>
            <div style="margin-top: 8px; font-size: 11px; color: #64748B;">
                <?php echo $notes; ?>
            </div>
        </div>

        <!-- أزرار التوجيه والمشاركة -->
        <a href="https://maps.google.com/?q=Seiyun" target="_blank" class="btn-action btn-map">
            🗺️ فتح الموقع مباشرة عبر خرائط جوجل (Google Maps)
        </a>

        <button onclick="shareLocationWhatsApp()" class="btn-action btn-whatsapp">
            💬 إرسال تفاصيل الموقع والعنوان للزبون عبر واتساب
        </button>
    </div>
</div>

<script>
function shareLocationWhatsApp() {
    const msg = "*📍 تفاصيل عنوان وموقع مؤسسة أبو ضياء للتوزيع*\n\n" +
              "🏢 *اسم المؤسسة:* مؤسسة أبو ضياء للتوزيع\n" +
              "🌍 *المدينة:* سيئون، السوق العام (حضرموت، اليمن)\n" +
              "📞 *هاتف التواصل:* 771458609\n\n" +
              "🗺️ *يمكنكم الوصول إلينا عبر خرائط جوجل من خلال الرابط التالي:*\n" +
              "https://maps.google.com/?q=Seiyun\n\n" +
              "نسعد بزيارتكم وخدمتكم في أي وقت!";
              
    window.open("https://wa.me/?text=" + encodeURIComponent(msg), "_blank");
}
</script>

<?php include 'nav_menu.php'; ?>
</body>
</html>
