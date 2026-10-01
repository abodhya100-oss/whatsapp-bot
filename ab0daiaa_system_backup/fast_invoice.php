<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if(session_status()===PHP_SESSION_NONE){session_start();} if(!isset($_SESSION["user_role"])){header("Location: login.php");exit;}
$db = new SQLite3('vmax.db');

$invoice_number = "INV-" . rand(100050, 999999);

// إضافة صنف للفاتورة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_formal_item'])) {
    $prod_id = intval($_POST['product_id'] ?? 0);
    $qty = floatval($_POST['qty'] ?? 1);
    if ($prod_id > 0 && $qty > 0) {
        if (!isset($_SESSION['formal_cart'])) { $_SESSION['formal_cart'] = []; }
        $stmt = $db->prepare("SELECT * FROM products WHERE id = :id");
        $stmt->bindValue(':id', $prod_id, SQLITE3_INTEGER);
        $res = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
        if ($res) {
            $price = isset($res['sell_price']) ? $res['sell_price'] : 0;
            if (isset($_SESSION['formal_cart'][$prod_id])) {
                $_SESSION['formal_cart'][$prod_id]['qty'] += $qty;
            } else {
                $_SESSION['formal_cart'][$prod_id] = [
                    'id' => $res['id'],
                    'name' => $res['name'],
                    'price' => $price,
                    'qty' => $qty
                ];
            }
        }
    }
    header("Location: fast_invoice.php");
    exit;
}

// حذف صنف من الفاتورة
if (isset($_GET['remove_id'])) {
    $rem_id = intval($_GET['remove_id']);
    unset($_SESSION['formal_cart'][$rem_id]);
    header("Location: fast_invoice.php");
    exit;
}

if (isset($_GET['clear_all'])) {
    unset($_SESSION['formal_cart']);
    header("Location: fast_invoice.php");
    exit;
}

// حفظ وترحيل الفاتورة الرسمية نهائياً مع الخصم والملاحظات
$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalize_formal_invoice'])) {
    $cust_id = intval($_POST['customer_id'] ?? 0);
    $pay_type = $_POST['pay_type'] ?? 'نقدي';
    $discount = floatval($_POST['discount'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    
    if (isset($_SESSION['formal_cart']) && !empty($_SESSION['formal_cart'])) {
        $subtotal_sum = 0;
        foreach($_SESSION['formal_cart'] as $item) { $subtotal_sum += $item['price'] * $item['qty']; }
        $final_total = max(0, $subtotal_sum - $discount);
        $date = date('Y-m-d H:i:s');
        
        $db->exec("CREATE TABLE IF NOT EXISTS invoices (id INTEGER PRIMARY KEY AUTOINCREMENT, customer_id INTEGER, total REAL, date TEXT, type TEXT, notes TEXT)");
        $stmt = $db->prepare("INSERT INTO invoices (customer_id, total, date, type, notes) VALUES (:cid, :tot, :dt, :tp, :nt)");
        $stmt->bindValue(':cid', $cust_id, SQLITE3_INTEGER);
        $stmt->bindValue(':tot', $final_total, SQLITE3_FLOAT);
        $stmt->bindValue(':dt', $date, SQLITE3_TEXT);
        $stmt->bindValue(':tp', 'فاتورة رسمية - ' . $pay_type, SQLITE3_TEXT);
        $stmt->bindValue(':nt', $notes, SQLITE3_TEXT);
        $stmt->execute();
        
        if ($cust_id > 0) { 
            $db->exec("UPDATE customers SET balance = balance + $final_total WHERE id = $cust_id"); 
        }
        foreach($_SESSION['formal_cart'] as $item) {
            $pname = $item['name'];
            $pq = $item['qty'];
            $db->exec("UPDATE products SET stock = stock - $pq WHERE name = '$pname'");
        }
        unset($_SESSION['formal_cart']);
        $msg = "✅ تم اعتماد وإصدار الفاتورة الرسمية بنجاح وتحديث حساب العميل والمخزون!";
    }
}

$products = [];
$res_p = $db->query("SELECT * FROM products ORDER BY name ASC");
while($p = $res_p->fetchArray(SQLITE3_ASSOC)) { $products[] = $p; }

$customers = [];
$res_c = $db->query("SELECT * FROM customers ORDER BY name ASC");
while($c = $res_c->fetchArray(SQLITE3_ASSOC)) { $customers[] = $c; }
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <link rel="manifest" href="manifest.json">
    <meta name='mobile-web-app-capable' content='yes'>
    <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js");}</script>
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>فاتورة المبيعات الرسمية - نظام أبو ضياء العالمي</title>
    <style>
        body {  font-family: Tahoma, sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 10px; direction: rtl;  padding-bottom: 75px; }
        .header { text-align: center; font-size: 16px; font-weight: bold; color: #38bdf8; margin-bottom: 3px; }
        .sub-header { text-align: center; font-size: 10px; color: #94a3b8; margin-bottom: 10px; }
        .card-box { background: #1e293b; border: 1px solid #334155; border-radius: 8px; padding: 12px; margin-bottom: 10px; }
        .label { font-size: 11px; color: #cbd5e1; margin-bottom: 4px; font-weight: bold; }
        .input-field, select, textarea { width: 100%; padding: 7px; background: #0f172a; color: #fff; border: 1px solid #475569; border-radius: 6px; font-size: 12px; box-sizing: border-box; }
        
        .row { display: flex; gap: 8px; margin-bottom: 8px; }
        .col { flex: 1; }

        table { width: 100%; border-collapse: collapse; margin-top: 6px; margin-bottom: 8px; }
        th, td { border: 1px solid #334155; padding: 6px; text-align: center; font-size: 11px; }
        th { background: #2563eb; color: #fff; }
        td { background: #1e293b; color: #f8fafc; }
        
        .btn-add { background: #2563eb; color: #fff; border: none; padding: 7px 12px; border-radius: 6px; font-weight: bold; font-size: 11px; cursor: pointer; margin-top: 6px; }
        .btn-add:hover { background: #1d4ed8; }
        .btn-delete { color: #f87171; text-decoration: none; font-weight: bold; font-size: 10px; background: #450a0a; padding: 2px 6px; border-radius: 4px; }
        .btn-save { background: #22c55e; color: #fff; border: none; width: 100%; padding: 10px; border-radius: 6px; font-weight: bold; font-size: 13px; cursor: pointer; margin-top: 8px; }
        .btn-save:hover { background: #16a34a; }
        .clear-cart { display: inline-block; color: #f87171; text-decoration: none; font-size: 10px; margin-top: 4px; font-weight: bold; }
        
        
        .nav-item { text-decoration: none; color: #94a3b8; font-size: 10px; text-align: center; }
        .nav-item.active { color: #fbbf24; font-weight: bold; }
    </style>

        <style>
        body { padding-bottom: 75px !important; }
        .nav-bar { 
            position: fixed; 
            bottom: 12px; 
            left: 12px; 
            right: 12px; 
            z-index: 9999; 
            display: flex; 
            justify-content: space-around; 
            background: rgba(30, 41, 59, 0.92); 
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            padding: 10px 5px; 
            border-radius: 14px; 
            box-shadow: 0 8px 25px rgba(0,0,0,0.5); 
            border: 1px solid rgba(51, 65, 85, 0.8); 
        }
        .nav-item { text-decoration: none; color: #94a3b8; font-size: 11px; font-weight: bold; text-align: center; transition: 0.2s; }
        .nav-item:hover, .nav-item.active { color: #38bdf8; transform: translateY(-2px); }
        </style>
</head>
<body>

    <div style="background: #1e293b; padding: 8px 12px; border-radius: 6px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; border: 1px solid #334155;">
        <a href="index.php" style="color: #38bdf8; text-decoration: none; font-size: 12px; font-weight: bold;">⬅️ رجوع للرئيسية</a>
        <span style="color: #fbbf24; font-size: 12px; font-weight: bold;">📄 الفاتورة الرسمية</span>
    </div>
    <div class="header">📄 فاتورة المبيعات الرسمية المفصلة</div>
    <div class="sub-header">رقم المستند: <?php echo $invoice_number; ?> | التاريخ: <?php echo date('Y-m-d H:i'); ?></div>

    <?php if($msg): ?>
        <div style="background: #14532d; color: #4ade80; padding: 8px; border-radius: 6px; text-align: center; margin-bottom: 8px; font-size: 11px; font-weight: bold;"><?php echo $msg; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="card-box">
            <div class="row">
                <div class="col">
                    <div class="label">👥 العميل المعتمد:</div>
                    <select name="customer_id" class="input-field">
                        <option value="0">-- العميل النقدي / العام --</option>
                        <?php foreach($customers as $c): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col">
                    <div class="label">💳 طريقة الدفع:</div>
                    <select name="pay_type" class="input-field">
                        <option value="نقدي">نقدي (كاش)</option>
                        <option value="أجل">أجل (على الحساب)</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card-box">
            <div class="label">📦 إدراج بند الصنف والكمية:</div>
            <div class="row">
                <div style="flex: 3;">
                    <select name="product_id" class="input-field">
                        <option value="">-- اختر الصنف من القائمة --</option>
                        <?php foreach($products as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?> (السعر: <?php echo isset($p['sell_price']) ? $p['sell_price'] : 0; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="flex: 1;">
                    <input type="number" name="qty" value="1" min="1" step="any" class="input-field" placeholder="الكمية">
                </div>
            </div>
            <button type="submit" name="add_formal_item" class="btn-add">➕ إضافة البند إلى الفاتورة</button>
        </div>

        <div class="card-box">
            <div class="label">📋 بنود الفاتورة الرسمية:</div>
            <table>
                <tr>
                    <th>الصنف</th>
                    <th>السعر</th>
                    <th>كمية</th>
                    <th>المجموع</th>
                    <th>إجراء</th>
                </tr>
                <?php 
                $subtotal_sum = 0;
                if(isset($_SESSION['formal_cart']) && !empty($_SESSION['formal_cart'])):
                    foreach($_SESSION['formal_cart'] as $id => $item):
                        $subtotal = $item['price'] * $item['qty'];
                        $subtotal_sum += $subtotal;
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                    <td><?php echo $item['price']; ?></td>
                    <td><?php echo $item['qty']; ?></td>
                    <td style="color: #4ade80; font-weight: bold;"><?php echo $subtotal; ?></td>
                    <td><a href="fast_invoice.php?remove_id=<?php echo $id; ?>" class="btn-delete">حذف</a></td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="5" style="color: #94a3b8; padding: 12px;">لا توجد بنود مضافة في الفاتورة بعد...</td></tr>
                <?php endif; ?>
            </table>
            
            <?php if(isset($_SESSION['formal_cart']) && !empty($_SESSION['formal_cart'])): ?>
                <a href="fast_invoice.php?clear_all=1" class="clear-cart">🗑️ تفريغ كافة بنود الفاتورة</a>
            <?php endif; ?>

            <div class="row" style="margin-top: 10px;">
                <div class="col">
                    <div class="label">🎁 خصم إضافي (ر.ي):</div>
                    <input type="number" name="discount" value="0" min="0" step="any" class="input-field">
                </div>
                <div class="col">
                    <div class="label">📝 ملاحظات الفاتورة:</div>
                    <input type="text" name="notes" class="input-field" placeholder="ملاحظات التسليم أو السداد...">
                </div>
            </div>

            <?php 
            $disc = isset($_POST['discount']) ? floatval($_POST['discount']) : 0;
            $final_total = max(0, $subtotal_sum - $disc);
            ?>
            <div style="margin-top: 8px; font-weight: bold; font-size: 14px; color: #4ade80; text-align: left;">
                الإجمالي النهائي المستحق: <span id="final-total"><?php echo $final_total; ?></span> ر.ي
            </div>

            <button type="submit" name="finalize_formal_invoice" class="btn-save">💾 اعتماد وترحيل الفاتورة الرسمية نهائياً</button>
        </div>
    </form>

<?php include "nav_menu.php"; ?>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
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

<!-- الحقن الدقيق لتعبئة العميل في fast_invoice (مضاف برمجياً وبأمان) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        const urlParams = new URLSearchParams(window.location.search);
        let clientName = urlParams.get('client') || urlParams.get('customer') || localStorage.getItem('active_invoice_customer');
        
        if(clientName) {
            clientName = decodeURIComponent(clientName).trim();
            
            // استهداف قائمة العميل الرسمية في الفاتورة name="customer_id" مباشرة
            var custSelect = document.querySelector('select[name="customer_id"]');
            if(custSelect) {
                for(var i = 0; i < custSelect.options.length; i++) {
                    if(custSelect.options[i].text.includes(clientName)) {
                        custSelect.selectedIndex = i;
                        custSelect.dispatchEvent(new Event('change'));
                        break;
                    }
                }
            }
            
            localStorage.removeItem('active_invoice_customer');
        }
    }, 400);
});
</script>
</body>
</html>

