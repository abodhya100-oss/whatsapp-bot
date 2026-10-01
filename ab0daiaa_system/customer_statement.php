<?php
// customer_statement.php - كشف حساب العميل الذكي مع معالجة التواريخ الفارغة - V-MAX PRO
require_once 'auth.php';
$db_file = '/data/data/com.termux/files/home/secure_dbs/vmax.db';
$db = new SQLite3($db_file);

// --- الفحص الذكي لعمود التاريخ في جدول الفواتير ---
$check_col =$db->query("PRAGMA table_info(invoices)");
$has_date_col = false;
$date_column_name = 'created_at';
while ($row =$check_col->fetchArray(SQLITE3_ASSOC)) {
    if ($row['name'] === 'created_at') { $has_date_col = true; $date_column_name = 'created_at'; break; }
    if ($row['name'] === 'date') { $has_date_col = true; $date_column_name = 'date'; break; }
}
if (!$has_date_col) {$db->exec("ALTER TABLE invoices ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
    $date_column_name = 'created_at';
}

// --- الفحص الذكي لعمود التاريخ في جدول السندات ---
$check_receipts =$db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='receipts'")->fetchArray();
$rec_date_column_name = 'created_at';
if ($check_receipts) {
    $check_col_rec =$db->query("PRAGMA table_info(receipts)");
    $has_rec_date_col = false;
    while ($row =$check_col_rec->fetchArray(SQLITE3_ASSOC)) {
        if ($row['name'] === 'created_at') { $has_rec_date_col = true; $rec_date_column_name = 'created_at'; break; }
        if ($row['name'] === 'date') { $has_rec_date_col = true; $rec_date_column_name = 'date'; break; }
    }
    if (!$has_rec_date_col) {$db->exec("ALTER TABLE receipts ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
        $rec_date_column_name = 'created_at';
    }
}
// ----------------------------------------------------

// استقبال رقم العميل سواء من الرابط (GET) أو من الفورم (POST)
$selected_customer_id = intval($_GET['customer_id'] ?? $_POST['customer_id'] ?? 0);

$customer_info = null;
$statement_data = [];

// جلب قائمة العملاء للقائمة المنسدلة
$customers = [];
$res =$db->query("SELECT id, name, phone FROM customers ORDER BY name ASC");
while ($row =$res->fetchArray(SQLITE3_ASSOC)) {
    $customers[] =$row;
}

// إذا تم تحديد عميل، نقوم بجلب كشف الحساب
if ($selected_customer_id > 0) {
    // 1. جلب بيانات العميل
    $stmt_cust =$db->prepare("SELECT * FROM customers WHERE id = :id");
    $stmt_cust->bindValue(':id',$selected_customer_id, SQLITE3_INTEGER);
    $customer_info =$stmt_cust->execute()->fetchArray(SQLITE3_ASSOC);

    if ($customer_info) {
        // 2. جلب الفواتير (المديونية / عليه)
        $inv_stmt =$db->prepare("SELECT id, grand_total, paid_amount, {$date_column_name} as invoice_date FROM invoices WHERE customer_id = :id");
        $inv_stmt->bindValue(':id',$selected_customer_id, SQLITE3_INTEGER);
        $inv_res =$inv_stmt->execute();
        while($inv = $inv_res->fetchArray(SQLITE3_ASSOC)) {$debt = max(0, floatval($inv['grand_total']) - floatval($inv['paid_amount']));
            if ($debt > 0) {$statement_data[] = [
                    'date' => $inv['invoice_date'],
                    'desc' => 'فاتورة مبيعات رقم #' . $inv['id'],
                    'debit' => $debt, // عليه
                    'credit' => 0,    // له
                    'type' => 'invoice'
                ];
            }
        }

        // 3. جلب السندات (القبض = له / الصرف = عليه)
        if ($check_receipts) {
            $rec_stmt =$db->prepare("SELECT id, amount, type, notes, {$rec_date_column_name} as receipt_date FROM receipts WHERE customer_id = :id");
            $rec_stmt->bindValue(':id',$selected_customer_id, SQLITE3_INTEGER);
            $rec_res =$rec_stmt->execute();
            while($rec =$rec_res->fetchArray(SQLITE3_ASSOC)) {
                $debit = 0; $credit = 0;
                if ($rec['type'] == 'قبض') {
                    $credit = floatval($rec['amount']); // دفع لنا (له)
                } else {
                    $debit = floatval($rec['amount']);  // أخذ منا (عليه)
                }
                $statement_data[] = [
                    'date' => $rec['receipt_date'],
                    'desc' => 'سند ' . $rec['type'] . ' #' . $rec['id'] . (!empty($rec['notes']) ? ' ('.$rec['notes'].')' : ''),
                    'debit' => $debit,
                    'credit' => $credit,
                    'type' => 'receipt'
                ];
            }
        }

        // 4. ترتيب العمليات تصاعدياً حسب التاريخ مع تجاوز التواريخ الفارغة بأمان
        usort($statement_data, function($a, $b) {$timeA = !empty($a['date']) ? strtotime($a['date']) : 0;
            $timeB = !empty($b['date']) ? strtotime($b['date']) : 0;
            return $timeA -$timeB;
        });

        // 5. حساب الرصيد التراكمي
        $running_balance = 0;
        foreach ($statement_data as$key => $row) {$running_balance += $row['debit'];$running_balance -= $row['credit'];$statement_data[$key]['running_balance'] =$running_balance;
        }
    }
}
$db->close();

// تجهيز نص الواتساب إذا كان هناك كشف
$wa_text = "";
if ($customer_info) {$wa_text = "📊 *كشف حساب عميل*\n";
    $wa_text .= "🏢 *مؤسسة أبو ضياء للتوزيع*\n";
    $wa_text .= "━━━━━━━━━━━━━━━\n";
    $wa_text .= "👤 *العميل:* " . $customer_info['name'] . "\n";
    $wa_text .= "💰 *الرصيد النهائي المعتمد:* " . number_format($customer_info['balance'], 2) . " ر.ي\n";
    $wa_text .= "━━━━━━━━━━━━━━━\n";
    $wa_text .= "العمليات الأخيرة:\n";

    $recent_transactions = array_slice($statement_data, -10);
    foreach ($recent_transactions as$trans) {
        if ($trans['debit'] > 0) {$wa_text .= "🔴 عليه: " . number_format($trans['debit'], 2) . " (" . $trans['desc'] . ")\n";
        } elseif ($trans['credit'] > 0) {$wa_text .= "🟢 له: " . number_format($trans['credit'], 2) . " (" . $trans['desc'] . ")\n";
        }
    }
    $wa_text .= "━━━━━━━━━━━━━━━\n";
    $wa_text .= "🙏 نرجو مراجعة الكشف، وشكراً لتعاملكم معنا.";
    $customer_phone = !empty($customer_info['phone']) ? preg_replace('/[^0-9]/', '', $customer_info['phone']) : '';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>كشف حساب - V-MAX PRO</title>
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
        body { font-family: 'Tahoma', sans-serif; background: #FFFFFF; margin: 0; padding: 10px; color: var(--text-main); min-height: 100vh; padding-bottom: 85px; }
        .container { max-width: 700px; margin: 0 auto; }

        .header-box { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: 10px; padding: 12px; text-align: center; margin-bottom: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .header-box h2 { margin: 0; font-size: 16px; color: #1E3A8A; font-weight: bold; }

        .search-form { display: flex; gap: 6px; margin-bottom: 12px; background: var(--card-bg); padding: 10px; border-radius: 10px; border: 1px solid var(--card-border); }
        .search-form select { flex: 1; padding: 10px; border-radius: 8px; border: 1px solid var(--card-border); font-size: 12px; }
        .btn-search { padding: 10px 15px; background: var(--primary); color: #fff; border: none; border-radius: 8px; font-weight: bold; font-size: 12px; cursor: pointer; }

        .statement-card { background: #FFFFFF; border: 1px solid var(--card-border); border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }

        .cust-header { background: #FFFFFF; color: #FFFFFF; padding: 15px; text-align: center; }
        .cust-header h3 { margin: 0 0 5px 0; font-size: 16px; }
        .cust-header .balance { font-size: 22px; font-weight: bold; color: #F87171; }
        .cust-header .balance.zero { color: #34D399; }

        .table-responsive { overflow-x: auto; }
        .stmt-table { width: 100%; border-collapse: collapse; min-width: 500px; }
        .stmt-table th { background: #FFFFFF; font-size: 11px; padding: 8px; text-align: center; border-bottom: 2px solid var(--card-border); color: #475569; }
        .stmt-table td { padding: 8px; text-align: center; font-size: 11.5px; border-bottom: 1px solid #E2E8F0; }
        .stmt-table tr:hover { background: #FFFFFF; }

        .col-debit { color: #EF4444; font-weight: bold; }
        .col-credit { color: #10B981; font-weight: bold; }
        .col-desc { text-align: right !important; font-weight: bold; color: #1E3A8A; }
        .col-date { font-size: 10px; color: #64748B; }

        .actions-panel { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; padding: 12px; background: #FFFFFF; }
        .btn { padding: 12px; border-radius: 8px; font-weight: bold; font-size: 12px; border: none; cursor: pointer; text-align: center; text-decoration: none; color: #FFFFFF; display: flex; align-items: center; justify-content: center; gap: 5px; }
        .btn-print { background: #FFFFFF; }
        .btn-whatsapp { background: #FFFFFF; }

        /* التعديل الوحيد تم هنا فقط */
        .btn-close { background: #FFFFFF; grid-column: span 2; }

        @media print {
            body { background: #FFFFFF; padding: 0; }
            .container { border: none; box-shadow: none; padding: 0; max-width: 100%; }
            .search-form, .actions-panel, nav, .nav-menu { display: none !important; }
            .statement-card { border: none; box-shadow: none; }
            .cust-header { background: #FFFFFF; color: #000; border-bottom: 2px solid #000; }
            .cust-header h3 { color: #000; }
            .cust-header .balance { color: #000; }
        }
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
        <h2>📊 كشف حساب تفصيلي</h2>
    </div>

    <form method="GET" class="search-form">
        <select name="customer_id" required>
            <option value="">-- اختر العميل لاستعراض كشف الحساب --</option>
            <?php foreach($customers as$c): ?>
                <option value="<?php echo $c['id']; ?>" <?php echo ($selected_customer_id ==$c['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($c['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-search">🔍 استعراض</button>
    </form>

    <?php if($customer_info): ?>
    <div class="statement-card">
        <div class="cust-header">
            <h3>فاتورة حساب العميل: <?php echo htmlspecialchars($customer_info['name']); ?></h3>
            <div style="font-size: 11px; margin-bottom: 5px; color: #94A3B8;">الرصيد النهائي المعتمد:</div>
            <div class="balance <?php echo ($customer_info['balance'] == 0) ? 'zero' : ''; ?>">
                <?php echo number_format($customer_info['balance'], 2); ?> ر.ي
            </div>
        </div>

        <div class="table-responsive">
            <table class="stmt-table">
                <thead>
                    <tr>
                        <th style="width: 15%;">التاريخ</th>
                        <th style="width: 35%;">البيان / التفاصيل</th>
                        <th style="width: 15%;">عليه (مدين)</th>
                        <th style="width: 15%;">له (دائن)</th>
                        <th style="width: 20%;">الرصيد التراكمي</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($statement_data) > 0): ?>
                        <?php foreach($statement_data as$row): ?>
                        <tr>
                            <!-- معالجة ذكية للتاريخ الفارغ -->
                            <td class="col-date"><?php echo !empty($row['date']) ? date('Y-m-d', strtotime($row['date'])) : '---'; ?></td>
                            <td class="col-desc"><?php echo htmlspecialchars($row['desc']); ?></td>
                            <td class="col-debit"><?php echo $row['debit'] > 0 ? number_format($row['debit'], 2) : '-'; ?></td>
                            <td class="col-credit"><?php echo $row['credit'] > 0 ? number_format($row['credit'], 2) : '-'; ?></td>
                            <td style="font-weight: bold; color: #475569;"><?php echo number_format($row['running_balance'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="padding: 20px; color: #64748B;">لا توجد حركات سابقة (فواتير أو سندات) لهذا العميل.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="actions-panel">
            <button onclick="window.print()" class="btn btn-print">🖨️ طباعة كشف الحساب / PDF</button>
            <button onclick="sendStatementWhatsApp()" class="btn btn-whatsapp">💬 إرسال ملخص عبر واتساب</button>
            <!-- التعديل الوحيد تم هنا أيضاً -->
        </div>
    </div>
    <?php endif; ?>
</div>


<script>
function sendStatementWhatsApp() {
    let phone = "<?php echo $customer_phone ?? ''; ?>";
    let text = `<?php echo addslashes($wa_text ?? ''); ?>`;
    let url = "";
    if (phone) {
        url = "https://api.whatsapp.com/send?phone=" + phone + "&text=" + encodeURIComponent(text);
    } else {
        url = "https://api.whatsapp.com/send?text=" + encodeURIComponent(text);
    }
    window.open(url, '_blank');
}
</script>
</body>
</html>
