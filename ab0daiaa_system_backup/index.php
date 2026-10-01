<?php                                         
// index.php - مركز العمليات الشامل والنهائي (جميع الأقسام مدمجة) - V-MAX PRO ULTIMATE                
require_once 'auth.php';                      
$db_file = __DIR__ . '/vmax.db';
$db = new SQLite3($db_file);                                                                
$total_customers = @$db->querySingle("SELECT COUNT(*) FROM customers") ?: 0;
$total_products = @$db->query("SELECT COUNT(*) FROM products") ? $db->querySingle("SELECT COUNT(*) FROM products") : 0;                  
$total_invoices = @$db->querySingle("SELECT COUNT(*) FROM invoices") ?: 0;                  
$total_sales = @$db->query("SELECT SUM(grand_total) FROM invoices") ? $db->querySingle("SELECT SUM(grand_total) FROM invoices") : 0;

$today = date('Y-m-d');
$today_sales = @$db->querySingle("SELECT SUM(grand_total) FROM invoices WHERE DATE(created_at) = '$today'") ?: 0;
$today_invoices_count = @$db->querySingle("SELECT COUNT(*) FROM invoices WHERE DATE(created_at) = '$today'") ?: 0;

$db->close();                                 
?>
<!DOCTYPE html>                               
<html lang="ar" dir="rtl" translate="no">     
<head>
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'>
    <meta name='mobile-web-app-capable' content='yes'>
    <meta name='apple-mobile-web-app-capable' content='yes'>
    <meta charset="UTF-8">
    <title>مركز العمليات الشامل - V-MAX PRO</title>
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
            padding: 8px;
            color: #f8fafc;
            background-image:
                radial-gradient(circle at 20% 20%, rgba(139, 92, 246, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 80% 80%, rgba(6, 182, 212, 0.15) 0%, transparent 40%);
            min-height: 100vh;
            background-attachment: fixed;
            padding-bottom: 75px; 
            transition: background 0.3s ease, color 0.3s ease;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
        }

        .top-bar {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;
        }
        .settings-btn {
            background: var(--glass-bg); color: #FBBF24; padding: 5px 10px; border-radius: 10px;
            text-decoration: none; font-weight: bold; font-size: 10px; border: 1px solid #3B82F6;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2); backdrop-filter: blur(15px);
        }
        .theme-toggle-btn {
            background: var(--glass-bg); backdrop-filter: blur(15px); border: 1px solid var(--glass-border);
            color: #FBBF24; padding: 5px 10px; border-radius: 10px; font-weight: bold; font-size: 10px;
            cursor: pointer; display: inline-flex; align-items: center; gap: 4px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2); transition: 0.2s;
        }

        .hero-banner {
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.9), rgba(30, 41, 59, 0.8));
            border: 1px solid var(--neon-blue);
            border-radius: 14px;
            padding: 10px;
            text-align: center;
            box-shadow: 0 0 15px rgba(6, 182, 212, 0.15);
            margin-bottom: 8px;
        }
        .hero-banner h1 {
            margin: 0 0 2px 0;
            font-size: 14px;
            background: linear-gradient(135deg, #38bdf8, #818cf8, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero-banner p { margin: 0; font-size: 9px; color: #94a3b8; }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 6px;
            margin-bottom: 8px;
        }
        .metric-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 10px;
            padding: 8px 6px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.3);
        }
        .metric-card.full-width {
            grid-column: span 3;
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.95), rgba(30, 58, 138, 0.8));
            border-color: #3B82F6;
        }
        .metric-card .val { font-size: 12px; font-weight: bold; color: #38bdf8; margin-top: 2px; }
        .metric-card .lbl { font-size: 8px; color: #94a3b8; font-weight: bold; }

        .section-title {
            font-size: 10.5px;
            font-weight: bold;
            color: #38bdf8;
            margin: 10px 0 6px 4px;
            border-right: 2.5px solid #38bdf8;
            padding-right: 6px;
        }

        .cyber-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 6px;
            margin-bottom: 6px;
        }
        .cyber-btn {
            background: var(--glass-bg);
            backdrop-filter: blur(15px);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 10px 4px;
            text-align: center;
            text-decoration: none;
            color: #f8fafc;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            transition: all 0.2s;
            box-shadow: 0 3px 10px rgba(0,0,0,0.3);
        }
        .cyber-btn:active { transform: scale(0.92); border-color: var(--neon-blue); background: rgba(6, 182, 212, 0.2); }
        .cyber-icon { font-size: 18px; }
        .cyber-text { font-size: 9px; font-weight: bold; }

        .action-dock { display: flex; gap: 6px; margin-top: 10px; }
        .btn-action {
            flex: 1; padding: 8px; border-radius: 10px; font-weight: bold; font-size: 9.5px; text-align: center; text-decoration: none; border: none; cursor: pointer;
        }
        .btn-danger { background: linear-gradient(135deg, #f43f5e, #e11d48); color: #fff; box-shadow: 0 3px 8px rgba(244, 63, 94, 0.3); }
        .btn-backup { background: linear-gradient(135deg, #10b981, #059669); color: #fff; box-shadow: 0 3px 8px rgba(16, 185, 129, 0.3); }

        body.light-mode {
            --bg-deep: #F1F5F9 !important;
            color: #0F172A !important;
            background-image: radial-gradient(circle at 20% 20%, rgba(59, 130, 246, 0.08) 0%, transparent 40%), radial-gradient(circle at 80% 80%, rgba(6, 182, 212, 0.08) 0%, transparent 40%) !important;
        }
        body.light-mode .hero-banner {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(241, 245, 249, 0.9)) !important;
            border-color: #3B82F6 !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05) !important;
        }
        body.light-mode .metric-card,
        body.light-mode .cyber-btn {
            background: rgba(255, 255, 255, 0.9) !important;
            border: 1px solid #CBD5E1 !important;
            color: #0F172A !important;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04) !important;
        }
        body.light-mode .metric-card.full-width {
            background: linear-gradient(135deg, #EFF6FF, #DBEAFE) !important;
            border-color: #93C5FD !important;
        }
        body.light-mode .metric-card .val { color: #2563EB !important; }
        body.light-mode .metric-card .lbl { color: #64748B !important; }
        body.light-mode .section-title { color: #2563EB !important; border-right-color: #2563EB !important; }
        body.light-mode .hero-banner p { color: #64748B !important; }
        body.light-mode .settings-btn, body.light-mode .theme-toggle-btn {
            background: #FFFFFF; border-color: #CBD5E1; color: #D97706; box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>

<div class="container">

    <div class="top-bar">
        <a href="app_settings.php" class="settings-btn">⚙️ إعدادات النظام</a>
        <button type="button" class="theme-toggle-btn" id="themeBtn" onclick="toggleTheme()">
            <span id="themeIcon">☀️</span> <span id="themeText">الوضع النهاري</span>
        </button>
    </div>

    <div class="hero-banner">
        <h1>🌐 مركز العمليات الشامل</h1>
        <p>إدارة أبو ضياء - لوحة القيادة والمؤشرات المالية</p>
    </div>

    <!-- شبكة المؤشرات الحية -->
    <div class="metrics-grid">
        <div class="metric-card full-width">
            <div class="lbl">📊 مبيعات اليوم (<?php echo $today; ?>)</div>
            <div class="val" style="color: #10B981; font-size: 16px; margin-top: 2px;">
                <?php echo number_format($today_sales, 2); ?> ر.ي
                <span style="font-size: 9px; color: #94a3b8; font-weight: normal;">(<?php echo $today_invoices_count; ?> فواتير)</span>
            </div>
        </div>

        <div class="metric-card">
            <div class="lbl">العملاء</div>
            <div class="val"><?php echo $total_customers; ?></div>
        </div>
        <div class="metric-card">
            <div class="lbl">الأصناف</div>
            <div class="val"><?php echo $total_products; ?></div>
        </div>
        <div class="metric-card">
            <div class="lbl">الفواتير</div>
            <div class="val"><?php echo $total_invoices; ?></div>
        </div>
    </div>

    <!-- أقسام العمليات الشاملة -->
    <div class="section-title">المبيعات ونقاط البيع</div>
    <div class="cyber-grid">
        <a href="invoice_history.php" class="cyber-btn"><span class="cyber-icon">📄</span><span class="cyber-text">سجل الفواتير</span></a>
        <a href="formal_invoice.php" class="cyber-btn"><span class="cyber-icon">🧾</span><span class="cyber-text">فاتورة رسمية</span></a>
        <a href="pos.php" class="cyber-btn"><span class="cyber-icon">🛒</span><span class="cyber-text">شاشة الكاشير</span></a>
    </div>

    <div class="section-title">العملاء والذمم المالية</div>
    <div class="cyber-grid">
        <a href="receipts.php" class="cyber-btn"><span class="cyber-icon">💰</span><span class="cyber-text">سندات القبض</span></a>
        <a href="smart_debts.php" class="cyber-btn"><span class="cyber-icon">🔔</span><span class="cyber-text">الديون والذمم</span></a>
        <a href="customers.php" class="cyber-btn"><span class="cyber-icon">👥</span><span class="cyber-text">إدارة العملاء</span></a>
    </div>

    <div class="section-title">المخزون والمشتريات</div>
    <div class="cyber-grid">
        <a href="inventory.php" class="cyber-btn"><span class="cyber-icon">📋</span><span class="cyber-text">جرد المخزون</span></a>
        <a href="purchases.php" class="cyber-btn"><span class="cyber-icon">🏢</span><span class="cyber-text">المشتريات والموردين</span></a>
        <a href="products.php" class="cyber-btn"><span class="cyber-icon">📦</span><span class="cyber-text">إدارة الأصناف</span></a>
    </div>

    <div class="section-title">الإدارة والتقارير والصيانة</div>
    <div class="cyber-grid">
        <a href="expenses.php" class="cyber-btn"><span class="cyber-icon">💸</span><span class="cyber-text">المصروفات</span></a>
        <a href="reports.php" class="cyber-btn"><span class="cyber-icon">📈</span><span class="cyber-text">التقارير</span></a>
        <a href="maintenance.php" class="cyber-btn"><span class="cyber-icon">🔧</span><span class="cyber-text">الصيانة</span></a>
    </div>

    <div class="action-dock">
        <a href="backup.php" class="btn-action btn-backup">🛡️ النسخ الاحتياطي</a>
        <a href="logout.php" class="btn-action btn-danger">🚪 تسجيل الخروج</a>
    </div>
</div>

<?php 
if(file_exists('nav_menu.php')) { 
    include 'nav_menu.php'; 
} 
?>

<script>
function toggleTheme() {
    let body = document.body;
    body.classList.toggle("light-mode");
    let icon = document.getElementById("themeIcon");
    let text = document.getElementById("themeText");

    if(body.classList.contains("light-mode")) {
        localStorage.setItem("vmax_theme", "light");
        icon.innerHTML = "🌙";
        text.innerHTML = "الوضع الليلي";
    } else {
        localStorage.setItem("vmax_theme", "dark");
        icon.innerHTML = "☀️";
        text.innerHTML = "الوضع النهاري";
    }
}

window.addEventListener("DOMContentLoaded", (event) => {
    if(localStorage.getItem("vmax_theme") === "light") {
        document.body.classList.add("light-mode");
        let icon = document.getElementById("themeIcon");
        let text = document.getElementById("themeText");
        if(icon && text) {
            icon.innerHTML = "🌙";
            text.innerHTML = "الوضع الليلي";
        }
    }
});
</script>
</body>
</html>
