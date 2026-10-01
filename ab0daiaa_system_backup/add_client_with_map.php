<?php
// add_client_with_map.php - شاشة إضافة العميل وتحديد موقعه الجغرافي رسمياً (مستقلة تماماً وآمنة)
error_reporting(E_ALL);
ini_set('display_errors', 1);

$db_file = 'database.db';
if (!file_exists($db_file)) {
    $files = glob("*.db");
    if (!empty($files)) { $db_file = $files[0]; }
    else {
        $files_sqlite = glob("*.sqlite");
        if (!empty($files_sqlite)) $db_file = $files_sqlite[0];
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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            $msg = "تم حفظ العميل وتثبيت موقعه الجغرافي بنجاح!";
        } else {
            $msg = "حدث خطأ أثناء الحفظ.";
        }
    } else {
        $msg = "يرجى كتابة اسم العميل على الأقل.";
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" style="direction: rtl; unicode-bidi: embed;">
<head>
<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><meta name='mobile-web-app-capable' content='yes'>
<meta name='apple-mobile-web-app-capable' content='yes'>

    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>إضافة عميل وتحديد موقعه - مؤسسة أبو ضياء</title>
    <style>
        * { box-sizing: border-box; font-family: 'Tahoma', Arial, sans-serif; margin: 0; padding: 0; direction: rtl; text-align: right; }
        body { background-color: #F8FAFC; color: #1E293B; padding-bottom: 70px; }

        .header {
            background: #1E3A8A; color: white; padding: 20px 15px; text-align: center;
            border-bottom-left-radius: 20px; border-bottom-right-radius: 20px;
        }
        .header h1 { font-size: 18px; margin-bottom: 4px; font-weight: bold; }
        .header p { font-size: 11px; opacity: 0.9; }

        .form-container { max-width: 500px; margin: 20px auto; background: #FFFFFF; padding: 20px; border-radius: 12px; border: 1px solid #E2E8F0; box-shadow: 0 4px 10px rgba(0,0,0,0.03); }
        
        .form-group { margin-bottom: 15px; }
        .form-label { display: block; font-size: 12px; font-weight: bold; color: #1E3A8A; margin-bottom: 5px; }
        .form-input { width: 100%; padding: 12px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 13px; outline: none; }
        .form-input:focus { border-color: #1E3A8A; box-shadow: 0 0 5px rgba(30,58,138,0.2); }

        .gps-btn {
            background: #0284C7; color: white; border: none; padding: 12px; border-radius: 8px;
            font-size: 13px; font-weight: bold; width: 100%; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px;
            margin-bottom: 8px; box-shadow: 0 4px 10px rgba(2,132,199,0.3);
        }
        .gps-btn:active { transform: scale(0.98); }

        .save-btn {
            background: #16A34A; color: white; border: none; padding: 14px; border-radius: 8px;
            font-size: 14px; font-weight: bold; width: 100%; cursor: pointer; box-shadow: 0 4px 10px rgba(22,163,74,0.3);
        }

        .alert { background: #DCFCE7; color: #166534; padding: 10px; border-radius: 8px; margin-bottom: 15px; font-size: 12px; text-align: center; font-weight: bold; }
        .coords-info { font-size: 11px; color: #64748B; background: #F1F5F9; padding: 8px; border-radius: 6px; text-align: center; }
        .nav-links { text-align: center; margin-top: 15px; }
        .nav-links a { color: #1E3A8A; font-size: 12px; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

    <div class="header">
        <h1>مؤسسة أبو ضياء للتوزيع</h1>
        <p>إضافة بيانات العميل وتثبيت موقعه رسمياً</p>
    </div>

    <div class="form-container">
        <?php if(!empty($msg)): ?>
            <div class="alert"><?php echo $msg; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">اسم العميل / المحل:</label>
                <input type="text" name="client_name" class="form-input" placeholder="أدخل اسم الزبون أو المحل" required>
            </div>

            <div class="form-group">
                <label class="form-label">رقم الهاتف:</label>
                <input type="text" name="phone" class="form-input" placeholder="77xxxxxxx" dir="ltr">
            </div>

            <div class="form-group">
                <label class="form-label">تحديد الموقع الجغرافي (عند العميل):</label>
                <button type="button" class="gps-btn" onclick="getClientLocation()">
                    📍 اضغط هنا لالتقاط موقعك الحالي بدقة (GPS)
                </button>
                <div class="coords-info" id="coordsStatus">لم يتم التقاط الموقع بعد</div>
                
                <!-- الإحداثيات التي سيتم حفظها في قاعدة البيانات -->
                <input type="hidden" id="lat" name="lat">
                <input type="hidden" id="lng" name="lng">
            </div>

            <div class="form-group">
                <label class="form-label">ملاحظات العنوان:</label>
                <input type="text" name="notes" class="form-input" placeholder="مثال: الشارع العام، بجوار المعلم">
            </div>

            <button type="submit" class="save-btn">💾 حفظ العميل والموقع في النظام</button>
        </form>

        <div class="nav-links">
            <a href="clients_map.php">📂 عرض قائمة العملاء والمواقع المحفوظة</a>
        </div>
    </div>

    <script>
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
                    status.innerHTML = "❌ فشل التقاط الموقع. تأكد من تفعيل الـ GPS والسماح للمتصفح.";
                    status.style.color = "#DC2626";
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        }
    </script>
</body>
</html>
