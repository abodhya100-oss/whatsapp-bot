<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$db = new SQLite3('/data/data/com.termux/files/home/secure_dbs/vmax.db', SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
$page_title = "سندات القبض والصرف - V-MAX PRO";
include 'layout.php';
?>
    <div class="app-header">💰 سندات القبض والصرف والسداد</div>
    <div class="card" style="text-align: center; padding: 25px;">
        <h3 style="color: var(--accent-blue); margin-top: 0; font-size: 15px;">⚡ إدارة السندات والحسابات</h3>
        <p style="color: var(--text-muted); font-size: 12px;">تسجيل سندات القبض والصرف ومتابعة الدفعات بكل سهولة.</p>
    </div>
    <?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>
</html>
