<?php
// app_settings.php - إعدادات النظام الشاملة (العملات، الصرف، وبيانات المؤسسة)
require_once 'auth.php';
$db_file = '/data/data/com.termux/files/home/secure_dbs/vmax.db';
$db = new SQLite3($db_file);

// إنشاء جدول الإعدادات إذا لم يكن موجوداً
$db->exec("CREATE TABLE IF NOT EXISTS settings (setting_key TEXT PRIMARY KEY, setting_value TEXT)");

$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $store_name = $_POST['store_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $default_currency = $_POST['default_currency'] ?? 'YER';
    $sar_rate = $_POST['sar_rate'] ?? '1';
    $usd_rate = $_POST['usd_rate'] ?? '1';

    // حفظ الإعدادات في قاعدة البيانات
    $stmt = $db->prepare("INSERT OR REPLACE INTO settings (setting_key, setting_value) VALUES (:k, :v)");
    
    $settings_to_save = [
        'store_name' => $store_name,
        'phone' => $phone,
        'default_currency' => $default_currency,
        'sar_rate' => $sar_rate,
        'usd_rate' => $usd_rate
    ];

    foreach($settings_to_save as $k => $v) {
        $stmt->bindValue(':k', $k, SQLITE3_TEXT);
        $stmt->bindValue(':v', $v, SQLITE3_TEXT);
        $stmt->execute();
    }
    $msg = "<div class='msg success'>✅ تم حفظ الإعدادات بنجاح!</div>";
}

// تحميل الإعدادات الحالية من قاعدة البيانات
$settings = [];
$res = $db->query("SELECT * FROM settings");
while($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// القيم الافتراضية في حال لم تكن محفوظة مسبقاً
$store_name = $settings['store_name'] ?? 'مؤسسة أبوضياء للتوزيع';
$phone = $settings['phone'] ?? '771458609';
$default_currency = $settings['default_currency'] ?? 'YER';
$sar_rate = $settings['sar_rate'] ?? '535'; // السعر التقريبي في مناطق حضرموت
$usd_rate = $settings['usd_rate'] ?? '2020';
$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>إعدادات النظام - V-MAX PRO</title>
    <style>
        :root {
            --primary: #0f172a;
            --secondary: #0284c7;
            --accent: #38bdf8;
            --success: #10b981;
            --card-bg: rgba(30, 41, 59, 0.7);
            --text-light: #f8fafc;
            --border-color: rgba(255, 255, 255, 0.1);
        }
        body { 
            font-family: 'Tahoma', sans-serif; 
            background: var(--primary); 
            margin: 0; 
            padding: 15px; 
            padding-bottom: 90px; 
            color: var(--text-light); 
        }
        .container { max-width: 600px; margin: 0 auto; }
        .header-card { 
            background: linear-gradient(135deg, var(--secondary), #1e3a8a); 
            border-radius: 12px; 
            padding: 20px; 
            text-align: center; 
            color: #fff; 
            margin-bottom: 20px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.3); 
        }
        .header-card h1 { margin: 0; font-size: 18px; font-weight: bold; }
        .header-card p { margin: 5px 0 0 0; font-size: 11px; opacity: 0.8; }
        
        .form-card { 
            background: var(--card-bg); 
            backdrop-filter: blur(10px);
            border-radius: 12px; 
            padding: 20px; 
            border: 1px solid var(--border-color); 
            box-shadow: 0 4px 15px rgba(0,0,0,0.2); 
            margin-bottom: 15px; 
        }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-size: 12px; font-weight: bold; margin-bottom: 6px; color: var(--accent); }
        .form-control { 
            width: 100%; 
            padding: 10px 12px; 
            border-radius: 8px; 
            border: 1px solid var(--border-color); 
            background: rgba(15, 23, 42, 0.5); 
            color: #fff; 
            font-size: 14px; 
            font-weight: bold; 
            outline: none; 
            box-sizing: border-box; 
            transition: 0.3s; 
        }
        .form-control:focus { border-color: var(--accent); background: rgba(15, 23, 42, 0.8); }
        select.form-control { appearance: none; cursor: pointer; }
        
        .btn-save { 
            background: linear-gradient(135deg, var(--success), #059669); 
            color: #fff; 
            border: none; 
            padding: 14px; 
            border-radius: 10px; 
            font-size: 15px; 
            font-weight: bold; 
            width: 100%; 
            cursor: pointer; 
            transition: 0.2s; 
            box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3); 
            margin-top: 10px;
        }
        .btn-save:active { transform: scale(0.98); }
        .msg { padding: 12px; border-radius: 8px; text-align: center; font-weight: bold; font-size: 13px; margin-bottom: 15px; }
        .msg.success { background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid #10b981; }
        .section-title { font-size: 13px; font-weight: bold; color: #fff; margin-bottom: 12px; border-bottom: 1px dashed var(--border-color); padding-bottom: 8px; }
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
        <div class="header-card">
            <h1>⚙️ إعدادات النظام الشاملة</h1>
            <p>تحكم ببيانات المؤسسة، العملة الافتراضية، وأسعار الصرف</p>
        </div>

        <?php echo $msg; ?>

        <form method="POST">
            <div class="form-card">
                <div class="section-title">🏢 بيانات المؤسسة</div>
                <div class="form-group">
                    <label>اسم المؤسسة / المتجر</label>
                    <input type="text" name="store_name" class="form-control" value="<?php echo htmlspecialchars($store_name); ?>" required>
                </div>
                <div class="form-group">
                    <label>رقم الهاتف للواتساب</label>
                    <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($phone); ?>" required>
                </div>
            </div>

            <div class="form-card">
                <div class="section-title">💱 إعدادات العملة والصرف</div>
                <div class="form-group">
                    <label>العملة الافتراضية للنظام</label>
                    <select name="default_currency" class="form-control">
                        <option value="YER" <?php if($default_currency == 'YER') echo 'selected'; ?>>ريال يمني (YER)</option>
                        <option value="SAR" <?php if($default_currency == 'SAR') echo 'selected'; ?>>ريال سعودي (SAR)</option>
                        <option value="USD" <?php if($default_currency == 'USD') echo 'selected'; ?>>دولار أمريكي (USD)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>سعر صرف الريال السعودي (مقابل اليمني)</label>
                    <input type="number" step="any" name="sar_rate" class="form-control" value="<?php echo htmlspecialchars($sar_rate); ?>">
                    <small style="color: #94a3b8; font-size: 10px;">مثال: 535 للسعودي (لصرف حضرموت/سيئون)</small>
                </div>
                <div class="form-group">
                    <label>سعر صرف الدولار (مقابل اليمني)</label>
                    <input type="number" step="any" name="usd_rate" class="form-control" value="<?php echo htmlspecialchars($usd_rate); ?>">
                </div>
            </div>

            <button type="submit" class="btn-save">💾 حفظ التعديلات في النظام</button>
        </form>
    </div>

</body>
</html>
