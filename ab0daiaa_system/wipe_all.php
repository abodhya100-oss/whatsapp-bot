<?php
$db = new SQLite3('/data/data/com.termux/files/home/secure_dbs/vmax.db');

// البحث التلقائي عن كل الجداول داخل القاعدة
$result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");

$count = 0;
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $t = $row['name'];
    // مسح البيانات
    $db->exec("DELETE FROM $t");
    // تصفير العداد
    @$db->exec("DELETE FROM sqlite_sequence WHERE name='$t'");
    $count++;
}

echo "SUCCESS: 100% Clean! Wiped $count tables including expenses.\n";
?>
