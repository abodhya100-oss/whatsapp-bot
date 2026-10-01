<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$db = new SQLite3('/data/data/com.termux/files/home/secure_dbs/vmax.db', SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
$page_title = "الأرشيف والتقارير - V-MAX PRO";
include 'layout.php';
?>
    <div class="app-header">📸 أرشيف الفواتير والمعاملات</div>
    <div class="card" style="text-align: center; padding: 25px;">
        <h3 style="color: var(--accent-blue); margin-top: 0; font-size: 15px;">⚡ الأرشيف السحابي والمحلي</h3>
    </div>
    <?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>
</html>
