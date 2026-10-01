<?php
// enterprise_hub.php - مركز العمليات الشامل (النسخة النظيفة بدون تكرار) - V-MAX PRO                                    
require_once 'auth.php';
$db_file = '/data/data/com.termux/files/home/secure_dbs/vmax.db';
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
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'>
    <title>مركز العمليات الشامل - V-MAX PRO</title>
    <style>
        :root {
            --bg-deep: #F1F5F9;
            --card-bg: #FFFFFF;
            --text-main: #0F172A;
            --text-muted: #64748B;
            --primary-blue: #2563EB;
            --success: #10B981;
            --border-color: #CBD5E1;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Tahoma', sans-serif;
            background: #FFFFFF;
            margin: 0;
            padding: 15px;
            color: var(--text-main);
            min-height: 100vh;
        }
        .container {
            max-width: 600px;
            margin: 0 auto 90px auto;
        }
        .hero-banner {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(241, 245, 249, 0.9));
            border: 1.5px solid var(--primary-blue);
            border-radius: 20px;
            padding: 18px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.06);
            margin-bottom: 15px;
        }
        .hero-banner h1 {
            margin: 0 0 4px 0;
            font-size: 18px;
            color: var(--primary-blue);
        }
        .hero-banner p { margin: 0; font-size: 11px; color: var(--text-muted); }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        .metric-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 14px 8px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
        }
        .metric-card.full-width {
            grid-column: span 3;
            background: linear-gradient(135deg, #EFF6FF, #DBEAFE);
            border-color: #93C5FD;
        }
        .metric-card .val { font-size: 16px; font-weight: bold; color: var(--primary-blue); margin-top: 4px; }
        .metric-card .lbl { font-size: 11px; color: var(--text-muted); font-weight: bold; }

        .info-box {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            color: var(--text-muted);
            font-size: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .back-dock { text-align: center; }
        .btn-back {
            background: #FFFFFF;
            color: #FFFFFF;
            padding: 10px 22px;
            border-radius: 12px;
            font-weight: bold;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div style="display: flex; gap: 5px;">
    </div>
    <div style="display: flex; gap: 4px; align-items: center;">
        <span style="color: #94a3b8; font-size: 10px; font-weight: bold;">تكبير الشاشة:</span>
    </div>
</div>
</div>

<div class="container">
    <div class="hero-banner">
        <h1>🌐 مركز العمليات الشامل</h1>
        <p>إدارة أبو ضياء - لوحة المؤشرات والأداء المالي</p>
    </div>

    <div class="metrics-grid">
        <!-- بطاقة مبيعات اليوم -->
        <div class="metric-card full-width">
            <div class="lbl">📊 الأستاذ: مبيعات اليوم (<?php echo $today; ?>)</div>
            <div class="val" style="color: var(--success); font-size: 20px; margin-top: 4px;">
                <?php echo number_format($today_sales, 2); ?> ر.ي
                <span style="font-size: 11px; color: var(--text-muted); font-weight: normal;">(<?php echo $today_invoices_count; ?> فواتير اليوم)</span>
            </div>
        </div>

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
    </div>

    <div class="info-box">
        💡 تم تنظيف هذا المركز من كافة الأزرار المكررة لتوفير تجربة استخدام سريعة ومباشرة. يمكنك الاعتماد على القائمة السفلية ورئيسية النظام للوصول الفوري لكافة الأقسام.
    </div>

    <div class="back-dock">
        <a href="index.php" class="btn-back">🏠 العودة للرئيسية</a>
    </div>
</div>

</body>
</html>
