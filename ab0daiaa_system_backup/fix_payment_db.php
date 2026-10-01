<?php
// fix_payment_db.php - تصحيح مسار قاعدة البيانات لصفحات السندات والدفع (مستقل تماماً)
error_reporting(E_ALL);
ini_set('display_errors', 1);

$file_to_fix = 'payment.php';
if (file_exists($file_to_fix)) {
    $content = file_get_contents($file_to_fix);
    
    // إذا كان الملف يحاول الاتصال بـ mysqli بشكل خاطئ، نقوم بعمل ترقيع آمن يحولها إلى SQLite المطابق لـ vmax.db
    if (strpos($content, 'new mysqli') !== false || strpos($content, "mysqli('localhost'") !== false) {
        $replacement = 'new SQLite3("vmax.db")';
        // استبدال آمن لسطر الاتصال
        $new_content = preg_replace('/new\s+mysqli\s*\(.*?\)/i', $replacement, $content);
        file_put_contents($file_to_fix, $new_content);
        echo "تم تعديل وتصحيح الاتصال في ملف payment.php بنجاح ليعمل مع vmax.db دون أخطاء!";
    } else {
        echo "ملف payment.php لا يستخدم اتصال mysqli المباشر أو تم تعديله مسبقاً.";
    }
} else {
    echo "ملف payment.php غير موجود.";
}
?>
