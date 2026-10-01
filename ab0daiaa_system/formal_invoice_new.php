<?php
// formal_invoice_new.php - فاتورة المبيعات الرسمية والبيع السريع (نسخة جديدة ونظيفة) - V-MAX PRO
require_once 'auth.php';
$db_file = '/data/data/com.termux/files/home/secure_dbs/vmax.db';
$db = new SQLite3($db_file);

$check_cols =$db->query("PRAGMA table_info(invoices)");
$has_paid = false;
$has_discount = false;
while ($row =$check_cols->fetchArray(SQLITE3_ASSOC)) {
    if ($row['name'] === 'paid_amount')$has_paid = true;
    if ($row['name'] === 'discount')$has_discount = true;
}
if (!$has_paid)$db->exec("ALTER TABLE invoices ADD COLUMN paid_amount REAL DEFAULT 0");
if (!$has_discount)$db->exec("ALTER TABLE invoices ADD COLUMN discount REAL DEFAULT 0");

$error_msg = "";
$edit_id = intval($_GET['edit_id'] ?? 0);$selected_customer_id = intval($_GET['customer_id'] ?? 0);$edit_invoice = null;
$edit_items = [];

if ($edit_id > 0 &&$_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt =$db->prepare("SELECT * FROM invoices WHERE id = :id");
    $stmt->bindValue(':id',$edit_id, SQLITE3_INTEGER);
    $res =$stmt->execute();
    $edit_invoice =$res->fetchArray(SQLITE3_ASSOC);

    if ($edit_invoice) {
        $selected_customer_id = intval($edit_invoice['customer_id']);

        $items_stmt =$db->prepare("SELECT ii.*, p.name as product_name FROM invoice_items ii LEFT JOIN products p ON ii.product_id = p.id WHERE ii.invoice_id = :id");
        $items_stmt->bindValue(':id',$edit_id, SQLITE3_INTEGER);
        $items_res =$items_stmt->execute();
        while ($r =$items_res->fetchArray(SQLITE3_ASSOC)) {
            if (empty($r['product_name']) && isset($r['custom_name'])) {
                $r['product_name'] =$r['custom_name'];
            }
            $edit_items[] =$r;
        }
    }
}

$customers_result =$db->query("SELECT id, name, phone FROM customers ORDER BY name ASC");
$customers = [];
while ($row =$customers_result->fetchArray(SQLITE3_ASSOC)) {
    $customers[] =$row;
}

$products_result =$db->query("SELECT id, name, price, stock FROM products ORDER BY name ASC");
$products = [];
while ($row =$products_result->fetchArray(SQLITE3_ASSOC)) {
    $products[] =$row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_edit_id = intval($_POST['edit_id'] ?? 0);
    $customer_id = intval($_POST['customer_id'] ?? 0);
    $discount = floatval($_POST['discount'] ?? 0);
    $paid_amount = floatval($_POST['paid_amount'] ?? 0);

    $items =$_POST['items'] ?? [];
    $custom_names =$_POST['custom_names'] ?? [];
    $units =$_POST['units'] ?? [];
    $quantities =$_POST['quantities'] ?? [];
    $prices =$_POST['prices'] ?? [];

    $subtotal_sum = 0;
    $invoice_items = [];

    for ($i = 0; $i < count($items); $i++) {$p_id = intval($items[$i]);
        $c_name = trim($custom_names[$i] ?? '');$unit = trim($units[$i] ?? 'شدة');
        $qty = floatval($quantities[$i]);$price = floatval($prices[$i]);

        if (($p_id > 0 \vert{}\vert{}$c_name !== '') && $qty > 0) {$subtotal = $qty * $price;
            $subtotal_sum +=$subtotal;
            $invoice_items[] = [
                'product_id' => $p_id, 
                'custom_name' => $c_name, 
                'unit' => $unit, 
                'qty' => $qty, 
                'price' => $price
            ];
        }
    }

    if (empty($invoice_items)) {$error_msg = "الرجاء إضافة صنف واحد على الأقل للفاتورة";
    } else {
        $grand_total = max(0,$subtotal_sum - $discount);$new_remaining = max(0, $grand_total -$paid_amount);

        if ($post_edit_id > 0) {
            $old_inv_stmt =$db->prepare("SELECT customer_id, grand_total, paid_amount FROM invoices WHERE id = :id");
            $old_inv_stmt->bindValue(':id',$post_edit_id, SQLITE3_INTEGER);
            $old_inv =$old_inv_stmt->execute()->fetchArray(SQLITE3_ASSOC);

            if ($old_inv) {$old_remaining = max(0, floatval($old_inv['grand_total']) - floatval($old_inv['paid_amount']));
                if (intval($old_inv['customer_id']) > 0 && $old_remaining > 0) {$db->exec("UPDATE customers SET balance = balance - " . $old_remaining . " WHERE id = " . intval($old_inv['customer_id']));
                }
            }

            $old_items_stmt =$db->prepare("SELECT product_id, qty FROM invoice_items WHERE invoice_id = :id");
            $old_items_stmt->bindValue(':id',$post_edit_id, SQLITE3_INTEGER);
            $old_items_res =$old_items_stmt->execute();
            while ($old =$old_items_res->fetchArray(SQLITE3_ASSOC)) {
                if (intval($old['product_id']) > 0) {$db->exec("UPDATE products SET stock = stock + " . floatval($old['qty']) . " WHERE id = " . intval($old['product_id']));
                }
            }

            $db->exec("DELETE FROM invoice_items WHERE invoice_id = " . $post_edit_id);

            $stmt =$db->prepare("UPDATE invoices SET customer_id = :cid, grand_total = :total, discount = :discount, paid_amount = :paid WHERE id = :id");
            $stmt->bindValue(':cid', $customer_id, SQLITE3_INTEGER);$stmt->bindValue(':total', $grand_total, SQLITE3_FLOAT);$stmt->bindValue(':discount', $discount, SQLITE3_FLOAT);$stmt->bindValue(':paid', $paid_amount, SQLITE3_FLOAT);$stmt->bindValue(':id', $post_edit_id, SQLITE3_INTEGER);$stmt->execute();

            $invoice_id =$post_edit_id;
        } else {
            $stmt =$db->prepare("INSERT INTO invoices (customer_id, grand_total, discount, paid_amount) VALUES (:cid, :total, :discount, :paid)");
            $stmt->bindValue(':cid', $customer_id, SQLITE3_INTEGER);$stmt->bindValue(':total', $grand_total, SQLITE3_FLOAT);$stmt->bindValue(':discount', $discount, SQLITE3_FLOAT);$stmt->bindValue(':paid', $paid_amount, SQLITE3_FLOAT);$stmt->execute();
            $invoice_id =$db->lastInsertRowID();
        }

        if ($customer_id > 0 && $new_remaining > 0) {$db->exec("UPDATE customers SET balance = balance + " . $new_remaining . " WHERE id = " . $customer_id);
        }

        foreach ($invoice_items as$item) {
            $item_stmt =$db->prepare("INSERT INTO invoice_items (invoice_id, product_id, qty, price) VALUES (:inv_id, :p_id, :qty, :price)");
            $item_stmt->bindValue(':inv_id',$invoice_id, SQLITE3_INTEGER);
            $item_stmt->bindValue(':p_id',$item['product_id'] > 0 ? $item['product_id'] : null, SQLITE3_INTEGER);$item_stmt->bindValue(':qty', $item['qty'], SQLITE3_FLOAT);$item_stmt->bindValue(':price', $item['price'], SQLITE3_FLOAT);$item_stmt->execute();

            if ($item['product_id'] > 0) {$db->exec("UPDATE products SET stock = stock - " . $item['qty'] . " WHERE id = " . $item['product_id']);
            }
        }

        $phone_redirect = "";
        if ($customer_id > 0) {
            $cust_q =$db->prepare("SELECT phone FROM customers WHERE id = :cid");
            $cust_q->bindValue(':cid',$customer_id, SQLITE3_INTEGER);
            $cust_res =$cust_q->execute()->fetchArray(SQLITE3_ASSOC);
            if ($cust_res && !empty($cust_res['phone'])) {
                $phone_redirect = preg_replace('/[^0-9]/', '',$cust_res['phone']);
            }
        }

        header("Location: invoice_whatsapp.php?id=" . $invoice_id . "&auto_wa=" . $phone_redirect);
        exit;
    }
}
$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>فاتورة البيع السريع - V-MAX PRO</title>
    <style>
        :root {
            --bg-deep: #FFFFFF;
            --card-bg: #F8FAFC;
            --card-border: #CBD5E1;
            --primary: #2563EB;
            --success: #10B981;
            --danger: #EF4444;
            --text-main: #0F172A;
            --text-muted: #64748B;
        }
        * { box-sizing: border-box; }
        body { font-family: 'Tahoma', sans-serif; background: #FFFFFF; margin: 0; padding: 6px; color: var(--text-main); min-height: 100vh; padding-bottom: 75px; }
        .container { max-width: 650px; margin: 0 auto; }

        .header-box { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: 10px; padding: 12px 8px; text-align: center; margin-bottom: 6px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); position: relative; }
        .header-box h2 { margin: 0; font-size: 15px; color: #1E3A8A; font-weight: bold; }

        .btn-close-top {
            position: absolute; left: 10px; top: 50%; transform: translateY(-50%);
            background: #FFFFFF; color: #EF4444; width: 28px; height: 28px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; text-decoration: none;
            font-weight: bold; font-size: 14px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .top-quick-sale-btn { background: linear-gradient(135deg, #10B981, #059669); color: #FFFFFF; border: none; border-radius: 10px; padding: 10px 14px; font-size: 13px; font-weight: bold; width: 100%; cursor: pointer; text-align: center; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4); margin-bottom: 6px; }
        .top-quick-sale-btn:active { transform: scale(0.98); }

        .form-card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: 10px; padding: 8px; margin-bottom: 6px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        label { display: block; font-size: 11px; font-weight: bold; margin-bottom: 3px; color: #1E3A8A; }
        select, input { width: 100%; padding: 7px 9px; background: #FFFFFF; border: 1px solid var(--card-border); border-radius: 7px; color: #0F172A; font-size: 11.5px; margin-bottom: 4px; }
        .search-box { width: 100%; padding: 8px; background: #FFFFFF; border: 1px solid #2563EB; border-radius: 8px; color: #0F172A; font-size: 12px; margin-bottom: 6px; text-align: right; }

        .products-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 5px; max-height: 150px; overflow-y: auto; margin-bottom: 6px; padding: 3px; background: #FFFFFF; border-radius: 8px; border: 1px solid var(--card-border); }
        .product-tile { background: #FFFFFF; border: 1px solid #94A3B8; border-radius: 7px; padding: 7px 3px; text-align: center; cursor: pointer; display: flex; flex-direction: column; justify-content: center; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .product-tile:active { transform: scale(0.92); background: #FFFFFF; }
        .tile-name { font-size: 10px; font-weight: bold; color: #0F172A; margin-bottom: 1px; }
        .tile-price { font-size: 9px; color: #059669; font-weight: bold; }

        .btn-quick-custom { background: #FFFFFF; color: #FFFFFF; border: none; padding: 7px; border-radius: 7px; font-size: 11px; font-weight: bold; width: 100%; cursor: pointer; margin-bottom: 6px; box-shadow: 0 2px 5px rgba(124, 58, 237, 0.3); }
        .btn-quick-custom:active { transform: scale(0.98); }

        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .items-table th { background: #FFFFFF; font-size: 10px; padding: 5px 3px; text-align: center; border: 1px solid #CBD5E1; color: #1E40AF; }
        .items-table td { padding: 5px 3px; border: 1px solid #CBD5E1; text-align: center; vertical-align: middle; color: #0F172A; background: #FFFFFF; }
        .items-table input.table-input, .items-table select.table-input { width: 100% !important; margin-bottom: 0 !important; font-size: 10.5px !important; padding: 4px !important; text-align: center !important; color: #0F172A !important; background: #FFFFFF !important; }

        .btn-normal-save { width: 100%; padding: 10px; border-radius: 9px; font-weight: bold; font-size: 12px; border: none; cursor: pointer; text-align: center; background: #FFFFFF; color: #FFFFFF; box-shadow: 0 3px 10px rgba(37, 99, 235, 0.3); }
        .btn-normal-save:active { transform: scale(0.98); }

        .btn-close-bottom {
            padding: 10px; border-radius: 9px; font-weight: bold; font-size: 12px; text-align: center; text-decoration: none;
            background: #FFFFFF; color: #FFFFFF; box-shadow: 0 3px 10px rgba(239, 68, 68, 0.3); display: flex; align-items: center; justify-content: center;
        }

        .btn-danger-custom { background: #FFFFFF; color: #FFFFFF; padding: 5px 8px; font-size: 11px; border-radius: 5px; border: none; cursor: pointer; font-weight: bold; }

        .financial-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 5px; margin-bottom: 4px; }
        .totals-summary { background: #FFFFFF; border: 1px solid #10B981; padding: 7px 10px; border-radius: 7px; margin-bottom: 6px; }
        .total-row { display: flex; justify-content: space-between; align-items: center; font-size: 11.5px; font-weight: bold; color: #065F46; margin-bottom: 2px; }
        .total-row span:last-child { color: #047857; }
        .total-row:last-child { margin-bottom: 0; border-top: 1px dashed #A7F3D0; padding-top: 2px; }
        .alert-error { background: #FFFFFF; border: 1px solid var(--danger); color: #991B1B; padding: 5px; border-radius: 7px; font-size: 10px; text-align: center; margin-bottom: 6px; font-weight: bold; }
    </style>
</head>
<body>
    <div style="display: flex; gap: 5px;">
    </div>
    <div style="display: flex; gap: 4px; align-items: center;">
        <span style="color: #94a3b8; font-size: 10px; font-weight: bold;">تكبير الشاشة:</span>
    </div>
</div>
</div>

<div class="container">
    <div class="header-box">
        <a href="index.php" class="btn-close-top">✕</a>
        <h2>فاتورة البيع السريع والجديد</h2>
    </div>

    <?php if(!empty($error_msg)): ?>
        <div class="alert-error"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <form method="POST" id="quickSaleForm">
        <input type="hidden" name="edit_id" value="<?php echo $edit_id; ?>">

        <button type="submit" class="top-quick-sale-btn">
            بيـع سريـع وإتمام فوري
        </button>

        <div class="form-card">
            <label>اختر العميل:</label>
            <select name="customer_id">
                <option value="0">-- عميل نقدي --</option>
                <?php foreach($customers as$c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php echo ($selected_customer_id ==$c['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c['name']); ?> <?php echo !empty($c['phone']) ? '('.$c['phone'].')' : ''; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-card">
            <label>بحث سريع عن صنف:</label>
            <input type="text" id="searchProduct" class="search-box" placeholder="اكتب اسم الصنف للبحث..." oninput="filterProducts()">

            <div class="products-grid" id="productsGrid">
                <?php foreach($products as$p): ?>
                    <div class="product-tile" data-name="<?php echo htmlspecialchars($p['name']); ?>" onclick="addProductRow(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars(addslashes($p['name'])); ?>', <?php echo$p['price']; ?>)">
                        <div class="tile-name"><?php echo htmlspecialchars($p['name']); ?></div>
                        <div class="tile-price"><?php echo $p['price']; ?> ر.ي</div>
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="button" class="btn-quick-custom" onclick="addCustomProductRow()">
                ➕ إضافة صنف سريع يدوي
            </button>

            <label>بنود الفاتورة:</label>
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 32%;">الصنف</th>
                        <th style="width: 18%;">الوحدة</th>
                        <th style="width: 14%;">الكمية</th>
                        <th style="width: 22%;">السعر</th>
                        <th style="width: 14%;">حذف</th>
                    </tr>
                </thead>
                <tbody id="itemsBody">
                </tbody>
            </table>
        </div>

        <div class="form-card">
            <div class="financial-grid">
                <div>
                    <label>الخصم:</label>
                    <input type="number" name="discount" id="discountInput" value="<?php echo $edit_invoice['discount'] ?? 0; ?>" step="any" min="0" oninput="calculateTotals()">
                </div>
                <div>
                    <label>المدفوع:</label>
                    <input type="number" name="paid_amount" id="paidInput" value="<?php echo $edit_invoice['paid_amount'] ?? 0; ?>" step="any" min="0" oninput="calculateTotals()">
                </div>
            </div>

            <div class="totals-summary">
                <div class="total-row">
                    <span>المجموع:</span>
                    <span id="subtotalDisplay">0.00 ر.ي</span>
                </div>
                <div class="total-row">
                    <span>الإجمالي:</span>
                    <span id="grandTotalDisplay">0.00 ر.ي</span>
                </div>
                <div class="total-row">
                    <span>المتبقي (أجل):</span>
                    <span id="remainingDisplay" style="color: #EF4444;">0.00 ر.ي</span>
                </div>
            </div>

            <div style="display: flex; gap: 6px; margin-top: 6px;">
                <button type="submit" class="btn-normal-save" style="flex: 2;">حفظ الفاتورة</button>
            </div>
        </div>
    </form>
</div>


<script>
function filterProducts() {
    let query = document.getElementById('searchProduct').value.toLowerCase();
    let tiles = document.querySelectorAll('.product-tile');
    tiles.forEach(tile => {
        let name = tile.getAttribute('data-name').toLowerCase();
        if(name.includes(query)) {
            tile.style.display = 'flex';
        } else {
            tile.style.display = 'none';
        }
    });
}

function addProductRow(id, name, price, defaultQty = 1) {
    let tbody = document.getElementById('itemsBody');

    let existingRow = Array.from(tbody.querySelectorAll('tr')).find(row => {
        let inputId = row.querySelector('input[name="items[]"]');
        return inputId && inputId.value == id && id > 0;
    });

    if(existingRow && defaultQty === 1) {
        let qtyInput = existingRow.querySelector('.qty-input');
        qtyInput.value = parseFloat(qtyInput.value) + 1;
        calculateTotals();
        return;
    }

    let row = document.createElement('tr');
    row.innerHTML = `
        <td style="text-align: right; padding: 4px;">
            <span style="font-size: 10.5px; font-weight: bold; color: #0F172A; display: block;">${name}</span>
            <input type="hidden" name="items[]" value="${id}">
            <input type="hidden" name="custom_names[]" value="">
        </td>
        <td>
            <select name="units[]" class="table-input">
                <option value="شدة">شدة</option>
                <option value="كرتون">كرتون</option>
                <option value="حبة">حبة</option>
                <option value="قطعة">قطعة</option>
                <option value="كيس">كيس</option>
            </select>
        </td>
        <td><input type="number" name="quantities[]" value="${defaultQty}" step="any" min="0.1" class="table-input qty-input" oninput="calculateTotals()" required></td>
        <td><input type="number" name="prices[]" value="${price}" step="any" class="table-input price-input" oninput="calculateTotals()" required></td>
        <td><button type="button" class="btn-danger-custom" onclick="removeRow(this)">✕</button></td>
    `;
    tbody.appendChild(row);
    calculateTotals();
}

function addCustomProductRow(customName = '', defaultQty = 1, price = 0) {
    let tbody = document.getElementById('itemsBody');
    let row = document.createElement('tr');
    row.innerHTML = `
        <td style="text-align: right; padding: 4px;">
            <input type="text" name="custom_names[]" value="${customName}" placeholder="اسم الصنف..." class="table-input" style="text-align: right !important; font-weight: bold;" required>
            <input type="hidden" name="items[]" value="0">
        </td>
        <td>
            <select name="units[]" class="table-input">
                <option value="شدة">شدة</option>
                <option value="كرتون">كرتون</option>
                <option value="حبة">حبة</option>
                <option value="قطعة">قطعة</option>
                <option value="كيس">كيس</option>
            </select>
        </td>
        <td><input type="number" name="quantities[]" value="${defaultQty}" step="any" min="0.1" class="table-input qty-input" oninput="calculateTotals()" required></td>
        <td><input type="number" name="prices[]" value="${price}" step="any" class="table-input price-input" oninput="calculateTotals()" required></td>
        <td><button type="button" class="btn-danger-custom" onclick="removeRow(this)">✕</button></td>
    `;
    tbody.appendChild(row);
    calculateTotals();
}

function removeRow(btn) {
    btn.closest('tr').remove();
    calculateTotals();
}

function calculateTotals() {
    let rows = document.querySelectorAll('#itemsBody tr');
    let subtotal = 0;
    rows.forEach(row => {
        let qty = parseFloat(row.querySelector('.qty-input').value) || 0;
        let price = parseFloat(row.querySelector('.price-input').value) || 0;
        subtotal += (qty * price);
    });

    let discount = parseFloat(document.getElementById('discountInput').value) || 0;
    let paid = parseFloat(document.getElementById('paidInput').value) || 0;

    let grandTotal = Math.max(0, subtotal - discount);
    let remaining = Math.max(0, grandTotal - paid);

    document.getElementById('subtotalDisplay').innerText = subtotal.toFixed(2) + ' ر.ي';
    document.getElementById('grandTotalDisplay').innerText = grandTotal.toFixed(2) + ' ر.ي';
    document.getElementById('remainingDisplay').innerText = remaining.toFixed(2) + ' ر.ي';
}

window.onload = function5() {
    <?php if(!empty($edit_items)): ?>
        <?php foreach($edit_items as$item): ?>
            <?php if(!empty($item['product_id']) && intval($item['product_id']) > 0): ?>
                addProductRow(<?php echo $item['product_id']; ?>, '<?php echo addslashes($item['product_name']); ?>', <?php echo $item['price']; ?>, <?php echo$item['qty']; ?>);
            <?php else: ?>
                addCustomProductRow('<?php echo addslashes($item['product_name'] ?? ''); ?>', <?php echo $item['qty']; ?>, <?php echo$item['price']; ?>);
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
    calculateTotals();
};
</script>
</body>
</html>
