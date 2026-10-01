<?php
// client_gps.php - نظام تحديد مواقع العملاء
error_reporting(E_ALL);
ini_set('display_errors', 1);

$db_file = 'vmax.db';
if (!file_exists($db_file)) {
    $db_file = 'database.db';
}

$db = new SQLite3($db_file);

@$db->exec("ALTER TABLE customers ADD COLUMN lat TEXT");
@$db->exec("ALTER TABLE customers ADD COLUMN lng TEXT");
@$db->exec("ALTER TABLE customers ADD COLUMN address_notes TEXT");

$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_new') {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $lat = trim($_POST['lat'] ?? '');
        $lng = trim($_POST['lng'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if (!empty($name)) {
            $stmt = $db->prepare("INSERT INTO customers (name, phone, lat, lng, address_notes) VALUES (?, ?, ?, ?, ?)");
            $stmt->bindValue(1, $name, SQLITE3_TEXT);
            $stmt->bindValue(2, $phone, SQLITE3_TEXT);
            $stmt->bindValue(3, $lat, SQLITE3_TEXT);
            $stmt->bindValue(4, $lng, SQLITE3_TEXT);
            $stmt->bindValue(5, $notes, SQLITE3_TEXT);
            if ($stmt->execute()) {
                $msg = "تم إضافة العميل وحفظ موقعه بنجاح!";
            } else {
                $msg = "حدث خطأ أثناء الحفظ.";
            }
        } else {
            $msg = "اسم العميل مطلوب.";
        }
    } elseif ($action === 'update_loc') {
        $cid = intval($_POST['customer_id'] ?? 0);
        $lat = trim($_POST['lat'] ?? '');
        $lng = trim($_POST['lng'] ?? '');
        
        if ($cid > 0 && !empty($lat)) {
            $stmt = $db->prepare("UPDATE customers SET lat = ?, lng = ? WHERE id = ?");
            $stmt->bindValue(1, $lat, SQLITE3_TEXT);
            $stmt->bindValue(2, $lng, SQLITE3_TEXT);
            $stmt->bindValue(3, $cid, SQLITE3_INTEGER);
            if ($stmt->execute()) {
                $msg = "تم تحديث موقع العميل بنجاح!";
            } else {
                $msg = "فشل تحديث الموقع.";
            }
        }
    }
}

$customers = [];
$res = @$db->query("SELECT * FROM customers ORDER BY id DESC");
if ($res) {
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $customers[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" style="direction: rtl; unicode-bidi: embed;">
<head>
<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><meta name='mobile-web-app-capable' content='yes'>
<meta name='apple-mobile-web-app-capable' content='yes'>

    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>إدارة مواقع العملاء - مؤسسة أبو ضياء</title>
    <style>
        * { box-sizing: border-box; font-family: 'Tahoma', Arial, sans-serif; margin: 0; padding: 0; direction: rtl; text-align: right; }
        body { background-color: #F8FAFC; color: #1E293B; padding-bottom: 70px; }
        .header { background: #1E3A8A; color: white; padding: 20px 15px; text-align: center; border-bottom-left-radius: 20px; border-bottom-right-radius: 20px; }
        .header h1 { font-size: 18px; margin-bottom: 4px; font-weight: bold; }
        .header p { font-size: 11px; opacity: 0.9; }
        .container { max-width: 600px; margin: 15px auto; padding: 0 10px; }
        .card { background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 12px; padding: 15px; margin-bottom: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.03); }
        .client-name { font-size: 14px; font-weight: bold; color: #1E3A8A; margin-bottom: 4px; }
        .client-details { font-size: 12px; color: #64748B; margin-bottom: 10px; line-height: 1.5; }
        .form-group { margin-bottom: 12px; }
        .form-label { display: block; font-size: 12px; font-weight: bold; color: #1E3A8A; margin-bottom: 4px; }
        .form-input { width: 100%; padding: 10px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 12px; outline: none; }
        .gps-btn { background: #0284C7; color: white; border: none; padding: 10px; border-radius: 8px; font-size: 12px; font-weight: bold; width: 100%; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; margin-bottom: 6px; }
        .save-btn { background: #16A34A; color: white; border: none; padding: 12px; border-radius: 8px; font-size: 13px; font-weight: bold; width: 100%; cursor: pointer; }
        .actions { display: flex; gap: 8px; margin-top: 10px; }
        .btn { flex: 1; padding: 8px 10px; border-radius: 6px; font-size: 11px; font-weight: bold; text-decoration: none; text-align: center; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 4px; color: white; }
        .btn-map { background: #0284C7; }
        .btn-whatsapp { background: #16A34A; }
        .alert { background: #DCFCE7; color: #166534; padding: 10px; border-radius: 8px; margin-bottom: 15px; font-size: 12px; text-align: center; font-weight: bold; }
        .coords-info { font-size: 10px; color: #64748B; background: #F1F5F9; padding: 6px; border-radius: 6px; text-align: center; }
        .no-data { text-align: center; color: #64748B; padding: 30px; font-size: 12px; }
        .toggle-btn { background: #D97706; color: white; border: none; padding: 10px 15px; border-radius: 20px; font-size: 12px; font-weight: bold; cursor: pointer; width: 100%; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>مؤسسة أبو ضياء للتوزيع</h1>
        <p>نظام تحديد مواقع العملاء (GPS)</p>
    </div>
    <div class="container">
        <?php if(!empty($msg)): ?>
            <div class="alert"><?php echo $msg; ?></div>
        <?php endif; ?>

        <button class="toggle-btn" onclick="toggleForm()">➕ إضافة عميل جديد وتحديد موقعه (GPS)</button>

        <div id="addForm" class="card" style="display: none; border: 2px solid #D97706;">
            <div class="client-name" style="margin-bottom: 10px;">تسجيل عميل جديد:</div>
            <form method="POST">
                <input type="hidden" name="action" value="add_new">
                <div class="form-group">
                    <label class="form-label">اسم العميل / المحل:</label>
                    <input type="text" name="name" class="form-input" placeholder="اسم المحل أو الزبون" required>
                </div>
                <div class="form-group">
                    <label class="form-label">رقم الهاتف:</label>
                    <input type="text" name="phone" class="form-input" placeholder="77xxxxxxx" dir="ltr">
                </div>
                <div class="form-group">
                    <label class="form-label">الموقع الجغرافي (GPS):</label>
                    <button type="button" class="gps-btn" onclick="getGPS('new_lat', 'new_lng', 'new_status')">
                        📍 التقاط موقعي الحالي عند العميل بدقة
                    </button>
                    <div class="coords-info" id="new_status">لم يتم التقاط الموقع بعد</div>
                    <input type="hidden" id="new_lat" name="lat">
                    <input type="hidden" id="new_lng" name="lng">
                </div>
                <div class="form-group">
                    <label class="form-label">ملاحظات العنوان:</label>
                    <input type="text" name="notes" class="form-input" placeholder="مثال: بجوار الشارع العام">
                </div>
                <button type="submit" class="save-btn">💾 حفظ العميل والموقع</button>
            </form>
        </div>

        <div style="font-size: 12px; font-weight: bold; color: #475569; margin: 15px 0 10px 5px;">قائمة العملاء والمحلات المسجلة:</div>

        <?php if(empty($customers)): ?>
            <div class="card"><div class="no-data">لا يوجد عملاء مسجلين في قاعدة البيانات حتى الآن.</div></div>
        <?php else: ?>
            <?php foreach($customers as $c): 
                $lat = $c['lat'] ?? '';
                $lng = $c['lng'] ?? '';
                $has_coords = (!empty($lat) && !empty($lng));
                $map_url = $has_coords ? "https://maps.google.com/?q={$lat},{$lng}" : "https://maps.google.com/?q=Seiyun";
            ?>
            <div class="card">
                <div class="client-name">🛒 <?php echo htmlspecialchars($c['name'] ?? 'بدون اسم'); ?></div>
                <div class="client-details">
                    📞 الهاتف: <span dir="ltr"><?php echo htmlspecialchars($c['phone'] ?? 'غير متوفر'); ?></span><br>
                    📍 الحالة: <?php echo $has_coords ? '<span style="color: #16A34A; font-weight: bold;">✅ متموضع ع الخريطة</span>' : '<span style="color: #DC2626;">⚠️ بدون إحداثيات GPS</span>'; ?><br>
                    <?php if($has_coords): ?>
                        🌐 الإحداثيات: <span dir="ltr" style="font-size: 10px;"><?php echo "{$lat}, {$lng}"; ?></span><br>
                    <?php endif; ?>
                </div>

                <div style="background: #F8FAFC; padding: 8px; border-radius: 6px; margin-bottom: 8px;">
                    <form method="POST" style="display: flex; gap: 5px;">
                        <input type="hidden" name="action" value="update_loc">
                        <input type="hidden" name="customer_id" value="<?php echo $c['id']; ?>">
                        <input type="hidden" id="lat_<?php echo $c['id']; ?>" name="lat">
                        <input type="hidden" id="lng_<?php echo $c['id']; ?>" name="lng">
                        <button type="button" class="gps-btn" style="margin:0; font-size: 10px; padding: 6px;" onclick="getGPS('lat_<?php echo $c['id']; ?>', 'lng_<?php echo $c['id']; ?>', 'st_<?php echo $c['id']; ?>')">
                            📍 تحديث موقعه الآن
                        </button>
                        <button type="submit" class="save-btn" style="margin:0; font-size: 10px; padding: 6px; width: auto;">حفظ</button>
                    </form>
                    <div id="st_<?php echo $c['id']; ?>" style="font-size: 9px; color: #64748B; margin-top: 3px; text-align: center;">اضغط لتثبيت موقعك الحالي له</div>
                </div>

                <div class="actions">
                    <a href="<?php echo $map_url; ?>" target="_blank" class="btn btn-map">🗺️ عرض ع الخريطة</a>
                    <button onclick="shareWhatsApp('<?php echo htmlspecialchars($c['name'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($c['phone'] ?? '', ENT_QUOTES); ?>', '<?php echo $map_url; ?>')" class="btn btn-whatsapp">💬 إرسال للواتساب</button>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        function toggleForm() {
            const form = document.getElementById('addForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        function getGPS(latId, lngId, statusId) {
            const status = document.getElementById(statusId);
            if (!navigator.geolocation) { status.innerHTML = "متصفحك لا يدعم تحديد الموقع."; return; }
            status.innerHTML = "جاري التقاط الإحداثيات من الأقمار الصناعية... انتظر ⏳";
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    document.getElementById(latId).value = pos.coords.latitude;
                    document.getElementById(lngId).value = pos.coords.longitude;
                    status.innerHTML = "✅ تم التقاط الموقع بنجاح!";
                    status.style.color = "#166534";
                },
                () => { status.innerHTML = "❌ فشل التقاط الموقع. تأكد من تفعيل الـ GPS."; status.style.color = "#DC2626"; },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        }
        function shareWhatsApp(name, phone, mapUrl) {
            const msg = "📍 *موقع العميل / المحل التجاري*\n\n👤 *اسم العميل:* " + name + "\n📞 *الهاتف:* " + (phone || 'غير متوفر') + "\n\n🗺️ *رابط الخريطة:*\n" + mapUrl + "\n\nصادر عن: مؤسسة أبو ضياء للتوزيع 🌟";
            window.open("https://wa.me/?text=" + encodeURIComponent(msg), "_blank");
        }
    </script>
</body>
</html>
