<?php
// formal_invoice.php - V-MAX PRO (White Clean UI - Auto Credit + Parse Error Fixed)
require_once 'auth.php';
$db = new SQLite3('/data/data/com.termux/files/home/secure_dbs/vmax.db');
$c_cols = $db->query("PRAGMA table_info(invoices)");
$cols = []; while ($r = $c_cols->fetchArray(SQLITE3_ASSOC)) $cols[] = $r['name'];
if (!in_array('paid_amount', $cols)) $db->exec("ALTER TABLE invoices ADD COLUMN paid_amount REAL DEFAULT 0");
if (!in_array('discount', $cols)) $db->exec("ALTER TABLE invoices ADD COLUMN discount REAL DEFAULT 0");
if (!in_array('notes', $cols)) $db->exec("ALTER TABLE invoices ADD COLUMN notes TEXT");
if (!in_array('payment_method', $cols)) $db->exec("ALTER TABLE invoices ADD COLUMN payment_method TEXT DEFAULT 'نقدي'");
if (!in_array('invoice_date', $cols)) $db->exec("ALTER TABLE invoices ADD COLUMN invoice_date TEXT");
if (!in_array('is_quotation', $cols)) $db->exec("ALTER TABLE invoices ADD COLUMN is_quotation INTEGER DEFAULT 0");
if (!in_array('signature', $cols)) $db->exec("ALTER TABLE invoices ADD COLUMN signature TEXT");
if (!in_array('invoice_type', $cols)) $db->exec("ALTER TABLE invoices ADD COLUMN invoice_type TEXT DEFAULT 'مبيعات'");
if (!in_array('currency', $cols)) $db->exec("ALTER TABLE invoices ADD COLUMN currency TEXT DEFAULT 'ريال يمني'");
if (!in_array('exchange_rate', $cols)) $db->exec("ALTER TABLE invoices ADD COLUMN exchange_rate REAL DEFAULT 1");
if (!in_array('rep_name', $cols)) $db->exec("ALTER TABLE invoices ADD COLUMN rep_name TEXT");

$p_colsq = $db->query("PRAGMA table_info(products)");
$pcols = []; while ($r = $p_colsq->fetchArray(SQLITE3_ASSOC)) $pcols[] = $r['name'];
if (!in_array('barcode', $pcols)) $db->exec("ALTER TABLE products ADD COLUMN barcode TEXT");
if (!in_array('cost_price', $pcols)) $db->exec("ALTER TABLE products ADD COLUMN cost_price REAL DEFAULT 0");

$i_colsq = $db->query("PRAGMA table_info(invoice_items)");
$icols = []; while ($r = $i_colsq->fetchArray(SQLITE3_ASSOC)) $icols[] = $r['name'];
if (!in_array('cost_price', $icols)) $db->exec("ALTER TABLE invoice_items ADD COLUMN cost_price REAL DEFAULT 0");

$err = ""; $edit_id = intval($_GET['edit_id'] ?? 0); $sel_cust = intval($_GET['customer_id'] ?? 0);
$edit_inv = null; $edit_items = []; $old_rem = 0; $old_type = 'مبيعات';

if ($edit_id > 0 and $_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $db->prepare("SELECT * FROM invoices WHERE id=:id"); $stmt->bindValue(':id', $edit_id, SQLITE3_INTEGER);
    $edit_inv = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    if ($edit_inv) {
        $sel_cust = intval($edit_inv['customer_id']); $old_type = $edit_inv['invoice_type'] ?? 'مبيعات';
        if ($edit_inv['is_quotation'] == 0) $old_rem = max(0, floatval($edit_inv['grand_total']) - floatval($edit_inv['paid_amount']));
        $istmt = $db->prepare("SELECT ii.*, p.name as p_name, p.stock as max_stock, COALESCE(p.cost_price,0) as cost FROM invoice_items ii LEFT JOIN products p ON ii.product_id=p.id WHERE ii.invoice_id=:id");
        $istmt->bindValue(':id', $edit_id, SQLITE3_INTEGER); $ires = $istmt->execute();
        while ($r = $ires->fetchArray(SQLITE3_ASSOC)) { if (empty($r['p_name']) and isset($r['custom_name'])) $r['p_name'] = $r['custom_name']; $edit_items[] = $r; }
    }
}

$c_res = $db->query("SELECT id, name, phone, COALESCE(debt,0) as balance FROM customers ORDER BY name ASC");
$customers = [];
while ($r = $c_res->fetchArray(SQLITE3_ASSOC)) {
    $bal = floatval($r['balance']);
    if ($edit_id > 0 and $sel_cust == $r['id']) {
        $is_old_return = false;
        if ($old_type === 'return') $is_old_return = true;
        if ($old_type === 'مرتجع') $is_old_return = true;

        $bal = $is_old_return ? ($bal + $old_rem) : ($bal - $old_rem);
    }
    $r['balance'] = $bal;
    $customers[] = $r;
}

$p_res = $db->query("SELECT id, name, price, COALESCE(cost_price,0) as cost_price, stock, barcode FROM products ORDER BY name ASC");
$products = [];
while ($r = $p_res->fetchArray(SQLITE3_ASSOC)) $products[] = $r;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pid = intval($_POST['edit_id'] ?? 0); $cid = intval($_POST['customer_id'] ?? 0);
    $disc = floatval($_POST['discount'] ?? 0); $paid = floatval($_POST['paid_amount'] ?? 0);
    $idate = trim($_POST['invoice_date'] ?? date('Y-m-d')); if (empty($idate)) $idate = date('Y-m-d');
    $pmethod = trim($_POST['payment_method'] ?? 'نقدي'); $notes = trim($_POST['notes'] ?? '');

    $itype_post = $_POST['invoice_type'] ?? 'sale';
    $is_return = false;
    if ($itype_post === 'return') $is_return = true;
    if ($itype_post === 'مرتجع') $is_return = true;

    $save_type = $is_return ? 'مرتجع' : 'مبيعات';

    $curr = $_POST['currency'] ?? 'ريال يمني';
    $exrate = floatval($_POST['exchange_rate'] ?? 1); $rep = trim($_POST['rep_name'] ?? '');
    $isq = isset($_POST['is_quotation']) ? 1 : 0;

    $items = $_POST['items'] ?? []; $cnames = $_POST['custom_names'] ?? []; $units = $_POST['units'] ?? [];
    $qtys = $_POST['quantities'] ?? []; $prices = $_POST['prices'] ?? []; $cprices = $_POST['cost_prices'] ?? [];

    $sub_sum = 0; $inv_items = [];
    for ($i = 0; $i < count($items); $i++) {
        $id = intval($items[$i]); $cn = trim($cnames[$i] ?? ''); $un = trim($units[$i] ?? 'شدة');
        $q = floatval($qtys[$i]); $p = floatval($prices[$i]); $cp = floatval($cprices[$i] ?? 0);

        $valid = false;
        if ($id > 0) $valid = true;
        if ($cn !== '') $valid = true;

        if ($valid and $q > 0) { $sub_sum += $q * $p; $inv_items[] = ['pid' => $id, 'cn' => $cn, 'un' => $un, 'q' => $q, 'p' => $p, 'cp' => $cp]; }
    }

    if (empty($inv_items)) $err = "الرجاء إضافة صنف واحد على الأقل للفاتورة";
    else {
        $g_total = max(0, $sub_sum - $disc); $n_rem = max(0, $g_total - $paid);

        if ($n_rem > 0) {
            $pmethod = 'آجل';
        } else {
            $pmethod = 'نقدي';
        }

        if ($pid > 0) {
            $old_stmt = $db->prepare("SELECT customer_id, grand_total, paid_amount, is_quotation, invoice_type FROM invoices WHERE id=:id");
            $old_stmt->bindValue(':id', $pid, SQLITE3_INTEGER); $old = $old_stmt->execute()->fetchArray(SQLITE3_ASSOC);
            if ($old and $old['is_quotation'] == 0) {
                $orem = max(0, floatval($old['grand_total']) - floatval($old['paid_amount']));

                $old_is_ret = false;
                if ($old['invoice_type'] === 'return') $old_is_ret = true;
                if ($old['invoice_type'] === 'مرتجع') $old_is_ret = true;

                if (intval($old['customer_id']) > 0 and $orem > 0) {
                    if ($old_is_ret) {
                        $db->exec("UPDATE customers SET debt=balance + {$orem} WHERE id=" . intval($old['customer_id']));
                    } else {
                        $db->exec("UPDATE customers SET balance=balance - {$orem} WHERE id=" . intval($old['customer_id']));
                    }
                }
                $ois = $db->prepare("SELECT product_id, qty FROM invoice_items WHERE invoice_id=:id");
                $ois->bindValue(':id', $pid, SQLITE3_INTEGER); $or = $ois->execute();
                while ($o = $or->fetchArray(SQLITE3_ASSOC)) {
                    if (intval($o['product_id']) > 0) {
                        if ($old_is_ret) {
                            $db->exec("UPDATE products SET stock=stock - " . floatval($o['qty']) . " WHERE id=" . intval($o['product_id']));
                        } else {
                            $db->exec("UPDATE products SET stock=stock + " . floatval($o['qty']) . " WHERE id=" . intval($o['product_id']));
                        }
                    }
                }
            }
            $db->exec("DELETE FROM invoice_items WHERE invoice_id=" . $pid);
            $stmt = $db->prepare("UPDATE invoices SET customer_id=:cid, grand_total=:tot, discount=:disc, paid_amount=:pd, invoice_date=:idt, payment_method=:pm, notes=:nt, is_quotation=:isq, invoice_type=:itype, currency=:curr, exchange_rate=:exr, rep_name=:rep WHERE id=:id");
            $stmt->bindValue(':id', $pid, SQLITE3_INTEGER); $inv_id = $pid;
        } else {
            $stmt = $db->prepare("INSERT INTO invoices (customer_id, grand_total, discount, paid_amount, invoice_date, payment_method, notes, is_quotation, invoice_type, currency, exchange_rate, rep_name) VALUES (:cid, :tot, :disc, :pd, :idt, :pm, :nt, :isq, :itype, :curr, :exr, :rep)");
        }
        $stmt->bindValue(':cid', $cid, SQLITE3_INTEGER); $stmt->bindValue(':tot', $g_total, SQLITE3_FLOAT);
        $stmt->bindValue(':disc', $disc, SQLITE3_FLOAT); $stmt->bindValue(':pd', $paid, SQLITE3_FLOAT);
        $stmt->bindValue(':idt', $idate, SQLITE3_TEXT); $stmt->bindValue(':pm', $pmethod, SQLITE3_TEXT);
        $stmt->bindValue(':nt', $notes, SQLITE3_TEXT); $stmt->bindValue(':isq', $isq, SQLITE3_INTEGER);
        $stmt->bindValue(':itype', $save_type, SQLITE3_TEXT);
        $stmt->bindValue(':curr', $curr, SQLITE3_TEXT); $stmt->bindValue(':exr', $exrate, SQLITE3_FLOAT);
        $stmt->bindValue(':rep', $rep, SQLITE3_TEXT); $stmt->execute();

        if ($pid == 0) $inv_id = $db->lastInsertRowID();

        if ($isq == 0) {
            if ($cid > 0 and $n_rem > 0) {
                if ($is_return) {
                    $db->exec("UPDATE customers SET balance=balance - {$n_rem} WHERE id={$cid}");
                } else {
                    $db->exec("UPDATE customers SET balance=balance + {$n_rem} WHERE id={$cid}");
                }
            }
            foreach ($inv_items as $itm) {
                if (intval($itm['pid']) > 0) {
                    if ($is_return) {
                        $db->exec("UPDATE products SET stock=stock + " . $itm['q'] . " WHERE id=" . intval($itm['pid']));
                    } else {
                        $db->exec("UPDATE products SET stock=stock - " . $itm['q'] . " WHERE id=" . intval($itm['pid']));
                    }
                }
            }
        }

        foreach ($inv_items as $itm) {
            $istmt = $db->prepare("INSERT INTO invoice_items(invoice_id, product_id, qty, price, cost_price) VALUES(:iid, :pid, :q, :p, :cp)");
            $istmt->bindValue(':iid', $inv_id, SQLITE3_INTEGER); $istmt->bindValue(':pid', intval($itm['pid']) > 0 ? intval($itm['pid']) : null, SQLITE3_INTEGER);
            $istmt->bindValue(':q', $itm['q'], SQLITE3_FLOAT); $istmt->bindValue(':p', $itm['p'], SQLITE3_FLOAT); $istmt->bindValue(':cp', $itm['cp'], SQLITE3_FLOAT);
            $istmt->execute();
        }

        $ph = ""; if ($cid > 0) { $cq = $db->prepare("SELECT phone FROM customers WHERE id=:cid"); $cq->bindValue(':cid', $cid, SQLITE3_INTEGER); $cr = $cq->execute()->fetchArray(SQLITE3_ASSOC); if ($cr and !empty($cr['phone'])) $ph = preg_replace('/[^0-9]/', '', $cr['phone']); }
        header("Location: invoice_whatsapp.php?id=" . $inv_id . "&auto_wa=" . $ph); exit;
    }
}
$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>V-MAX PRO | الفاتورة</title>
    <style>
        :root {
            --bg: #FFFFFF;
            --card-bg: #FFFFFF;
            --border: #CBD5E1;
            --primary: #2563EB;
            --success: #10B981;
            --danger: #EF4444;
            --warning: #F59E0B;
            --text-main: #0F172A;
            --text-muted: #475569;
        }
        * { box-sizing: border-box; }
        body { font-family: 'Tahoma', Arial, sans-serif; background: var(--bg); color: var(--text-main); margin: 0; padding: 8px; padding-bottom: 70px; min-height: 100vh; }
        .container { max-width: 650px; margin: 0 auto; }

        .card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 8px; padding: 10px; margin-bottom: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .header-box { text-align: center; background: #F8FAFC; border: 1px solid var(--border); border-radius: 8px; padding: 12px; margin-bottom: 8px; position: relative; }
        .header-box h2 { margin: 0; font-size: 16px; color: #1E3A8A; font-weight: bold; }
        .btn-close { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); background: #FEE2E2; color: var(--danger); width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; font-weight: bold; font-size: 14px; }

        label { display: block; font-size: 12px; font-weight: bold; margin-bottom: 4px; color: #1E3A8A; }
        select, input, textarea { width: 100%; padding: 8px; background: #FFFFFF; border: 1px solid #94A3B8; border-radius: 6px; font-size: 13px; margin-bottom: 6px; color: var(--text-main); }
        select:focus, input:focus, textarea:focus { border-color: var(--primary); outline: none; }

        .btn { width: 100%; padding: 10px; border-radius: 6px; font-weight: bold; font-size: 14px; border: none; cursor: pointer; text-align: center; text-decoration: none; display: inline-block; }
        .btn-primary { background: var(--primary); color: #FFF; margin-bottom: 8px; box-shadow: 0 2px 4px rgba(37,99,235,0.2); }
        .btn-secondary { background: #E2E8F0; color: #0F172A; border: 1px solid #CBD5E1; margin-bottom: 8px; font-size: 12px; }
        .btn-save { background: var(--success); color: #FFF; }
        .btn-cancel { background: #E2E8F0; color: #0F172A; }
        .btn-danger-icon { background: #FEE2E2; color: var(--danger); border: 1px solid #FCA5A5; padding: 4px 8px; border-radius: 4px; font-size: 12px; cursor: pointer; }

        .search-box { background: #FFFBEB; border-color: #F59E0B; text-align: right; }
        .products-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; max-height: 180px; overflow-y: auto; margin-bottom: 8px; padding: 4px; background: #F8FAFC; border-radius: 6px; border: 1px solid var(--border); }
        .product-tile { background: #FFFFFF; border: 1px solid var(--border); border-radius: 6px; padding: 8px 4px; text-align: center; cursor: pointer; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
        .product-tile:active { background: #EFF6FF; border-color: #93C5FD; }
        .p-name { font-size: 11px; font-weight: bold; color: var(--text-main); margin-bottom: 2px; }
        .p-price { font-size: 10px; color: var(--success); font-weight: bold; }
        .p-stock { font-size: 9px; color: var(--text-muted); font-weight: bold; margin-top: 2px; background: #F1F5F9; padding: 2px; border-radius: 4px; }

        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .items-table th { background: #F1F5F9; font-size: 11px; padding: 6px 4px; color: var(--text-muted); border-bottom: 2px solid var(--border); }
        .items-table td { padding: 4px; border-bottom: 1px solid #E2E8F0; text-align: center; }
        .items-table input, .items-table select { margin-bottom: 0; padding: 4px; font-size: 12px; text-align: center; }

        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 4px; }

        .summary-box { background: #F8FAFC; border: 1px solid var(--border); padding: 10px; border-radius: 6px; margin-bottom: 8px; }
        .s-row { display: flex; justify-content: space-between; font-size: 13px; font-weight: bold; color: var(--text-main); margin-bottom: 4px; }
        .s-val { color: var(--success); }
        .danger-val { color: var(--danger); }
        .divider { border: 0; border-top: 1px dashed #CBD5E1; margin: 6px 0; }
        .net-row { display: none; background: #DCFCE7; padding: 6px; border-radius: 4px; justify-content: space-between; font-weight: bold; font-size: 13px; color: #166534; margin-top: 6px; border: 1px solid #BBF7D0; }
        .prev-bal { display: none; background: #FEF3C7; border: 1px solid #FDE68A; color: #92400E; padding: 8px; border-radius: 6px; margin-bottom: 8px; font-size: 12px; font-weight: bold; text-align: center; }

        .quotation-wrap { background: #FEF2F2; border: 1px solid #FECACA; padding: 8px; border-radius: 6px; margin-bottom: 8px; display: flex; align-items: center; gap: 8px; }
        .quotation-wrap label { margin: 0; color: #991B1B; cursor: pointer; font-size: 12px; }
        .error-msg { background: #FEE2E2; color: var(--danger); padding: 8px; border-radius: 6px; font-size: 12px; font-weight: bold; text-align: center; margin-bottom: 8px; border: 1px solid #FCA5A5; }

        .mode-return { background: #FEF2F2 !important; border-color: #FECACA !important; }
        .mode-return h2 { color: #991B1B !important; }

        @media print {
            body { padding: 0; background: #FFF; color: #000; }
            .container { max-width: 100%; }
            .no-print { display: none !important; }
            .card { border: none; padding: 0; box-shadow: none; margin-bottom: 10px; }
            select, input, textarea { border: none !important; background: transparent !important; padding: 0 !important; color: #000; appearance: none; }
            .summary-box { background: transparent; border: none; }
            .items-table th { background: transparent; color: #000; border-bottom: 2px solid #000; }
            .items-table td { border-bottom: 1px dashed #000; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header-box no-print" id="hbox">
        <a href="index.php" class="btn-close">✕</a>
        <h2 id="ptit"><?=($edit_id>0)?"تعديل الفاتورة":"فاتورة مبيعات"?></h2>
    </div>

    <?php if(!empty($err)):?><div class="error-msg"><?=$err?></div><?php endif;?>

    <button type="button" class="btn btn-secondary no-print" onclick="window.print()">🖨️ طباعة الفاتورة للعميل</button>

    <form method="POST" id="qsf">
        <input type="hidden" name="edit_id" value="<?=$edit_id?>">
        <button type="submit" class="btn btn-primary no-print"><?=($edit_id>0)?"حفظ التعديلات":"إصدار الفاتورة وحفظها"?></button>

        <div class="quotation-wrap no-print">
            <input type="checkbox" name="is_quotation" id="isq" value="1" style="width:18px; height:18px; margin:0;" <?=($edit_inv['is_quotation']??0)==1?'checked':''?>>
            <label for="isq">حفظ كمسودة (عرض سعر فقط)</label>
        </div>

        <div class="card">
            <div class="grid-2 no-print" style="border-bottom: 1px solid var(--border); padding-bottom: 8px; margin-bottom: 8px;">
                <div>
                    <label style="color: var(--success);">نوع العملية:</label>
                    <select name="invoice_type" id="itype" onchange="tType()" style="border-color: var(--success); font-weight: bold; background: #F0FDF4;">
                        <?php
                        $cur_type = $edit_inv['invoice_type'] ?? '';$is_sale = true;
                        if ($cur_type === 'return')$is_sale = false;
                        if ($cur_type === 'مرتجع') $is_sale = false;
                        ?>
                        <option value="sale" <?=$is_sale?'selected':''?>>بيع (صرف)</option>
                        <option value="return" <?=!$is_sale?'selected':''?>>مرتجع (استلام)</option>
                    </select>
                </div>
                <div>
                    <label>المندوب / السائق:</label>
                    <input type="text" name="rep_name" placeholder="أبو ضياء" value="<?=htmlspecialchars($edit_inv['rep_name']??'')?>">
                </div>
            </div>

            <label>العميل:</label>
            <select name="customer_id" id="csel" onchange="custChanged()">
                <option value="0" data-bal="0">-- عميل نقدي طيار --</option>
                <?php foreach($customers as$c):?>
                    <option value="<?=$c['id']?>" data-bal="<?=$c['balance']?>" <?=($sel_cust==$c['id'])?'selected':''?>>
                        <?=htmlspecialchars($c['name'])?> <?=!empty($c['phone'])?'('.$c['phone'].')':''?>
                    </option>
                <?php endforeach;?>
            </select>

            <div id="pbbx" class="prev-bal">رصيد العميل السابق: <span id="pbamt">0</span> <span class="csym">ر.ي</span></div>

            <div class="grid-2">
                <div>
                    <label>العملة:</label>
                    <select name="currency" id="csel2" onchange="cTot()">
                        <option value="ريال يمني" <?=($edit_inv['currency']??'')=='ريال يمني'?'selected':''?>>ريال يمني</option>
                        <option value="ريال سعودي" <?=($edit_inv['currency']??'')=='ريال سعودي'?'selected':''?>>ريال سعودي</option>
                        <option value="دولار" <?=($edit_inv['currency']??'')=='دولار'?'selected':''?>>دولار</option>
                    </select>
                </div>
                <div><label>سعر الصرف:</label><input type="number" name="exchange_rate" value="<?=floatval($edit_inv['exchange_rate']??1)?>" step="any"></div>
            </div>

            <div class="grid-2">
                <div><label>التاريخ:</label><input type="date" name="invoice_date" value="<?=htmlspecialchars($edit_inv['invoice_date']??date('Y-m-d'))?>"></div>
                <div>
                    <label>الدفع:</label>
                    <?php
                        $defPay = 'نقدي';
                        if ($edit_id > 0) {
                            $defPay =$edit_inv['payment_method'] ?? 'نقدي';
                        } else {
                            if ($sel_cust > 0) {$defPay = 'آجل';
                            }
                        }

                        $is_cash = false;
                        if ($defPay == 'نقدي') $is_cash = true;
                        if ($defPay == 'نقداً') $is_cash = true;
                    ?>
                    <select name="payment_method" id="payMethodSel">
                        <option value="نقدي" <?=$is_cash?'selected':''?>>نقدي</option>
                        <option value="آجل" <?=($defPay=='آجل')?'selected':''?>>آجل</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card no-print">
            <label>🔍 بحث سريع أو مسح باركود (Enter):</label>
            <input type="text" id="sprod" class="search-box" placeholder="..." onkeyup="hSrch(event)">

            <div class="products-grid" id="pgrd">
                <?php foreach($products as$p):?>
                <div class="product-tile" data-n="<?=htmlspecialchars($p['name'])?>" data-b="<?=htmlspecialchars($p['barcode']??'')?>" onclick="aRow(<?=$p['id']?>,'<?=htmlspecialchars(addslashes($p['name']))?>',<?=$p['price']?>,1,<?=floatval($p['stock'])?>,<?=floatval($p['cost_price'])?>)">
                    <div class="p-name"><?=htmlspecialchars($p['name'])?></div>
                    <div class="p-price"><?=$p['price']?></div>
                    <div class="p-stock" style="color:<?=($p['stock']<=0)?'var(--danger)':'var(--text-muted)'?>">الكمية: <?=floatval($p['stock'])?></div>
                </div>
                <?php endforeach;?>
            </div>
            <button type="button" class="btn btn-secondary" style="margin-bottom:0;" onclick="aCust()">➕ إضافة صنف سريع (غير مسجل)</button>
        </div>

        <div class="card">
            <label>📝 البنود:</label>
            <table class="items-table">
                <thead><tr><th style="text-align:right;">الصنف</th><th>الوحدة</th><th>الكمية</th><th>السعر</th><th class="no-print">حذف</th></tr></thead>
                <tbody id="itb"></tbody>
            </table>
        </div>

        <div class="card">
            <div class="grid-2 no-print">
                <div><label>الخصم:</label><input type="number" name="discount" id="dinp" value="<?=floatval($edit_inv['discount']??0)?>" step="any" min="0" oninput="cTot()"></div>
                <div><label id="lbl_paid">المدفوع:</label><input type="number" name="paid_amount" id="pinp" value="<?=floatval($edit_inv['paid_amount']??0)?>" step="any" min="0" oninput="cTot()"></div>
            </div>

            <label>ملاحظات الفاتورة:</label>
            <textarea name="notes" rows="1" placeholder="أضف ملاحظة تظهر للعميل..."><?=htmlspecialchars($edit_inv['notes']??'')?></textarea>

            <div class="summary-box">
                <div class="s-row"><span>المجموع الفرعي:</span><span id="ds">0</span></div>
                <div class="s-row"><span>الإجمالي:</span><span id="dg" class="s-val">0</span></div>
                <div class="s-row"><span id="lrm">المتبقي:</span><span id="drm" class="danger-val">0</span></div>
                <hr class="divider">
                <div class="s-row" id="prw" style="display:none; color: var(--warning);"><span>الرصيد السابق:</span><span id="dpr">0</span></div>
                <div class="net-row" id="nrw"><span id="lnb">المديونية الكلية للعميل:</span><span id="dnb">0</span></div>
                <div class="s-row no-print" id="prf" style="margin-top:8px; font-size:12px; color:var(--primary);"><span>📈 صافي الربح:</span><span id="dprf">0</span></div>
            </div>

            <div class="grid-2 no-print" style="margin-top: 10px;">
                <button type="submit" class="btn btn-save">حفظ</button>
                <a href="index.php" class="btn btn-cancel">إغلاق</a>
            </div>
        </div>
    </form>
</div>

<?php if(file_exists('nav_menu.php')) include 'nav_menu.php'; ?>

<script>
document.getElementById('qsf').onkeypress = function(e) {
    let k = e.charCode ? e.charCode : e.keyCode;
    if(k == 13) {
        if(e.target.tagName.toLowerCase() !== 'textarea') {
            e.preventDefault(); return false;
        }
    }
}
let pData = <?=json_encode($products)?>;
function gSym(){ let c=document.getElementById('csel2').value; return c==='ريال سعودي'?' ر.س':(c==='دولار'?' $':' ر.ي'); }
function hSrch(e){
    let i=e.target, q=i.value.toLowerCase().trim();
    let k = e.charCode ? e.charCode : e.keyCode;
    if(e.key==='Enter' || k===13){
        e.preventDefault();
        if(q==="") return;
        let p=pData.find(x => {
            let match = false;
            if(x.barcode) { if(x.barcode.toLowerCase() === q) match = true; }
            if(x.id == q) match = true;
            return match;
        });
        if(p){ aRow(p.id,p.name,p.price,1,parseFloat(p.stock),parseFloat(p.cost_price)); i.value=''; fProds(); return; }
    }
    fProds();
}
function fProds(){
    let q=document.getElementById('sprod').value.toLowerCase(), t=document.querySelectorAll('.product-tile');
    t.forEach(x => {
        let n=x.getAttribute('data-n').toLowerCase(), b=x.getAttribute('data-b').toLowerCase();
        let show = false;
        if(n.includes(q)) show = true;
        if(b.includes(q)) show = true;
        x.style.display = show ? 'flex' : 'none';
    });
}

function aRow(id,n,p,dq=1,ms=999999,cp=0){
    let tb=document.getElementById('itb');
    let ex=Array.from(tb.querySelectorAll('tr')).find(r => {
        let i=r.querySelector('input[name="items[]"]');
        let isMatch = false;
        if(i) { if(i.value == id) { if(id > 0) isMatch = true; } }
        return isMatch;
    });
    if(ex){
        if(dq===1){ let qi=ex.querySelector('.qty-input'); qi.value=parseFloat(qi.value)+1; cStk(qi,ms); return; }
    }
    let r=document.createElement('tr');
    r.innerHTML=`
        <td style="text-align:right;">
            <span style="font-size:12px; font-weight:bold; color:var(--text-main);">${n}</span>
            <input type="hidden" name="items[]" value="${id}"><input type="hidden" name="custom_names[]" value=""><input type="hidden" name="cost_prices[]" class="ci" value="${cp}">
        </td>
        <td><select name="units[]"><option>شدة</option><option>كرتون</option><option>حبة</option><option>قطعة</option><option>كيس</option></select></td>
        <td><input type="number" name="quantities[]" value="${dq}" step="any" class="qty-input" oninput="cStk(this,${ms})" required></td>
        <td><input type="number" name="prices[]" value="${p}" step="any" class="pi" oninput="cTot()" required></td>
        <td class="no-print"><button type="button" class="btn-danger-icon" onclick="rm(this)">✕</button></td>
    `;
    tb.appendChild(r); cStk(r.querySelector('.qty-input'), ms);
}

function cStk(i,ms){
    let v=parseFloat(i.value), ir=document.getElementById('itype').value==='return';
    if(v>ms && !ir){ i.style.background='#FEE2E2'; i.style.borderColor='var(--danger)'; }else{ i.style.background='#FFFFFF'; i.style.borderColor='#94A3B8'; } cTot();
}
function aCust(n='',dq=1,p=0){ let tb=document.getElementById('itb'), r=document.createElement('tr'); r.innerHTML=`<td style="text-align:right;"><input type="text" name="custom_names[]" value="${n}" placeholder="صنف" required><input type="hidden" name="items[]" value="0"><input type="hidden" name="cost_prices[]" class="ci" value="0"></td><td><select name="units[]"><option>شدة</option><option>كرتون</option><option>حبة</option></select></td><td><input type="number" name="quantities[]" value="${dq}" step="any" class="qty-input" oninput="cTot()" required></td><td><input type="number" name="prices[]" value="${p}" step="any" class="pi" oninput="cTot()" required></td><td class="no-print"><button type="button" class="btn-danger-icon" onclick="rm(this)">✕</button></td>`; tb.appendChild(r); cTot(); }
function rm(b){ b.closest('tr').remove(); cTot(); }

function custChanged() {
    let s = document.getElementById('csel');
    document.getElementById('payMethodSel').value = (s.value > 0) ? 'آجل' : 'نقدي';
    uBal();
}

function tType(){
    let ir=document.getElementById('itype').value==='return';
    document.getElementById('hbox').className = ir ? 'header-box no-print mode-return' : 'header-box no-print';
    document.getElementById('ptit').innerText = ir ? "فاتورة مرتجع" : "فاتورة مبيعات";
    document.getElementById('lbl_paid').innerText = ir ? "المُسَلَّم للعميل:" : "المدفوع:";
    document.getElementById('lrm').innerText = ir ? "مطلوب إرجاعه:" : "المتبقي:";
    document.getElementById('lnb').innerText = ir ? "المديونية بعد الإرجاع:" : "المديونية الكلية:";
    document.getElementById('prf').style.display = ir ? 'none' : 'flex';
    document.querySelectorAll('.qty-input').forEach(i=>i.dispatchEvent(new Event('input'))); cTot();
}

function uBal(){
    let s=document.getElementById('csel'), o=s.options[s.selectedIndex], sm=gSym();
    let b=parseFloat(o.getAttribute('data-bal')); b = isNaN(b) ? 0 : b;
    if(b !== 0){ document.getElementById('pbbx').style.display='block'; document.getElementById('pbamt').innerText=b.toLocaleString(); document.getElementById('prw').style.display='flex'; document.getElementById('dpr').innerText=b.toLocaleString()+sm; document.getElementById('nrw').style.display='flex'; }
    else{ document.getElementById('pbbx').style.display='none'; document.getElementById('prw').style.display='none'; document.getElementById('nrw').style.display='none'; } cTot();
}

function cTot(){
    let s=0, tc=0, rs=document.querySelectorAll('#itb tr');
    rs.forEach(r => {
        let qVal=parseFloat(r.querySelector('.qty-input').value); let q = isNaN(qVal) ? 0 : qVal;
        let pVal=parseFloat(r.querySelector('.pi').value); let p = isNaN(pVal) ? 0 : pVal;
        let cVal=parseFloat(r.querySelector('.ci').value); let c = isNaN(cVal) ? 0 : cVal;
        s+=q*p; tc+=q*c;
    });

    let dVal=parseFloat(document.getElementById('dinp').value); let d = isNaN(dVal) ? 0 : dVal;
    let pdVal=parseFloat(document.getElementById('pinp').value); let pd = isNaN(pdVal) ? 0 : pdVal;

    let gt=Math.max(0,s-d), rm=Math.max(0,gt-pd), pf=Math.max(0,(s-tc)-d), sm=gSym();
    document.querySelectorAll('.csym').forEach(e=>e.innerText=sm);
    document.getElementById('ds').innerText = s.toLocaleString(undefined, {minimumFractionDigits: 2}) + sm;
    document.getElementById('dg').innerText = gt.toLocaleString(undefined, {minimumFractionDigits: 2}) + sm;
    document.getElementById('drm').innerText = rm.toLocaleString(undefined, {minimumFractionDigits: 2}) + sm;
    document.getElementById('dprf').innerText = pf.toLocaleString(undefined, {minimumFractionDigits: 2}) + sm;

    let payMethod = document.getElementById('payMethodSel');
    if (rm > 0) {
        payMethod.value = 'آجل';
    } else {
        payMethod.value = 'نقدي';
    }

    let so=document.getElementById('csel').options[document.getElementById('csel').selectedIndex];
    let bVal=parseFloat(so.getAttribute('data-bal')); let b = isNaN(bVal) ? 0 : bVal;
    let ir=document.getElementById('itype').value==='return';
    let nb=ir?(b-rm):(b+rm);

    let showNet = false;
    if (nb !== 0) showNet = true;
    if (b !== 0) showNet = true;

    if(showNet){ document.getElementById('nrw').style.display='flex'; document.getElementById('dnb').innerText=nb.toLocaleString(undefined, {minimumFractionDigits: 2})+sm; } else document.getElementById('nrw').style.display='none';
}

window.onload=function(){
    <?php if(!empty($edit_items)): foreach($edit_items as$itm): if(!empty($itm['product_id'])&&intval($itm['product_id'])>0):?>
        aRow(<?=$itm['product_id']?>,'<?=addslashes($itm['product_name'])?>',<?=$itm['price']?>,<?=$itm['qty']?>,<?=floatval($itm['max_stock']??999999)?>,<?=floatval($itm['current_cost']??0)?>);
    <?php else:?>
        aCust('<?=addslashes($itm['product_name']??'')?>',<?=$itm['qty']?>,<?=$itm['price']?>);
    <?php endif; endforeach; endif;?>
    tType(); uBal();
}
</script>
</body>
</html>
