<?php
error_reporting(E_ALL & ~E_WARNING & ~E_DEPRECATED);
ini_set('display_errors', 0);

$conn = @new mysqli('localhost', 'root', '', 'vmax_db');
if ($conn && !$conn->connect_error) {
    $conn->query("CREATE TABLE IF NOT EXISTS client_returns (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT,
        invoice_id INT NULL,
        total_amount DECIMAL(10,2),
        invoice_details TEXT,
        notes TEXT,
        created_at DATETIME
    )");
}

$clients = [];
if ($conn && !$conn->connect_error) {
    $res = $conn->query("SELECT * FROM clients");
    if ($res) { while ($row = $res->fetch_assoc()) { $clients[] = $row; } }
}
if (empty($clients)) { $clients = [['id' => 1, 'name' => 'بقالة مريمة - الستين']]; }

$success = false;
$saved_id = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = intval($_POST['client_id']);
    $invoice_id = !empty($_POST['invoice_id']) ? intval($_POST['invoice_id']) : NULL;
    $inv_sql = $invoice_id !== NULL ? $invoice_id : "NULL";
    $notes = $conn->real_escape_string($_POST['notes']);
    
    $item_names = $_POST['item_name'] ?? ['بضاعة مرتجعة'];
    $units = $_POST['unit'] ?? ['حبة'];
    $quantities = $_POST['quantity'] ?? [1];
    $prices = $_POST['price'] ?? [0];

    $total_amount = 0;
    $items_list = [];
    for ($i = 0; $i < count($item_names); $i++) {
        $name = $item_names[$i];
        $unit = $units[$i] ?? 'حبة';
        $qty = floatval($quantities[$i] ?? 1);
        $price = floatval($prices[$i] ?? 0);
        $row_total = $qty * $price;
        $total_amount += $row_total;
        $items_list[] = ['name' => $name, 'unit' => $unit, 'qty' => $qty, 'price' => $price, 'total' => $row_total];
    }

    if ($conn && !$conn->connect_error) {
        $items_json = json_encode($items_list, JSON_UNESCAPED_UNICODE);
        $conn->query("INSERT INTO client_returns (client_id, invoice_id, total_amount, invoice_details, notes, created_at) VALUES ($client_id, $inv_sql, '$total_amount', '$items_json', '$notes', NOW())");
        $saved_id = $conn->insert_id;
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <link rel="manifest" href="manifest.json">
    <meta name='mobile-web-app-capable' content='yes'>
    <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js");}</script>
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>مرتجع مبيعات العميل - أبو ضياء</title>
    <style>
        * { box-sizing: border-box; font-family: 'Tahoma', Arial, sans-serif; }
        body { background: #e2e8f0; margin: 0; padding: 10px; display: flex; justify-content: center; flex-direction: column; align-items: center; }
        .top-nav { width: 100%; max-width: 750px; background: #000; padding: 8px 12px; border-radius: 6px; display: flex; justify-content: space-between; gap: 5px; margin-bottom: 10px; flex-wrap: wrap; }
        .nav-link { color: #fff; text-decoration: none; font-size: 11px; font-weight: bold; padding: 6px 8px; border-radius: 4px; background: #333; }
        .nav-link.active { background: #2563eb; }

        .form-container { background: #fff; width: 100%; max-width: 750px; padding: 15px; border: 2px solid #000; border-radius: 6px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #000; border-bottom: 2px solid #000; padding-bottom: 8px; margin-top: 0; font-size: 16px; }
        label { display: block; font-weight: bold; margin-bottom: 3px; color: #000; font-size: 12px; }
        select, input, textarea { width: 100%; padding: 8px; border: 2px solid #000; border-radius: 4px; font-size: 13px; font-weight: bold; background: #fff; margin-bottom: 10px; }
        textarea { resize: vertical; height: 50px; }
        
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; border: 2px solid #000; }
        .items-table th, .items-table td { border: 1px solid #000; padding: 6px; text-align: center; font-size: 12px; }
        .items-table th { background: #000; color: #fff; }
        .items-table input { border: 1px solid #ccc; padding: 4px; font-size: 12px; text-align: center; margin-bottom: 0; }
        
        .btn { padding: 8px 12px; border: none; border-radius: 4px; font-size: 13px; font-weight: bold; cursor: pointer; color: white; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 5px; }
        .btn-add { background: #2563eb; width: 100%; margin-bottom: 10px; }
        .btn-submit { background: #b91c1c; width: 100%; padding: 12px; font-size: 15px; }
        .btn-del { background: #dc2626; padding: 3px 8px; font-size: 11px; }
        .total-preview { font-size: 14px; font-weight: bold; text-align: left; margin-bottom: 10px; color: #b91c1c; }
        .alert { background: #fee2e2; color: #991b1b; padding: 10px; border: 2px solid #dc2626; border-radius: 4px; text-align: center; font-weight: bold; margin-bottom: 10px; font-size: 13px; }
    </style>
</head>
<body>

<div class="top-nav">
    <a href="index.php" class="nav-link">📄 مبيعات</a>
    <a href="purchase.php" class="nav-link">🛒 شراء</a>
    <a href="return.php" class="nav-link active">🔄 مرتجع</a>
    <a href="client_ledger.php" class="nav-link">👥 العملاء</a>
    <a href="receipt.php" class="nav-link">📥 قبض</a>
    <a href="payment.php" class="nav-link">📤 صرف</a>
    <a href="journal.php" class="nav-link">⚖️ قيود</a>
    <a href="settings.php" class="nav-link">⚙️ إعدادات</a>
</div>

<div class="form-container">
    <h2>🔄 مرتجع مبيعات (بضاعة مرتجعة من العميل)</h2>
    
    <?php if ($success): ?>
        <div class="alert">✅ تم حفظ سند المرتجع رقم #<?php echo $saved_id; ?> وتخفيض المبلغ من رصيد المديونية على العميل بنجاح!</div>
    <?php endif; ?>

    <form method="POST">
        <label>اختر العميل:</label>
        <select name="client_id" required>
            <?php foreach ($clients as $c): ?>
                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
            <?php endforeach; ?>
        </select>

        <label>رقم الفاتورة الأصلية (اختياري):</label>
        <input type="text" name="invoice_id" placeholder="مثال: 38">

        <label>أصناف المرتجع:</label>
        <table class="items-table" id="itemsTable">
            <thead>
                <tr>
                    <th style="width: 40%;">اسم الصنف</th>
                    <th style="width: 15%;">الوحدة</th>
                    <th style="width: 15%;">العدد</th>
                    <th style="width: 15%;">السعر</th>
                    <th style="width: 10%;">حذف</th>
                </tr>
            </thead>
            <tbody id="itemsBody">
                <tr>
                    <td><input type="text" name="item_name[]" value="بفك وشبس تالف / مرتجع" required></td>
                    <td><input type="text" name="unit[]" value="كيس" required></td>
                    <td><input type="number" name="quantity[]" value="2" min="1" class="qty" required oninput="calcTotal()"></td>
                    <td><input type="number" step="0.01" name="price[]" value="4500" class="price" required oninput="calcTotal()"></td>
                    <td><button type="button" class="btn btn-del" onclick="removeRow(this)">✕</button></td>
                </tr>
            </tbody>
        </table>

        <button type="button" class="btn btn-add" onclick="addRow('', 'حبة', 1, 0)">➕ إضافة صنف مرتجع</button>
        
        <label>سبب المرتجع / ملاحظات:</label>
        <textarea name="notes">إرجاع بضاعة غير مسحوبة أو تالفة</textarea>

        <div class="total-preview" id="grandTotalText">إجمالي المبلغ المرتجع: 9,000 ر.ي</div>
        <button type="submit" class="btn-submit">💾 اعتماد مرتجع المبيعات وخصم الرصيد</button>
    </form>
</div>

<script>
    function addRow(name = '', unit = 'حبة', qty = 1, price = 0) {
        const tbody = document.getElementById('itemsBody');
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><input type="text" name="item_name[]" value="${name}" placeholder="اسم الصنف" required></td>
            <td><input type="text" name="unit[]" value="${unit}" required></td>
            <td><input type="number" name="quantity[]" value="${qty}" min="1" class="qty" required oninput="calcTotal()"></td>
            <td><input type="number" step="0.01" name="price[]" value="${price}" class="price" required oninput="calcTotal()"></td>
            <td><button type="button" class="btn btn-del" onclick="removeRow(this)">✕</button></td>
        `;
        tbody.appendChild(tr);
        calcTotal();
    }
    function removeRow(btn) {
        const tbody = document.getElementById('itemsBody');
        if (tbody.rows.length > 1) { btn.closest('tr').remove(); calcTotal(); }
        else { alert("يجب أن يحتوي المرتجع على صنف واحد على الأقل!"); }
    }
    function calcTotal() {
        let sum = 0;
        const qtys = document.querySelectorAll('.qty');
        const prices = document.querySelectorAll('.price');
        for (let i = 0; i < qtys.length; i++) {
            let q = parseFloat(qtys[i].value) || 0;
            let p = parseFloat(prices[i].value) || 0;
            sum += (q * p);
        }
        document.getElementById('grandTotalText').innerText = "إجمالي المبلغ المرتجع: " + sum.toLocaleString() + " ر.ي";
    }
</script>


<?php include 'nav_bar.php'; ?>
<?php include "nav_menu.php"; ?>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>
</html>

