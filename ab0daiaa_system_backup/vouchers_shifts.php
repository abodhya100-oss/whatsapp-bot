<?php
// vouchers_shifts.php - سندات القبض والصرف وإغلاق الورديات - V-MAX PRO
require_once 'auth.php';
$db = new SQLite3(__DIR__ . '/vmax.db');

if (isset($_POST['action']) && $_POST['action'] === 'add_voucher') {
    $type = $_POST['type'];
    $cust_id = intval($_POST['customer_id'] ?? 0);
    $amount = floatval($_POST['amount']);
    $notes = trim($_POST['notes'] ?? '');
    $date = date('Y-m-d H:i:s');

    $stmt = $db->prepare("INSERT INTO vouchers (type, customer_id, amount, notes, date) VALUES (:t, :c, :a, :n, :d)");
    $stmt->bindValue(':t', $type);
    $stmt->bindValue(':c', $cust_id);
    $stmt->bindValue(':a', $amount);
    $stmt->bindValue(':n', $notes);
    $stmt->bindValue(':d', $date);
    $stmt->execute();
    header("Location: vouchers_shifts.php");
    exit;
}

$customers = [];
$res = $db->query("SELECT * FROM customers ORDER BY name ASC");
while($row = $res->fetchArray(SQLITE3_ASSOC)) { $customers[] = $row; }

$vouchers = [];
$res_v = $db->query("SELECT v.*, c.name as customer_name FROM vouchers v LEFT JOIN customers c ON v.customer_id = c.id ORDER BY v.id DESC LIMIT 20");
while($row_v = $res_v->fetchArray(SQLITE3_ASSOC)) { $vouchers[] = $row_v; }

$today = date('Y-m-d');
$total_sales_today = @$db->querySingle("SELECT SUM(grand_total) FROM invoices WHERE date LIKE '$today%'") ?: 0;
$total_expenses_today = @$db->querySingle("SELECT SUM(amount) FROM expenses WHERE date LIKE '$today%'") ?: 0;
$total_receipts_today = @$db->querySingle("SELECT SUM(amount) FROM vouchers WHERE type='receipt' AND date LIKE '$today%'") ?: 0;

$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><meta name='mobile-web-app-capable' content='yes'>
<meta name='apple-mobile-web-app-capable' content='yes'>

    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>السندات وإغلاق الورديات - V-MAX PRO</title>
    <style>
        :root {
            --bg: #030712;
            --surface: rgba(15, 23, 42, 0.85);
            --border: rgba(56, 189, 248, 0.25);
            --primary: #38bdf8;
            --accent: #fbbf24;
            --success: #10b981;
            --danger: #f43f5e;
            --text: #f8fafc;
            --muted: #94a3b8;
        }
        * { box-sizing: border-box; }
        body { font-family: Tahoma, sans-serif; background: var(--bg); margin: 0; padding: 12px; color: var(--text); min-height: 100vh; }
        .wrap { max-width: 650px; margin: 0 auto 90px auto; }
        .card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 16px; margin-bottom: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
        h2, h3 { margin-top: 0; color: var(--primary); font-size: 16px; }
        .form-control { width: 100%; padding: 10px; margin-bottom: 10px; border-radius: 10px; border: 1px solid var(--border); background: #1e293b; color: #fff; font-size: 12px; outline: none; }
        .btn { width: 100%; padding: 12px; border-radius: 10px; border: none; font-weight: bold; font-size: 13px; cursor: pointer; text-align: center; text-decoration: none; display: block; }
        .btn-primary { background: linear-gradient(135deg, #0284c7, #0369a1); color: #fff; }
        .voucher-item { display: flex; justify-content: space-between; align-items: center; background: #1e293b; padding: 10px; margin-bottom: 8px; border-radius: 10px; border-left: 4px solid var(--primary); }
        .shift-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; text-align: center; }
        .shift-box { background: #1e293b; padding: 12px; border-radius: 10px; border: 1px solid var(--border); }
        .shift-val { font-size: 18px; font-weight: bold; color: var(--accent); margin-top: 4px; }
    </style>
</head>
<body>

<div class="wrap">
    <div class="card">
        <h3>⚡ تقرير الوردية والدرج (اليوم)</h3>
        <div class="shift-grid">
            <div class="shift-box">
                <div style="font-size:11px; color:var(--muted);">إجمالي المبيعات</div>
                <div class="shift-val" style="color:var(--success);"><?php echo number_format($total_sales_today, 2); ?></div>
            </div>
            <div class="shift-box">
                <div style="font-size:11px; color:var(--muted);">سندات القبض</div>
                <div class="shift-val" style="color:var(--primary);"><?php echo number_format($total_receipts_today, 2); ?></div>
            </div>
            <div class="shift-box">
                <div style="font-size:11px; color:var(--muted);">المصروفات النقدية</div>
                <div class="shift-val" style="color:var(--danger);"><?php echo number_format($total_expenses_today, 2); ?></div>
            </div>
            <div class="shift-box">
                <div style="font-size:11px; color:var(--muted);">صافي النقدية بالدرج</div>
                <div class="shift-val"><?php echo number_format(($total_sales_today + $total_receipts_today) - $total_expenses_today, 2); ?></div>
            </div>
        </div>
        <button class="btn btn-primary" style="margin-top:12px;" onclick="window.print()">🖨️ طباعة تقرير الوردية الحراري</button>
    </div>

    <div class="card">
        <h3>💰 تسجيل سند (قبض / صرف)</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add_voucher">
            
            <label style="font-size:11px; color:var(--muted); font-weight:bold;">نوع السند</label>
            <select name="type" class="form-control">
                <option value="receipt">سند قبض (استلام أموال من عميل)</option>
                <option value="payment">سند صرف (دفع أموال لمورد أو مصروف)</option>
            </select>

            <label style="font-size:11px; color:var(--muted); font-weight:bold;">اختر العميل (اختياري)</label>
            <select name="customer_id" class="form-control">
                <option value="0">-- بدون عميل محدد --</option>
                <?php foreach($customers as $c): ?>
                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                <?php endforeach; ?>
            </select>

            <label style="font-size:11px; color:var(--muted); font-weight:bold;">المبلغ</label>
            <input type="number" step="0.01" name="amount" class="form-control" placeholder="0.00" required>

            <label style="font-size:11px; color:var(--muted); font-weight:bold;">البيان / ملاحظات</label>
            <input type="text" name="notes" class="form-control" placeholder="سبب القبض أو الصرف...">

            <button type="submit" class="btn btn-primary">حفظ وتسجيل السند</button>
        </form>
    </div>

    <div class="card">
        <h3>📋 سجل السندات الأخيرة</h3>
        <?php if(empty($vouchers)): ?>
            <p style="text-align:center; color:var(--muted); font-size:12px;">لا توجد سندات مسجلة حتى الآن.</p>
        <?php else: ?>
            <?php foreach($vouchers as $v): ?>
                <?php 
                    $v_type = $v['type'] ?? 'receipt';
                    $v_amount = $v['amount'] ?? 0;
                    $v_notes = $v['notes'] ?? '';
                    $v_date = $v['date'] ?? '';
                    $cust_name = $v['customer_name'] ?? '';
                ?>
                <div class="voucher-item" style="border-left-color: <?php echo $v_type=='receipt'?'var(--success)':'var(--danger)'; ?>;">
                    <div>
                        <b style="font-size:12px;"><?php echo $v_type=='receipt'?'📥 سند قبض':'📤 سند صرف'; ?></b>
                        <?php if($cust_name): ?>
                            <br><small style="color:var(--muted);">العميل: <?php echo htmlspecialchars($cust_name); ?></small>
                        <?php endif; ?>
                        <?php if($v_notes): ?>
                            <br><small style="color:#cbd5e1;"><?php echo htmlspecialchars($v_notes); ?></small>
                        <?php endif; ?>
                    </div>
                    <div style="text-align:left;">
                        <span style="font-weight:bold; font-size:13px; color:<?php echo $v_type=='receipt'?'var(--success)':'var(--danger)'; ?>;">
                            <?php echo ($v_type=='receipt'?'+':'-') . number_format($v_amount, 2); ?>
                        </span>
                        <br><small style="font-size:9px; color:var(--muted);"><?php echo $v_date; ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <a href="index.php" class="btn" style="background:#334155; color:#fff;">🏠 العودة للرئيسية</a>
</div>

<?php if(file_exists('nav_menu.php')) { include 'nav_menu.php'; } ?>
</body>
</html>
