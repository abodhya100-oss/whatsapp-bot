<?php
// galaxy_pos.php - النظام الخيالي من عالم آخر - V-MAX PRO GALAXY
require_once 'auth.php';
$db_file = __DIR__ . '/vmax.db';
$db = new SQLite3($db_file);

$total_customers = @$db->querySingle("SELECT COUNT(*) FROM customers") ?: 0;
$total_products = @$db->querySingle("SELECT COUNT(*) FROM products") ?: 0;
$total_invoices = @$db->querySingle("SELECT COUNT(*) FROM invoices") ?: 0;
$total_sales = @$db->querySingle("SELECT SUM(grand_total) FROM invoices") ?: 0;
$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><meta name='mobile-web-app-capable' content='yes'>
<meta name='apple-mobile-web-app-capable' content='yes'>

    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>V-MAX PRO GALAXY - من عالم آخر</title>
    <style>
        :root {
            --space-bg: #010409;
            --holo-cyan: #00f0ff;
            --holo-purple: #b000ff;
            --holo-pink: #ff007f;
            --glass: rgba(13, 17, 23, 0.85);
            --border-glow: rgba(0, 240, 255, 0.3);
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Tahoma', sans-serif;
            background: var(--space-bg);
            margin: 0;
            padding: 15px;
            color: #ffffff;
            background-image: 
                radial-gradient(circle at 10% 10%, rgba(176, 0, 255, 0.2) 0%, transparent 50%),
                radial-gradient(circle at 90% 90%, rgba(0, 240, 255, 0.2) 0%, transparent 50%),
                radial-gradient(circle at 50% 50%, rgba(255, 0, 127, 0.15) 0%, transparent 60%);
            min-height: 100vh;
            background-attachment: fixed;
        }
        .galaxy-wrapper { max-width: 650px; margin: 0 auto 90px auto; }
        
        .holo-header {
            background: linear-gradient(135deg, rgba(13, 17, 23, 0.95), rgba(22, 27, 34, 0.9));
            border: 2px solid var(--holo-cyan);
            border-radius: 25px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 0 40px rgba(0, 240, 255, 0.3), inset 0 0 20px rgba(0, 240, 255, 0.15);
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
        }
        .holo-header h1 {
            margin: 0 0 8px 0;
            font-size: 24px;
            background: linear-gradient(135deg, #00f0ff, #b000ff, #ff007f);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 0 20px rgba(0,240,255,0.5);
        }
        .holo-header p { margin: 0; font-size: 12px; color: #8b949e; letter-spacing: 1px; }

        .quantum-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }
        .stat-pod {
            background: var(--glass);
            backdrop-filter: blur(25px);
            border: 1px solid var(--border-glow);
            border-radius: 20px;
            padding: 18px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.6);
            transition: all 0.3s;
        }
        .stat-pod:hover {
            border-color: var(--holo-cyan);
            box-shadow: 0 0 25px rgba(0, 240, 255, 0.4);
            transform: translateY(-3px);
        }
        .stat-pod .num { font-size: 22px; font-weight: bold; color: var(--holo-cyan); margin-top: 6px; text-shadow: 0 0 10px rgba(0,240,255,0.5); }
        .stat-pod .lbl { font-size: 11px; color: #8b949e; font-weight: bold; }

        .galaxy-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }
        .galaxy-btn {
            background: var(--glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-glow);
            border-radius: 20px;
            padding: 20px 10px;
            text-align: center;
            text-decoration: none;
            color: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 8px 25px rgba(0,0,0,0.5);
        }
        .galaxy-btn:active {
            transform: scale(0.92);
            background: rgba(0, 240, 255, 0.15);
            border-color: var(--holo-cyan);
            box-shadow: 0 0 20px rgba(0, 240, 255, 0.5);
        }
        .galaxy-icon { font-size: 30px; filter: drop-shadow(0 0 10px rgba(255,255,255,0.4)); }
        .galaxy-title { font-size: 11.5px; font-weight: bold; }

        .control-dock { display: flex; gap: 10px; }
        .dock-btn {
            flex: 1; padding: 15px; border-radius: 16px; font-weight: bold; font-size: 13px; text-align: center; text-decoration: none; border: none; cursor: pointer;
            box-shadow: 0 5px 20px rgba(0,0,0,0.5);
        }
        .btn-holo { background: linear-gradient(135deg, #00f0ff, #0077ff); color: #010409; box-shadow: 0 0 20px rgba(0,240,255,0.4); }
        .btn-plasma { background: linear-gradient(135deg, #ff007f, #b000ff); color: #fff; box-shadow: 0 0 20px rgba(255,0,127,0.4); }
    </style>
</head>
<body>

<div class="galaxy-wrapper">
    <div class="holo-header">
        <h1>🌌 V-MAX PRO GALAXY 🌌</h1>
        <p>نظام الإدارة الفضائي من عالم آخر - إشراف أبو ضياء</p>
    </div>

    <div class="quantum-stats">
        <div class="stat-pod">
            <div class="lbl">إجمالي العملاء</div>
            <div class="num"><?php echo $total_customers; ?></div>
        </div>
        <div class="stat-pod">
            <div class="lbl">أصناف المخزون</div>
            <div class="num"><?php echo $total_products; ?></div>
        </div>
        <div class="stat-pod">
            <div class="lbl">إجمالي الفواتير</div>
            <div class="num"><?php echo $total_invoices; ?></div>
        </div>
        <div class="stat-pod">
            <div class="lbl">إجمالي المبيعات</div>
            <div class="num" style="color:#00ff88;"><?php echo number_format($total_sales, 2); ?></div>
        </div>
    </div>

    <div class="galaxy-grid">
        <a href="pos.php" class="galaxy-btn"><span class="galaxy-icon">🛒</span><span class="galaxy-title">الكاشير الفضائي</span></a>
        <a href="customers.php" class="galaxy-btn"><span class="galaxy-icon">👥</span><span class="galaxy-title">العملاء والشركاء</span></a>
        <a href="maintenance.php" class="galaxy-btn"><span class="galaxy-icon">🔧</span><span class="galaxy-title">قسم الصيانة</span></a>
        <a href="products.php" class="galaxy-btn"><span class="galaxy-icon">📦</span><span class="galaxy-title">المخزون والقطع</span></a>
        <a href="reports.php" class="galaxy-btn"><span class="galaxy-icon">📈</span><span class="galaxy-title">تقارير الأرباح</span></a>
        <a href="smart_debts.php" class="galaxy-btn"><span class="galaxy-icon">🔔</span><span class="galaxy-title">الديون والذمم</span></a>
        <a href="purchases.php" class="galaxy-btn"><span class="galaxy-icon">🏢</span><span class="galaxy-title">المشتريات</span></a>
        <a href="expenses.php" class="galaxy-btn"><span class="galaxy-icon">💸</span><span class="galaxy-title">المصروفات</span></a>
        <a href="backup.php" class="galaxy-btn"><span class="galaxy-icon">🛡️</span><span class="galaxy-title">النسخ الاحتياطي</span></a>
    </div>

    <div class="control-dock">
        <a href="enterprise_hub.php" class="dock-btn btn-holo">🌐 مركز العمليات</a>
        <a href="logout.php" class="dock-btn btn-plasma">🚪 خروج آمن</a>
    </div>
</div>

<?php if(file_exists('nav_menu.php')) { include 'nav_menu.php'; } ?>
<!-- auto_fill_client_universal -->

<!-- استقبال وتعبئة اسم العميل تلقائياً (مضاف برمجياً وبأمان تام) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        const urlParams = new URLSearchParams(window.location.search);
        const clientParam = urlParams.get('client');
        
        if(clientParam) {
            // البحث عن أي حقل مخصص للعميل في صفحة الفاتورة وتعبئته فوراً
            var custInputs = document.querySelectorAll('input[name*="customer"], select[name*="customer"], #customer, .customer-input, input[name*="client"], select[name*="client"]');
            custInputs.forEach(function(inp) {
                if(inp.tagName === 'SELECT') {
                    for(var i = 0; i < inp.options.length; i++) {
                        if(inp.options[i].text.includes(clientParam)) {
                            inp.selectedIndex = i;
                            inp.dispatchEvent(new Event('change'));
                            break;
                        }
                    }
                } else {
                    inp.value = decodeURIComponent(clientParam);
                    inp.dispatchEvent(new Event('input'));
                }
            });
        }
    }, 600);
});
</script>
<!-- pos_auto_inject_client_script -->

<!-- استقبال وتعبئة اسم العميل في الفاتورة تلقائياً (مضاف برمجياً وبأمان) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        // التقاط اسم العميل من رابط الصفحة (URL Parameters) أو من الذاكرة المحلية
        const urlParams = new URLSearchParams(window.location.search);
        let clientName = urlParams.get('client') || urlParams.get('customer') || localStorage.getItem('active_invoice_customer');
        
        if(clientName) {
            clientName = decodeURIComponent(clientName).trim();
            
            // البحث عن حقل اختيار العميل الظاهر في واجهة الفاتورة (الذي يظهر فيه عميل نقدي عام)
            var customerSelects = document.querySelectorAll('select, input');
            customerSelects.forEach(function(el) {
                // التحقق إذا كان الحقل يخص العميل عبر النص أو الخيارات الموجودة داخله
                if(el.tagName === 'SELECT') {
                    for(var i = 0; i < el.options.length; i++) {
                        if(el.options[i].text.includes(clientName) || el.options[i].text.includes('العميل')) {
                            el.selectedIndex = i;
                            el.dispatchEvent(new Event('change'));
                            break;
                        }
                    }
                } else if(el.type === 'text' || !el.type) {
                    if(el.placeholder && (el.placeholder.includes('عميل') || el.placeholder.includes('الزبون')) || el.value.includes('عميل نقدي')) {
                        el.value = clientName;
                        el.dispatchEvent(new Event('input'));
                        el.dispatchEvent(new Event('change'));
                    }
                }
            });
            
            // مسح الذاكرة المؤقتة بعد التعبئة لضمان عدم تكرارها بطريقة خاطئة
            localStorage.removeItem('active_invoice_customer');
        }
    }, 800);
});
</script>
<!-- pos_select_dropdown_fix -->

<!-- اختيار العميل المطابق تلقائياً في واجهة الفاتورة (مضاف برمجياً وبأمان) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        const urlParams = new URLSearchParams(window.location.search);
        let clientName = urlParams.get('client') || urlParams.get('customer') || localStorage.getItem('active_invoice_customer');
        
        if(clientName) {
            clientName = decodeURIComponent(clientName).trim();
            
            // البحث عن جميع القوائم المنسدلة (Select) في الصفحة
            var selects = document.querySelectorAll('select');
            selects.forEach(function(sel) {
                for(var i = 0; i < sel.options.length; i++) {
                    var optText = sel.options[i].text.trim();
                    // إذا كان اسم العميل مطابقاً أو جزءاً من خيار القائمة
                    if(optText.includes(clientName) || clientName.includes(optText)) {
                        sel.selectedIndex = i;
                        sel.dispatchEvent(new Event('change'));
                        // إطلاق حدث إضافي لضمان تحديث النظام للاختيار
                        if(typeof $ !== 'undefined' && $(sel).hasClass('select2')) {
                            $(sel).trigger('change');
                        }
                        break;
                    }
                }
            });
            
            localStorage.removeItem('active_invoice_customer');
        }
    }, 1000);
});
</script>
<!-- universal_client_auto_fill -->

<!-- تعبئة اسم العميل تلقائياً (مضاف برمجياً وبأمان تام) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        const urlParams = new URLSearchParams(window.location.search);
        let clientName = urlParams.get('client') || urlParams.get('customer') || localStorage.getItem('active_invoice_customer');
        
        if(clientName) {
            clientName = decodeURIComponent(clientName).trim();
            var selects = document.querySelectorAll('select, input');
            selects.forEach(function(el) {
                if(el.tagName === 'SELECT') {
                    for(var i = 0; i < el.options.length; i++) {
                        if(el.options[i].text.includes(clientName)) {
                            el.selectedIndex = i;
                            el.dispatchEvent(new Event('change'));
                            if(typeof $ !== 'undefined' && $(el).hasClass('select2')) {
                                $(el).trigger('change');
                            }
                            break;
                        }
                    }
                } else if(el.type === 'text' || !el.type) {
                    if(el.value.includes('عميل نقدي') || (el.placeholder && el.placeholder.includes('العميل'))) {
                        el.value = clientName;
                        el.dispatchEvent(new Event('input'));
                    }
                }
            });
            localStorage.removeItem('active_invoice_customer');
        }
    }, 500);
});
</script>
</body>
</html>
