<?php
// radar.php - الرادار الذكي (كبار الشخصيات ونواقص المخزون) - V-MAX PRO
require_once 'auth.php';
$db = new SQLite3(__DIR__ . '/vmax.db');

// 1. جلب نادي كبار الشخصيات (أكثر 3 عملاء صرفاً للأموال في المحل)
$vip_list = [];
$res_vip = $db->query("
    SELECT c.name, SUM(i.grand_total) as total_spent 
    FROM invoices i 
    JOIN customers c ON i.customer_id = c.id 
    WHERE c.id > 0 
    GROUP BY c.id 
    ORDER BY total_spent DESC 
    LIMIT 3
");
if($res_vip) {
    while($row = $res_vip->fetchArray(SQLITE3_ASSOC)) { $vip_list[] = $row; }
}

// 2. جلب الأصناف المنتهية (منطقة الإنذار)
$dead_stock = [];
$res_dead = $db->query("SELECT * FROM products WHERE qty <= 0 ORDER BY name ASC");
if($res_dead) {
    while($row = $res_dead->fetchArray(SQLITE3_ASSOC)) { $dead_stock[] = $row; }
}

// 3. نبض مبيعات اليوم
$today = date('Y-m-d');
$today_sales = @$db->querySingle("SELECT SUM(grand_total) FROM invoices WHERE date LIKE '$today%'") ?: 0;

$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><meta name='mobile-web-app-capable' content='yes'>
<meta name='apple-mobile-web-app-capable' content='yes'>

    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>الرادار الذكي - V-MAX PRO</title>
    <?php if(file_exists('global_style.php')) include 'global_style.php'; ?>
    <style>
        body { background: #030712; padding: 10px; }
        .ultra-wrapper { max-width: 600px; margin: 0 auto 70px auto; }
        
        /* مؤشر مبيعات اليوم */
        .pulse-dashboard {
            background: rgba(13, 22, 38, 0.95);
            border: 1px solid var(--cyan-glow);
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
            box-shadow: 0 5px 25px rgba(6, 182, 212, 0.15);
        }

        /* تأثيرات القائمة الذهبية VIP */
        .vip-container {
            background: linear-gradient(135deg, #1f1604, #422006);
            border: 2px solid #eab308;
            border-radius: 16px;
            padding: 15px;
            margin-bottom: 20px;
            animation: goldPulse 2s infinite alternate;
        }
        @keyframes goldPulse {
            0% { box-shadow: 0 0 10px rgba(234, 179, 8, 0.3); border-color: #ca8a04; }
            100% { box-shadow: 0 0 25px rgba(234, 179, 8, 0.8); border-color: #fef08a; }
        }
        .vip-header {
            color: #fde047; font-size: 16px; font-weight: 900; text-align: center; 
            border-bottom: 1px solid #ca8a04; padding-bottom: 10px; margin-bottom: 15px;
            text-shadow: 0 0 10px #eab308;
        }
        .vip-card {
            background: rgba(0,0,0,0.4); border: 1px solid #eab308; border-radius: 10px; padding: 12px;
            margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center;
            position: relative; overflow: hidden;
        }
        .vip-card::before {
            content: '👑 VIP'; position: absolute; top: 12px; left: -25px; background: #eab308; color: #000;
            font-size: 9px; font-weight: bold; padding: 2px 30px; transform: rotate(-45deg);
        }

        /* تأثيرات منطقة العجز والإنذار */
        .dead-container {
            background: #1a0505; border: 2px dashed #ef4444; border-radius: 16px; padding: 15px; margin-bottom: 20px;
        }
        .dead-header {
            color: #ef4444; font-size: 16px; font-weight: bold; text-align: center;
            margin-bottom: 15px; text-shadow: 0 0 10px rgba(239, 68, 68, 0.5);
        }
        .dead-card {
            background: #270303; border: 1px solid #7f1d1d; border-radius: 8px; padding: 10px;
            margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center;
            animation: redBlink 1.5s infinite;
        }
        @keyframes redBlink {
            0%, 100% { border-color: #7f1d1d; }
            50% { border-color: #ef4444; box-shadow: inset 0 0 10px rgba(239, 68, 68, 0.2); }
        }
    </style>
</head>
<body>
<div class="ultra-wrapper">

    <!-- نبض المبيعات -->
    <div class="pulse-dashboard">
        <h3 style="margin:0 0 5px 0; color:var(--text-muted); font-size:12px;">📈 نبض مبيعات المحل (اليوم)</h3>
        <div style="font-size: 26px; font-weight: bold; color: var(--cyan-glow); text-shadow: 0 0 15px rgba(6,182,212,0.5);">
            <?php echo number_format($today_sales, 2); ?> <small style="font-size:12px;">ر.ي</small>
        </div>
    </div>

    <!-- نادي الـ VIP -->
    <div class="vip-container">
        <div class="vip-header">👑 نادي كبار الشخصيات (VIP) 👑</div>
        <?php if(empty($vip_list)): ?>
            <p style="text-align:center; color:#fef08a; font-size:11px;">لم يتم تسجيل فواتير للعملاء بعد لترشيحهم!</p>
        <?php else: ?>
            <?php foreach($vip_list as $index => $vip): ?>
                <div class="vip-card">
                    <div>
                        <div style="font-size:14px; font-weight:bold; color:#fff;"><?php echo ($index+1) . ". " . htmlspecialchars($vip['name']); ?></div>
                        <div style="font-size:10px; color:#fde047; margin-top:4px;">عميل مميز ونشط بالمحل</div>
                    </div>
                    <div style="text-align:left; margin-left: 20px;">
                        <div style="font-size:10px; color:#94a3b8;">إجمالي مشترياته</div>
                        <div style="font-size:15px; font-weight:bold; color:#eab308;"><?php echo number_format($vip['total_spent'], 2); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- منطقة عجز المخزون -->
    <div class="dead-container">
        <div class="dead-header">⚠️ منطقة الإنذار: أصناف نفدت (الرصيد صفر)</div>
        <?php if(empty($dead_stock)): ?>
            <p style="text-align:center; color:#10b981; font-size:12px; font-weight:bold;">المخزون سليم ولا توجد نواقص! ✅</p>
        <?php else: ?>
            <?php foreach($dead_stock as $ds): ?>
                <div class="dead-card">
                    <div style="font-size:13px; font-weight:bold; color:#fca5a5;"><?php echo htmlspecialchars($ds['name']); ?></div>
                    <div style="background:#ef4444; color:#fff; font-size:10px; font-weight:bold; padding:3px 8px; border-radius:5px;">رصيد: 0</div>
                </div>
            <?php endforeach; ?>
            <div style="text-align:center; margin-top:12px;">
                <a href="products.php" class="lux-btn" style="background:#450a0a; color:#fca5a5; border:1px solid #ef4444; padding:8px; font-size:11px;">تحديث المخزون الآن</a>
            </div>
        <?php endif; ?>
    </div>

</div>
<?php if(file_exists('nav_menu.php')) { include 'nav_menu.php'; } ?>
</body>
</html>
