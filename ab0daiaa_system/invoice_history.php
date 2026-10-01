<?php
// invoice_history.php - سجل الفواتير المباشر - V-MAX PRO
require_once 'auth.php';

$db_file = '/data/data/com.termux/files/home/secure_dbs/vmax.db';
$db = new SQLite3($db_file);

$invoices = [];
$res = @$db->query("SELECT * FROM invoices ORDER BY id DESC");
if ($res) {
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $invoices[] = $row;
    }
}
$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'>
    <title>سجل الفواتير - V-MAX PRO</title>
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
            padding-bottom: 90px;
        }
        .container { max-width: 600px; margin: 0 auto; }
        .header-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(241, 245, 249, 0.9));
            border: 1.5px solid var(--primary-blue);
            border-radius: 20px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.06);
            margin-bottom: 15px;
        }
        .header-card h1 { margin: 0; font-size: 18px; color: var(--primary-blue); }
        .invoice-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 12px 15px;
            margin-bottom: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .inv-info { display: flex; flex-direction: column; gap: 4px; }
        .inv-id { font-size: 13px; font-weight: bold; color: var(--primary-blue); }
        .inv-client { font-size: 12px; font-weight: bold; color: var(--text-main); }
        .inv-date { font-size: 10px; color: var(--text-muted); }
        .inv-total { font-size: 14px; font-weight: 900; color: var(--success); }
        .back-dock { margin-top: 20px; text-align: center; }
        .btn-back {
            background: #FFFFFF;
            color: #FFFFFF;
            padding: 12px 25px;
            border-radius: 14px;
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
    <div class="header-card">
        <h1>📄 سجل الفواتير والمبيعات</h1>
    </div>

    <?php if (count($invoices) > 0): ?>
        <?php foreach ($invoices as $inv): ?>
            <div class="invoice-card">
                <div class="inv-info">
                    <span class="inv-id">فاتورة رقم #<?php echo $inv['id']; ?></span>
                    <span class="inv-client">👤 الزبون: <?php echo htmlspecialchars($inv['customer_name'] ?? 'عميل نقدي'); ?></span>
                    <span class="inv-date">📅 <?php echo htmlspecialchars($inv['created_at'] ?? 'التاريخ غير متوفر'); ?></span>
                </div>
                <div class="inv-total">
                    <?php echo number_format($inv['grand_total'] ?? 0, 2); ?> ر.ي
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div style="text-align: center; padding: 40px; color: var(--text-muted); font-weight: bold;">
            لا توجد فواتير مسجلة حتى الآن.
        </div>
    <?php endif; ?>

    <div class="back-dock">
        <a href="enterprise_hub.php" class="btn-back">⬅️ العودة لمركز العمليات</a>
    </div>
</div>

</body>
</html>
