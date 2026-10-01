<?php
// تعديل الروابط في customers.php لتتوافق 100% مع معايير النظام الأصلي للسداد
$file = 'customers.php';
if (file_exists($file)) {
    $content = file_get_contents($file);
    
    // ضبط رابط تسديد الفواتير ليشمل المعرف بالطريقتين (id و customer_id) لضمان عدم التحويل للرئيسية
    $content = str_replace(
        'pay_invoices.php?customer_id=<?php echo $cid; ?>',
        'pay_invoices.php?id=<?php echo $cid; ?>&customer_id=<?php echo $cid; ?>',
        $content
    );
    
    file_put_contents($file, $content);
    echo "تم تحديث روابط سداد الفواتير في customers.php بنجاح تام!";
} else {
    echo "ملف customers.php غير موجود.";
}
?>
