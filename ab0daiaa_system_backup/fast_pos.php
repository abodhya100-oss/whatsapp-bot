<?php
// fast_pos.php - أسرع كاشير في العالم (الإصدار الفائق المطور) - V-MAX PRO
require_once 'auth.php';
$db = new SQLite3(__DIR__ . '/vmax.db');

$msg = "";
$last_sold = "";
if (isset($_POST['fast_sale'])) {
    $p_id = intval($_POST['product_id']);
    $p_price = floatval($_POST['price']);
    $p_name = $_POST['name'];
    $date = date('Y-m-d H:i:s');

    // تسجيل الفاتورة فوراً
    $stmt = $db->prepare("INSERT INTO invoices (customer_id, total, discount, grand_total, date) VALUES (0, :t, 0, :t, :dt)");
    $stmt->bindValue(':t', $p_price);
    $stmt->bindValue(':dt', $date);
    $stmt->execute();

    // خصم الكمية من المخزون لحظياً
    $db->exec("UPDATE products SET qty = qty - 1 WHERE id = $p_id AND qty > 0");
    
    $last_sold = $p_name . " (" . $p_price . " ر.ي)";
    $msg = "تم بيع: " . $last_sold;
}

// جلب إجمالي مبيعات اليوم لجلسة الكاشير الحالية
$today = date('Y-m-d');
$session_sales = @$db->querySingle("SELECT SUM(grand_total) FROM invoices WHERE date LIKE '$today%'") ?: 0;
$session_count = @$db->querySingle("SELECT COUNT(*) FROM invoices WHERE date LIKE '$today%'") ?: 0;

// جلب المنتجات المتاحة
$products = [];
$res = $db->query("SELECT * FROM products WHERE qty > 0 ORDER BY name ASC");
while($row = $res->fetchArray(SQLITE3_ASSOC)) { $products[] = $row; }

$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><meta name='mobile-web-app-capable' content='yes'>
<meta name='apple-mobile-web-app-capable' content='yes'>

    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>أسرع كاشير في العالم - V-MAX PRO</title>
    <?php if(file_exists('global_style.php')) include 'global_style.php'; ?>
    <style>
        .pos-header-bar {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(6, 182, 212, 0.15));
            border: 1px solid var(--emerald-glow);
            border-radius: 16px;
            padding: 12px 16px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .fast-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 15px; }
        .fast-btn-card {
            background: var(--surface-glass);
            border: 1px solid var(--border-glass);
            border-radius: 16px;
            padding: 16px 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.1s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.4);
            position: relative;
            overflow: hidden;
        }
        .fast-btn-card:active {
            transform: scale(0.93);
            background: rgba(16, 185, 129, 0.3);
            border-color: var(--emerald-glow);
        }
        .search-box {
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 12px;
            border-radius: 14px;
            border: 1px solid var(--emerald-glow);
            background: rgba(13, 22, 38, 0.9);
            color: #fff;
            font-size: 13px;
            outline: none;
            box-shadow: 0 0 15px rgba(16, 185, 129, 0.2);
        }
    </style>
</head>
<body>
<div class="lux-wrapper">
    
    <!-- شريط ملخص الجلسة الحي -->
    <div class="pos-header-bar">
        <div>
            <div style="font-size:10px; color:var(--text-muted);">مبيعات اليوم الحية</div>
            <div style="font-size:15px; font-weight:bold; color:var(--emerald-glow);"><?php echo number_format($session_sales, 2); ?> ر.ي</div>
        </div>
        <div style="text-align:left;">
            <div style="font-size:10px; color:var(--text-muted);">الفواتير المصدرة</div>
            <div style="font-size:15px; font-weight:bold; color:var(--cyan-glow);"><?php echo $session_count; ?> فواتير</div>
        </div>
    </div>

    <?php if($msg): ?>
        <div id="alertMsg" style="background:rgba(16,185,129,0.25); border:1px solid var(--emerald-glow); color:var(--emerald-glow); padding:10px 14px; border-radius:12px; margin-bottom:12px; font-size:12px; font-weight:bold; text-align:center; animation: fadeIn 0.3s;">
            ⚡ <?php echo $msg; ?>
        </div>
    <?php endif; ?>

    <!-- خانة البحث الفوري الحي -->
    <input type="text" id="liveSearch" class="search-box" placeholder="🔍 ابحث عن أي صنف بالاسم لتصفية الأزرار فوراً..." onkeyup="filterProducts()">

    <!-- شبكة الأزرار الفائقة السرعة -->
    <div class="fast-grid" id="productGrid">
        <?php if(empty($products)): ?>
            <p style="grid-column: span 2; text-align:center; color:var(--text-muted); font-size:12px;">لا توجد أصناف متاحة بالمخزون حالياً.</p>
        <?php else: ?>
            <?php foreach($products as $p): ?>
                <form method="POST" style="margin:0;">
                    <input type="hidden" name="fast_sale" value="1">
                    <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                    <input type="hidden" name="price" value="<?php echo $p['price']; ?>">
                    <input type="hidden" name="name" value="<?php echo htmlspecialchars($p['name']); ?>">
                    
                    <button type="submit" class="fast-btn-card product-item" data-name="<?php echo strtolower(htmlspecialchars($p['name'])); ?>" style="width:100%; border:none; color:inherit; font-family:inherit;">
                        <div style="font-size:13px; font-weight:bold; color:#fff; margin-bottom:6px;"><?php echo htmlspecialchars($p['name']); ?></div>
                        <div style="font-size:14px; font-weight:bold; color:var(--emerald-glow);"><?php echo $p['price']; ?> ر.ي</div>
                        <div style="font-size:9.5px; color:var(--text-muted); margin-top:4px;">المخزون: <span style="color:var(--cyan-glow); font-weight:bold;"><?php echo $p['qty']; ?></span></div>
                    </button>
                </form>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <a href="index.php" class="lux-btn" style="background:#1e293b; color:var(--cyan-glow); border:1px solid var(--border-glass); text-decoration:none; text-align:center;">🏠 العودة للرئيسية الملكية</a>
</div>

<script>
// محرك التصفية والبحث الفوري بدون إعادة تحميل
function filterProducts() {
    let input = document.getElementById('liveSearch').value.toLowerCase();
    let cards = document.getElementsByClassName('product-item');
    
    for (let i = 0; i < cards.length; i++) {
        let name = cards[i].getAttribute('data-name');
        if (name.includes(input)) {
            cards[i].parentElement.style.display = "";
        } else {
            cards[i].parentElement.style.display = "none";
        }
    }
}
</script>

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
