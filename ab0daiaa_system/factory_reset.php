<?php
$db = new SQLite3('/data/data/com.termux/files/home/secure_dbs/vmax.db');

// قائمة الجداول الخاصة بنظام V-MAX PRO
$tables = ['customers', 'products', 'invoices', 'invoice_items', 'account_payments', 'vouchers'];

foreach($tables as $t) {
    // 1. مسح جميع البيانات من الجدول
    $db->exec("DELETE FROM $t");
    
    // 2. تصفير العداد لتبدأ الفواتير والعملاء من رقم 1
    $db->exec("DELETE FROM sqlite_sequence WHERE name='$t'");
}

echo "SUCCESS: V-MAX PRO Factory Reset Complete!\n";
?>
