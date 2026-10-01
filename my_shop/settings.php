<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') { 
    die("<h2 style='color:red; text-align:center;'>⛔ غير مصرح لك بدخول هذه الصفحة. مخصصة للمدير فقط.</h2>"); 
}
$db = new SQLite3('vmax.db');
$msg = "";

// تحديث الإعدادات
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    foreach ($_POST['settings'] as $key => $value) {
        $clean_val = SQLite3::escapeString(trim($value));
        $db->exec("UPDATE settings SET value = '$clean_val' WHERE key = '$key'");
    }
    $msg = "<div class='alert-success'>✅ تم تحديث إعدادات النظام والفاتورة بنجاح.</div>";
}

// تنظيف وتسريع قاعدة البيانات
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['optimize_db'])) {
    $db->exec('VACUUM');
    $msg = "<div class='alert-success'>⚡ تم تنظيف الذاكرة وترتيب قاعدة البيانات لتعمل بأقصى سرعة!</div>";
}

$settings = [];
$s_res = $db->query("SELECT key, value FROM settings");
if ($s_res) {
    while ($s_row = $s_res->fetchArray(SQLITE3_ASSOC)) {
        $settings[$s_row['key']] = $s_row['value'];
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الإعدادات - أبو ضياء</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="manifest" href="manifest.json">
    <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js");}</script>
    <style>
        body { font-family: 'Cairo', sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 10px; padding-bottom: 80px; }
        .header { text-align: center; font-size: 16px; font-weight: 900; color: #a855f7; margin-bottom: 15px; }
        .glass-panel { background: rgba(30, 41, 59, 0.95); border: 1px solid rgba(168, 85, 247, 0.4); border-radius: 12px; padding: 15px; margin-bottom: 15px; }
        .form-control { width: 100%; padding: 10px; background: #1e293b; color: #fff; border: 1px solid rgba(168, 85, 247, 0.4); border-radius: 8px; margin-bottom: 10px; box-sizing: border-box; font-weight: bold; }
        .btn-submit { background: linear-gradient(135deg, #9333ea, #7e22ce); color: white; padding: 12px; border: none; width: 100%; font-weight: bold; border-radius: 8px; cursor: pointer; box-shadow: 0 4px 10px rgba(147, 51, 234, 0.4); }
        .btn-vacuum { background: linear-gradient(135deg, #10b981, #047857); margin-top: 15px; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.4); }
        label { font-size: 11px; color: #c084fc; font-weight: bold; }
        .alert-success { background: rgba(34, 197, 94, 0.2); color: #4ade80; border: 1px solid #22c55e; padding: 10px; border-radius: 8px; text-align: center; margin-bottom: 10px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">⚙️ إعدادات النظام والفاتورة</div>
    <?php echo $msg; ?>

    <div class="glass-panel">
        <form method="POST">
            <input type="hidden" name="update_settings" value="1">
            <label>اسم المحل (يظهر في رأس الفاتورة):</label>
            <input type="text" name="settings[store_name]" class="form-control" value="<?php echo htmlspecialchars($settings['store_name'] ?? ''); ?>">
            <label>النشاط التجاري / الشعار اللفظي:</label>
            <input type="text" name="settings[store_tagline]" class="form-control" value="<?php echo htmlspecialchars($settings['store_tagline'] ?? ''); ?>">
            <label>العنوان:</label>
            <input type="text" name="settings[store_address]" class="form-control" value="<?php echo htmlspecialchars($settings['store_address'] ?? ''); ?>">
            <label>رقم الجوال للتواصل:</label>
            <input type="text" name="settings[store_phone]" class="form-control" value="<?php echo htmlspecialchars($settings['store_phone'] ?? ''); ?>">
            <button type="submit" class="btn-submit">💾 حفظ التعديلات</button>
        </form>

        <form method="POST" onsubmit="return confirm('هذه العملية ستقوم بترتيب قاعدة البيانات. هل تريد المتابعة؟');">
            <input type="hidden" name="optimize_db" value="1">
            <button type="submit" class="btn-submit btn-vacuum">⚡ تنظيف وتسريع قاعدة البيانات (VACUUM)</button>
        </form>
    </div>

    <a href="dashboard.php" style="display: block; text-align: center; color: #94a3b8; text-decoration: none; font-size: 12px; font-weight: bold;">🔙 عودة للوحة القيادة</a>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body></html>

