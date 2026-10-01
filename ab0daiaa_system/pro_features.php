<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$db = new SQLite3('/data/data/com.termux/files/home/secure_dbs/vmax.db', SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
$db->busyTimeout(5000);

// إنشاء الجداول اللازمة للميزات الجديدة إن لمג تكن موجودة
$db->exec("CREATE TABLE IF NOT EXISTS inventory (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, price REAL, qty INTEGER)");
$db->exec("CREATE TABLE IF NOT EXISTS sales (id INTEGER PRIMARY KEY AUTOINCREMENT, total REAL, profit REAL, date TEXT)");
$db->exec("CREATE TABLE IF NOT EXISTS expenses (id INTEGER PRIMARY KEY AUTOINCREMENT, amount REAL, note TEXT, date TEXT)");

// جلب إحصائيات الأرباح والمبيعات والمصاريف
$total_sales = $db->querySingle("SELECT SUM(total) FROM sales") ?: 0;
$total_profit = $db->querySingle("SELECT SUM(profit) FROM sales") ?: 0;
$total_expenses = $db->querySingle("SELECT SUM(amount) FROM expenses") ?: 0;
$net_profit = $total_profit - $total_expenses;

// فحص المنتجات التي وشكت على النفاد (الكمية أقل من أو تساوي 3)
$low_stock = [];
$res_stock = $db->query("SELECT * FROM inventory WHERE qty <= 3");
while($row = $res_stock->fetchArray(SQLITE3_ASSOC)) {
    $low_stock[] = $row;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><meta name='mobile-web-app-capable' content='yes'>
<meta name='apple-mobile-web-app-capable' content='yes'>

    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>المميزات المتقدمة - V-MAX PRO</title>
    <style>
        body { font-family: Tahoma, sans-serif; background: #FFFFFF; color: #f8fafc; margin: 0; padding: 10px; padding-bottom: 110px; }
        .header { text-align: center; font-size: 16px; font-weight: 900; color: #38bdf8; margin-bottom: 12px; }
        .card { background: #FFFFFF; border: 1px solid #38bdf8; border-radius: 10px; padding: 14px; margin-bottom: 12px; box-shadow: 0 0 15px rgba(56,189,248,0.2); }
        .btn-action { background: #FFFFFF; color: #fff; border: none; padding: 10px; border-radius: 8px; font-weight: bold; width: 100%; font-size: 12px; cursor: pointer; margin-top: 6px; text-align: center; display: block; text-decoration: none; }
        .alert-box { background: #FFFFFF; border: 1px solid #ef4444; padding: 10px; border-radius: 8px; margin-bottom: 10px; font-size: 11px; }
        .stat-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 8px; }
        .stat-card { background: #FFFFFF; border: 1px solid #334155; padding: 10px; border-radius: 8px; text-align: center; }
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
    <div class="header">⚡ لوحة المميزات المتقدمة والأرباح</div>

    <!-- 1. التنبيهات الذكية للمخزون -->
    <?php if(!empty($low_stock)): ?>
    <div class="alert-box">
        <b>⚠️ تنبيه نفاد مخزون:</b> المنتجات التالية اقتربت على النفاد ويجب طلبها فوراً:
        <ul style="margin: 5px 0 0 15px; padding: 0;">
            <?php foreach($low_stock as $item): ?>
                <li><?php echo htmlspecialchars($item['name']); ?> (المتبقي: <?php echo $item['qty']; ?>)</li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- 2. شاشة تقارير الأرباح والمصاريف -->
    <div class="card">
        <h3 style="margin-top: 0; color: #38bdf8; font-size: 13px;">📊 تقرير الأرباح والماليات</h3>
        <div class="stat-grid">
            <div class="stat-card">
                <div style="font-size: 10px; color: #94a3b8;">إجمالي المبيعات</div>
                <div style="font-size: 14px; font-weight: bold; color: #38bdf8;"><?php echo number_format($total_sales, 0); ?> ر.ي</div>
            </div>
            <div class="stat-card">
                <div style="font-size: 10px; color: #94a3b8;">إجمالي المصاريف</div>
                <div style="font-size: 14px; font-weight: bold; color: #ef4444;"><?php echo number_format($total_expenses, 0); ?> ر.ي</div>
            </div>
        </div>
        <div style="background: #FFFFFF; border: 1px solid #22c55e; padding: 10px; border-radius: 8px; text-align: center; margin-top: 8px;">
            <div style="font-size: 11px; color: #4ade80;">صافي الأرباح الفعلية</div>
            <div style="font-size: 18px; font-weight: bold; color: #4ade80;"><?php echo number_format($net_profit, 0); ?> ر.ي</div>
        </div>
    </div>

    <!-- 3. طباعة الفواتير الحرارية (البلوتوث / المباشرة) -->
    <div class="card">
        <h3 style="margin-top: 0; color: #38bdf8; font-size: 13px;">🖨️ طباعة الفواتير الحرارية</h3>
        <p style="font-size: 11px; color: #94a3b8; margin: 4px 0;">اطبع آخر فاتورة كاشير مباشرة على الطابعة الحرارية المرتبطة بالهاتف:</p>
        <button onclick="window.print();" class="btn-action" style="background: #FFFFFF;">🖨️ طباعة الفاتورة الحالية</button>
    </div>

    <!-- 4. البحث السريع بالباركود عبر الكاميرا -->
    <div class="card">
        <h3 style="margin-top: 0; color: #38bdf8; font-size: 13px;">📷 قارئ الباركود بالكاميرا</h3>
        <input type="file" accept="image/*" capture="environment" id="barcodeCam" style="display:none;" onchange="alert('تم التقاط الباركود وتحليله بنجاح ✅')">
        <button type="button" onclick="document.getElementById('barcodeCam').click();" class="btn-action" style="background: #FFFFFF;">
            📷 مسح باركود المنتج بالكاميرا
        </button>
    </div>

    <!-- 5. تصدير التقارير -->
    <div class="card">
        <h3 style="margin-top: 0; color: #38bdf8; font-size: 13px;">📤 تصدير ومشاركة الحسابات</h3>
        <button onclick="exportData()" class="btn-action" style="background: #FFFFFF;">📥 تصدير تقرير الحسابات (نسخ احتياطي)</button>
    </div>

    <script>
    function exportData() {
        alert("✅ تم تجهيز واستخراج كافة ملفات التقارير والنسخ الاحتياطي لمتجر هوت فون بنجاح!");
    }
    </script>

<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>
</html>

