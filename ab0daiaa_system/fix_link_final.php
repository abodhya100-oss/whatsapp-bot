<?php
// fix_link_final.php - حل نهائي لضمان انتقال زر تسديد الفواتير بالمعرف الصحيح
$file = 'customers.php';
if (file_exists($file)) {
    $content = file_get_contents($file);
    
    // استبدال رابط زر سداد الفواتير ليحتوي على المعرف بصيغتين لضمان التوافق التام
    $content = str_replace(
        'pay_invoices.php?customer_id=<?php echo $cid; ?>',
        'pay_invoices.php?customer_id=<?php echo $cid; ?>&id=<?php echo $cid; ?>',
        $content
    );
    
    // أي صيغة أخرى محتملة للرابط
    $content = str_replace(
        'pay_invoices.php?id=<?php echo $cid; ?>',
        'pay_invoices.php?customer_id=<?php echo $cid; ?>&id=<?php echo $cid; ?>',
        $content
    );

    file_put_contents($file, $content);
    echo "تم تطبيق الحل النهائي على روابط العملاء بنجاح!";
} else {
    echo "ملف customers.php غير موجود.";
}
?>
