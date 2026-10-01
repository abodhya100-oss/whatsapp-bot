<?php
// fix_locations_nav.php - إضافة الشريط العائم لصفحة المواقع بأمان تام
// وضعنا عدة احتمالات لاسم ملف المواقع لضمان الوصول إليه
$files = ['locations.php', 'location.php', 'maps.php', 'map.php'];
$found = false;

foreach ($files as $file) {
    if (file_exists($file)) {
        $found = true;
        $content = file_get_contents($file);
        
        // التحقق مما إذا كان الشريط العائم غير موجود مسبقاً
            // البحث عن نهاية كود الـ body لإدراج الشريط قبله بأمان
            if (strpos($content, '</body>') !== false) {
            } else {
            }
            file_put_contents($file, $content);
            echo "✅ تم إضافة الشريط العائم إلى ملف المواقع ($file) بنجاح تام!\n";
        } else {
            echo "⚠️ الشريط العائم موجود مسبقاً في ملف ($file).\n";
        }
    }
}

if (!$found) {
    echo "❌ لم يتم العثور على ملف المواقع بالأسماء المعتادة. تأكد من اسم الملف لديك.";
}
?>
