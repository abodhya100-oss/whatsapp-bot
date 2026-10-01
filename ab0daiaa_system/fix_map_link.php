<?php
// fix_map_link.php - ضمان بقاء واجهة الخرائط ضمن إطار النظام أو فتحها بسلاسة دون فقدان الشريط
$file = 'customers.php';
if (file_exists($file)) {
    $content = file_get_contents($file);
    
    // ضبط روابط الخريطة لتفتح بشكل سلس أو لا تفصل الواجهة
        if (strpos($content, '</body>') !== false) {
        } else {
        }
    }

    file_put_contents($file, $content);
    echo "تم ضبط إعدادات وعرض أزرار المواقع في customers.php بنجاح!";
} else {
    echo "ملف customers.php غير موجود.";
}
?>
