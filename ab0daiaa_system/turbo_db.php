<?php
$db = new SQLite3('/data/data/com.termux/files/home/secure_dbs/vmax.db');
// تفعيل أوضاع السرعة القصوى
$db->exec('PRAGMA journal_mode = WAL;');
$db->exec('PRAGMA synchronous = NORMAL;');
$db->exec('PRAGMA temp_store = MEMORY;');
$db->exec('PRAGMA cache_size = -64000;');

// إنشاء فهارس لتسريع استعلامات لوحة التحكم والكاشير
$db->exec('CREATE INDEX IF NOT EXISTS idx_invoice_date ON invoices(date);');
$db->exec('CREATE INDEX IF NOT EXISTS idx_cust_balance ON customers(balance);');
$db->exec('CREATE INDEX IF NOT EXISTS idx_item_name ON inventory(item_name);');

echo "🚀 تم تفعيل وضع الصاروخ لقاعدة البيانات بنجاح!\n";
?>

<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
