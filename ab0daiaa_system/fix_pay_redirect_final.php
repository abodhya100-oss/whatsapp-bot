<?php
// fix_pay_redirect_final.php - الحل الجذري لمنع التحويل للرئيسية عند تسديد الفواتير
error_reporting(E_ALL);
ini_set('display_errors', 1);

$target = 'pay_invoices.php';
if (file_exists($target)) {
    $content = file_get_contents($target);
    
    // استبدال أي أمر يقوم بإعادة التوجيه إلى index.php أو dashboard.php عند غياب المعرف ليقبل المعرفين معا (id و customer_id)
    $old_check = "header('Location: index.php')";
    $new_check = "// تم إلغاء التحويل للرئيسية منعاً للإشكالية: header('Location: index.php')";
    $content = str_replace($old_check, $new_check, $content);
    
    $old_check2 = 'header("Location: index.php")';
    $new_check2 = '// تم إلغاء التحويل: header("Location: index.php")';
    $content = str_replace($old_check2, $new_check2, $content);

    // معالجة قراءة المعرف بغض النظر عن اسمه (id أو customer_id) في أعلى الملف
    $fix_id_snippet = '<?php'."\n".'$customer_id = $_GET[\'customer_id\'] ?? $_GET[\'id\'] ?? $_POST[\'customer_id\'] ?? $_POST[\'id\'] ?? 0;';
    
    // إذا كان الملف يبدأ بـ <?php نقوم بحقن التقاط المعرف بذكاء
    if (strpos($content, '<?php') === 0) {
        $content = preg_replace('/<\?php/', $fix_id_snippet, $content, 1);
    }

    file_put_contents($target, $content);
    echo "تم تطبيق الحل الجذري على pay_invoices.php بنجاح تام!";
} else {
    echo "ملف pay_invoices.php غير موجود.";
}
?>
