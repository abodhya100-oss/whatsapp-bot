<?php
// import_contacts.php - استيراد أسماء العملاء من ملف VCF - نظام أبوضياء العالمي
require_once 'auth.php';
$db_file = __DIR__ . '/vmax.db';
$db = new SQLite3($db_file);

// التأكد من وجود جدول العملاء
$db->exec("CREATE TABLE IF NOT EXISTS customers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    phone TEXT,
    created_date TEXT
)");

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['vcf_file'])) {
    if ($_FILES['vcf_file']['error'] === UPLOAD_ERR_OK) {
        $content = file_get_contents($_FILES['vcf_file']['tmp_name']);
        
        // تحليل ملف الـ VCF لاستخراج الأسماء وأرقام الهواتف بدقة
        preg_match_all('/FN:(.*?)\r?\n/i', $content, $names);
        preg_match_all('/TEL[^:]*:(.*?)\r?\n/i', $content, $phones);
        
        $imported_count = 0;
        $max_count = max(count($names[1] ?? []), count($phones[1] ?? []));
        
        for ($i = 0; $i < $max_count; $i++) {
            $name = trim($names[1][$i] ?? 'عميل مستورد');
            $phone = trim($phones[1][$i] ?? '');
            
            // تنظيف النص وإزالة الرموز الزائدة
            $name = str_replace(["\r", "\n"], "", $name);
            $phone = str_replace(["\r", "\n", " ", "-"], "", $phone);
            
            if (!empty($name)) {
                $safe_name = $db->escapeString($name);
                $safe_phone = $db->escapeString($phone);
                $db->exec("INSERT INTO customers (name, phone, created_date) VALUES ('$safe_name', '$safe_phone', datetime('now', 'localtime'))");
                $imported_count++;
            }
        }
        $msg = "✅ تم استيراد ($imported_count) عميلاً بنجاح تام إلى قاعدة بيانات النظام!";
    } else {
        $msg = "❌ حدث خطأ أثناء رفع ملف الـ VCF. يرجى المحاولة مرة أخرى.";
    }
}
$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><meta name='mobile-web-app-capable' content='yes'>
<meta name='apple-mobile-web-app-capable' content='yes'>

    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>استيراد الأسماء من VCF - V-MAX PRO</title>
    <style>
        body { font-family: 'Tahoma', sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 15px; direction: rtl; padding-bottom: 95px; }
        .container { max-width: 600px; margin: auto; }
        .card { background: rgba(30, 41, 59, 0.9); backdrop-filter: blur(10px); border: 1.5px solid rgba(56, 189, 248, 0.3); border-radius: 16px; padding: 20px; margin-bottom: 15px; box-shadow: 0 8px 30px rgba(0,0,0,0.5); text-align: center; }
        h1 { text-align: center; color: #38bdf8; font-size: 20px; margin-bottom: 5px; }
        p.sub { text-align: center; color: #94a3b8; font-size: 12px; margin-bottom: 20px; }
        h3 { color: #38bdf8; font-size: 14px; margin-top: 0; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 8px; text-align: right; }
        
        .btn-action { display: block; width: 100%; padding: 13px; border-radius: 10px; border: none; font-weight: bold; font-size: 14px; cursor: pointer; text-decoration: none; text-align: center; margin-top: 12px; background: linear-gradient(135deg, #22c55e, #16a34a); color: #fff; box-shadow: 0 4px 15px rgba(34,197,94,0.4); transition: transform 0.2s; }
        .btn-action:active { transform: scale(0.98); }
        
        input[type="file"] { width: 100%; padding: 12px; background: rgba(15, 23, 42, 0.9); border: 1px dashed rgba(56, 189, 248, 0.4); color: #fff; border-radius: 8px; font-size: 12px; margin-bottom: 12px; box-sizing: border-box; cursor: pointer; }
    </style>
</head>
<body>

<div class="container">
    <h1>📥 استيراد أسماء العملاء</h1>
    <p class="sub">استيراد جهات الاتصال من ملف بصيغة VCF إلى جدول العملاء بضغطة زر واحدة</p>

    <?php if(!empty($msg)): ?>
    <div style="background: rgba(34,197,94,0.2); border: 1px solid #22c55e; color: #4ade80; padding: 12px; border-radius: 10px; margin-bottom: 15px; font-weight: bold; text-align: center;">
        <?php echo $msg; ?>
    </div>
    <?php endif; ?>

    <div class="card">
        <h3>📂 رفع ملف جهات الاتصال (VCF)</h3>
        <p style="color: #94a3b8; font-size: 12px; text-align: right; margin-bottom: 15px; line-height: 1.5;">
            قم بتحديد ملف الأسماء (.vcf) المخزن على هاتفك لكي يتم قراءة جميع الأسماء وأرقام الهواتف وإضافتها تلقائياً إلى نظام العملاء والديون.
        </p>
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="vcf_file" accept=".vcf" required>
            <button type="submit" class="btn-action">🚀 بدء استيراد الأسماء إلى النظام</button>
        </form>
    </div>
</div>

<?php include 'nav_menu.php'; ?>
</body>
</html>
