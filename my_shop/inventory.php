<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$db = new SQLite3('vmax.db', SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
$page_title = "إدارة المخزون - V-MAX PRO";
include 'layout.php';
?>
    <div class="app-header">📦 إدارة المخزون والبضائع</div>
    <div class="card" style="text-align: center; padding: 25px;">
        <h3 style="color: var(--accent-blue); margin-top: 0; font-size: 15px;">⚡ مراقبة الأصناف والكميات</h3>
        <p style="color: var(--text-muted); font-size: 12px;">إدارة المستودع وتتبع حركة البضائع ووحدات البيع.</p>
    </div>
    <?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
    <?php include 'nav_menu.php'; ?>
</body>
</html>
