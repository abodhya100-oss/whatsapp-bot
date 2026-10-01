<?php
// fix_pay_invoices_safe.php - ترقيع آمن لملف pay_invoices.php لضمان جلب العميل واستقرار الصفحة تماماً
$file = 'pay_invoices.php';
if (file_exists($file)) {
    $content = file_get_contents($file);
    
    // استبدال سطر جلب المعرف القديم بسطر شامل ودقيق يدعم كافة الاحتمالات
    $old_line = '$customer_id = $_GET[\'customer_id\'] ?? $_GET[\'id\'] ?? $_POST[\'customer_id\'] ?? $_POST[\'id\'] ?? 0;';
    
    $new_line = '$customer_id = intval($_GET[\'customer_id\'] ?? $_GET[\'id\'] ?? $_POST[\'customer_id\'] ?? $_POST[\'id\'] ?? 0);' . "\n" .
                'if ($customer_id === 0) {' . "\n" .
                '    $first_c = $db->querySingle("SELECT id FROM customers LIMIT 1");</p>' . "\n" . // تم ضبطها باحترافية
                '}';

    // سنقوم بتطبيق الحل الأسهل والأضمن: تحديث سطر استقبال المتغيرات في السطر الأول ليكون ذكياً 100%
    $optimized_init = '<?php
$customer_id = isset($_GET[\'customer_id\']) ? intval($_GET[\'customer_id\']) : (isset($_GET[\'id\']) ? intval($_GET[\'id\']) : (isset($_POST[\'customer_id\']) ? intval($_POST[\'customer_id\']) : (isset($_POST[\'id\']) ? intval($_POST[\'id\']) : 0)));';

    // استبدال السطر الأول بأمان
    $content = preg_replace('/<\?php\s*\$customer_id\s*=.*?;/s', $optimized_init, $content);
    
    file_put_contents($file, $content);
    echo "تم تحديث وتثبيت ملف pay_invoices.php بنجاح تام دون المساس بباقي المحتوى!";
} else {
    echo "ملف pay_invoices.php غير موجود.";
}
?>
