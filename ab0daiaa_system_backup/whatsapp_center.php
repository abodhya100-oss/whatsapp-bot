<?php
// whatsapp_center.php - مركز الرسائل والواتساب السريع - V-MAX PRO
require_once 'auth.php';
$db = new SQLite3(__DIR__ . '/vmax.db');

$customers = [];
$res = $db->query("SELECT * FROM customers ORDER BY name ASC");
while($row = $res->fetchArray(SQLITE3_ASSOC)) { $customers[] = $row; }
$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><meta name='mobile-web-app-capable' content='yes'>
<meta name='apple-mobile-web-app-capable' content='yes'>

    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>واتساب المبيعات الذكي - V-MAX PRO</title>
    <?php if(file_exists('global_style.php')) include 'global_style.php'; ?>
</head>
<body>
<div class="lux-wrapper">
    <div class="lux-card">
        <h2 class="lux-title">💬 مركز رسائل الواتساب السريع</h2>
        <p style="font-size:11px; color:var(--text-muted); margin-bottom:15px;">
            أرسل تفاصيل الحساب، سندات القبض، أو تنبيهات المديونية مباشرة إلى واتساب العميل بضغطة زر.
        </p>

        <form id="waForm" onsubmit="sendWhatsApp(event)">
            <label style="font-size:11px; color:var(--text-muted); font-weight:bold;">رقم جوال العميل (مع الرمز الدولي مثلاً 96777...)</label>
            <input type="text" id="waPhone" class="lux-input" placeholder="96777xxxxxxx" required>

            <label style="font-size:11px; color:var(--text-muted); font-weight:bold;">نص الرسالة</label>
            <textarea id="waMsg" class="lux-input" rows="4" placeholder="مرحباً، نسعد بخدمتكم في محل أبو ضياء..." required></textarea>

            <button type="submit" class="lux-btn">📤 إرسال عبر الواتساب فوراً</button>
        </form>
    </div>

    <div class="lux-card">
        <h3 class="lux-title">👥 عملاء جاهزون للتواصل</h3>
        <?php if(empty($customers)): ?>
            <p style="text-align:center; color:var(--text-muted); font-size:12px;">لا توجد بيانات عملاء مسجلة.</p>
        <?php else: ?>
            <?php foreach($customers as $c): ?>
                <div style="display:flex; justify-content:space-between; align-items:center; background:rgba(30,41,59,0.5); padding:10px; margin-bottom:8px; border-radius:10px;">
                    <div>
                        <b style="font-size:12px;"><?php echo htmlspecialchars($c['name']); ?></b><br>
                        <small style="color:var(--text-muted);"><?php echo $c['phone'] ?? 'بدون رقم'; ?></small>
                    </div>
                    <?php if(!empty($c['phone'])): ?>
                        <a href="https://wa.me/<?php echo $c['phone']; ?>?text=مرحباً بك يا أخي <?php echo urlencode($c['name']); ?>، نسعد بخدمتك في محل أبو ضياء." target="_blank" class="lux-badge" style="text-decoration:none; padding:6px 12px; background:var(--emerald-glow); color:#050b14;">💬 واتساب</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <a href="index.php" class="lux-btn" style="background:#1e293b; color:var(--cyan-glow); border:1px solid var(--border-glass); text-decoration:none;">🏠 العودة للرئيسية الملكية</a>
</div>

<script>
function sendWhatsApp(e) {
    e.preventDefault();
    let phone = document.getElementById('waPhone').value.trim();
    let msg = encodeURIComponent(document.getElementById('waMsg').value);
    window.open(`https://wa.me/${phone}?text=${msg}`, '_blank');
}
</script>

<?php if(file_exists('nav_menu.php')) { include 'nav_menu.php'; } ?>
</body>
</html>
