<?php
$file = 'pay_invoices.php';
if (file_exists($file)) {
    $content = file_get_contents($file);
    
    // البحث عن أي أمر header location قد يعيد للرئيسية وتعطيله نهائياً
    $content = preg_replace("/header\s*\(\s*['\"]Location:\s*(index|dashboard)\.php['\"]\s*\);?/i", "// تم تعطيل التحويل للرئيسية: $0", $content);
    
    // ضمان أن يتم التقاط الـ customer_id أو id بقوة أول الملف
    $robust_init = "<?php\n" .
                   "if (session_status() === PHP_SESSION_NONE) { session_start(); }\n" .
                   "\$customer_id = 0;\n" .
                   "if (isset(\$_GET['customer_id'])) { \$customer_id = intval(\$_GET['customer_id']); }\n" .
                   "elseif (isset(\$_GET['id'])) { \$customer_id = intval(\$_GET['id']); }\n" .
                   "elseif (isset(\$_POST['customer_id'])) { \$customer_id = intval(\$_POST['customer_id']); }\n" .
                   "elseif (isset(\$_POST['id'])) { \$customer_id = intval(\$_POST['id']); }\n" .
                   "elseif (isset(\$_SESSION['current_customer_id'])) { \$customer_id = intval(\$_SESSION['current_customer_id']); }\n\n" .
                   "if (\$customer_id > 0) { \$_SESSION['current_customer_id'] = \$customer_id; }\n";

    // استبدال بداية الملف بهذا الكود الحصين
    $content = preg_replace('/<\?php/', $robust_init, $content, 1);
    
    file_put_contents($file, $content);
    echo "تم تطبيق الحل الحصين على pay_invoices.php بنجاح تام!";
} else {
    echo "ملف pay_invoices.php غير موجود.";
}
?>
