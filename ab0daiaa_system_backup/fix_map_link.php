<?php
// fix_map_link.php - ضمان بقاء واجهة الخرائط ضمن إطار النظام أو فتحها بسلاسة دون فقدان الشريط
$file = 'customers.php';
if (file_exists($file)) {
    $content = file_get_contents($file);
    
    // ضبط روابط الخريطة لتفتح بشكل سلس أو لا تفصل الواجهة
    // التأكد من وجود استدعاء الشريط العائم nav_menu.php في نهاية ملف customers.php أيضاً
    if (strpos($content, 'nav_menu.php') === false) {
        if (strpos($content, '</body>') !== false) {
            $content = str_replace('</body>', "<?php if (file_exists('nav_menu.php')) include 'nav_menu.php'; ?>\n</body>", $content);
        } else {
            $content .= "\n<?php if (file_exists('nav_menu.php')) include 'nav_menu.php'; ?>\n";
        }
    }

    file_put_contents($file, $content);
    echo "تم ضبط إعدادات وعرض أزرار المواقع في customers.php بنجاح!";
} else {
    echo "ملف customers.php غير موجود.";
}
?>
