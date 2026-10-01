<?php
// index.php - الواجهة الرئيسية المطورة جذرياً - V-MAX PRO ULTIMATE
require_once 'auth.php';
$db_file = __DIR__ . '/vmax.db';
$db = new SQLite3($db_file);

$total_customers = @$db->querySingle("SELECT COUNT(*) FROM customers") ?: 0;
$total_products = @$db->querySingle("SELECT COUNT(*) FROM products") ?: 0;
$total_invoices = @$db->querySingle("SELECT COUNT(*) FROM invoices") ?: 0;
$total_sales = @$db->querySingle("SELECT SUM(grand_total) FROM invoices") ?: 0;
$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><meta name='mobile-web-app-capable' content='yes'>
<meta name='apple-mobile-web-app-capable' content='yes'>

    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>V-MAX PRO - الواجهة العالمية الجديدة</title>
    <style>
        :root {
            --bg-deep: #030712;
            --neon-blue: #06b6d4;
            --neon-purple: #8b5cf6;
            --glass-bg: rgba(15, 23, 42, 0.75);
            --glass-border: rgba(255, 255, 255, 0.12);
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Tahoma', sans-serif;
            background: var(--bg-deep);
            margin: 0;
            padding: 15px;
            color: #f8fafc;
            background-image: 
                radial-gradient(circle at 20% 20%, rgba(139, 92, 246, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 80% 80%, rgba(6, 182, 212, 0.15) 0%, transparent 40%);
            min-height: 100vh;
            background-attachment: fixed;
        }
        .container {
            max-width: 600px;
            margin: 0 auto 90px auto;
        }
        .hero-banner {
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.9), rgba(30, 41, 59, 0.8));
            border: 1.5px solid var(--neon-blue);
            border-radius: 24px;
            padding: 25px 20px;
            text-align: center;
            box-shadow: 0 0 30px rgba(6, 182, 212, 0.25);
            margin-bottom: 20px;
        }
        .hero-banner h1 {
            margin: 0 0 8px 0;
            font-size: 22px;
            background: linear-gradient(135deg, #38bdf8, #818cf8, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero-banner p { margin: 0; font-size: 12px; color: #94a3b8; }
        
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }
        .metric-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 18px;
            padding: 16px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
        }
        .metric-card .val { font-size: 20px; font-weight: bold; color: #38bdf8; margin-top: 6px; }
        .metric-card .lbl { font-size: 11px; color: #94a3b8; font-weight: bold; }

        .cyber-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }
        .cyber-btn {
            background: var(--glass-bg);
            backdrop-filter: blur(15px);
            border: 1px solid var(--glass-border);
            border-radius: 18px;
            padding: 16px 10px;
            text-align: center;
            text-decoration: none;
            color: #f8fafc;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            transition: all 0.2s;
            box-shadow: 0 8px 20px rgba(0,0,0,0.4);
        }
        .cyber-btn:active { transform: scale(0.92); border-color: var(--neon-blue); background: rgba(6, 182, 212, 0.2); }
        .cyber-icon { font-size: 26px; }
        .cyber-text { font-size: 11px; font-weight: bold; }
        
        .action-dock { display: flex; gap: 10px; }
        .btn-action {
            flex: 1; padding: 14px; border-radius: 14px; font-weight: bold; font-size: 12px; text-align: center; text-decoration: none; border: none; cursor: pointer;
        }
        .btn-primary { background: linear-gradient(135deg, #06b6d4, #3b82f6); color: #fff; }
        .btn-danger { background: linear-gradient(135deg, #f43f5e, #e11d48); color: #fff; }
    </style>
</head>
<body>

<div class="container">
    <div class="hero-banner">
        <h1>⚡ V-MAX PRO ULTIMATE ⚡</h1>
        <p>الواجهة الجذرية الجديدة - إدارة أبو ضياء</p>
    </div>

    <div class="metrics-grid">
        <div class="metric-card">
            <div class="lbl">إجمالي العملاء</div>
            <div class="val"><?php echo $total_customers; ?></div>
        </div>
        <div class="metric-card">
            <div class="lbl">أصناف المخزون</div>
            <div class="val"><?php echo $total_products; ?></div>
        </div>
        <div class="metric-card">
            <div class="lbl">إجمالي الفواتير</div>
            <div class="val"><?php echo $total_invoices; ?></div>
        </div>
        <div class="metric-card">
            <div class="lbl">إجمالي المبيعات</div>
            <div class="val" style="color:#10b981;"><?php echo number_format($total_sales, 2); ?></div>
        </div>
    </div>

    <div class="cyber-grid">
        <a href="pos.php" class="cyber-btn"><span class="cyber-icon">🛒</span><span class="cyber-text">الكاشير</span></a>
        <a href="customers.php" class="cyber-btn"><span class="cyber-icon">👥</span><span class="cyber-text">العملاء</span></a>
        <a href="maintenance.php" class="cyber-btn"><span class="cyber-icon">🔧</span><span class="cyber-text">الصيانة</span></a>
        <a href="products.php" class="cyber-btn"><span class="cyber-icon">📦</span><span class="cyber-text">المخزون</span></a>
        <a href="reports.php" class="cyber-btn"><span class="cyber-icon">📈</span><span class="cyber-text">التقارير</span></a>
        <a href="smart_debts.php" class="cyber-btn"><span class="cyber-icon">🔔</span><span class="cyber-text">الديون والذمم</span></a>
        <a href="purchases.php" class="cyber-btn"><span class="cyber-icon">🏢</span><span class="cyber-text">المشتريات</span></a>
        <a href="expenses.php" class="cyber-btn"><span class="cyber-icon">💸</span><span class="cyber-text">المصروفات</span></a>
        <a href="backup.php" class="cyber-btn"><span class="cyber-icon">🛡️</span><span class="cyber-text">النسخ الاحتياطي</span></a>
    </div>

    <div class="action-dock">
        <a href="enterprise_hub.php" class="btn-action btn-primary">🌐 مركز العمليات الشامل</a>
        <a href="logout.php" class="btn-action btn-danger">🚪 تسجيل الخروج</a>
    </div>
</div>

<?php if(file_exists('nav_menu.php')) { include 'nav_menu.php'; } ?>
</body>
</html>
