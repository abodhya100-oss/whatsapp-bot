<?php
$file = 'customers.php';
if(file_exists($file)) {
    $content = file_get_contents($file);
    // تحويل زر تفاصيل العميل ليفتح كشف الحساب مباشرة للعميل المعني
    $content = str_replace(
        '<a href="#" class="btn-details">تفاصيل العميل 📋</a>',
        '<a href="statement_new.php?customer_id=<?php echo $row[\'id\']; ?>" class="btn-details">كشف الحساب 📊</a>',
        $content
    );
    file_put_contents($file, $content);
    echo "✅ تم تعديل زر تفاصيل العميل بنجاح!\n";
}
?>
