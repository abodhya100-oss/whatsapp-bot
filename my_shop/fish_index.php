<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_role'])) { header("Location: login.php"); exit; }
$db = new SQLite3('vmax.db');

// إنشاء جداول خاصة بعالم الأسماك والصيد
$db->exec("CREATE TABLE IF NOT EXISTS fish_products (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, unit TEXT DEFAULT 'كيلو', price REAL, stock REAL, supplier TEXT);");
$db->exec("CREATE TABLE IF NOT EXISTS fish_invoices (id INTEGER PRIMARY KEY AUTOINCREMENT, customer_name TEXT, total REAL, weight_total REAL, date TEXT, payment_method TEXT);");
$db->exec("CREATE TABLE IF NOT EXISTS fish_purchases (id INTEGER PRIMARY KEY AUTOINCREMENT, supplier_name TEXT, fish_type TEXT, qty REAL, price REAL, total REAL, date TEXT);");
$db->exec("CREATE TABLE IF NOT EXISTS fish_customers (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, phone TEXT, balance REAL DEFAULT 0);");

$today = date('Y-m-d');
$fish_sales_today = $db->querySingle("SELECT SUM(total) FROM fish_invoices WHERE date LIKE '$today%'") ?: 0;
$fish_purchases_today = $db->querySingle("SELECT SUM(total) FROM fish_purchases WHERE date LIKE '$today%'") ?: 0;
$fish_debts = $db->querySingle("SELECT SUM(balance) FROM fish_customers WHERE balance > 0") ?: 0;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <link rel="manifest" href="manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js");}</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إمبراطورية الأسماك والصيد البحري (أبو ضياء)</title>
    <style>
        body { font-family: Tahoma, sans-serif; background: #020617; color: #f8fafc; margin: 0; padding: 15px; padding-bottom: 100px; direction: rtl; }
        .ocean-hero { background: linear-gradient(135deg, #0369a1 0%, #06b6d4 100%); border-radius: 20px; padding: 25px; text-align: center; margin-bottom: 20px; box-shadow: 0 15px 35px rgba(6, 182, 212, 0.25); border: 1px solid rgba(255,255,255,0.2); }
        .ocean-title { font-size: 22px; font-weight: 900; color: #fff; margin-bottom: 5px; text-shadow: 0 2px 4px rgba(0,0,0,0.3); }
        .ocean-sub { font-size: 12px; color: #cffafe; }
        
        .stat-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 20px; }
        .stat-card { background: rgba(15, 23, 42, 0.9); border: 1px solid #0e7490; border-radius: 14px; padding: 15px; text-align: center; box-shadow: 0 8px 20px rgba(0,0,0,0.4); }
        .stat-val { font-size: 18px; font-weight: 900; color: #38bdf8; margin-top: 5px; font-family: Tahoma, sans-serif; }
        
        .menu-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
        .menu-btn { background: linear-gradient(145deg, #0f172a, #1e293b); border: 1px solid #0284c7; border-radius: 16px; padding: 20px 10px; text-align: center; color: #f8fafc; text-decoration: none; font-size: 13px; font-weight: bold; display: flex; flex-direction: column; align-items: center; gap: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); transition: 0.3s; }
        .menu-btn:active { transform: scale(0.94); border-color: #38bdf8; background: rgba(14, 116, 144, 0.2); }
        .menu-icon { font-size: 32px; filter: drop-shadow(0 2px 5px rgba(0,0,0,0.5)); }

        .back-empire { display: block; text-align: center; background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #f87171; padding: 12px; border-radius: 14px; text-decoration: none; font-weight: bold; font-size: 13px; margin-top: 25px; transition: 0.2s; }
        .back-empire:active { background: rgba(239, 68, 68, 0.3); }
    </style>
</head>
<body>
    <div class="ocean-hero">
        <div style="font-size: 40px; margin-bottom: 5px;">🌊🐟</div>
        <div class="ocean-title">عالم إمبراطورية الأسماك والصيد</div>
        <div class="ocean-sub">منظومة محاسبية مستقلة لأسواق الأسماك والمراسي</div>
    </div>

    <div class="stat-grid">
        <div class="stat-card" style="border-color: #22c55e;">
            <div style="font-size: 11px; color: #94a3b8;">مبيعات الأسماك اليوم:</div>
            <div class="stat-val" style="color: #4ade80;"><?php echo number_format($fish_sales_today, 0); ?> ر.ي</div>
        </div>
        <div class="stat-card" style="border-color: #f97316;">
            <div style="font-size: 11px; color: #94a3b8;">مشتريات الصيد اليوم:</div>
            <div class="stat-val" style="color: #f97316;"><?php echo number_format($fish_purchases_today, 0); ?> ر.ي</div>
        </div>
    </div>

    <div class="menu-grid">
        <a href="fish_pos.php" class="menu-btn" style="border-color: #22c55e;">
            <span class="menu-icon">⚖️</span><span>كاشير بيع الأسماك (بالوزن)</span>
        </a>
        <a href="fish_purchases.php" class="menu-btn" style="border-color: #38bdf8;">
            <span class="menu-icon">🚤</span><span>شراء الصيد من البحارة</span>
        </a>
        <a href="fish_customers.php" class="menu-btn" style="border-color: #a855f7;">
            <span class="menu-icon">👥</span><span>حسابات عملاء السمك</span>
        </a>
        <a href="fish_products.php" class="menu-btn" style="border-color: #facc15;">
            <span class="menu-icon">🐟</span><span>إدارة أنواع الأسماك والأسعار</span>
        </a>
    </div>

    <a href="index.php" class="back-empire">🔙 العودة إلى الإمبراطورية الرئيسية (الالنظام المحاسبي العام)</a>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>
</html>

