<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_role'])) { header("Location: login.php"); exit; }
$db = new SQLite3('vmax.db');

$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_reset'])) {
    // جلب جميع الجداول في قاعدة البيانات وتفريغها بالكامل (موردين، صيانة، أصناف، عملاء، فواتير، سندات...)
    $res = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $table = $row['name'];
        $db->exec("DELETE FROM \"$table\";");
    }
    $message = "✅ تم تصفير وحذف كافة بيانات الجداول (الموردين، الصيانة، الأصناف، العملاء، والفواتير) بنجاح تامة!";
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <link rel="manifest" href="manifest.json">
    <meta name='mobile-web-app-capable' content='yes'>
    <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js");}</script>
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>تصفير النظام الجذري - مؤسسة أبو ضياء</title>
    <style>
        body { font-family: Tahoma, sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 20px; direction: rtl; }
        .card { max-width: 400px; margin: 50px auto; background: #1e293b; border: 2px solid #ef4444; border-radius: 20px; padding: 20px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.8); }
        .btn-danger { background: #ef4444; color: #fff; border: none; padding: 12px 20px; border-radius: 10px; font-weight: bold; cursor: pointer; width: 100%; font-size: 14px; margin-top: 15px; }
        .btn-back { background: #334155; color: #fff; border: none; padding: 10px 20px; border-radius: 10px; text-decoration: none; display: inline-block; margin-top: 10px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="card">
        <h2 style="color: #ef4444; margin-top: 0;">⚠️ التصفير الجذري الشامل</h2>
        <p style="font-size: 12px; color: #94a3b8; line-height: 1.6;">تحذير: سيتم مسح كل شيء من قاعدة البيانات (الموردين، الصيانة، الأصناف، العملاء، الفواتير، والسندات) نهائياً!</p>
        
        <?php if(!empty($message)): ?>
            <div style="background: rgba(34,197,94,0.2); border: 1px solid #22c55e; color: #4ade80; padding: 10px; border-radius: 8px; font-size: 12px; margin-bottom: 15px;"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST" onsubmit="return confirm('⚠️ هل أنت متأكد تماماً من محو كل سجلات النظام والبدء كأنها جديدة؟');">
            <button type="submit" name="confirm_reset" value="1" class="btn-danger">🗑️ مسح وتصفير كل الجداول نهائياً</button>
        </form>
        <a href="pos_touch.php" class="btn-back">⬅ العودة للكاشير</a>
    </div>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
<?php if(file_exists('nav_menu.php')) { include 'nav_menu.php'; } ?>
</body>
</html>

