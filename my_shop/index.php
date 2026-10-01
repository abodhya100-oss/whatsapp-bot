<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$db = new SQLite3('vmax.db', SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
$page_title = "الرئيسية - V-MAX PRO";
include 'layout.php';
?>
    <div class="app-header">📊 لوحة التحكم الرئيسية - V-MAX PRO</div>
    <div class="card" style="text-align: center; padding: 25px;">
        <h3 style="color: var(--accent-blue); margin-top: 0; font-size: 16px;">أهلاً بك يا أبو ضياء في نظامك المطور</h3>
        <p style="color: var(--text-muted); font-size: 12px;">النظام يعمل بكفاءة عالية وبكامل الأزرار والمميزات.</p>
    </div>
    <?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
    <?php include 'nav_menu.php'; ?>
</body>
</html>
