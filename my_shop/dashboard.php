<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$db = new SQLite3('vmax.db', SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
$db->busyTimeout(5000);

// جلب الإحصائيات للتلخيص الصوتي
$settings = [];
$s_res = @$db->query("SELECT key, value FROM settings");
if ($s_res) { while ($s_row = $s_res->fetchArray(SQLITE3_ASSOC)) { $settings[$s_row['key']] = $s_row['value']; } }
$store_name = $settings['store_name'] ?? 'محل هوت فون';

// حساب إجمالي المبيعات والأرباح
$sales_data = @$db->querySingle("SELECT SUM(net_amount) as total_sales, SUM(total_cost) as total_cost FROM invoices", true);
$total_sales = $sales_data['total_sales'] ?? 0;
$total_cost = $sales_data['total_cost'] ?? 0;
$net_profit = $total_sales - $total_cost;

// عدد العملاء والموردين
$cust_count = @$db->querySingle("SELECT COUNT(*) FROM customers") ?? 0;
$inv_count = @$db->querySingle("SELECT COUNT(*) FROM inventory") ?? 0;

// تجهيز رسالة الترحيب والملخص الصوتي للذكاء الاصطناعي
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم والمدير الصوتي - V-MAX PRO</title>
    <style>
        body { font-family: Tahoma, sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 10px; padding-bottom: 110px; }
        .header { text-align: center; font-size: 16px; font-weight: 900; color: #38bdf8; margin-bottom: 12px; }
        .card { background: #1e293b; border: 1px solid #38bdf8; border-radius: 10px; padding: 14px; margin-bottom: 12px; text-align: center; }
        .btn-ceo { background: #ec4899; color: #fff; border: none; padding: 14px; border-radius: 10px; font-weight: bold; width: 100%; font-size: 14px; cursor: pointer; margin-top: 10px; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 4px 15px rgba(236,72,153,0.4); }
        .stat-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; margin-bottom: 10px; }
        .stat-box { background: #0f172a; border: 1px solid #334155; padding: 10px; border-radius: 8px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">👑 لوحة التحكم التنفيذية - V-MAX PRO</div>

    <div class="card">
        <h3 style="margin-top: 0; color: #38bdf8; font-size: 14px;"><?php echo htmlspecialchars($store_name); ?></h3>
        <p style="font-size: 11px; color: #94a3b8; margin-bottom: 10px;">مديرك الافتراضي الناطق جاهز لإعطائك التقرير اليومي الصوتي.</p>
        
        <button onclick="speakCEOReport()" class="btn-ceo">
            🎙️ استمع إلى تقرير المدير الافتراضي الصوتي
        </button>
    </div>

    <div class="stat-grid">
        <div class="stat-box">
            <div style="font-size: 10px; color: #94a3b8;">إجمالي المبيعات</div>
            <div style="font-size: 15px; font-weight: 900; color: #4ade80; direction: ltr; margin-top: 4px;"><?php echo number_format($total_sales, 0); ?> ر.ي</div>
        </div>
        <div class="stat-box">
            <div style="font-size: 10px; color: #94a3b8;">صافي الأرباح</div>
            <div style="font-size: 15px; font-weight: 900; color: #38bdf8; direction: ltr; margin-top: 4px;"><?php echo number_format($net_profit, 0); ?> ر.ي</div>
        </div>
    </div>

    <script>
    // نص التقرير القادم من قاعدة البيانات
    const ceoSpeechText = "<?php echo $welcome_speech; ?>";

    function speakCEOReport() {
        if (!('speechSynthesis' in window)) {
            alert('متصفحك لا يدعم التحدث الصوتي!');
            return;
        }
        
        // إيقاف أي كلام سابق
        window.speechSynthesis.cancel();

        const utterance = new SpeechSynthesisUtterance(ceoSpeechText);
        utterance.lang = 'ar-SA'; // اللهجة العربية
        utterance.rate = 1.0;     // سرعة الكلمة
        utterance.pitch = 1.0;    // نبرة الصوت

        window.speechSynthesis.speak(utterance);
    }

    // تشغيل التقرير تلقائياً بعد تحميل الصفحة بـ ثانية واحدة (اختياري)
    window.onload = function() {
        setTimeout(speakCEOReport, 1500);
    };
    </script>

    <?php if(file_exists('nav_menu.php')) { include 'nav_menu.php'; } ?>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>
</html>

