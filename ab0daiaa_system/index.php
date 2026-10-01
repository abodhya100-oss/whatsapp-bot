<?php
// index.php - مركز العمليات الشامل - الوضع الداكن الشامل - V-MAX PRO
require_once 'auth.php';
$db_file = '/data/data/com.termux/files/home/secure_dbs/vmax.db';
$db = new SQLite3($db_file);
$total_customers = @$db->querySingle("SELECT COUNT(*) FROM customers") ?: 0;
$total_products = @$db->query("SELECT COUNT(*) FROM products") ? $db->querySingle("SELECT COUNT(*) FROM products") : 0;
$total_invoices = @$db->query("SELECT COUNT(*) FROM invoices") ? $db->querySingle("SELECT COUNT(*) FROM invoices") : 0;
$total_sales = @$db->query("SELECT SUM(grand_total) FROM invoices") ? $db->querySingle("SELECT SUM(grand_total) FROM invoices") : 0;

$today = date('Y-m-d');
$today_sales = @$db->querySingle("SELECT SUM(grand_total) FROM invoices WHERE DATE(created_at) = '$today'") ?: 0;
$today_invoices_count = @$db->querySingle("SELECT COUNT(*) FROM invoices WHERE DATE(created_at) = '$today'") ?: 0;
$today_profit = @$db->querySingle("SELECT SUM((i.unit_price - COALESCE(p.cost, 0)) * i.quantity) FROM invoice_items i LEFT JOIN products p ON i.product_id = p.id WHERE DATE(i.created_at) = '$today'") ?: 0;
$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'>
    <meta charset="UTF-8">
    <title>مركز العمليات الشامل - الوضع الداكن - V-MAX PRO</title>
    <style>
        :root {
            --bg-deep: #0B0F19;
            --card-bg: #111827;
            --text-main: #F9FAFB;
            --text-muted: #9CA3AF;
            --primary-blue: #38BDF8;
            --border-color: #374151;
        }
        * { box-sizing: border-box; }
        body { font-family: 'Tahoma', sans-serif; background: var(--bg-deep); margin: 0; padding: 12px; color: var(--text-main); min-height: 100vh; padding-bottom: 30px; }
        .container { max-width: 600px; margin: 0 auto; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .settings-btn { background: #1F2937; color: #FBBF24; padding: 6px 12px; border-radius: 10px; text-decoration: none; font-weight: bold; font-size: 11px; border: 1px solid var(--border-color); }
        .hero-banner { background: linear-gradient(135deg, #1F2937, #111827); border: 1.5px solid var(--primary-blue); border-radius: 14px; padding: 14px; text-align: center; margin-bottom: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.3); }
        .hero-banner h1 { margin: 0 0 2px 0; font-size: 16px; color: var(--primary-blue); }
        .hero-banner p { margin: 0; font-size: 10px; color: var(--text-muted); }
        .metrics-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 12px; }
        .metric-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 10px 8px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.2); }
        .metric-card.full-width { grid-column: span 3; background: linear-gradient(135deg, #1E293B, #0F172A); border-color: #3B82F6; }
        .metric-card .val { font-size: 13px; font-weight: bold; color: #38BDF8; margin-top: 2px; }
        .metric-card .lbl { font-size: 9px; color: var(--text-muted); font-weight: bold; }
        .section-title { font-size: 11px; font-weight: bold; color: #38BDF8; margin: 12px 0 6px 4px; border-right: 3px solid #38BDF8; padding-right: 6px; }
        .cyber-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 8px; }
        .cyber-btn { background: var(--card-bg); border: 1.5px solid var(--border-color); border-radius: 12px; padding: 12px 6px; text-align: center; text-decoration: none; color: #F9FAFB; display: flex; flex-direction: column; align-items: center; gap: 5px; box-shadow: 0 3px 8px rgba(0,0,0,0.2); font-weight: bold; }
        .cyber-btn:active { transform: scale(0.96); background: #1F2937; border-color: #38BDF8; }
        .cyber-icon { font-size: 20px; }
        .cyber-text { font-size: 11px; font-weight: bold; color: #F9FAFB; }
        .action-dock { display: flex; gap: 8px; margin-top: 15px; }
        .btn-action { flex: 1; padding: 12px; border-radius: 10px; font-weight: bold; font-size: 11px; text-align: center; text-decoration: none; border: none; cursor: pointer; box-shadow: 0 3px 8px rgba(0,0,0,0.3); }
        .btn-danger { background: #9F1239; color: #FFF; }
        .btn-backup { background: #065F46; color: #FFF; }
    </style>
</head>
<body>
<div class="container">
    <div class="top-bar">
        <a href="app_settings.php" class="settings-btn">⚙️ إعدادات النظام</a>
        <span style="font-size: 11px; font-weight: bold; color: var(--text-muted);">مؤسسة أبو ضياء</span>
    </div>
    <div class="hero-banner">
        <h1>🌐 مركز العمليات الشامل</h1>
        <p>إدارة أبو ضياء - لوحة القيادة والمؤشرات المالية</p>
    </div>
    <div class="metrics-grid">
        <div class="metric-card full-width">
            <div class="lbl">📊 مبيعات اليوم (<?php echo $today; ?>)</div>
            <div class="val" style="color: #34D399; font-size: 16px;">
                <?php echo number_format($today_sales, 2); ?> ر.ي
                <span style="font-size: 9px; color: var(--text-muted);">(<?php echo $today_invoices_count; ?> فواتير)</span>
            </div>
        </div>
        <div class="metric-card full-width" style="background: linear-gradient(135deg, #064E3B, #022C22); border-color: #059669;">
            <div class="lbl">💰 صافي أرباح اليوم التقديرية</div>
            <div class="val" style="color: #34D399; font-size: 15px;"><?php echo number_format($today_profit, 2); ?> ر.ي</div>
        </div>
        <div class="metric-card"><div class="lbl">العملاء</div><div class="val"><?php echo $total_customers; ?></div></div>
        <div class="metric-card"><div class="lbl">الأصناف</div><div class="val"><?php echo $total_products; ?></div></div>
        <div class="metric-card"><div class="lbl">الفواتير</div><div class="val"><?php echo $total_invoices; ?></div></div>
    </div>

    <div class="section-title">المبيعات والفواتير</div>
    <div class="cyber-grid">
        <a href="reports.php" class="cyber-btn"><span class="cyber-icon">🚚</span><span class="cyber-text">حركة الموزعين</span></a>
        <a href="formal_invoice.php" class="cyber-btn"><span class="cyber-icon">🧾</span><span class="cyber-text">فاتورة رسمية</span></a>
        <a href="invoice_history.php" class="cyber-btn"><span class="cyber-icon">📄</span><span class="cyber-text">سجل الفواتير</span></a>
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
        <a href="backup.php" class="btn-action btn-backup">🛡 النسخ الاحتياطي</a>
        <a href="logout.php" class="btn-action btn-danger">🚪 تسجيل الخروج</a>
    </div>
</div>
</body>
</html>
