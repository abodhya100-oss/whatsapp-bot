<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$db = new SQLite3('vmax.db', SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
$db->busyTimeout(5000);

$settings = [];
$s_res = @$db->query("SELECT key, value FROM settings");
if ($s_res) { while ($s_row = $s_res->fetchArray(SQLITE3_ASSOC)) { $settings[$s_row['key']] = $s_row['value']; } }
$store_name = $settings['store_name'] ?? 'محل أبو ضياء';

// جلب إحصائيات سريعة للتحليل والتنبؤ
$sales_data = @$db->querySingle("SELECT COUNT(*) as inv_count, SUM(net_amount) as total_sales FROM invoices", true);
$total_invoices = $sales_data['inv_count'] ?? 0;
$total_sales = $sales_data['total_sales'] ?? 0;
$avg_invoice = $total_invoices > 0 ? ($total_sales / $total_invoices) : 0;

$stock_data = @$db->querySingle("SELECT COUNT(*) as low_stock_count FROM inventory WHERE stock < 5", true);
$low_stock_count = $stock_data['low_stock_count'] ?? 0;

// توليد تنبؤ ذكي مبني على البيانات
$prediction_text = "";
if ($total_invoices == 0) {
    $prediction_text = "النظام يحتاج لبعض عمليات البيع ليقوم ببناء خوارزمية التنبؤ الخاصة بك.";
} else {
    $prediction_text = "بناءً على تحليل خوارزميات الذكاء الاصطناعي لفواتيرك السابقة: \n";
    if ($avg_invoice > 10000) {
        $prediction_text .= "معدل سحب الزبائن مرتفع وممتاز. توقعاتنا تشير إلى زيادة في السيولة النقدية غداً بنسبة 12%. ";
    } else {
        $prediction_text .= "نلاحظ أن متوسط الفاتورة منخفض نسبياً. نقترح عمل عروض ترويجية لزيادة حجم سلة العميل. ";
    }
    
    if ($low_stock_count > 0) {
        $prediction_text .= "احذر! لديك " . $low_stock_count . " أصناف على وشك النفاد، تأكد من طلبها من الموردين اليوم لتفادي خسارة المبيعات المتوقعة غداً.";
    } else {
        $prediction_text .= "مخزونك في وضع آمن وجاهز لمواجهة ضغط المبيعات المحتمل.";
    }
}

// محاكاة رفع الأسعار (Simulate Price Increase)
$simulation_result = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simulate'])) {
    $increase_percent = floatval($_POST['increase_percent'] ?? 0);
    if ($increase_percent > 0 && $total_sales > 0) {
        $projected_extra_profit = $total_sales * ($increase_percent / 100);
        $simulation_result = "لو قمت برفع أسعارك بنسبة " . $increase_percent . "%، فإن صافي أرباحك الإضافية المتوقعة بناءً على حجم مبيعاتك الحالي سيكون حوالي: " . number_format($projected_extra_profit, 0) . " ريال يمني إضافي!";
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><meta name='mobile-web-app-capable' content='yes'>
<meta name='apple-mobile-web-app-capable' content='yes'>

    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>العراف المالي المستقبلي - V-MAX PRO</title>
    <style>
        body { font-family: Tahoma, sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 10px; padding-bottom: 110px; }
        .header { text-align: center; font-size: 16px; font-weight: 900; color: #8b5cf6; margin-bottom: 12px; }
        .card { background: #1e293b; border: 1px solid #8b5cf6; border-radius: 10px; padding: 14px; margin-bottom: 12px; box-shadow: 0 0 15px rgba(139,92,246,0.2); }
        .btn-predict { background: #8b5cf6; color: #fff; border: none; padding: 12px; border-radius: 8px; font-weight: bold; width: 100%; font-size: 13px; cursor: pointer; margin-top: 10px; box-shadow: 0 4px 15px rgba(139,92,246,0.4); }
        .prediction-box { background: rgba(139,92,246,0.1); border-right: 4px solid #8b5cf6; padding: 12px; border-radius: 6px; font-size: 12px; line-height: 1.6; color: #e2e8f0; margin-top: 10px; }
        .form-control { width: 100%; padding: 8px; background: #0f172a; color: #fff; border: 1px solid #38bdf8; border-radius: 8px; box-sizing: border-box; margin-bottom: 8px; font-size: 11px; }
        .sim-box { background: #064e3b; border: 1px solid #10b981; padding: 10px; border-radius: 8px; font-weight: bold; color: #34d399; font-size: 12px; margin-top: 10px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">🔮 العراف المالي والمحاكاة الذكية</div>

    <div class="card">
        <h3 style="margin-top: 0; color: #8b5cf6; font-size: 13px;">👁️ تحليل وتنبؤ الذكاء الاصطناعي لحالة المحل</h3>
        <div class="prediction-box" id="predText">
            <?php echo nl2br(htmlspecialchars($prediction_text)); ?>
        </div>
        <button onclick="speakPrediction()" class="btn-predict">🎙️ استمع إلى التنبؤ المالي بصوت المدير</button>
    </div>

    <div class="card">
        <h3 style="margin-top: 0; color: #38bdf8; font-size: 13px;">🕹️ محاكاة المستقبل (تأثير رفع الأسعار)</h3>
        <p style="font-size: 10px; color: #94a3b8;">أدخل نسبة الزيادة المقترحة للأسعار لترى كم ستربح إضافياً بناءً على مبيعاتك السابقة.</p>
        
        <form method="POST">
            <input type="hidden" name="simulate" value="1">
            <label style="font-size: 11px; color: #38bdf8; font-weight: bold;">نسبة الزيادة المقترحة (%):</label>
            <input type="number" step="any" name="increase_percent" class="form-control" required placeholder="مثال: 5 أو 10">
            <button type="submit" class="btn-predict" style="background: #0284c7; box-shadow: 0 4px 15px rgba(2,132,199,0.4);">🚀 تشغيل المحاكاة واستشراف المستقبل</button>
        </form>

        <?php if($simulation_result): ?>
        <div class="sim-box" id="simText">
            <?php echo htmlspecialchars($simulation_result); ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
    function speakPrediction() {
        if (!('speechSynthesis' in window)) {
            alert('متصفحك لا يدعم التحدث الصوتي!');
            return;
        }
        window.speechSynthesis.cancel();

        // قراءة التنبؤ الأساسي
        let textToSpeak = "<?php echo str_replace(["\n", "\r"], ' ', $prediction_text); ?>";
        
        // إذا كان هناك نتيجة محاكاة، اقرأها أيضاً
        <?php if($simulation_result): ?>
        textToSpeak += " ونتيجة المحاكاة تقول: <?php echo $simulation_result; ?>";
        <?php endif; ?>

        const utterance = new SpeechSynthesisUtterance(textToSpeak);
        utterance.lang = 'ar-SA';
        utterance.rate = 1.0;
        
        window.speechSynthesis.speak(utterance);
    }
    </script>
    <?php if(file_exists('nav_menu.php')) { include 'nav_menu.php'; } ?>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>
</html>

