<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
try {
    if (!file_exists('store_database.db')) {
        die("خطأ: ملف قاعدة البيانات غير موجود في هذا المجلد!\n");
    }
    $db = new PDO('sqlite:store_database.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_COLUMN);
    
    if(empty($tables)) {
        echo "قاعدة البيانات فارغة أو لا يوجد بها جداول!\n";
        exit;
    }
    
    $count = 0;
    foreach($tables as $t) {
        $db->exec("DELETE FROM $t");
        echo "تم تنظيف الجدول: $t \n";
        $count++;
    }
    echo "--- تمت العملية! تم تصفير $count جداول بنجاح. ---\n";
} catch (Exception $e) {
    echo "يوجد مشكلة تمنع المسح: " . $e->getMessage() . "\n";
}
?>
