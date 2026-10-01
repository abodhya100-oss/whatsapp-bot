<?php
// power_features.php - محرك الميزات الخارقة والذكاء التجاري - V-MAX PRO
require_once 'auth.php';
$db = new SQLite3('/data/data/com.termux/files/home/secure_dbs/vmax.db');

// البحث السريع عن المنتجات
$search_query = trim($_GET['q'] ?? '');
$search_results = [];
if (!empty($search_query)) {
    $stmt = $db->prepare("SELECT * FROM products WHERE name LIKE :q OR price LIKE :q");
    $stmt->bindValue(':q', '%' . $search_query . '%');
    $res = $stmt->execute();
    while($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $search_results[] = $row;
    }
}

// إحصائيات مالية متقدمة
$total_invoices_sum = @$db->querySingle("SELECT SUM(grand_total) FROM invoices") ?: 0;
$total_customers_count = @$db->querySingle("SELECT COUNT(*) FROM customers") ?: 0;
$total_vouchers_receipt = @$db->querySingle("SELECT SUM(amount) FROM vouchers WHERE type='receipt'") ?: 0;
$total_vouchers_payment = @$db->querySingle("SELECT SUM(amount) FROM vouchers WHERE type='payment'") ?: 0;

$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><meta name='mobile-web-app-capable' content='yes'>
<meta name='apple-mobile-web-app-capable' content='yes'>

    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>الميزات الخارقة والذكاء التجاري - V-MAX PRO</title>
    <?php if(file_exists('global_style.php')) include 'global_style.php'; ?>
</head>
<body>
    <div style="display: flex; gap: 5px;">
    </div>
    <div style="display: flex; gap: 4px; align-items: center;">
        <span style="color: #94a3b8; font-size: 10px; font-weight: bold;">تكبير الشاشة:</span>
    </div>
</div>
</div>
<div class="lux-wrapper">
    <!-- محرك البحث اللحظي الخارق -->
    <div class="lux-card">
        <h2 class="lux-title">🔍 محرك البحث السريع والباركود اللحظي</h2>
        <form method="GET" style="margin-bottom: 12px;">
            <input type="text" name="q" value="<?php echo htmlspecialchars($search_query); ?>" class="lux-input" placeholder="ابحث باسم الصنف أو السعر...">
            <button type="submit" class="lux-btn">بحث فوري في المخزون</button>
        </form>

        <?php if (!empty($search_query)): ?>
            <h4 style="font-size: 12px; color: var(--cyan-glow); margin-bottom: 8px;">نتائج البحث عن ("<?php echo htmlspecialchars($search_query); ?>"):</h4>
            <?php if (empty($search_results)): ?>
                <p style="font-size: 11px; color: var(--text-muted); text-align: center;">لم يتم العثور على أي صنف مطابق.</p>
            <?php else: ?>
                <?php foreach ($search_results as $sr): ?>
                    <div style="background: rgba(30,41,59,0.7); padding: 10px; margin-bottom: 6px; border-radius: 10px; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <b style="font-size: 12px;"><?php echo htmlspecialchars($sr['name']); ?></b>
                            <br><small style="color: var(--text-muted);">المخزون المتوفر: <?php echo $sr['qty']; ?></small>
                        </div>
                        <span style="color: var(--emerald-glow); font-weight: bold; font-size: 13px;"><?php echo $sr['price']; ?> ر.ي</span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- مصفوفة الأداء المالي التنفيذي -->
    <div class="lux-card">
        <h3 class="lux-title">📈 مصفوفة الأداء المالي والسيولة</h3>
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
            <div style="background: rgba(16,185,129,0.1); border: 1px solid var(--border-glass); padding: 12px; border-radius: 12px; text-align: center;">
                <div style="font-size: 10px; color: var(--text-muted);">إجمالي المبيعات العامة</div>
                <div style="font-size: 15px; font-weight: bold; color: var(--emerald-glow); margin-top: 4px;"><?php echo number_format($total_invoices_sum, 2); ?></div>
            </div>
            <div style="background: rgba(6,182,212,0.1); border: 1px solid var(--border-glass); padding: 12px; border-radius: 12px; text-align: center;">
                <div style="font-size: 10px; color: var(--text-muted);">إجمالي العملاء المسجلين</div>
                <div style="font-size: 15px; font-weight: bold; color: var(--cyan-glow); margin-top: 4px;"><?php echo $total_customers_count; ?> عميل</div>
            </div>
            <div style="background: rgba(251,191,36,0.1); border: 1px solid var(--border-glass); padding: 12px; border-radius: 12px; text-align: center;">
                <div style="font-size: 10px; color: var(--text-muted);">سندات القبض الواردة</div>
                <div style="font-size: 15px; font-weight: bold; color: var(--gold-glow); margin-top: 4px;"><?php echo number_format($total_vouchers_receipt, 2); ?></div>
            </div>
            <div style="background: rgba(244,63,94,0.1); border: 1px solid var(--border-glass); padding: 12px; border-radius: 12px; text-align: center;">
                <div style="font-size: 10px; color: var(--text-muted);">سندات الصرف الصادرة</div>
                <div style="font-size: 15px; font-weight: bold; color: #f43f5e; margin-top: 4px;"><?php echo number_format($total_vouchers_payment, 2); ?></div>
            </div>
        </div>
    </div>

    <a href="index.php" class="lux-btn" style="background: #FFFFFF; color:var(--cyan-glow); border:1px solid var(--border-glass); text-decoration:none;">🏠 العودة للرئيسية الملكية</a>
</div>

</body>
</html>
