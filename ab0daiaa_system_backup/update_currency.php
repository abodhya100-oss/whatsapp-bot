<?php
// تحديث قاعدة البيانات لإضافة دعم العملات
$db_file = __DIR__ . '/vmax.db';
$db = new SQLite3($db_file);
@$db->exec("ALTER TABLE invoices ADD COLUMN currency TEXT DEFAULT 'YER'");
@$db->exec("ALTER TABLE invoices ADD COLUMN exchange_rate REAL DEFAULT 1.0");
$db->close();
echo "✅ تم تحديث قاعدة البيانات لدعم العملات بنجاح!\n";
?>
