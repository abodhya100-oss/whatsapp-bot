<?php
error_reporting(E_ALL & ~E_WARNING & ~E_DEPRECATED);
ini_set('display_errors', 0);

$conn = null;
if (file_exists('db.php')) { include_once 'db.php'; }
if (!isset($conn) || !$conn) { $conn = @new mysqli('localhost', 'root', '', 'vmax_db'); }

$clients = [];
if ($conn && !$conn->connect_error) {
    $res = $conn->query("SELECT * FROM clients");
    if ($res) { while ($row = $res->fetch_assoc()) { $clients[] = $row; } }
}

$selected_client_id = $_GET['client_id'] ?? ($clients[0]['id'] ?? 1);
$selected_client_name = "";
$client_invoices = [];
$client_receipts = [];
$total_inv_sum = 0;
$total_rec_sum = 0;

if ($conn && !$conn->connect_error) {
    $res_c = $conn->query("SELECT name FROM clients WHERE id = '$selected_client_id'");
    if ($res_c && $row_c = $res_c->fetch_assoc()) { $selected_client_name = $row_c['name']; }

    // جلب سندات القبض
    $res_rec = $conn->query("SELECT * FROM receipts WHERE client_id = '$selected_client_id' ORDER BY id DESC");
    if ($res_rec) {
        while ($rec = $res_rec->fetch_assoc()) {
            $client_receipts[] = $rec;
            $total_rec_sum += floatval($rec['amount']);
        }
    }

    // جلب الفواتير وفحص ما إذا تم سدادها كلياً أو جزئياً
    $res_inv = $conn->query("SELECT * FROM invoices WHERE client_id = '$selected_client_id' ORDER BY id DESC");
    if ($res_inv) {
        while ($inv = $res_inv->fetch_assoc()) {
            $total_inv_sum += floatval($inv['total_amount']);
            
            // حساب كم تم سداد هذه الفاتورة تحديداً
            $inv_id = $inv['id'];
            $res_p = $conn->query("SELECT SUM(amount) as paid FROM receipts WHERE invoice_id = $inv_id");
            $row_p = $res_p ? $res_p->fetch_assoc() : ['paid' => 0];
            $paid_amt = floatval($row_p['paid'] ?? 0);
            $remaining = floatval($inv['total_amount']) - $paid_amt;

            // إذا لم يتم سداد الفاتورة بالكامل (المتبقي أكبر من صفر)، أضفها للعرض
            if ($remaining > 0.01) {
                $inv['paid_amount'] = $paid_amt;
                $inv['remaining_amount'] = $remaining;
                $client_invoices[] = $inv;
            }
        }
    }
}

$net_balance_due = $total_inv_sum - $total_rec_sum;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <link rel="manifest" href="manifest.json">
    <meta name='mobile-web-app-capable' content='yes'>
    <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js");}</script>
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>كشف حساب العميل - أبو ضياء</title>
    <style>
        * { box-sizing: border-box; font-family: 'Tahoma', Arial, sans-serif; }
        body { background: #FFFFFF; margin: 0; padding: 10px; display: flex; justify-content: center; flex-direction: column; align-items: center; }
        .top-nav { width: 100%; max-width: 750px; background: #FFFFFF; padding: 8px 12px; border-radius: 6px; display: flex; justify-content: space-between; gap: 5px; margin-bottom: 10px; flex-wrap: wrap; }
        .nav-link { color: #fff; text-decoration: none; font-size: 11px; font-weight: bold; padding: 6px 10px; border-radius: 4px; background: #FFFFFF; }
        .nav-link.active { background: #FFFFFF; }

        .container { background: #FFFFFF; width: 100%; max-width: 750px; padding: 20px; border: 2px solid #000; border-radius: 6px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #000; border-bottom: 2px solid #000; padding-bottom: 8px; margin-top: 0; font-size: 18px; }
        
        .client-select-bar { display: flex; gap: 10px; align-items: center; margin-bottom: 15px; background: #FFFFFF; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; }
        select { flex: 1; padding: 10px; border: 2px solid #000; border-radius: 4px; font-size: 14px; font-weight: bold; background: #FFFFFF; }

        .summary-box { display: flex; justify-content: space-between; background: #FFFFFF; color: #fff; padding: 12px; border-radius: 6px; margin-bottom: 20px; text-align: center; font-size: 12px; font-weight: bold; gap: 5px; }
        .summary-box div { flex: 1; background: #FFFFFF; padding: 8px; border-radius: 4px; }
        .summary-box span { display: block; font-size: 14px; margin-top: 4px; color: #38bdf8; }

        .section-title { font-size: 15px; border-bottom: 2px solid #000; padding-bottom: 5px; margin-top: 20px; margin-bottom: 12px; font-weight: bold; }

        .invoice-card { background: #FFFFFF; border: 2px solid #000; border-radius: 6px; margin-bottom: 15px; padding: 12px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .invoice-card.partial { background: #FFFFFF; border: 2px solid #ca8a04; }
        
        .inv-header { display: flex; justify-content: space-between; background: #FFFFFF; color: #fff; padding: 6px 10px; border-radius: 4px; font-size: 13px; font-weight: bold; margin-bottom: 8px; }
        .invoice-card.partial .inv-header { background: #FFFFFF; }

        .inv-table { width: 100%; border-collapse: collapse; font-size: 11px; margin-bottom: 8px; }
        .inv-table th, .inv-table td { border: 1px solid #000; padding: 5px; text-align: center; }
        .invoice-card.partial .inv-table th, .invoice-card.partial .inv-table td { border-color: #ca8a04; }
        .inv-table th { background: #FFFFFF; color: #000; }
        .invoice-card.partial .inv-table th { background: #FFFFFF; color: #713f12; }

        .inv-footer { display: border-box; border-top: 1px dashed #000; padding-top: 8px; font-size: 12px; font-weight: bold; }
        .invoice-card.partial .inv-footer { border-top: 1px dashed #ca8a04; }
        
        .inv-totals-row { display: flex; justify-content: space-between; margin-bottom: 6px; }
        .btn-receipt { background: #FFFFFF; color: #fff; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: bold; display: inline-block; text-align: center; width: 100%; margin-top: 5px; }
        
        .partial-badge { background: #FFFFFF; color: #fff; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: bold; }

        .receipt-item { background: #FFFFFF; border: 2px solid #16a34a; padding: 10px; border-radius: 6px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; font-size: 13px; font-weight: bold; color: #166534; }
        .no-data { text-align: center; padding: 20px; font-weight: bold; color: #666; font-size: 13px; }
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

<div class="top-nav">
    <a href="index.php" class="nav-link">📄 فاتورة مبيعات</a>
    <a href="client_ledger.php" class="nav-link active">👥 العملاء والفواتير</a>
    <a href="receipt.php" class="nav-link">📥 سند قبض</a>
    <a href="payment.php" class="nav-link">📤 سند صرف</a>
    <a href="journal.php" class="nav-link">⚖️ قيد يومية</a>
    <a href="settings.php" class="nav-link">⚙️ الإعدادات</a>
</div>

<div class="container">
    <h2>👥 كشف حساب العميل والملخص المالي</h2>

    <form method="GET" class="client-select-bar">
        <label style="font-weight: bold; font-size: 13px;">اختر العميل:</label>
        <select name="client_id" onchange="this.form.submit()">
            <?php foreach ($clients as $c): ?>
                <option value="<?php echo $c['id']; ?>" <?php echo ($c['id'] == $selected_client_id) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($c['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <div class="summary-box">
        <div>إجمالي الفواتير: <span style="color:#f87171;"><?php echo number_format($total_inv_sum, 2); ?> ر.ي</span></div>
        <div>إجمالي المقبوضات: <span style="color:#4ade80;"><?php echo number_format($total_rec_sum, 2); ?> ر.ي</span></div>
        <div>الصافي عليكم: <span style="color:#facc15; font-size:15px;"><?php echo number_format($net_balance_due, 2); ?> ر.ي</span></div>
    </div>

    <div class="section-title">📄 الفواتير المتبقية (غير المسددة بالكامل) للعميل: <span style="color: #2563eb;"><?php echo htmlspecialchars($selected_client_name); ?></span></div>

    <?php if (empty($client_invoices)): ?>
        <div class="no-data">🎉 رائع جداً! لا توجد فواتير متبقية على هذا العميل، جميع فواتيرهم مسددة بالكامل.</div>
    <?php else: ?>
        <?php foreach ($client_invoices as $inv): 
            $is_partial = ($inv['paid_amount'] > 0);
            $items_raw = isset($inv['invoice_details']) ? $inv['invoice_details'] : '[]';
            $items = json_decode($items_raw, true) ?? [['name' => 'مبيعات بضاعة عامة', 'unit' => 'حبة', 'qty' => 1, 'price' => $inv['total_amount'], 'total' => $inv['total_amount']]];
        ?>
            <div class="invoice-card <?php echo $is_partial ? 'partial' : ''; ?>">
                <div class="inv-header">
                    <span>📄 فاتورة رقم: #<?php echo $inv['id']; ?></span>
                    <?php if ($is_partial): ?>
                        <span class="partial-badge">مسدد جزء منها ⚠️</span>
                    <?php endif; ?>
                    <span>التاريخ: <?php echo $inv['created_at']; ?></span>
                </div>
                
                <table class="inv-table">
                    <thead>
                        <tr>
                            <th>م</th>
                            <th>اسم الصنف</th>
                            <th>الوحدة</th>
                            <th>العدد</th>
                            <th>السعر</th>
                            <th>الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $idx => $it): ?>
                        <tr>
                            <td><?php echo $idx + 1; ?></td>
                            <td style="text-align: right;"><?php echo htmlspecialchars($it['name'] ?? 'صنف'); ?></td>
                            <td><?php echo htmlspecialchars($it['unit'] ?? 'حبة'); ?></td>
                            <td><?php echo number_format($it['qty'] ?? 1); ?></td>
                            <td><?php echo number_format($it['price'] ?? 0, 2); ?></td>
                            <td><?php echo number_format($it['total'] ?? 0, 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="inv-footer">
                    <div class="inv-totals-row">
                        <span>إجمالي الفاتورة: <?php echo number_format($inv['total_amount'], 2); ?> ر.ي</span>
                        <?php if ($is_partial): ?>
                            <span style="color: #15803d;">المدفوع: <?php echo number_format($inv['paid_amount'], 2); ?> ر.ي</span>
                        <?php endif; ?>
                        <span style="color: #b91c1c; font-size: 14px;">المتبقي: <?php echo number_format($inv['remaining_amount'], 2); ?> ر.ي</span>
                    </div>
                    <a href="receipt.php?client_id=<?php echo $selected_client_id; ?>&invoice_id=<?php echo $inv['id']; ?>&amount=<?php echo $inv['remaining_amount']; ?>" class="btn-receipt">📥 عمل سند قبض بالمبلغ المتبقي (<?php echo number_format($inv['remaining_amount'], 2); ?> ر.ي)</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="section-title">📥 سندات القبض المدفوعة من العميل</div>
    <?php if (empty($client_receipts)): ?>
        <div class="no-data">لا توجد سندات قبض مسجلة لهذا العميل حتى الآن.</div>
    <?php else: ?>
        <?php foreach ($client_receipts as $rec): ?>
            <div class="receipt-item">
                <div>
                    <div>📥 سند قبض رقم: #<?php echo $rec['id']; ?> <?php echo !empty($rec['invoice_id']) ? '(مرتبط بالفاتورة #' . $rec['invoice_id'] . ')' : '(سند عام)'; ?> - (<?php echo htmlspecialchars($rec['payment_method']); ?>)</div>
                    <div style="font-size: 11px; color: #166534; font-weight: normal; margin-top: 3px;"><?php echo htmlspecialchars($rec['notes']); ?> - <?php echo $rec['created_at']; ?></div>
                </div>
                <div style="font-size: 15px; color: #15803d;"><?php echo number_format($rec['amount'], 2); ?> ر.ي</div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>


<?php include 'nav_bar.php'; ?>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>
</html>

