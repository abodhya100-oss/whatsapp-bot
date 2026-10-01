<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$db = new SQLite3('vmax.db', SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
$page_title = "إدارة الموردين - V-MAX PRO";
include 'layout.php';
?>
    <div class="app-header">🏢 إدارة الموردين وحساباتهم</div>
    <div class="card" style="text-align: center; padding: 25px;">
        <h3 style="color: var(--accent-blue); margin-top: 0; font-size: 15px;">⚡ قائمة الموردين والشركات</h3>
        <p style="color: var(--text-muted); font-size: 12px;">متابعة حسابات الموردين والذمم المالية بدقة عالية.</p>
    </div>
    <?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
    <?php include 'nav_menu.php'; ?>
</body>
</html>
