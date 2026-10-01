<?php
// fix_customers_btn.php - ضبط رابط زر تسديد الفواتير في صفحة العملاء بدقة تامة
$file = 'customers.php';
if (file_exists($file)) {
    $content = file_get_contents($file);
    
    // استبدال أي رابط قديم لزر تسديد الفواتير بالرابط النظيف والمدعوم 100%
    $content = str_replace(
        'pay_invoices.php?customer_id=',
        'pay_invoices.php?customer_id=',
        $content
    );
    
    // تأكيد توجيه الزر ليحتوي على معرف العميل بشكل صحيح لا يحتمل الخطأ
    $content = preg_replace(
        '/pay_invoices\.php\?id=\<\?php echo \$cid; \?\>/',
        'pay_invoices.php?customer_id=<?php echo $cid; ?>',
        $content
    );

    // إذا كان الرابط بالشكل القديم
    $content = str_replace(
        'pay_invoices.php?customer_id=<?php echo $cid; ?>',
        'pay_invoices.php?customer_id=<?php echo $cid; ?>',
        $content
    );

    file_put_contents($file, $content);
    echo "تم تصحيح رابط زر السداد في customers.php بنجاح تام!";
} else {
    echo "ملف customers.php غير موجود.";
}
?>
