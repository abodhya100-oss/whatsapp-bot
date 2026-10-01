<?php
// active_features.php - لوحة الميزات الحقيقية والفعالة - V-MAX PRO
require_once 'auth.php';
$db = new SQLite3('/data/data/com.termux/files/home/secure_dbs/vmax.db');

// إنشاء جدول طابور الزبائن إذا لم يكن موجوداً
$db->exec("CREATE TABLE IF NOT EXISTS queue (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_name TEXT,
    service_type TEXT,
    status TEXT,
    time TEXT
)");

// معالجة إضافة زبون جديد للطابور
if (isset($_POST['add_queue'])) {
    $cname = trim($_POST['customer_name']);
    $stype = trim($_POST['service_type']);
    $time = date('H:i');
    if (!empty($cname)) {
        $stmt = $db->prepare("INSERT INTO queue (customer_name, service_type, status, time) VALUES (:cn, :st, 'waiting', :t)");
        $stmt->bindValue(':cn', $cname);
        $stmt->bindValue(':st', $stype);
        $stmt->bindValue(':t', $time);
        $stmt->execute();
    }
    header("Location: active_features.php");
    exit;
}

// معالجة إنهاء خدمة زبون
if (isset($_GET['done_queue'])) {
    $qid = intval($_GET['done_queue']);
    $db->exec("UPDATE queue SET status='done' WHERE id=$qid");
    header("Location: active_features.php");
    exit;
}

// جلب المنتجات ذات المخزون المنخفض فعلياً
$low_stock = [];
$res_low = $db->query("SELECT * FROM products WHERE qty <= 5 ORDER BY qty ASC");
while($row = $res_low->fetchArray(SQLITE3_ASSOC)) { $low_stock[] = $row; }

// جلب طابور الانتظار
$queue_list = [];
$res_q = $db->query("SELECT * FROM queue WHERE status='waiting' ORDER BY id ASC");
while($row_q = $res_q->fetchArray(SQLITE3_ASSOC)) { $queue_list[] = $row_q; }

// حساب إجمالي الإيرادات الحقيقية
$total_revenue = @$db->querySingle("SELECT SUM(grand_total) FROM invoices") ?: 0;
$total_products_count = @$db->querySingle("SELECT COUNT(*) FROM products") ?: 0;

$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><meta name='mobile-web-app-capable' content='yes'>
<meta name='apple-mobile-web-app-capable' content='yes'>

    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>الميزات التشغيلية الفعالة - V-MAX PRO</title>
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
    <div class="lux-card">
        <h2 class="lux-title">⚡ لوحة الميزات التشغيلية الفعالة</h2>
        <p style="font-size:11px; color:var(--text-muted); margin-bottom:15px;">
            هذه الأدوات مرتبطة مباشرة بقاعدة البيانات وتعمل فعلياً في إدارة محلك (محل أبو ضياء سيئون).
        </p>

        <!-- مؤشرات حية -->
        <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:10px; margin-bottom:15px;">
            <div style="background:rgba(16,185,129,0.1); border:1px solid var(--border-glass); padding:12px; border-radius:12px; text-align:center;">
                <div style="font-size:10px; color:var(--text-muted);">إجمالي المبيعات المحفوظة</div>
                <div style="font-size:16px; font-weight:bold; color:var(--emerald-glow); margin-top:4px;"><?php echo number_format($total_revenue, 2); ?> ر.ي</div>
            </div>
            <div style="background:rgba(6,182,212,0.1); border:1px solid var(--border-glass); padding:12px; border-radius:12px; text-align:center;">
                <div style="font-size:10px; color:var(--text-muted);">أصناف المخزون المسجلة</div>
                <div style="font-size:16px; font-weight:bold; color:var(--cyan-glow); margin-top:4px;"><?php echo $total_products_count; ?> أصناف</div>
            </div>
        </div>
    </div>

    <!-- إدارة طابور الزبائن الفعلي -->
    <div class="lux-card">
        <h3 class="lux-title">👥 إدارة طابور الزبائن (فوري)</h3>
        <form method="POST" style="margin-bottom:15px;">
            <input type="hidden" name="add_queue" value="1">
            <input type="text" name="customer_name" class="lux-input" placeholder="اسم الزبون (مثلاً: أحمد باحيثم)..." required>
            <input type="text" name="service_type" class="lux-input" placeholder="الخدمة المطلوبة (شراء جوال، صيانة شاشة)..." required>
            <button type="submit" class="lux-btn">➕ إدخال الزبون إلى الطابور</button>
        </form>

        <h4 style="font-size:12px; color:var(--cyan-glow); margin-bottom:8px;">الزبائن المنتظرون حالياً:</h4>
        <?php if(empty($queue_list)): ?>
            <p style="font-size:11px; color:var(--text-muted); text-align:center;">لا يوجد زبائن في الطابور حالياً.</p>
        <?php else: ?>
            <?php foreach($queue_list as $q): ?>
                <div style="display:flex; justify-content:space-between; align-items:center; background:rgba(30,41,59,0.6); padding:10px; margin-bottom:6px; border-radius:10px;">
                    <div>
                        <b style="font-size:12px;"><?php echo htmlspecialchars($q['customer_name']); ?></b>
                        <br><small style="color:var(--text-muted);"><?php echo htmlspecialchars($q['service_type']); ?> (الساعة: <?php echo $q['time']; ?>)</small>
                    </div>
                    <a href="active_features.php?done_queue=<?php echo $q['id']; ?>" class="lux-badge" style="text-decoration:none; background:var(--emerald-glow); color:#050b14; padding:6px 10px;">✔ تم إنجازه</a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- تنبيهات المخزون الحرجة الفعلية -->
    <div class="lux-card">
        <h3 class="lux-title">🚨 تنبيهات المخزون التلقائية</h3>
        <?php if(empty($low_stock)): ?>
            <p style="font-size:11px; color:var(--emerald-glow); text-align:center;">المخزون في وضع ممتاز ولا توجد أصناف منخفضة.</p>
        <?php else: ?>
            <?php foreach($low_stock as $ls): ?>
                <div style="display:flex; justify-content:space-between; align-items:center; background:rgba(244,63,94,0.1); border:1px solid rgba(244,63,94,0.3); padding:10px; margin-bottom:6px; border-radius:10px;">
                    <div>
                        <b style="font-size:12px;"><?php echo htmlspecialchars($ls['name']); ?></b>
                        <br><small style="color:var(--text-muted);">السعر: <?php echo $ls['price']; ?></small>
                    </div>
                    <span style="background: #FFFFFF; color:#fff; font-size:10px; padding:3px 8px; border-radius:6px; font-weight:bold;">
                        الكمية المتبقية: <?php echo $ls['qty']; ?>
                    </span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <a href="index.php" class="lux-btn" style="background: #FFFFFF; color:var(--cyan-glow); border:1px solid var(--border-glass); text-decoration:none;">🏠 العودة للرئيسية الملكية</a>
</div>

</body>
</html>
