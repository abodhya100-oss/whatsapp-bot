<style>
.floating-nav {
    position: fixed;
    bottom: 12px;
    left: 12px;
    right: 12px;
    max-width: 680px;
    margin: 0 auto;
    background: rgba(15, 32, 39, 0.88);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 210, 0, 0.35);
    border-radius: 22px;
    padding: 8px 10px;
    display: flex;
    justify-content: space-around;
    align-items: center;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.7), 0 0 20px rgba(255, 210, 0, 0.15);
    z-index: 9999;
}
.nav-item {
    color: #aaa;
    text-decoration: none;
    font-size: 10px;
    font-weight: bold;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 3px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    flex: 1;
    padding: 6px 2px;
    border-radius: 14px;
}
.nav-item .icon {
    font-size: 20px;
    transition: transform 0.3s ease;
}
.nav-item:hover {
    color: #ffd200;
}
.nav-item:hover .icon {
    transform: translateY(-4px) scale(1.1);
}
.nav-item.active {
    color: #000;
    background: linear-gradient(135deg, #ffd200, #ffaa00);
    box-shadow: 0 6px 18px rgba(255, 210, 0, 0.45);
    transform: translateY(-2px);
}
.nav-item.active .icon {
    transform: translateY(-2px) scale(1.1);
}
/* زيادة مسافة الأسفل في كل الصفحات لضمان عدم تغطية المحتوى */
body {
    padding-bottom: 105px !important;
}
</style>
<div class="floating-nav">
    <a href="main.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'main.php' ? 'active' : ''; ?>">
        <span class="icon">🏠</span>
        <span>الرئيسية</span>
    </a>
    <a href="fast_invoice.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'fast_invoice.php' ? 'active' : ''; ?>">
        <span class="icon">⚡</span>
        <span>الفاتورة</span>
    </a>
    <a href="pos_touch.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'pos_touch.php' ? 'active' : ''; ?>">
        <span class="icon">📱</span>
        <span>كاشير</span>
    </a>
    <a href="cash_drawer.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'cash_drawer.php' ? 'active' : ''; ?>">
        <span class="icon">💰</span>
        <span>الخزينة</span>
    </a>
    <a href="reports.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
        <span class="icon">📊</span>
        <span>التقارير</span>
    </a>
    <a href="backup.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'backup.php' ? 'active' : ''; ?>">
        <span class="icon">📥</span>
        <span>النسخ</span>
    </a>
</div>

<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
