<?php
require_once 'auth.php';
$db = new SQLite3('/data/data/com.termux/files/home/secure_dbs/vmax.db');

$c_cols =$db->query("PRAGMA table_info(invoices)");
$cols = []; while ($r =$c_cols->fetchArray(SQLITE3_ASSOC)) $cols[] =$r['name'];
if (!in_array('paid_amount', $cols))$db->exec("ALTER TABLE invoices ADD COLUMN paid_amount REAL DEFAULT 0");
if (!in_array('discount', $cols))$db->exec("ALTER TABLE invoices ADD COLUMN discount REAL DEFAULT 0");
if (!in_array('notes', $cols))$db->exec("ALTER TABLE invoices ADD COLUMN notes TEXT");
if (!in_array('payment_method', $cols))$db->exec("ALTER TABLE invoices ADD COLUMN payment_method TEXT DEFAULT 'نقدي'");
if (!in_array('is_quotation', $cols))$db->exec("ALTER TABLE invoices ADD COLUMN is_quotation INTEGER DEFAULT 0");
if (!in_array('rep_name', $cols))$db->exec("ALTER TABLE invoices ADD COLUMN rep_name TEXT");

$db->exec("CREATE TABLE IF NOT EXISTS invoice_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    invoice_id INTEGER,
    product_id INTEGER,
    qty REAL,
    price REAL,
    unit TEXT
)");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = intval($_POST['customer_id'] ?? 0);
    $grand_total = floatval($_POST['grand_total'] ?? 0);
    $discount = floatval($_POST['discount'] ?? 0);
    $paid_amount = floatval($_POST['paid_amount'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    $rep_name = trim($_POST['rep_name'] ?? 'أبو ضياء');
    $is_quotation = isset($_POST['is_quotation']) ? 1 : 0;
    $invoice_date = date('Y-m-d');$rem = max(0, $grand_total -$paid_amount);
    $pmethod = ($rem > 0) ? 'آجل' : 'نقدي';

    $stmt =$db->prepare("INSERT INTO invoices (customer_id, grand_total, discount, paid_amount, payment_method, invoice_date, notes, is_quotation, rep_name) VALUES (:cid, :tot, :disc, :paid, :pm, :idt, :nt, :isq, :rep)");
    $stmt->bindValue(':cid', $customer_id, SQLITE3_INTEGER);$stmt->bindValue(':tot', $grand_total, SQLITE3_FLOAT);$stmt->bindValue(':disc', $discount, SQLITE3_FLOAT);$stmt->bindValue(':paid', $paid_amount, SQLITE3_FLOAT);$stmt->bindValue(':pm', $pmethod, SQLITE3_TEXT);$stmt->bindValue(':idt', $invoice_date, SQLITE3_TEXT);$stmt->bindValue(':nt', $notes, SQLITE3_TEXT);
    $stmt->bindValue(':isq',$is_quotation, SQLITE3_INTEGER);
    $stmt->bindValue(':rep',$rep_name, SQLITE3_TEXT);
    
    if ($stmt->execute()) {
        $new_invoice_id =$db->lastInsertRowID();

        if (!empty($_POST['items']) && is_array($_POST['items'])) {
            $items =$_POST['items'];
            $qtys =$_POST['quantities'] ?? [];
            $prices =$_POST['prices'] ?? [];
            $units =$_POST['units'] ?? [];

            for ($i = 0; $i < count($items);$i++) {
                $pid = intval($items[$i]);$q = floatval($qtys[$i] ?? 1);
                $p = floatval($prices[$i] ?? 0);$u = trim($units[$i] ?? 'كارتون');
                
                if ($pid > 0 &&$q > 0) {
                    $istmt =$db->prepare("INSERT INTO invoice_items (invoice_id, product_id, qty, price, unit) VALUES (:iid, :pid, :q, :p, :u)");
                    $istmt->bindValue(':iid', $new_invoice_id, SQLITE3_INTEGER);$istmt->bindValue(':pid', $pid, SQLITE3_INTEGER);$istmt->bindValue(':q', $q, SQLITE3_FLOAT);$istmt->bindValue(':p', $p, SQLITE3_FLOAT);$istmt->bindValue(':u', $u, SQLITE3_TEXT);$istmt->execute();

                    if ($is_quotation == 0) {$db->exec("UPDATE products SET stock = stock - {$q} WHERE id = {$pid}");
                    }
                }
            }
        }

        if ($is_quotation == 0 &&$customer_id > 0 && $rem > 0) {$db->exec("UPDATE customers SET balance = balance + {$rem} WHERE id = {$customer_id}");
        }

        $db->close();
        header("Location: invoice_whatsapp.php?id=" . $new_invoice_id);
        exit;
    }
}

$customers = [];
$c_res =$db->query("SELECT id, name, COALESCE(debt, 0) as balance FROM customers ORDER BY name ASC");
while ($r = $c_res->fetchArray(SQLITE3_ASSOC)) {$customers[] = $r; }$products = [];
$p_res =$db->query("SELECT id, name, price, stock FROM products ORDER BY name ASC");
while ($r = $p_res->fetchArray(SQLITE3_ASSOC)) {$products[] = $r; }$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>فاتورة بيع - أبو ضياء للتوزيع</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Tahoma, sans-serif; background: #F1F5F9; margin: 0; padding: 0; color: #0F172A; }
        .top-bar { background: #1E40AF; color: #FFF; padding: 10px 15px; display: flex; justify-content: space-between; align-items: center; font-size: 14px; font-weight: bold; }
        .top-bar .close-btn { color: #FCA5A5; font-size: 18px; text-decoration: none; cursor: pointer; }
        .container { padding: 8px; max-width: 600px; margin: auto; }
        .card { background: #FFF; border: 1px solid #CBD5E1; border-radius: 6px; padding: 8px; margin-bottom: 6px; }
        .row { display: flex; gap: 6px; margin-bottom: 6px; align-items: center; }
        .row select, .row input { flex: 1; padding: 6px; border: 1px solid #CBD5E1; border-radius: 4px; font-size: 12px; background: #FFF; }
        .radio-group { display: flex; justify-content: space-between; background: #F8FAFC; padding: 6px; border-radius: 4px; border: 1px solid #CBD5E1; font-size: 11px; font-weight: bold; }
        .radio-group label { display: flex; align-items: center; gap: 3px; cursor: pointer; }
        .items-table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        .items-table th { background: #1E293B; color: #FFF; font-size: 11px; padding: 6px; text-align: center; }
        .items-table td { padding: 6px; border-bottom: 1px solid #E2E8F0; text-align: center; font-size: 12px; }
        .summary-card { background: #FFF; border: 1px solid #CBD5E1; border-radius: 6px; padding: 8px; margin-top: 10px; font-size: 11px; }
        .sum-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; font-weight: bold; }
        .sum-row input { width: 90px; padding: 4px; text-align: center; border: 1px solid #CBD5E1; border-radius: 4px; }
        .sum-val { background: #EFF6FF; border: 1px solid #93C5FD; padding: 4px 8px; border-radius: 4px; font-weight: bold; color: #1E3A8A; width: 90px; text-align: center; }
        .bal-box { background: #FEF3C7; border: 1px solid #F59E0B; padding: 6px; border-radius: 4px; margin-bottom: 6px; font-size: 11px; font-weight: bold; color: #92400E; display: none; text-align: center; }
        .extra-options { background: #EFF6FF; border: 1px solid #BFDBFE; padding: 6px; border-radius: 4px; margin-bottom: 6px; display: flex; justify-content: space-between; align-items: center; font-size: 11px; font-weight: bold; color: #1E40AF; }
        .btn-save { width: 100%; padding: 12px; background: #10B981; color: #FFF; border: none; border-radius: 6px; font-weight: bold; font-size: 14px; cursor: pointer; margin-top: 8px; }
    </style>
</head>
<body>

<div class="top-bar">
    <a href="index.php" class="close-btn">✕</a>
    <span>إصدار فاتورة جديدة - أبو ضياء</span>
    <span><?=date('H:i d/m/Y')?></span>
</div>

<div class="container">
    <form method="POST">
        <div class="card">
            <div class="row">
                <span style="font-size:11px; font-weight:bold; color:#1E3A8A; width:90px;">حساب الصندوق</span>
                <select name="treasury"><option>الصندوق الرئيسي</option></select>
            </div>
            <div class="radio-group">
                <label><input type="radio" name="b_type" checked> بيع</label>
                <label><input type="radio" name="pay_type" checked> نقدا</label>
                <label><input type="radio" name="pay_type"> اجل</label>
            </div>
        </div>

        <div class="extra-options">
            <label style="display:flex; align-items:center; gap:4px; cursor:pointer;">
                <input type="checkbox" name="is_quotation" value="1" style="width:16px; height:16px;"> حفظ كمسودة (عرض سعر فقط)
            </label>
            <div style="display:flex; align-items:center; gap:4px;">
                <span>المندوب:</span>
                <input type="text" name="rep_name" value="أبو ضياء" style="width:90px; padding:4px; text-align:center; border:1px solid #BFDBFE; border-radius:4px;">
            </div>
        </div>

        <div class="card">
            <div class="row">
                <select name="customer_id" id="customerSelect" onchange="updateCustomerBalance()" style="flex:2;">
                    <option value="0" data-bal="0">-- اختر العميل --</option>
                    <?php foreach($customers as$c): ?>
                        <option value="<?=$c['id']?>" data-bal="<?=$c['balance']?>"><?=htmlspecialchars($c['name'])?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" placeholder="ملاحظات" name="notes" style="flex:2;">
            </div>
            
            <div id="customerBalBox" class="bal-box">
                الرصيد السابق: <span id="prevBal">0.00</span> ر.ي | إجمالي الحساب بعد الفاتورة: <span id="totalBal">0.00</span> ر.ي
            </div>

            <div class="row">
                <select id="prodSelect" onchange="addItem()" style="flex:1;">
                    <option value="">-- اضغط هنا لاختيار وإضافة صنف للفاتورة --</option>
                    <?php foreach($products as$p): ?>
                        <option value="<?=$p['id']?>" data-name="<?=htmlspecialchars($p['name'])?>" data-price="<?=$p['price']?>"><?=$p['name']?> (<?=$p['price']?> ر.ي)</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="card" style="padding: 0; overflow-x: auto;">
            <table class="items-table">
                <thead>
                    <tr>
                        <th>اسم الصنف</th>
                        <th>الوحدة</th>
                        <th>العدد</th>
                        <th>السعر</th>
                        <th>الاجمالي</th>
                        <th>حذف</th>
                    </tr>
                </thead>
                <tbody id="itemsList">
                </tbody>
            </table>
        </div>

        <div class="summary-card">
            <div class="sum-row">
                <span>عدد القطع: <span id="numPieces" style="color:#EF4444;">0.00</span></span>
                <span>الاجمالي:</span>
                <div class="sum-val" id="lblSubTotal">0.00</div>
            </div>
            <div class="sum-row">
                <span>نسبة الخصم % <input type="number" id="discPerc" value="0" oninput="calcTotals()"></span>
                <span>الخصم:</span>
                <input type="number" name="discount" id="discVal" value="0.00" oninput="calcTotals()">
            </div>
            <div class="sum-row">
                <span style="color:#10B981;">المبلغ المستلم: <input type="number" name="paid_amount" id="paidAmt" value="0.00" oninput="calcTotals()" style="border-color:#10B981;"></span>
                <span style="color:#1E3A8A;">صافي الفاتورة:</span>
                <input type="hidden" name="grand_total" id="hiddenGrandTotal" value="0">
                <div class="sum-val" id="lblNetTotal" style="color:#047857; background:#ECFDF5; border-color:#6EE7B7;">0.00</div>
            </div>
        </div>

        <button type="submit" class="btn-save">💾 حفظ واعتماد الفاتورة وطباعتها</button>
    </form>
</div>

<script>
let currentCustomerBalance = 0;

function updateCustomerBalance() {
    let sel = document.getElementById('customerSelect');
    let opt = sel.options[sel.selectedIndex];
    currentCustomerBalance = parseFloat(opt.getAttribute('data-bal')) || 0;
    let balBox = document.getElementById('customerBalBox');
    
    if(sel.value > 0) {
        balBox.style.display = 'block';
        document.getElementById('prevBal').innerText = currentCustomerBalance.toFixed(2);
    } else {
        balBox.style.display = 'none';
    }
    calcTotals();
}

function addItem() {
    let sel = document.getElementById('prodSelect');
    let opt = sel.options[sel.selectedIndex];
    if(!sel.value) return;
    
    let id = sel.value;
    let name = opt.getAttribute('data-name');
    let price = parseFloat(opt.getAttribute('data-price')) || 0;

    let tbody = document.getElementById('itemsList');
    let tr = document.createElement('tr');
    tr.innerHTML = `
        <td style="text-align:right; padding-right:5px;">
            ${name}
            <input type="hidden" name="items[]" value="${id}">
        </td>
        <td>
            <select name="units[]" style="padding:4px; font-size:11px;">
                <option>كارتون</option>
                <option>شدة</option>
                <option>حبة</option>
            </select>
        </td>
        <td><input type="number" name="quantities[]" value="1" step="any" style="width:50px; text-align:center;" oninput="calcTotals()" class="q-inp"></td>
        <td><input type="number" name="prices[]" value="${price}" step="any" style="width:65px; text-align:center;" oninput="calcTotals()" class="p-inp"></td>
        <td class="row-total" style="font-weight:bold; color:#1E3A8A;">${price.toFixed(2)}</td>
        <td><button type="button" onclick="this.closest('tr').remove(); calcTotals();" style="color:red; border:none; background:none; cursor:pointer; font-size:14px;">✕</button></td>
    `;
    tbody.appendChild(tr);
    sel.value = "";
    calcTotals();
}

function calcTotals() {
    let rows = document.querySelectorAll('#itemsList tr');
    let subTotal = 0;
    let totalPieces = 0;

    rows.forEach(r => {
        let q = parseFloat(r.querySelector('.q-inp').value) || 0;
        let p = parseFloat(r.querySelector('.p-inp').value) || 0;
        let tot = q * p;
        r.querySelector('.row-total').innerText = tot.toFixed(2);
        subTotal += tot;
        totalPieces += q;
    });

    let discPerc = parseFloat(document.getElementById('discPerc').value) || 0;
    let discVal = parseFloat(document.getElementById('discVal').value) || 0;
    if(discPerc > 0) {
        discVal = (subTotal * discPerc) / 100;
        document.getElementById('discVal').value = discVal.toFixed(2);
    }

    let netTotal = Math.max(0, subTotal - discVal);
    let paid = parseFloat(document.getElementById('paidAmt').value) || 0;
    let remaining = Math.max(0, netTotal - paid);

    document.getElementById('lblSubTotal').innerText = subTotal.toFixed(2);
    document.getElementById('lblNetTotal').innerText = netTotal.toFixed(2);
    document.getElementById('hiddenGrandTotal').value = netTotal.toFixed(2);
    document.getElementById('numPieces').innerText = totalPieces.toFixed(2);

    if(document.getElementById('customerSelect').value > 0) {
        let totalDebt = currentCustomerBalance + remaining;
        document.getElementById('totalBal').innerText = totalDebt.toFixed(2);
    }
}
</script>

</body>
</html>
