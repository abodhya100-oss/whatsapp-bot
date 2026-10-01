<?php
// fix_catalog_nav.php - إضافة الشريط العائم لملف الكتالوج بأمان تام
$file = 'catalog.php';
if (file_exists($file)) {
    $content = file_get_contents($file);
    
    // التحقق مما إذا كان الشريط العائم غير موجود مسبقاً في الملف
    if (strpos($content, 'nav_menu.php') === false) {
        // البحث عن نهاية كود الـ body أو الـ html لإدراج الشريط قبله بلطف
        if (strpos($content, '</body>') !== false) {
            $content = str_replace('</body>', "<?php if (file_exists('nav_menu.php')) include 'nav_menu.php'; ?>\n</body>", $content);
        } else {
            $content .= "\n<?php if (file_exists('nav_menu.php')) include 'nav_menu.php'; ?>\n";
        }
        file_put_contents($file, $content);
        echo "تم إضافة الشريط العائم إلى catalog.php بنجاح تام!";
    } else {
        echo "الشريط العائم موجود مسبقاً في ملف catalog.php.";
    }
} else {
    echo "ملف catalog.php غير موجود في المجلد الحالي.";
}
?>
