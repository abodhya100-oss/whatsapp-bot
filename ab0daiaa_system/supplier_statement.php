<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_role'])) { header("Location: login.php"); exit; }

$db = new SQLite3('/data/data/com.termux/files/home/secure_dbs/vmax.db');
$supplier_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

$supplier = $db->querySingle("SELECT * FROM suppliers WHERE id = $supplier_id", true);
if (!$supplier) { echo "❌ المورد غير موجود!"; exit; }

if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Supplier_' . preg_replace('/[^a-zA-Z0-9]/', '_', $supplier['name']) . '.csv');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, ['ID', 'التاريخ', 'نوع الحركة', 'البيان', 'مدين', 'دائن', 'الرصيد بعد']);
    
    $csv_where = "supplier_id = $supplier_id";
    if (!empty($date_from) && !empty($date_to)) {
        $csv_where .= " AND date BETWEEN '$date_from' AND '$date_to 23:59:59'";
    }
    $res = $db->query("SELECT * FROM supplier_vouchers WHERE $csv_where ORDER BY id ASC");
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $is_credit = (mb_strpos($row['type'], 'سداد') !== false || mb_strpos($row['type'], 'صرف') !== false);
        $debit = $is_credit ? 0 : $row['amount'];
        $credit = $is_credit ? $row['amount'] : 0;
        fputcsv($output, [$row['id'], $row['date'], $row['type'], $row['note'], $debit, $credit, $row['new_balance']]);
    }
    fclose($output);
    exit;
}

$opening_balance = 0;
$where = "supplier_id = $supplier_id";
if (!empty($date_from)) {
    $prev_res = $db->query("SELECT new_balance FROM supplier_vouchers WHERE supplier_id = $supplier_id AND date < '$date_from 00:00:00' ORDER BY id DESC LIMIT 1");
    $prev_row = $prev_res ? $prev_res->fetchArray(SQLITE3_ASSOC) : false;
    if ($prev_row) { $opening_balance = $prev_row['new_balance']; }
    $where .= " AND date BETWEEN '$date_from' AND '" . (!empty($date_to) ? $date_to : $date_from) . " 23:59:59'";
} elseif (!empty($date_to)) {
    $where .= " AND date <= '$date_to 23:59:59'";
}

$vouchers_res = $db->query("SELECT * FROM supplier_vouchers WHERE $where ORDER BY id ASC");
$vouchers = [];
$total_debit_period = 0;
$total_credit_period = 0;
while ($row = $vouchers_res->fetchArray(SQLITE3_ASSOC)) { 
    $vouchers[] = $row; 
    $is_credit = (mb_strpos($row['type'], 'سداد') !== false || mb_strpos($row['type'], 'صرف') !== false);
    if ($is_credit) { $total_credit_period += $row['amount']; } else { $total_debit_period += $row['amount']; }
}

$closing_balance = $supplier['balance'];
if (!empty($vouchers)) {
    $last_v = end($vouchers);
    $closing_balance = $last_v['new_balance'];
} elseif (!empty($date_from)) {
    $closing_balance = $opening_balance;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <link rel="manifest" href="manifest.json">
    <meta name='mobile-web-app-capable' content='yes'>
    <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js");}</script>
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>كشف حساب المورد: <?php echo htmlspecialchars($supplier['name']); ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        body { font-family: Tahoma, sans-serif; background: #FFFFFF; color: #f8fafc; margin: 0; padding: 10px; padding-bottom: 160px; direction: rtl; }
        .glass-card { background: linear-gradient(135deg, rgba(15, 23, 42, 0.95), rgba(30, 41, 59, 0.98)); backdrop-filter: blur(25px); border: 1.5px solid rgba(56, 189, 248, 0.6); border-radius: 24px; padding: 20px; margin-bottom: 15px; box-shadow: 0 20px 40px rgba(0,0,0,0.8); }
        .header-title { text-align: center; font-size: 16px; font-weight: 900; color: #38bdf8; margin-bottom: 15px; }
        .filter-box { background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(56, 189, 248, 0.3); border-radius: 16px; padding: 12px; margin-bottom: 15px; display: flex; gap: 8px; align-items: center; }
        .filter-input { background: #FFFFFF; border: 1px solid rgba(56, 189, 248, 0.4); color: #fff; padding: 8px; border-radius: 10px; font-size: 11px; flex: 1; }
        .filter-btn { background: linear-gradient(135deg, #0284c7, #2563eb); color: #fff; border: none; padding: 8px 14px; border-radius: 10px; font-weight: bold; font-size: 11px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; border-radius: 14px; overflow: hidden; }
        th, td { padding: 9px 6px; text-align: right; font-size: 10px; border-bottom: 1px solid rgba(56, 189, 248, 0.2); }
        th { background: linear-gradient(135deg, #0284c7, #2563eb); color: #fff; font-weight: bold; }
        td { background: rgba(30, 41, 59, 0.5); color: #f8fafc; }
        tfoot td { background: rgba(15, 23, 42, 0.85) !important; font-weight: bold; color: #38bdf8 !important; border-top: 2px solid #0284c7; }
        .footer-section { margin-top: 15px; display: flex; gap: 10px; align-items: center; }
        .cyber-seal-mini { flex: 1; background: linear-gradient(135deg, rgba(15, 23, 42, 0.9), rgba(2, 132, 199, 0.15)); border: 1.5px solid rgba(56, 189, 248, 0.7); border-radius: 14px; padding: 10px 14px; display: flex; justify-content: space-between; align-items: center; }
        .seal-stamp-mini { border: 1.5px dashed rgba(56, 189, 248, 0.9); border-radius: 50%; width: 38px; height: 38px; display: flex; flex-direction: column; align-items: center; justify-content: center; background: rgba(56, 189, 248, 0.15); }
        .customer-sign-box { width: 120px; background: rgba(15, 23, 42, 0.9); border: 1.5px dashed rgba(56, 189, 248, 0.5); border-radius: 14px; padding: 10px; text-align: center; height: 55px; display: flex; flex-direction: column; justify-content: space-between; }
        .btn-action { background: linear-gradient(135deg, #2563eb, #0ea5e9); color: #fff; border: none; padding: 14px; border-radius: 16px; font-weight: bold; cursor: pointer; font-size: 13px; display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; }
        .btn-whatsapp { background: linear-gradient(135deg, #16a34a, #22c55e); }
        .btn-print { background: linear-gradient(135deg, #ca8a04, #eab308); color: #0f172a; }
        .btn-excel { background: linear-gradient(135deg, #0d9488, #14b8a6); }
        .printable-area { background: #ffffff !important; color: #0f172a !important; padding: 20px; border-radius: 16px; }
        .printable-area table th { background: #0284c7 !important; color: #ffffff !important; }
        .printable-area table td { background: #ffffff !important; color: #0f172a !important; border-bottom: 1px solid #cbd5e1 !important; }
        .printable-area tfoot td { background: #f0f9ff !important; color: #0284c7 !important; }
        .printable-area .cyber-seal-mini { background: #f0f9ff !important; border: 1.5px solid #0284c7 !important; }
        .printable-area .seal-stamp-mini { border: 1.5px dashed #0284c7 !important; background: #e0f2fe !important; }
        .printable-area .customer-sign-box { background: #f8fafc !important; border: 1.5px dashed #0284c7 !important; color: #0f172a !important; }
        @media print { body * { visibility: hidden; } #printableStatement, #printableStatement * { visibility: visible; } #printableStatement { position: absolute; top: 0; left: 0; width: 100%; } .no-print { display: none !important; } }
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
    <div class="no-print" style="display: flex; gap: 10px; margin-bottom: 12px; align-items: center;">
        <a href="suppliers.php" style="background: rgba(51,65,85,0.8); color: #fff; text-decoration: none; padding: 10px 16px; border-radius: 14px; font-weight: bold; font-size: 11px;">⬅️ عودة</a>
        <div class="header-title" style="margin: 0; flex: 1;">كشف حساب المورد التفصيلي</div>
    </div>

    <form method="GET" class="filter-box no-print">
        <input type="hidden" name="id" value="<?php echo $supplier_id; ?>">
        <div style="font-size: 10px; font-weight: bold; color: #38bdf8;">📅 من:</div>
        <input type="date" name="date_from" value="<?php echo $date_from; ?>" class="filter-input">
        <div style="font-size: 10px; font-weight: bold; color: #38bdf8;">إلى:</div>
        <input type="date" name="date_to" value="<?php echo $date_to; ?>" class="filter-input">
        <button type="submit" class="filter-btn">فلتر</button>
        <?php if(!empty($date_from)): ?>
            <a href="supplier_statement.php?id=<?php echo $supplier_id; ?>" style="color: #ef4444; font-size: 10px; text-decoration: none; font-weight: bold;">إلغاء</a>
        <?php endif; ?>
    </form>

    <div id="printableStatement" class="glass-card printable-area">
        <div style="border-bottom: 2px solid #0284c7; padding-bottom: 12px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <div style="font-size: 18px; font-weight: 900; color: #0284c7;">مؤسسة أبو ضياء التجارية</div>
                <div style="font-size: 9px; color: #94a3b8; margin-top: 1px;">إدارة حسابات الموردين المعتمدة</div>
            </div>
            <div style="text-align: left; display: flex; align-items: center; gap: 8px;">
                <div>
                    <div style="font-size: 11px; font-weight: bold; color: #0284c7;">كشف حساب مورد</div>
                    <div style="font-size: 8px; color: #64748b;"><?php echo date('Y-m-d H:i'); ?></div>
                </div>
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=50x50&data=Supp_<?php echo $supplier_id; ?>_Bal_<?php echo $supplier['balance']; ?>" alt="QR" style="width: 40px; height: 40px; border-radius: 4px; border: 1px solid #0284c7;">
            </div>
        </div>

        <div style="background: #FFFFFF; border-right: 4px solid #0284c7; padding: 12px; border-radius: 12px; margin-bottom: 15px;">
            <div style="font-size: 14px; font-weight: 900; color: #0f172a; margin-bottom: 4px;">🤝 المورد / الشركة: <?php echo htmlspecialchars($supplier['name']); ?></div>
            <div style="font-size: 11px; color: #334155;">📞 الجوال: <b><?php echo htmlspecialchars($supplier['phone']); ?></b> | 📍 العنوان: <b><?php echo htmlspecialchars($supplier['address'] ?? 'غير محدد'); ?></b></div>
            <div style="margin-top: 8px; font-size: 13px; font-weight: bold;">
                الرصيد الحالي للمورد: <span style="color: #dc2626; direction: ltr; display: inline-block;">
                    <?php echo number_format($supplier['balance'], 0); ?> ر.ي
                </span>
            </div>
        </div>

        <div style="font-weight: bold; color: #0284c7; margin-bottom: 8px; font-size: 12px;">📑 سجل الحركات والسندات:</div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>التاريخ</th>
                    <th>البيان والنوع</th>
                    <th>مدين</th>
                    <th>دائن (سداد)</th>
                    <th>الرصيد بعد</th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($date_from)): ?>
                <tr style="background: rgba(2, 132, 199, 0.1);">
                    <td colspan="5" style="font-weight: bold; color: #0284c7;">الرصيد السابق:</td>
                    <td style="font-weight: bold; direction: ltr; color: #0284c7;"><?php echo number_format($opening_balance, 0); ?> ر.ي</td>
                </tr>
                <?php endif; ?>

                <?php if(empty($vouchers)): ?>
                <tr><td colspan="6" style="text-align: center; color: #64748b; padding: 20px;">لا توجد حركات مسجلة في هذه الفترة.</td></tr>
                <?php else: foreach($vouchers as $v): 
                    $is_credit = (mb_strpos($v['type'], 'سداد') !== false || mb_strpos($v['type'], 'صرف') !== false);
                    $debit_val = $is_credit ? 0 : $v['amount'];
                    $credit_val = $is_credit ? $v['amount'] : 0;
                ?>
                <tr>
                    <td>#<?php echo $v['id']; ?></td>
                    <td style="font-size: 9px; color: #64748b;"><?php echo $v['date']; ?></td>
                    <td><b><?php echo htmlspecialchars($v['type']); ?></b><br><span style="color:#94a3b8; font-size:9px;"><?php echo htmlspecialchars($v['note']); ?></span></td>
                    <td style="font-weight: bold; color: #ef4444; direction: ltr;"><?php echo $debit_val > 0 ? number_format($debit_val, 0) : '-'; ?></td>
                    <td style="font-weight: bold; color: #16a34a; direction: ltr;"><?php echo $credit_val > 0 ? number_format($credit_val, 0) : '-'; ?></td>
                    <td style="font-weight: bold; direction: ltr;"><?php echo number_format($v['new_balance'], 0); ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
            <tfoot>
                <?php if(!empty($vouchers)): ?>
                <tr>
                    <td colspan="3" style="text-align: left;">إجمالي حركات الفترة:</td>
                    <td style="direction: ltr; text-align: right; color: #ef4444;"><?php echo number_format($total_debit_period, 0); ?></td>
                    <td style="direction: ltr; text-align: right; color: #16a34a;"><?php echo number_format($total_credit_period, 0); ?></td>
                    <td></td>
                </tr>
                <?php endif; ?>
                <tr style="background: rgba(2, 132, 199, 0.2) !important;">
                    <td colspan="3" style="text-align: left; font-size: 11px; font-weight: 900; color: #38bdf8;">الرصيد الختامي للمورد:</td>
                    <td colspan="3" style="direction: ltr; text-align: right; font-size: 12px; font-weight: 900; color: #facc15;"><?php echo number_format($closing_balance, 0); ?> ر.ي</td>
                </tr>
            </tfoot>
        </table>

        <div class="footer-section">
            <div class="cyber-seal-mini">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div class="seal-stamp-mini">
                        <div style="font-size: 7px; font-weight: bold; color: #38bdf8; text-align: center;">معتمد<br>✓</div>
                    </div>
                    <div>
                        <div style="font-size: 7px; color: #94a3b8; font-weight: bold;">الاعتماد الرقمي</div>
                        <div style="font-size: 12px; font-weight: 900; color: #38bdf8;">أبو ضياء 👑</div>
                    </div>
                </div>
                <div style="text-align: left;">
                    <div style="font-size: 7px; color: #94a3b8; font-weight: bold;">تواصل معتمد</div>
                </div>
            </div>
            <div class="customer-sign-box">
                <div style="font-size: 8px; color: #94a3b8; font-weight: bold;">توقيع أو بصمة المورد</div>
                <div style="font-size: 8px; color: #cbd5e1;">ختم / بصمة</div>
            </div>
        </div>
        
        <div style="margin-top: 12px; text-align: center; font-size: 9px; color: #94a3b8; font-weight: bold; border-top: 1px dashed #cbd5e1; padding-top: 8px;">
            تم استخراج هذا الكشف واعتماده رسمياً من مؤسسة أبو ضياء التجارية.
        </div>
    </div>

    <div class="no-print" style="display: flex; flex-direction: column; gap: 10px; margin-top: 15px;">
        <a href="supplier_statement.php?id=<?php echo $supplier_id; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>&export=csv" class="btn-action btn-excel">
            📊 تحميل كشف الحساب Excel (CSV)
        </a>
        <button onclick="window.print()" class="btn-action btn-print">
            🖨️ طباعة كشف الحساب الفورية
        </button>
    </div>

<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>
</html>

