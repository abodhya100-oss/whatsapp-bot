<?php
session_start();
$_SESSION["admin"] = true;
ini_set('display_errors', 0);
error_reporting(E_ALL);
if(session_status()===PHP_SESSION_NONE){session_start();} if(!isset($_SESSION["user_role"])){header("Location: login.php");exit;}
$db = new SQLite3(__DIR__ . '/vmax.db');

$msg = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ultimate_reset'])) {
    $secret_code = trim($_POST['secret_code'] ?? '');
    if ($secret_code === 'ABO_DIAA_2026_RESET') {
        $db->close();
        @unlink(__DIR__ . '/vmax.db');
        array_map('unlink', glob(__DIR__ . "/uploads/*"));
        
        $new_db = new SQLite3(__DIR__ . '/vmax.db');
        $new_db->exec("CREATE TABLE IF NOT EXISTS customers (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, phone TEXT, balance REAL DEFAULT 0, latitude REAL DEFAULT 0, longitude REAL DEFAULT 0, status TEXT DEFAULT 'نشط', type TEXT DEFAULT 'تجزئة', region TEXT DEFAULT 'السوق العام', credit_limit REAL DEFAULT 0, notes TEXT)");
        $new_db->exec("CREATE TABLE IF NOT EXISTS products (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, buy_price REAL DEFAULT 0, sell_price REAL DEFAULT 0, stock REAL DEFAULT 0, unit TEXT DEFAULT 'حبة', alt1_unit TEXT, alt1_factor REAL DEFAULT 1, alt1_price REAL DEFAULT 0, alt2_unit TEXT, alt2_factor REAL DEFAULT 1, alt2_price REAL DEFAULT 0, barcode TEXT, category TEXT DEFAULT 'عام', image TEXT)");
        $new_db->exec("CREATE TABLE IF NOT EXISTS invoices (id INTEGER PRIMARY KEY AUTOINCREMENT, customer_id INTEGER, total REAL DEFAULT 0, paid REAL DEFAULT 0, type TEXT DEFAULT 'مبيعات', date TEXT)");
        $new_db->exec("CREATE TABLE IF NOT EXISTS invoice_items (id INTEGER PRIMARY KEY AUTOINCREMENT, invoice_id INTEGER, product_id INTEGER, qty REAL, price REAL)");
        $new_db->exec("CREATE TABLE IF NOT EXISTS returns (id INTEGER PRIMARY KEY AUTOINCREMENT, customer_id INTEGER, product_id INTEGER, qty REAL, total REAL, reason TEXT, date TEXT)");
        $new_db->exec("CREATE TABLE IF NOT EXISTS expenses (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, amount REAL, date TEXT)");
        $new_db->exec("CREATE TABLE IF NOT EXISTS suppliers (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, phone TEXT, balance REAL DEFAULT 0, notes TEXT)");
        $new_db->exec("CREATE TABLE IF NOT EXISTS purchases (id INTEGER PRIMARY KEY AUTOINCREMENT, supplier_id INTEGER, product_id INTEGER, qty REAL, total REAL, date TEXT)");
        $new_db->exec("CREATE TABLE IF NOT EXISTS receipts (id INTEGER PRIMARY KEY AUTOINCREMENT, type TEXT, target_name TEXT, amount REAL, notes TEXT, date TEXT)");
        $new_db->exec("CREATE TABLE IF NOT EXISTS installments (id INTEGER PRIMARY KEY AUTOINCREMENT, customer_id INTEGER, amount REAL, due_date TEXT, status TEXT DEFAULT 'معلق', notes TEXT, date TEXT)");
        $new_db->exec("CREATE TABLE IF NOT EXISTS staff (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, phone TEXT, salary REAL DEFAULT 0, notes TEXT)");
        $new_db->exec("CREATE TABLE IF NOT EXISTS staff_advances (id INTEGER PRIMARY KEY AUTOINCREMENT, staff_id INTEGER, amount REAL, notes TEXT, date TEXT)");
        $new_db->exec("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT UNIQUE, password TEXT, role TEXT DEFAULT 'cashier', name TEXT)");
        $new_db->exec("CREATE TABLE IF NOT EXISTS audit_logs (id INTEGER PRIMARY KEY AUTOINCREMENT, user_name TEXT, action TEXT, details TEXT, date TEXT)");
        $new_db->exec("CREATE TABLE IF NOT EXISTS customer_subscriptions (id INTEGER PRIMARY KEY AUTOINCREMENT, customer_id INTEGER, sub_name TEXT, price REAL DEFAULT 0, start_date TEXT, end_date TEXT, status TEXT DEFAULT 'ساري')");
        $new_db->exec("CREATE TABLE IF NOT EXISTS settings (id INTEGER PRIMARY KEY AUTOINCREMENT, key_name TEXT UNIQUE, key_value TEXT)");
        $new_db->close();
        
        $msg = "<div style='background:rgba(56,239,125,0.3); color:#38ef7d; padding:12px; border-radius:8px; text-align:center; font-size:12px; margin-bottom:10px; font-weight:bold;'>🚀 تم تصفير النظام وإرجاعه جديداً تماماً!</div>";
    } else {
        $msg = "<div style='background:rgba(255,77,77,0.3); color:#ff4d4d; padding:12px; border-radius:8px; text-align:center; font-size:12px; margin-bottom:10px; font-weight:bold;'>❌ الرمز السري خاطئ!</div>";
    }
    if(session_status()===PHP_SESSION_NONE){session_start();} if(!isset($_SESSION["user_role"])){header("Location: login.php");exit;}
$db = new SQLite3(__DIR__ . '/vmax.db');
}

$cust_count = $db->querySingle("SELECT COUNT(*) FROM customers");
$prod_count = $db->querySingle("SELECT COUNT(*) FROM products");
$supp_count = $db->querySingle("SELECT COUNT(*) FROM suppliers");
$low_stock_count = $db->querySingle("SELECT COUNT(*) FROM products WHERE stock <= 5");
$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <link rel="manifest" href="manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js");}</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ابوضياء العالمي</title>
    <style>
        body { font-family: 'Tahoma', sans-serif; background: #0f2027; color: #fff; margin: 0; padding: 10px; padding-bottom: 110px; }
        .container { max-width: 680px; margin: auto; }
        h1 { text-align: center; color: #ffd200; font-size: 20px; margin-bottom: 2px; }
        p.sub-title { text-align: center; color: #aaa; font-size: 11px; margin-bottom: 15px; }
        .stats-grid { display: flex; gap: 5px; margin-bottom: 12px; }
        .stat-card { background: rgba(255,255,255,0.08); flex: 1; padding: 8px; border-radius: 10px; text-align: center; border: 1px solid rgba(255,255,255,0.15); }
        .stat-card h3 { color: #ffd200; font-size: 15px; margin: 0 0 2px 0; }
        .stat-card p { color: #aaa; font-size: 9px; margin: 0; }
        .menu-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 15px; }
        .menu-btn { background: rgba(255,255,255,0.08); border: 1px solid rgba(255,210,0,0.3); padding: 14px; border-radius: 12px; text-align: center; text-decoration: none; color: #fff; font-weight: bold; font-size: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.3); display: flex; flex-direction: column; align-items: center; gap: 6px; }
        .menu-btn:hover { background: rgba(255,210,0,0.2); border-color: #ffd200; }
        .menu-btn span.icon { font-size: 22px; }
        .danger-zone { background: rgba(255,0,0,0.05); border: 1px dashed rgba(255,77,77,0.4); padding: 12px; border-radius: 12px; margin-top: 20px; text-align: center; }
        .danger-toggle { background: rgba(255,77,77,0.2); color: #ff4d4d; border: 1px solid #ff4d4d; padding: 8px 12px; border-radius: 8px; font-size: 11px; cursor: pointer; font-weight: bold; }
        .danger-form { display: none; margin-top: 10px; text-align: right; }
        .danger-form input { width: 100%; padding: 8px; background: rgba(0,0,0,0.6); color: #fff; border: 1px solid #ff4d4d; border-radius: 6px; box-sizing: border-box; font-size: 11px; margin-bottom: 8px; text-align: center; }
        .btn-wipe { width: 100%; background: #ff4d4d; color: #fff; padding: 9px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 11px; }
        .nav-bar { position: fixed; bottom: 0; left: 0; right: 0; background: #0f2027; border-top: 1px solid rgba(255,210,0,0.2); padding: 6px 0; text-align: center; z-index: 1000; }
        .nav-links { display: flex; justify-content: space-around; font-size: 12px; }
        .nav-links a { color: #aaa; text-decoration: none; font-weight: bold; text-align: center; }
        .nav-links a.active, .nav-links a:hover { color: #ffd200; }
    </style>
</head>
<body>
<div class="container">
    <h1>👑 ابوضياء العالمي</h1>
    <p class="sub-title">النظام التجاري والمحاسبي العالمي الفضائي المتكامل</p>
    <?php echo $msg; ?>
    <div class="stats-grid">
        <div class="stat-card"><h3><?php echo $cust_count; ?></h3><p>👥 العملاء</p></div>
        <div class="stat-card"><h3><?php echo $prod_count; ?></h3><p>📦 الأصناف</p></div>
        <div class="stat-card"><h3><?php echo $supp_count; ?></h3><p>🏭 الموردين</p></div>
        <div class="stat-card"><h3><?php echo $low_stock_count; ?></h3><p style="color:#ff4d4d;">🚨 النواقص</p></div>
    </div>
    
    <div class="menu-grid">
        <a href="fast_invoice.php" class="menu-btn" style="border-color:#25d366; background:rgba(37,211,102,0.1);">
            <span class="icon">⚡</span>
            الفاتورة السريعة والباركود
        </a>
        <a href="pos_touch.php" class="menu-btn" style="border-color:#ffc107; background:rgba(255,193,7,0.1);">
            <span class="icon">📱</span>
            كاشير اللمس السريع (POS)
        </a>
        <a href="audit_log.php" class="menu-btn" style="border-color:#6c757d; background:rgba(108,117,125,0.1);">
            <span class="icon">🕵️‍♂️</span>
            سجل التدقيق والمراقبة
        </a>
        <a href="whatsapp_broadcast.php" class="menu-btn" style="border-color:#25d366; background:rgba(37,211,102,0.1);">
            <span class="icon">📢</span>
            التسويق الجماعي واتساب
        </a>
        <a href="item_ledger.php" class="menu-btn" style="border-color:#17a2b8; background:rgba(23,162,184,0.1);">
            <span class="icon">🔍</span>
            كارت الصنف وتتبع الحركة
        </a>
        <a href="payroll.php" class="menu-btn" style="border-color:#ffc107; background:rgba(255,193,7,0.1);">
            <span class="icon">💼</span>
            رواتب وسلف الموظفين
        </a>
        <a href="installments.php" class="menu-btn" style="border-color:#20c997; background:rgba(32,201,151,0.1);">
            <span class="icon">📅</span>
            أقساط العملاء والاستحقاق
        </a>
        <a href="profit_loss.php" class="menu-btn" style="border-color:#38ef7d; background:rgba(56,239,125,0.1);">
            <span class="icon">📈</span>
            تقرير الأرباح والخسائر
        </a>
        <a href="users.php" class="menu-btn" style="border-color:#6610f2; background:rgba(102,16,242,0.1);">
            <span class="icon">👥</span>
            إدارة المستخدمين والصلاحيات
        </a>
        <a href="returns.php" class="menu-btn" style="border-color:#ff4d4d; background:rgba(255,77,77,0.1);">
            <span class="icon">🔄</span>
            مرتجعات المبيعات والبدل
        </a>
        <a href="invoice_history.php" class="menu-btn" style="border-color:#ffd200; background:rgba(255,210,0,0.1);">
            <span class="icon">📜</span>
            سجل وأرشيف الفواتير
        </a>
        <a href="purchase_invoice.php" class="menu-btn" style="border-color:#28a745; background:rgba(40,167,69,0.1);">
            <span class="icon">📥</span>
            فواتير المشتريات والبضاعة
        </a>
        <a href="statement.php" class="menu-btn" style="border-color:#17a2b8; background:rgba(23,162,184,0.1);">
            <span class="icon">📜</span>
            كشف حساب تفصيلي
        </a>
        <a href="receipts.php" class="menu-btn">
            <span class="icon">💵</span>
            سندات القبض والصرف
        </a>
        <a href="cash_drawer.php" class="menu-btn" style="border-color:#ffc107; background:rgba(255,193,7,0.1);">
            <span class="icon">💰</span>
            إغلاق الخزينة والوردية
        </a>
        <a href="customer_map.php" class="menu-btn">
            <span class="icon">🗺️</span>
            خريطة مواقع العملاء GPS
        </a>
        <a href="barcode_gen.php" class="menu-btn">
            <span class="icon">🏷️</span>
            مولد وطابع الباركود
        </a>
        <a href="customers.php" class="menu-btn">
            <span class="icon">👥</span>
            إدارة العملاء والتحصيل
        </a>
        <a href="products.php" class="menu-btn">
            <span class="icon">📦</span>
            المستودع والوحدات
        </a>
        <a href="suppliers.php" class="menu-btn">
            <span class="icon">🏭</span>
            إدارة الموردين والشركات
        </a>
        <a href="reports.php" class="menu-btn">
            <span class="icon">📊</span>
            التقارير المالية العامة
        </a>
        <a href="low_stock.php" class="menu-btn" style="border-color:#ff4d4d;">
            <span class="icon">🚨</span>
            تنبيهات نقص المخزون
        </a>
        <a href="expenses.php" class="menu-btn">
            <span class="icon">💸</span>
            المصروفات اليومية
        </a>
        <a href="backup.php" class="menu-btn" style="border-color:#6c757d; background:rgba(108,117,125,0.1);">
            <span class="icon">📥</span>
            النسخ الاحتياطي
        </a>
    </div>

    <?php include 'signature.php'; ?>

    <div class="danger-zone">
        <button type="button" class="danger-toggle" onclick="toggleDangerZone()">🧨 منطقة الخطورة: زر تصفير النظام (صعب المنال)</button>
        <div id="dangerFormContainer" class="danger-form">
            <p style="color:#ff4d4d; font-size:10px; margin-bottom:8px; text-align:center;">⚠️ تحذير: مسح كافة بيانات النظام!</p>
            <form method="POST" onsubmit="return confirm('هل أنت متأكد من تصفير النظام بالكامل؟');">
                <input type="password" name="secret_code" placeholder="أدخل رمز الحماية السري..." required>
                <button type="submit" name="ultimate_reset" class="btn-wipe">💥 تنفيذ التصفير الشامل</button>
            </form>
        </div>
    </div>
</div>

<?php include 'nav_bar.php'; ?>

<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
