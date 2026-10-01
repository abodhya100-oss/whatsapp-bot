<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_role'])) { header("Location: login.php"); exit; }
$db = new SQLite3('vmax.db');

$db->exec("CREATE TABLE IF NOT EXISTS suppliers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    phone TEXT,
    balance REAL DEFAULT 0
);");

$db->exec("CREATE TABLE IF NOT EXISTS purchases (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    supplier_id INTEGER,
    supplier_name TEXT,
    total_amount REAL,
    date TEXT,
    user_name TEXT
);");

$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_purchase'])) {
    $supplier_id = intval($_POST['supplier_id']);
    $purch_data = json_decode($_POST['purch_json'], true);
    
    if (!empty($purch_data) && $supplier_id > 0) {
        $total_amount = 0;
        foreach ($purch_data as $item) {
            $total_amount += ($item['cost'] * $item['qty']);
        }
        $date = date('Y-m-d H:i:s');
        $user_name = $_SESSION['username'] ?? 'المدير';
        $s_name = $db->querySingle("SELECT name FROM suppliers WHERE id = $supplier_id");

        // حفظ رأس فاتورة الشراء
        $stmt = $db->prepare("INSERT INTO purchases (supplier_id, supplier_name, total_amount, date, user_name) VALUES (:sid, :sname, :tot, :date, :user)");
        $stmt->bindValue(':sid', $supplier_id, SQLITE3_INTEGER);
        $stmt->bindValue(':sname', $s_name, SQLITE3_TEXT);
        $stmt->bindValue(':tot', $total_amount, SQLITE3_FLOAT);
        $stmt->bindValue(':date', $date, SQLITE3_TEXT);
        $stmt->bindValue(':user', $user_name, SQLITE3_TEXT);
        $stmt->execute();

        // تحديث المخزون وأسعار الشراء لكل صنف
        foreach ($purch_data as $item) {
            $db->exec("UPDATE inventory SET stock = stock + " . intval($item['qty']) . ", buy_price = " . floatval($item['cost']) . " WHERE id = " . intval($item['id']));
        }

        // تحديث رصيد المورد (زيادة الدين للمورد)
        $db->exec("UPDATE suppliers SET balance = balance + $total_amount WHERE id = $supplier_id");

        $msg = "✅ تمت توريد فواتير الشراء وزيادة المخزون بنجاح تام!";
    }
}

$suppliers = [];
$s_res = $db->query("SELECT * FROM suppliers ORDER BY name ASC");
while ($row = $s_res->fetchArray(SQLITE3_ASSOC)) { $suppliers[] = $row; }

$inventory_items = [];
$res = $db->query("SELECT * FROM inventory ORDER BY id DESC");
while ($row = $res->fetchArray(SQLITE3_ASSOC)) { $inventory_items[] = $row; }
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
    <link rel="manifest" href="manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js");}</script>
    <meta charset="UTF-8">
    <meta name="google" content="notranslate">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فواتير المشتريات - نظام الإمبراطورية</title>
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <style>
        body { font-family: Tahoma, sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 6px; padding-bottom: 110px; direction: rtl; }
        .header { text-align: center; font-size: 14px; font-weight: 900; color: #38bdf8; margin-bottom: 8px; text-shadow: 0 0 10px rgba(56,189,248,0.5); }
        .glass-panel { background: linear-gradient(135deg, rgba(30, 41, 59, 0.95), rgba(15, 23, 42, 0.98)); backdrop-filter: blur(20px); border: 1.5px solid rgba(56, 189, 248, 0.4); border-radius: 18px; padding: 10px; margin-bottom: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.6); }
        .items-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; max-height: 180px; overflow-y: auto; padding: 2px; }
        .item-card { background: linear-gradient(135deg, rgba(30, 58, 138, 0.6), rgba(15, 23, 42, 0.95)); border: 1px solid rgba(56, 189, 248, 0.4); border-radius: 12px; padding: 8px 4px; text-align: center; cursor: pointer; transition: 0.2s; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .item-card:active { transform: scale(0.95); background: rgba(56, 189, 248, 0.2); }
        .item-icon { font-size: 18px; margin-bottom: 2px; }
        .item-title { font-size: 10px; font-weight: bold; color: #f8fafc; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; width: 100%; }
        .item-price { font-size: 11px; font-weight: 900; color: #4ade80; }
        .form-control { width: 100%; padding: 8px; background: #0f172a; color: #fff; border: 1px solid rgba(56, 189, 248, 0.4); border-radius: 10px; font-size: 11px; box-sizing: border-box; margin-bottom: 6px; }

        .supplier-picker-btn {
            width: 100%; background: #0f172a; border: 1px solid rgba(56, 189, 248, 0.5);
            color: #f8fafc; padding: 10px; border-radius: 10px; font-size: 12px; font-weight: bold;
            text-align: right; cursor: pointer; display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 6px; box-sizing: border-box;
        }
        
        .supp-modal-overlay {
            position: fixed; inset: 0; background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(12px);
            display: none; align-items: center; justify-content: center; z-index: 999999; padding: 10px;
        }
        .supp-modal-card {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.98), rgba(15, 23, 42, 0.99));
            border: 2px solid #38bdf8; width: 100%; max-width: 400px; border-radius: 20px;
            padding: 15px; box-shadow: 0 25px 50px rgba(0,0,0,0.9); max-height: 85vh; display: flex; flex-direction: column;
        }
        .supp-list-box { flex: 1; overflow-y: auto; max-height: 50vh; margin-top: 8px; }
        .supp-option-item {
            background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(56, 189, 248, 0.2);
            padding: 10px; border-radius: 12px; margin-bottom: 6px; cursor: pointer; transition: 0.2s;
            display: flex; justify-content: space-between; align-items: center;
        }
    </style>
</head>
<body>
    <div class="header">📥 فواتير ومشتريات البضائع</div>

    <?php if(!empty($msg)): ?>
        <div style="background: rgba(34,197,94,0.2); border: 1px solid #22c55e; color: #4ade80; padding: 10px; border-radius: 10px; font-size: 11px; margin-bottom: 10px; text-align:center; font-weight:bold;"><?php echo $msg; ?></div>
    <?php endif; ?>

    <!-- نافذة اختيار المورد المنبثقة -->
    <div id="suppModal" class="supp-modal-overlay">
        <div class="supp-modal-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                <div style="font-weight:bold; color:#38bdf8; font-size:13px;">🔍 اختر المورد المطلوب</div>
                <button onclick="document.getElementById('suppModal').style.display='none'" style="background:transparent; border:none; color:#ef4444; font-size:16px; font-weight:bold; cursor:pointer;">✕</button>
            </div>
            <div class="supp-list-box">
                <?php foreach($suppliers as $s): ?>
                <div class="supp-option-item" onclick="selectSupplier(<?php echo $s['id']; ?>, '<?php echo htmlspecialchars($s['name'], ENT_QUOTES); ?>')">
                    <div><b>👤 <?php echo htmlspecialchars($s['name']); ?></b></div>
                    <div style="font-size:10px; color:#38bdf8;"><?php echo htmlspecialchars($s['phone']); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="glass-panel">
        <div style="font-size: 11px; font-weight: bold; color: #38bdf8; margin-bottom: 4px;">🚚 المورد:</div>
        <div class="supplier-picker-btn" onclick="document.getElementById('suppModal').style.display='flex'">
            <span id="selectedSupplierText">👤 -- اختر المورد --</span>
            <span style="color:#38bdf8; font-size:10px;">▼ اختر</span>
        </div>
    </div>

    <div class="glass-panel">
        <div style="font-size: 11px; font-weight: bold; color: #38bdf8; margin-bottom: 4px; display: flex; justify-content: space-between;">
            <span>📦 اختر أصناف المستودع للإضافة:</span>
            <span style="color:#4ade80;"><?php echo count($inventory_items); ?> صنف</span>
        </div>
        <input type="text" id="searchBox" onkeyup="filterItems()" class="form-control" placeholder="🔍 بحث سريع عن صنف...">

        <div class="items-grid" id="itemsGrid">
            <?php foreach($inventory_items as $item): ?>
                <div class="item-card" data-name="<?php echo strtolower(htmlspecialchars($item['item_name'])); ?>" onclick="addToPurchCart(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['item_name'], ENT_QUOTES); ?>', <?php echo $item['buy_price']; ?>)">
                    <div class="item-icon"><?php echo !empty($item['image']) ? $item['image'] : '📦'; ?></div>
                    <div class="item-title"><?php echo htmlspecialchars($item['item_name']); ?></div>
                    <div class="item-price"><?php echo number_format($item['buy_price'], 0); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="glass-panel" style="padding: 10px;">
        <div style="font-weight: bold; color: #38bdf8; font-size: 11px; margin-bottom: 6px;">🛒 سلة مشتريات البضاعة:</div>
        <div id="purchCartList" style="font-size: 10px; color: #94a3b8; text-align: center; padding: 8px;">السلة فارغة... اختر الأصناف للإضافة 👆</div>
        
        <div style="margin-top: 6px; border-top: 1px solid rgba(56,189,248,0.3); padding-top: 6px; font-size: 12px; display: flex; justify-content: space-between; font-weight: bold;">
            <span>إجمالي الفاتورة:</span>
            <span id="purchGrandTotal" style="color: #4ade80; direction: ltr;">0 ر.ي</span>
        </div>

        <button onclick="submitPurchCheckout()" style="margin-top: 8px; background: linear-gradient(135deg, #22c55e, #16a34a); color: #fff; border: none; padding: 10px; border-radius: 12px; font-weight: bold; width: 100%; cursor: pointer; font-size: 12px;">
            💾 اعتماد وتوريد الفاتورة للمخزون
        </button>
    </div>

    <form id="purchForm" method="POST" style="display:none;">
        <input type="hidden" name="save_purchase" value="1">
        <input type="hidden" name="supplier_id" id="form_supplier_id" value="0">
        <input type="hidden" name="purch_json" id="form_purch_json">
    </form>

    <script>
    let supplierId = 0;
    function selectSupplier(id, name) {
        supplierId = id;
        document.getElementById('form_supplier_id').value = id;
        document.getElementById('selectedSupplierText').innerText = '👤 ' + name;
        document.getElementById('suppModal').style.display = 'none';
    }

    let purchCart = [];

    function addToPurchCart(id, name, buyPrice) {
        let found = purchCart.find(i => i.id == id);
        if (found) {
            found.qty += 1;
        } else {
            purchCart.push({ id: id, name: name, cost: buyPrice, qty: 1 });
        }
        renderPurchCart();
    }

    function updatePurchCost(index, val) {
        let p = parseFloat(val);
        if (!isNaN(p) && p >= 0) {
            purchCart[index].cost = p;
            renderPurchCart();
        }
    }

    function updatePurchQty(index, val) {
        let q = parseInt(val);
        if (!isNaN(q) && q > 0) {
            purchCart[index].qty = q;
            renderPurchCart();
        }
    }

    function removePurchItem(index) {
        purchCart.splice(index, 1);
        renderPurchCart();
    }

    function renderPurchCart() {
        let list = document.getElementById('purchCartList');
        if (purchCart.length === 0) {
            list.innerHTML = '<div style="color: #94a3b8; padding: 8px;">السلة فارغة... اختر الأصناف للإضافة 👆</div>';
            document.getElementById('purchGrandTotal').innerText = '0 ر.ي';
            return;
        }
        let total = 0;
        let html = '<table style="width:100%; border-collapse:collapse; font-size:10px;">';
        html += '<tr style="background:rgba(2,132,199,0.3); color:#38bdf8;"><th>الصنف</th><th>الكمية</th><th>تكلفة القطعة</th><th>المجموع</th><th>حذف</th></tr>';
        purchCart.forEach((item, index) => {
            let sub = item.cost * item.qty;
            total += sub;
            html += `<tr style="border-bottom:1px solid rgba(56,189,248,0.2);">
                <td style="padding:4px;"><b>${item.name}</b></td>
                <td style="padding:4px; text-align:center;"><input type="number" value="${item.qty}" onchange="updatePurchQty(${index}, this.value)" style="width:40px; background:#0f172a; color:#fff; border:1px solid #38bdf8; border-radius:4px; text-align:center;"></td>
                <td style="padding:4px;"><input type="number" value="${item.cost}" onchange="updatePurchCost(${index}, this.value)" style="width:60px; background:#0f172a; color:#4ade80; border:1px solid #38bdf8; border-radius:4px; text-align:center;"></td>
                <td style="padding:4px; color:#4ade80; font-weight:bold; direction:ltr; text-align:center;">${sub.toLocaleString()}</td>
                <td style="padding:4px; text-align:center;"><button onclick="removePurchItem(${index})" style="background:transparent; border:none; color:#ef4444; cursor:pointer;">❌</button></td>
            </tr>`;
        });
        html += '</table>';
        list.innerHTML = html;
        document.getElementById('purchGrandTotal').innerText = total.toLocaleString() + ' ر.ي';
    }

    function filterItems() {
        let val = document.getElementById('searchBox').value.toLowerCase();
        let cards = document.getElementsByClassName('item-card');
        for (let card of cards) {
            let name = card.getAttribute('data-name');
            card.style.display = name.includes(val) ? "flex" : "none";
        }
    }

    function submitPurchCheckout() {
        if (supplierId === 0) { alert("⚠️ يرجى اختيار المورد أولاً!"); return; }
        if (purchCart.length === 0) { alert("⚠️ سلة المشتريات فارغة!"); return; }
        document.getElementById('form_purch_json').value = JSON.stringify(purchCart);
        document.getElementById('purchForm').submit();
    }
    </script>

    <?php include "nav_menu.php"; ?>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>
</html>

