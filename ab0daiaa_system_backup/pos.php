<?php
// pos.php - الكاشير السريع (الخصم الذكي للمخزون بناءً على هرم الوحدات والعملات) - نظام أبوضياء
require_once 'auth.php';                      
$db_file = __DIR__ . '/vmax.db';
$db = new SQLite3($db_file);                  

$db->exec("CREATE TABLE IF NOT EXISTS customers (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, phone TEXT, address TEXT, notes TEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
@$db->exec("ALTER TABLE customers ADD COLUMN phone TEXT");                                  
@$db->exec("ALTER TABLE customers ADD COLUMN address TEXT");                                

$check_cash_cust = $db->querySingle("SELECT id FROM customers WHERE name = 'عميل نقدي' LIMIT 1", true);
if (empty($check_cash_cust['id'])) {
    $db->exec("INSERT INTO customers (name, phone, address) VALUES ('عميل نقدي', '000000000', 'عام')");
}

$db->exec("CREATE TABLE IF NOT EXISTS products (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, unit TEXT DEFAULT 'كرتون', price REAL NOT NULL, qty REAL DEFAULT 0, image TEXT)");
$db->exec("CREATE TABLE IF NOT EXISTS invoices (id INTEGER PRIMARY KEY AUTOINCREMENT, customer_id INTEGER, total REAL, discount REAL DEFAULT 0, tax REAL DEFAULT 0, payment_type TEXT DEFAULT 'cash', currency TEXT DEFAULT 'YER', exchange_rate REAL DEFAULT 1.0, created_date DATETIME DEFAULT CURRENT_TIMESTAMP)");

@$db->exec("ALTER TABLE invoices ADD COLUMN currency TEXT DEFAULT 'YER'");
@$db->exec("ALTER TABLE invoices ADD COLUMN exchange_rate REAL DEFAULT 1.0");

// التأكد من وجود أعمدة الهرم في جدول المنتجات
@$db->exec("ALTER TABLE products ADD COLUMN currency TEXT DEFAULT 'YER'");
@$db->exec("ALTER TABLE products ADD COLUMN unit_major TEXT");        
@$db->exec("ALTER TABLE products ADD COLUMN price_major REAL DEFAULT 0"); 
@$db->exec("ALTER TABLE products ADD COLUMN unit_mid TEXT");          
@$db->exec("ALTER TABLE products ADD COLUMN price_mid REAL DEFAULT 0");   
@$db->exec("ALTER TABLE products ADD COLUMN unit_minor TEXT");        
@$db->exec("ALTER TABLE products ADD COLUMN price_minor REAL DEFAULT 0"); 
@$db->exec("ALTER TABLE products ADD COLUMN rate_mid REAL DEFAULT 1");    
@$db->exec("ALTER TABLE products ADD COLUMN rate_major REAL DEFAULT 1");  

$db->exec("CREATE TABLE IF NOT EXISTS invoice_items (id INTEGER PRIMARY KEY AUTOINCREMENT, invoice_id INTEGER, product_name TEXT, unit TEXT, qty REAL, free_qty REAL DEFAULT 0, price REAL, total REAL)");

if (isset($_POST['action']) && $_POST['action'] === 'save_invoice') {
    $customer_name_input = trim($_POST['customer_name'] ?? '');
    $payment_type = trim($_POST['payment_type'] ?? 'cash');
    $currency = trim($_POST['currency'] ?? 'YER');
    $items_json = $_POST['items_data'] ?? '[]';
    $items = json_decode($items_json, true);
    $customer_id = 0;

    if ($payment_type === 'credit' && empty($customer_name_input)) {
        echo "<script>alert('❌ البيع الآجل يتطلب كتابة اسم العميل!'); window.history.back();</script>"; exit;
    }

    if (empty($customer_name_input)) {
        $cash_cust = $db->querySingle("SELECT id FROM customers WHERE name = 'عميل نقدي' LIMIT 1", true);
        $customer_id = $cash_cust['id'] ?? 1;
    } else {
        $stmt_c = $db->prepare("SELECT id FROM customers WHERE name = :name LIMIT 1");
        $stmt_c->bindValue(':name', $customer_name_input, SQLITE3_TEXT);
        $res_c = $stmt_c->execute()->fetchArray(SQLITE3_ASSOC);
        if ($res_c) { $customer_id = $res_c['id']; }
        else {
            $stmt_ins = $db->prepare("INSERT INTO customers (name) VALUES (:name)");
            $stmt_ins->bindValue(':name', $customer_name_input, SQLITE3_TEXT);
            $stmt_ins->execute();
            $customer_id = $db->lastInsertRowID();
        }
    }

    if (!empty($items) && $customer_id > 0) {
        $grand_total = 0;
        foreach ($items as $it) { $grand_total += floatval($it['qty']) * floatval($it['price']); }

        $stmt = $db->prepare("INSERT INTO invoices (customer_id, total, payment_type, currency, created_date) VALUES (:cid, :total, :ptype, :curr, datetime('now', 'localtime'))");
        $stmt->bindValue(':cid', $customer_id, SQLITE3_INTEGER);
        $stmt->bindValue(':total', $grand_total, SQLITE3_FLOAT);
        $stmt->bindValue(':ptype', $payment_type, SQLITE3_TEXT);
        $stmt->bindValue(':curr', $currency, SQLITE3_TEXT);
        $stmt->execute();
        $invoice_id = $db->lastInsertRowID();

        foreach ($items as $it) {
            $qty = floatval($it['qty']);
            $free = floatval($it['free']);
            $unit_sold = $it['unit'];
            $p_name = $it['name'];

            $istmt = $db->prepare("INSERT INTO invoice_items (invoice_id, product_name, unit, qty, free_qty, price, total) VALUES (:iid, :name, :unit, :qty, :free, :price, :total)");
            $istmt->bindValue(':iid', $invoice_id, SQLITE3_INTEGER);
            $istmt->bindValue(':name', $p_name, SQLITE3_TEXT);
            $istmt->bindValue(':unit', $unit_sold, SQLITE3_TEXT);
            $istmt->bindValue(':qty', $qty, SQLITE3_FLOAT);
            $istmt->bindValue(':free', $free, SQLITE3_FLOAT);
            $istmt->bindValue(':price', floatval($it['price']), SQLITE3_FLOAT);
            $istmt->bindValue(':total', $qty * floatval($it['price']), SQLITE3_FLOAT);
            $istmt->execute();

            // حساب معامل الخصم الدقيق بناءً على الوحدة المباعة
            $prod_info = $db->querySingle("SELECT unit_major, rate_major, unit_mid, rate_mid, unit_minor FROM products WHERE name = '$p_name'", true);
            
            $multiplier = 1;
            if ($prod_info) {
                if ($unit_sold === $prod_info['unit_major']) {
                    $multiplier = floatval($prod_info['rate_major']) > 0 ? floatval($prod_info['rate_major']) : 1;
                } elseif ($unit_sold === $prod_info['unit_mid']) {
                    $multiplier = floatval($prod_info['rate_mid']) > 0 ? floatval($prod_info['rate_mid']) : 1;
                }
            }

            $total_deduct = ($qty + $free) * $multiplier;

            $upd = $db->prepare("UPDATE products SET qty = qty - :deduct WHERE name = :pname");
            $upd->bindValue(':deduct', $total_deduct, SQLITE3_FLOAT);
            $upd->bindValue(':pname', $p_name, SQLITE3_TEXT);
            $upd->execute();
        }
        echo "<script>localStorage.setItem('last_customer', " . json_encode($customer_name_input) . "); window.location.href='view_invoice.php?id=$invoice_id';</script>";
        exit;
    }
}

$customers = [];
$c_res = $db->query("SELECT * FROM customers ORDER BY name ASC");
while ($row = $c_res->fetchArray(SQLITE3_ASSOC)) { $customers[] = $row; }

$products = [];
$p_res = $db->query("SELECT * FROM products ORDER BY name ASC");
while ($row = $p_res->fetchArray(SQLITE3_ASSOC)) { $products[] = $row; }
$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no" class="notranslate">
<head>
<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'>
    <meta charset="UTF-8">
    <title>الكاشير - نظام أبوضياء</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Tahoma', sans-serif; background: #0f172a; margin: 0; padding: 8px; padding-bottom: 75px; color: #1e293b; }
        .main-container { max-width: 650px; margin: 0 auto; background: #fff; border-radius: 12px; padding: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #fbbf24; padding-bottom: 8px; margin-bottom: 10px; background: #1d4ed8; color: #fff; padding: 10px 12px; border-radius: 8px; }
        .header h2 { margin: 0; font-size: 15px; }
        .section-box { background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px; margin-bottom: 10px; }
        .section-title { font-size: 12px; font-weight: bold; color: #1d4ed8; margin-bottom: 6px; display: flex; justify-content: space-between; align-items: center; }
        .form-control { width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 12px; background: #fff; outline: none; margin-bottom: 6px; }
        .btn { padding: 8px 12px; border: none; border-radius: 6px; font-weight: bold; font-size: 11px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 4px; }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-success { background: #16a34a; color: #fff; }
        .btn-danger { background: #dc2626; color: #fff; }
        .btn-warning { background: #d97706; color: #fff; }

        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(95px, 1fr)); gap: 6px; max-height: 160px; overflow-y: auto; padding: 4px; background: #fff; border: 1px solid #cbd5e1; border-radius: 6px; margin-bottom: 6px; }
        .product-card { background: linear-gradient(135deg, #1e293b, #334155); color: #fff; border-radius: 6px; padding: 5px; text-align: center; cursor: pointer; border: 1.5px solid #fbbf24; display: flex; flex-direction: column; align-items: center; justify-content: space-between; height: 95px; position: relative; }
        .p-thumb-placeholder { width: 32px; height: 32px; border-radius: 50%; background: #2563eb; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: bold; border: 1px solid #fbbf24; margin-bottom: 2px; }
        .p-card-name { font-size: 10px; font-weight: bold; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; width: 100%; }
        .p-card-price { font-size: 9.5px; color: #fbbf24; }

        .qty-badge { position: absolute; top: 2px; left: 2px; font-size: 8px; font-weight: bold; padding: 2px 4px; border-radius: 4px; background: rgba(0,0,0,0.6); }
        .qty-badge.low { color: #fca5a5; }
        .qty-badge.good { color: #bbf7d0; }

        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th { background: #1e293b; color: #fff; padding: 6px 4px; text-align: center; font-size: 10px; }
        td { padding: 6px 4px; text-align: center; border-bottom: 1px solid #e2e8f0; font-weight: bold; }
        .qty-input { width: 65px; padding: 6px; text-align: center; font-weight: bold; font-size: 12px; border: 1px solid #cbd5e1; border-radius: 4px; outline: none; background: #fff; }
        
        .total-display { background: #1d4ed8; color: #fff; border-radius: 8px; padding: 12px; display: flex; flex-direction: column; align-items: stretch; gap: 8px; margin-bottom: 10px; }
        .total-row { display: flex; justify-content: space-between; align-items: center; font-size: 16px; font-weight: bold; }

        .bottom-nav { position: fixed; bottom: 0; left: 0; width: 100%; background: #1e293b; border-top: 2px solid #fbbf24; display: flex; justify-content: space-around; padding: 8px 0; z-index: 9999; box-shadow: 0 -5px 15px rgba(0,0,0,0.3); }
        .nav-item { color: #94a3b8; text-decoration: none; font-size: 10px; font-weight: bold; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 2px; flex: 1; }
        .nav-item span { font-size: 16px; }
        .nav-item.active, .nav-item:hover { color: #fbbf24; }
        
        .unit-dropdown { width: 100%; padding: 2px; font-size: 10px; font-weight: bold; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 4px; margin-top: 3px; outline: none; }
    </style>
</head>
<body>

<div class="main-container">
    <div class="header">
        <h2>🛒 الكاشير السريع</h2>
        <div><a href="history.php" class="btn btn-warning" style="padding: 5px 8px; font-size: 10px;">🗂️ السجل</a></div>
    </div>

    <div class="section-box">
        <div class="section-title">
            <span>👤 اسم العميل</span>
        </div>
        <input type="text" id="customer_name" name="customer_name" class="form-control" list="customers_list" placeholder="اكتب اسم البقالة..." autocomplete="off" oninput="saveCustomerMemory(this.value)">
        <datalist id="customers_list">
            <?php foreach($customers as $c): ?>
                <?php if($c['name'] === 'عميل نقدي') continue; ?>
                <option value="<?php echo htmlspecialchars($c['name']); ?>">
            <?php endforeach; ?>
        </datalist>
    </div>

    <div class="section-box">
        <div class="section-title"><span>📦 اختر صنفاً</span></div>
        <div class="products-grid">
            <?php foreach($products as $pr): 
                $display_price = $pr['price_major'] > 0 ? $pr['price_major'] : $pr['price'];
            ?>
                <div class="product-card" onclick="quickAddProduct('<?php echo htmlspecialchars($pr['name'], ENT_QUOTES); ?>')">
                    <div class="qty-badge <?php echo $pr['qty'] <= 10 ? 'low' : 'good'; ?>">م: <?php echo floatval($pr['qty']); ?></div>
                    <div class="p-thumb-placeholder"><?php echo mb_substr($pr['name'], 0, 1); ?></div>
                    <div class="p-card-name"><?php echo htmlspecialchars($pr['name']); ?></div>
                    <div class="p-card-price"><?php echo number_format($display_price, 0); ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 4px; margin-top: 6px;">
            <input type="text" id="p_name" class="form-control" placeholder="الصنف" autocomplete="off" style="margin:0;">
            <input type="text" inputmode="decimal" id="p_qty" class="form-control" placeholder="الكمية" autocomplete="off" style="margin:0;" onfocus="this.select()">
            <input type="text" inputmode="decimal" id="p_free" class="form-control" placeholder="بونص" value="0" autocomplete="off" style="margin:0;" onfocus="this.select()">
            <input type="text" inputmode="decimal" id="p_price" class="form-control" placeholder="السعر" value="0" autocomplete="off" style="margin:0;" onfocus="this.select()">
            <button type="button" class="btn btn-primary" onclick="addItem()">➕</button>
        </div>
    </div>

    <div class="section-box">
        <div class="section-title">📋 تفاصيل الفاتورة</div>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>الصنف</th>
                        <th>الكمية</th>
                        <th>بونص</th>
                        <th>السعر</th>
                        <th>المجموع</th>
                        <th>حذف</th>
                    </tr>
                </thead>
                <tbody id="cartTableBody">
                    <tr><td colspan="6" style="color:#94a3b8; padding:10px;">الفاتورة فارغة</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <form method="POST" id="invoiceForm">
        <input type="hidden" name="action" value="save_invoice">
        <input type="hidden" name="customer_name" id="form_customer_name">
        <input type="hidden" name="items_data" id="form_items_data">
        <input type="hidden" name="payment_type" id="form_payment_type" value="cash">

        <div class="total-display">
            <div class="total-row">
                <span>الإجمالي:</span>
                <span id="grandTotalText" style="font-size: 20px;">0</span>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px;">
            <button type="button" class="btn btn-success" style="padding: 12px; font-size: 13px;" onclick="submitInvoice('cash')">💵 حفظ كاش</button>
            <button type="button" class="btn btn-warning" style="padding: 12px; font-size: 13px;" onclick="submitInvoice('credit')">📜 حفظ آجل</button>
        </div>
    </form>
</div>

<div class="bottom-nav">
    <a href="pos.php" class="nav-item active"><span>🛒</span>الكاشير</a>
    <a href="inventory.php" class="nav-item"><span>📦</span>المستودع</a>
    <a href="debts.php" class="nav-item"><span>📒</span>الديون</a>
    <a href="daily_report.php" class="nav-item"><span>📊</span>اليومية</a>
    <a href="dashboard.php" class="nav-item"><span>⚙️</span>الرئيسية</a>
</div>

<script>
const catalogData = <?php echo json_encode($products, JSON_UNESCAPED_UNICODE); ?>;
let cart = [];

function saveCustomerMemory(name) { if (name.trim().length > 0) localStorage.setItem('temp_customer', name.trim()); }

function buildUnitsList(p) {
    let units = [];
    
    // الوحدة الكبرى
    let uMajor = p.unit_major || p.unit || 'كرتون';
    let pMajor = parseFloat(p.price_major) > 0 ? parseFloat(p.price_major) : parseFloat(p.price);
    units.push({ name: uMajor, price: pMajor });
    
    // الوحدة المتوسطة
    if(p.unit_mid && parseFloat(p.price_mid) > 0) {
        units.push({ name: p.unit_mid, price: parseFloat(p.price_mid) });
    }
    
    // الوحدة الصغرى
    if(p.unit_minor && parseFloat(p.price_minor) > 0) {
        units.push({ name: p.unit_minor, price: parseFloat(p.price_minor) });
    }
    
    return units;
}

function quickAddProduct(name) {
    let p = catalogData.find(item => item.name === name);
    if(!p) return;
    
    let availableUnits = buildUnitsList(p);
    let existing = cart.find(item => item.name === name && item.unit === availableUnits[0].name);
    
    if (existing) {
        let currentQty = (existing.qty === '' || isNaN(existing.qty)) ? 0 : parseFloat(existing.qty);
        existing.qty = currentQty + 1;
    } else {
        cart.push({ 
            name: name, 
            unit: availableUnits[0].name, 
            qty: '', 
            free: 0, 
            price: availableUnits[0].price,
            available_units: availableUnits
        });
    }
    renderCart();
}

function addItem() {
    let name = document.getElementById('p_name').value.trim();
    let qtyInput = document.getElementById('p_qty').value.trim();
    let qty = qtyInput === '' ? '' : parseFloat(qtyInput);
    let free = parseFloat(document.getElementById('p_free').value) || 0;
    let price = parseFloat(document.getElementById('p_price').value) || 0;
    if (!name) { alert('الرجاء إدخال الصنف!'); return; }
    
    let p = catalogData.find(item => item.name === name);
    let availableUnits = [];
    
    if(p) {
        availableUnits = buildUnitsList(p);
    } else {
        availableUnits = [{ name: 'كرتون', price: price }];
    }

    cart.push({ 
        name: name, 
        unit: availableUnits[0].name, 
        qty: qty, 
        free: free, 
        price: price > 0 ? price : availableUnits[0].price,
        available_units: availableUnits
    });
    
    document.getElementById('p_name').value = ''; document.getElementById('p_qty').value = '';
    document.getElementById('p_free').value = '0'; document.getElementById('p_price').value = '0';
    renderCart();
}

function updateItemUnit(index, unitName) {
    let item = cart[index];
    let selectedUnit = item.available_units.find(u => u.name === unitName);
    if (selectedUnit) {
        item.unit = selectedUnit.name;
        item.price = selectedUnit.price;
        renderCart(); 
    }
}

function updateQtyAndTotal(index, input) {
    let val = input.value;
    cart[index].qty = val.trim() === '' ? '' : parseFloat(val);
    renderCart();
}

function removeItem(index) { cart.splice(index, 1); renderCart(); }

function renderCart() {
    let tbody = document.getElementById('cartTableBody');

    if (cart.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="color:#94a3b8; padding:10px;">الفاتورة فارغة</td></tr>';
        document.getElementById('grandTotalText').innerText = '0';
        return;
    }
    
    let html = ''; let grandTotal = 0;
    
    cart.forEach((item, index) => {
        let qVal = item.qty === '' ? '' : item.qty;
        let lineTotal = (parseFloat(item.qty) || 0) * item.price;
        grandTotal += lineTotal;
        
        let unitOptions = '';
        if(item.available_units && item.available_units.length > 0) {
            unitOptions = item.available_units.map(u => 
                `<option value="${u.name}" ${item.unit === u.name ? 'selected' : ''}>${u.name}</option>`
            ).join('');
        } else {
            unitOptions = `<option value="${item.unit}" selected>${item.unit}</option>`;
        }

        html += `<tr>
            <td style="text-align:right;">
                <div style="font-size:11px; font-weight:bold;">${item.name}</div>
                <select class="unit-dropdown" onchange="updateItemUnit(${index}, this.value)">
                    ${unitOptions}
                </select>
            </td>
            <td><input type="text" inputmode="decimal" class="qty-input" value="${qVal}" autocomplete="off" onfocus="this.select()" oninput="updateQtyAndTotal(${index}, this)"></td>
            <td style="color:#d97706;">${item.free > 0 ? '+' + item.free : '0'}</td>
            <td>${item.price.toLocaleString()}</td>
            <td style="color:#16a34a;" class="line-total-cell">${lineTotal.toLocaleString()}</td>
            <td><button type="button" class="btn btn-danger" style="padding:2px 6px;" onclick="removeItem(${index})">❌</button></td>
        </tr>`;
    });
    
    tbody.innerHTML = html;
    document.getElementById('grandTotalText').innerText = grandTotal.toLocaleString();
}

function submitInvoice(ptype) {
    let custName = document.getElementById('customer_name').value.trim();
    if (ptype === 'credit' && !custName) { alert('❌ البيع الآجل يتطلب اسم العميل!'); return; }
    if (cart.length === 0) { alert('❌ الفاتورة فارغة!'); return; }
    
    for (let i=0; i<cart.length; i++) {
        if (cart[i].qty === '' || isNaN(cart[i].qty) || cart[i].qty <= 0) { alert('❌ أدخل كمية صحيحة لكل الأصناف!'); return; }
    }
    
    document.getElementById('form_customer_name').value = custName;
    document.getElementById('form_items_data').value = JSON.stringify(cart);
    document.getElementById('form_payment_type').value = ptype;
    
    document.getElementById('invoiceForm').submit();
}
</script>

<?php if(file_exists('nav_menu.php')) { include 'nav_menu.php'; } ?>
</body>
</html>
