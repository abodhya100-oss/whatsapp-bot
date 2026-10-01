<?php
// clients_map.php - إدارة وعرض وتحديد مواقع العملاء (نسخة مندمجة ومستقلة 100%)
error_reporting(E_ALL);
ini_set('display_errors', 1);

$db_file = 'database.db';
if (!file_exists($db_file)) {
    $files = glob("*.db");
    if (!empty($files)) { $db_file = $files[0]; }
    else {
        $files_sqlite = glob("*.sqlite");
        if (!empty($files_sqlite)) { $db_file = $files_sqlite[0]; }
        else { $db_file = 'pos.db'; }
    }
}

$db = new SQLite3($db_file);

// إنشاء جدول العملاء والمواقع المستقل
$db->exec("CREATE TABLE IF NOT EXISTS client_locations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_name TEXT NOT NULL,
    phone TEXT,
    lat TEXT,
    lng TEXT,
    address_notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_client') {
    $client_name = trim($_POST['client_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $lat = trim($_POST['lat'] ?? '');
    $lng = trim($_POST['lng'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (!empty($client_name)) {
        $stmt = $db->prepare("INSERT INTO client_locations (client_name, phone, lat, lng, address_notes) VALUES (?, ?, ?, ?, ?)");
        $stmt->bindValue(1, $client_name, SQLITE3_TEXT);
        $stmt->bindValue(2, $phone, SQLITE3_TEXT);
        $stmt->bindValue(3, $lat, SQLITE3_TEXT);
        $stmt->bindValue(4, $lng, SQLITE3_TEXT);
        $stmt->bindValue(5, $notes, SQLITE3_TEXT);
        
        if ($stmt->execute()) {
            $msg = "تم حفظ العميل وموقعه الجغرافي بنجاح!";
        } else {
            $msg = "حدث خطأ أثناء الحفظ.";
        }
    } else {
        $msg = "يرجى كتابة اسم العميل على الأقل.";
    }
}

$clients = [];
$res = $db->query("SELECT * FROM client_locations ORDER BY id DESC");
if ($res) {
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $clients[] = $row;
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

        .header {
            background: #1E3A8A; color: white; padding: 20px 15px; text-align: center;
            border-bottom-left-radius: 20px; border-bottom-right-radius: 20px;
        }
        .header h1 { font-size: 18px; margin-bottom: 4px; font-weight: bold; }
        .header p { font-size: 11px; opacity: 0.9; }

        .container { max-width: 600px; margin: 15px auto; padding: 0 10px; }
        
        .card { background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 12px; padding: 15px; margin-bottom: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.03); }
        .client-name { font-size: 14px; font-weight: bold; color: #1E3A8A; margin-bottom: 4px; }
        .client-details { font-size: 12px; color: #64748B; margin-bottom: 10px; line-height: 1.5; }
        
        .form-group { margin-bottom: 12px; }
        .form-label { display: block; font-size: 12px; font-weight: bold; color: #1E3A8A; margin-bottom: 4px; }
        .form-input { width: 100%; padding: 10px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 12px; outline: none; }
        
        .gps-btn {
            background: #0284C7; color: white; border: none; padding: 10px; border-radius: 8px;
            font-size: 12px; font-weight: bold; width: 100%; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px;
            margin-bottom: 6px; box-shadow: 0 4px 10px rgba(2,132,199,0.3);
        }
        .save-btn {
            background: #16A34A; color: white; border: none; padding: 12px; border-radius: 8px;
            font-size: 13px; font-weight: bold; width: 100%; cursor: pointer; box-shadow: 0 4px 10px rgba(22,163,74,0.3);
        }

        .actions { display: flex; gap: 8px; margin-top: 10px; }
        .btn {
            flex: 1; padding: 8px 10px; border-radius: 6px; font-size: 11px; font-weight: bold;
            text-decoration: none; text-align: center; border: none; cursor: pointer; display: flex;
            align-items: center; justify-content: center; gap: 4px; color: white;
        }
        .btn-map { background: #0284C7; }
        .btn-whatsapp { background: #16A34A; }
        
        .alert { background: #DCFCE7; color: #166534; padding: 8px; border-radius: 6px; margin-bottom: 12px; font-size: 11px; text-align: center; font-weight: bold; }
        .coords-info { font-size: 10px; color: #64748B; background: #F1F5F9; padding: 6px; border-radius: 6px; text-align: center; }
        .no-data { text-align: center; color: #64748B; padding: 30px; font-size: 12px; }
        
        .toggle-form-btn {
            background: #D97706; color: white; border: none; padding: 10px 15px; border-radius: 20px; font-size: 12px;
            font-weight: bold; cursor: pointer; width: 100%; box-shadow: 0 4px 10px rgba(217,119,6,0.3); margin-bottom: 15px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>مؤسسة أبو ضياء للتوزيع</h1>
        <p>إدارة واستعراض مواقع العملاء (GPS)</p>
    </div>

    <div class="container">
        <?php if(!empty($msg)): ?>
            <div class="alert"><?php echo $msg; ?></div>
        <?php endif; ?>

        <!-- زر إظهار/إخفاء نموذج الإضافة -->
        <button class="toggle-form-btn" onclick="toggleForm()">➕ إضافة عميل جديد وتحديد موقعه (GPS)</button>

        <!-- نموذج إضافة عميل وموقعه (مخفي افتراضياً ليظهر بضغطة زر) -->
        <div id="addFormCard" class="card" style="display: none; border: 2px solid #D97706;">
            <div class="client-name" style="margin-bottom: 10px;">تسجيل عميل جديد مع الموقع الحالي:</div>
            <form method="POST">
                <input type="hidden" name="action" value="add_client">
                
                <div class="form-group">
                    <label class="form-label">اسم العميل / المحل:</label>
                    <input type="text" name="client_name" class="form-input" placeholder="أدخل اسم الزبون أو المحل" required>
                </div>

                <div class="form-group">
                    <label class="form-label">رقم الهاتف:</label>
                    <input type="text" name="phone" class="form-input" placeholder="77xxxxxxx" dir="ltr">
                </div>

                <div class="form-group">
                    <label class="form-label">تحديد الموقع الجغرافي:</label>
                    <button type="button" class="gps-btn" onclick="getClientLocation()">
                        📍 التقاط موقعك الحالي عند العميل (GPS)
                    </button>
                    <div class="coords-info" id="coordsStatus">لم يتم التقاط الموقع بعد</div>
                    <input type="hidden" id="lat" name="lat">
                    <input type="hidden" id="lng" name="lng">
                </div>

                <div class="form-group">
                    <label class="form-label">ملاحظات العنوان:</label>
                    <input type="text" name="notes" class="form-input" placeholder="مثال: بجوار الشارع العام">
                </div>

                <button type="submit" class="save-btn">💾 حفظ العميل وموقعه رسمياً</button>
            </form>
        </div>

        <div style="font-size: 12px; font-weight: bold; color: #475569; margin: 15px 0 10px 5px;">قائمة العملاء والمحلات المسجلة:</div>

        <?php if(empty($clients)): ?>
            <div class="card">
                <div class="no-data">لا توجد مواقع عملاء مسجلة حتى الآن.<br>اضغط على زر "إضافة عميل جديد" بالأعلى وأنت عند الزبون لحفظ موقعه!</div>
            </div>
        <?php else: ?>
            <?php foreach($clients as $c): 
                $lat = $c['lat'] ?? '';
                $lng = $c['lng'] ?? '';
                $has_coords = (!empty($lat) && !empty($lng));
                $map_url = $has_coords ? "https://maps.google.com/?q={$lat},{$lng}" : "https://maps.google.com/?q=Seiyun";
            ?>
            <div class="card">
                <div class="client-name">🛒 <?php echo htmlspecialchars($c['client_name']); ?></div>
                <div class="client-details">
                    📞 الهاتف: <span dir="ltr"><?php echo htmlspecialchars($c['phone'] ?? 'غير متوفر'); ?></span><br>
                    📍 العنوان: <?php echo htmlspecialchars($c['address_notes'] ?? 'بدون ملاحظات'); ?><br>
                    🕒 تاريخ التسجيل: <?php echo htmlspecialchars($c['created_at']); ?>
                </div>

                <div class="actions">
                    <a href="<?php echo $map_url; ?>" target="_blank" class="btn btn-map">
                        🗺️ عرض ع الخريطة
                    </a>
                    <button onclick="shareClientWhatsApp('<?php echo htmlspecialchars($c['client_name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($c['phone'] ?? '', ENT_QUOTES); ?>', '<?php echo $map_url; ?>')" class="btn btn-whatsapp">
                        💬 إرسال للواتساب
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        function toggleForm() {
            const formCard = document.getElementById('addFormCard');
            if (formCard.style.display === 'none') {
                formCard.style.display = 'block';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                formCard.style.display = 'none';
            }
        }

        function getClientLocation() {
            const status = document.getElementById('coordsStatus');
            if (!navigator.geolocation) {
                status.innerHTML = "متصفحك لا يدعم تحديد الموقع.";
                return;
            }

            status.innerHTML = "جاري التقاط الإحداثيات من الأقمار الصناعية... انتظر قليلاً ⏳";

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    
                    document.getElementById('lat').value = lat;
                    document.getElementById('lng').value = lng;
                    
                    status.innerHTML = "✅ تم تحديد موقع العميل بنجاح! (" + lat.toFixed(4) + " , " + lng.toFixed(4) + ")";
                    status.style.color = "#166534";
                },
                (error) => {
                    status.innerHTML = "❌ فشل التقاط الموقع. تأكد من إذن الـ GPS في المتصفح.";
                    status.style.color = "#DC2626";
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        }

        function shareClientWhatsApp(name, phone, mapUrl) {
            const msg = "📍 *موقع العميل / المحل التجاري*\n\n" +
                      "👤 *اسم العميل:* " + name + "\n" +
                      "📞 *الهاتف:* " + (phone ? phone : 'غير متوفر') + "\n\n" +
                      "🗺️ *رابط الموقع المباشر على خرائط جوجل:*\n" +
                      mapUrl + "\n\n" +
                      "صادر عن: مؤسسة أبو ضياء للتوزيع 🌟";

            window.open("https://wa.me/?text=" + encodeURIComponent(msg), "_blank");
        }
    </script>
</body>
</html>
