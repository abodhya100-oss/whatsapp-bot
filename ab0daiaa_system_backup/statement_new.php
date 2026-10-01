<?php
$customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-t');

$dbFile = 'vmax.db';
if(!file_exists($dbFile)) { $dbFile = 'database.db'; }
$db = new SQLite3($dbFile);

$customerName = "اختر العميل";
$totalDebt = 0;
$phone = "غير مسجل";
$address = "غير مسجل";

$customersResult = $db->query("SELECT id, name, debt FROM customers ORDER BY name ASC");

if($customer_id > 0) {
    $stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->bindValue(1, $customer_id, SQLITE3_INTEGER);
    $res = $stmt->execute();
    if($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $customerName = $row['name'] ?? 'غير معروف';
        $totalDebt = $row['debt'] ?? 0;
        $phone = $row['phone'] ?? 'غير مسجل';
        $address = $row['address'] ?? 'غير مسجل';
    }
}

$transactions = [];
if($customer_id > 0) {
    $res = $db->query("PRAGMA table_info(invoices)");
    if($res) {
        $cols = []; while($r = $res->fetchArray(SQLITE3_ASSOC)) $cols[] = $r['name'];
        if(in_array('customer_id', $cols) || in_array('c_id', $cols)) {
            $cid_col = in_array('customer_id', $cols) ? 'customer_id' : 'c_id';
            $date_col = in_array('date', $cols) ? 'date' : (in_array('created_at', $cols) ? 'created_at' : "'1970-01-01'");
            $amt_col = in_array('total', $cols) ? 'total' : (in_array('net_total', $cols) ? 'net_total' : (in_array('amount', $cols) ? 'amount' : '0'));
            
            $q = $db->query("SELECT id, $date_col as t_date, $amt_col as t_amount FROM invoices WHERE $cid_col = $customer_id");
            while($row = $q->fetchArray(SQLITE3_ASSOC)) {
                $transactions[] = ['date' => $row['t_date'], 'details' => 'فاتورة مبيعات رقم ' . $row['id'], 'debit' => (float)$row['t_amount'], 'credit' => 0];
            }
        }
    }

    $res = $db->query("PRAGMA table_info(payments)");
    if($res) {
        $cols = []; while($r = $res->fetchArray(SQLITE3_ASSOC)) $cols[] = $r['name'];
        if(in_array('customer_id', $cols) || in_array('c_id', $cols)) {
            $cid_col = in_array('customer_id', $cols) ? 'customer_id' : 'c_id';
            $date_col = in_array('date', $cols) ? 'date' : (in_array('created_at', $cols) ? 'created_at' : "'1970-01-01'");
            $amt_col = in_array('amount', $cols) ? 'amount' : (in_array('total', $cols) ? 'total' : '0');
            $note_col = in_array('note', $cols) ? 'note' : (in_array('notes', $cols) ? 'notes' : "''");
            
            $q = $db->query("SELECT id, $date_col as t_date, $amt_col as t_amount, $note_col as t_note FROM payments WHERE $cid_col = $customer_id");
            while($row = $q->fetchArray(SQLITE3_ASSOC)) {
                $note = (!empty($row['t_note']) && $row['t_note'] != "''") ? ' - ' . $row['t_note'] : '';
                $transactions[] = ['date' => $row['t_date'], 'details' => 'سند قبض / دفعة رقم ' . $row['id'] . $note, 'debit' => 0, 'credit' => (float)$row['t_amount']];
            }
        }
    }
    usort($transactions, function($a, $b) { return strtotime($a['date']) - strtotime($b['date']); });
}

$display_tx = [];
$opening_balance = 0;
foreach($transactions as $tx) {
    $tx_date = substr($tx['date'], 0, 10);
    if ($tx_date < $from_date) {
        $opening_balance += $tx['debit'] - $tx['credit'];
    } elseif ($tx_date <= $to_date) {
        $display_tx[] = $tx;
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>كشف حساب - مؤسسة أبو ضياء</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    
    <style>
        body { font-family: 'Segoe UI', Tahoma, Arial, sans-serif; background-color: #f4f7fa; margin: 0; padding: 15px; padding-bottom: 90px; color: #333; }
        .filter-card { background: white; border-radius: 10px; padding: 15px; margin-bottom: 15px; border: 1px solid #e2e8f0; }
        .form-group { margin-bottom: 15px; }
        select, input[type="date"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; box-sizing: border-box; }
        .date-row { display: flex; gap: 10px; }
        .date-row div { flex: 1; }
        .btn-search { width: 100%; background: #16a34a; color: white; border: none; padding: 12px; border-radius: 8px; font-weight: bold; font-size: 15px; cursor: pointer; }
        
        .statement-card { background: white; border-radius: 10px; padding: 20px; border: 1px solid #e2e8f0; margin-bottom: 15px; }
        .st-header { display: flex; justify-content: space-between; border-bottom: 2px solid #1e3a8a; padding-bottom: 10px; margin-bottom: 15px; }
        .st-header h3 { margin: 0; color: #1e3a8a; font-size: 18px; }
        .st-header-info { font-size: 13px; color: #555; text-align: left; }
        
        .client-details-box { background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 15px; margin-bottom: 20px; }
        .client-details-title { color: #1e3a8a; font-size: 15px; font-weight: bold; margin-bottom: 10px; display: flex; align-items: center; gap: 5px; }
        .client-details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 13px; color: #475569; }
        .detail-item strong { color: #1e293b; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; text-align: center; font-size: 13px; }
        th { background-color: #1e3a8a; color: white; padding: 10px 5px; border: 1px solid #1e3a8a; }
        td { padding: 10px 5px; border: 1px solid #ddd; }
        .totals-row { font-weight: bold; background-color: #f8fafc; }
        .val-red { color: #dc2626; font-weight:bold; }
        .val-green { color: #16a34a; font-weight:bold; }
        
        .final-balance { border: 2px solid #1e3a8a; border-radius: 8px; padding: 15px; text-align: center; font-size: 16px; font-weight: bold; margin-bottom: 15px; background: #fff9c4; }
        .dev-footer { text-align: center; font-size: 11px; color: #999; border-top: 1px dashed #ddd; padding-top: 10px; }
        
        .action-btns { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; margin-bottom: 10px; }
        .btn-action { padding: 10px; border: none; border-radius: 6px; color: white; font-weight: bold; font-size: 13px; cursor: pointer; text-align: center; }
        .btn-pdf { background-color: #0284c7; }
        .btn-img { background-color: #d97706; }
        .btn-print { background-color: #475569; }
        .btn-whatsapp { width: 100%; background-color: #25D366; color: white; padding: 12px; border: none; border-radius: 8px; font-weight: bold; font-size: 14px; margin-bottom: 15px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; }
        
        .bottom-nav { position: fixed; bottom: 0; left: 0; right: 0; background: linear-gradient(180deg, #1e293b, #0f172a); display: flex; justify-content: space-around; padding: 12px 0; border-top-left-radius: 20px; border-top-right-radius: 20px; z-index: 1000;}
        .bottom-nav a { text-decoration: none; color: #94a3b8; font-size: 11px; text-align: center; flex: 1; display: flex; flex-direction: column; align-items: center; gap: 4px; }
        .bottom-nav a.active { color: white; }
        .bottom-nav a span { font-size: 20px; }
        
        @media print {
            body { background: white; padding: 0; }
            .filter-card, .action-btns, .btn-whatsapp, .bottom-nav { display: none !important; }
            .statement-card { border: none; padding: 0; margin: 0; box-shadow:none; }
        }
    </style>
</head>
<body>

    <div class="filter-card">
        <form method="GET" action="statement_new.php">
            <div class="form-group">
                <select name="customer_id" onchange="this.form.submit()">
                    <option value="0">اختر العميل...</option>
                    <?php while($c = $customersResult->fetchArray(SQLITE3_ASSOC)): ?>
                        <option value="<?php echo $c['id']; ?>" <?php if($c['id'] == $customer_id) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($c['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="date-row form-group">
                <div>
                    <label style="font-size: 12px; color: #555;">من تاريخ:</label>
                    <input type="date" name="from_date" value="<?php echo $from_date; ?>">
                </div>
                <div>
                    <label style="font-size: 12px; color: #555;">إلى تاريخ:</label>
                    <input type="date" name="to_date" value="<?php echo $to_date; ?>">
                </div>
            </div>
            <button type="submit" class="btn-search">🔍 عرض كشف الحساب</button>
        </form>
    </div>

    <div class="statement-card" id="capture-area">
        <div class="st-header">
            <h3>مؤسسة أبو ضياء للتوزيع</h3>
            <div class="st-header-info">
                الفترة: من <?php echo $from_date; ?> إلى <?php echo $to_date; ?><br>
                تاريخ الكشف: <?php echo date('Y-m-d'); ?>
            </div>
        </div>
        
        <div class="client-details-box">
            <div class="client-details-title">
                👤 بيانات العميل
            </div>
            <div class="client-details-grid">
                <div class="detail-item"><strong>الاسم:</strong> <span style="color:#0284c7; font-weight:bold;"><?php echo htmlspecialchars($customerName); ?></span></div>
                <div class="detail-item"><strong>رقم العميل:</strong> <?php echo $customer_id > 0 ? $customer_id : '-'; ?></div>
                <div class="detail-item"><strong>رقم الهاتف:</strong> <span dir="ltr"><?php echo htmlspecialchars($phone); ?></span></div>
                <div class="detail-item"><strong>الموقع/العنوان:</strong> <?php echo htmlspecialchars($address); ?></div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>التاريخ</th>
                    <th>البيان</th>
                    <th>مدين (له)</th>
                    <th>دائن (عليه)</th>
                    <th>الرصيد المتراكم</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $running_balance = $opening_balance;
                $period_debit = 0;
                $period_credit = 0;
                
                if($opening_balance != 0 || count($display_tx) > 0): 
                ?>
                    <tr style="background-color: #f1f5f9; font-weight: bold;">
                        <td colspan="2">الرصيد الافتتاحي (السابق)</td>
                        <td>-</td>
                        <td>-</td>
                        <td dir="ltr" class="val-red"><?php echo number_format($opening_balance, 2); ?></td>
                    </tr>
                <?php endif; ?>

                <?php 
                if(count($display_tx) > 0):
                    foreach($display_tx as $tx):
                        $running_balance += $tx['debit'] - $tx['credit'];
                        $period_debit += $tx['debit'];
                        $period_credit += $tx['credit'];
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars(substr($tx['date'], 0, 10)); ?></td>
                        <td style="text-align: right; font-size:12px;"><?php echo htmlspecialchars($tx['details']); ?></td>
                        <td class="val-red"><?php echo $tx['debit'] > 0 ? number_format($tx['debit'], 2) : '-'; ?></td>
                        <td class="val-green"><?php echo $tx['credit'] > 0 ? number_format($tx['credit'], 2) : '-'; ?></td>
                        <td dir="ltr" style="font-weight: bold;"><?php echo number_format($running_balance, 2); ?></td>
                    </tr>
                <?php 
                    endforeach;
                else:
                ?>
                    <tr>
                        <td colspan="5" style="padding: 20px; color: #777;">لا توجد حركات مسجلة لهذا العميل في هذه الفترة.</td>
                    </tr>
                <?php endif; ?>
                <tr class="totals-row">
                    <td colspan="2">إجمالي الفترة:</td>
                    <td class="val-red"><?php echo number_format($period_debit, 2); ?></td>
                    <td class="val-green"><?php echo number_format($period_credit, 2); ?></td>
                    <td>-</td>
                </tr>
            </tbody>
        </table>

        <div class="final-balance">
            💰 إجمالي الرصيد المتبقي: <span class="val-red"><?php echo number_format($totalDebt, 2); ?></span> ريال يمني
        </div>

        <div class="dev-footer">إدارة وتطوير: أبو ضياء 771458609</div>
    </div>

    <div class="action-btns">
        <button class="btn-action btn-pdf" onclick="generatePDF(false)">📄 تنزيل PDF</button>
        <button class="btn-action btn-img" onclick="saveAsImage()">🖼️ حفظ كصورة</button>
        <button class="btn-action btn-print" onclick="window.print()">🖨️ طباعة</button>
    </div>
    
    <button class="btn-whatsapp" onclick="shareViaWhatsApp()">
        <svg xmlns="http://www.w3.org/0000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/></svg>
        مشاركة ملف الكشف مباشرة عبر واتساب
    </button>

    <div style="height: 60px;"></div>

    <div class="bottom-nav">
        <a href="settings.php"><span>⚙️</span>الإعدادات</a>
        <a href="reports.php"><span>📊</span>التقارير</a>
        <a href="customers.php" class="active"><span>👥</span>العملاء</a>
        <a href="index.php"><span>🏠</span>الرئيسية</a>
        <a href="pos.php"><span>🧾</span>الفاتورة</a>
    </div>

    <script>
        async function saveAsImage() {
            const element = document.getElementById('capture-area');
            const canvas = await html2canvas(element, { scale: 2, useCORS: true, backgroundColor: '#ffffff' });
            const link = document.createElement('a');
            link.download = 'كشف_حساب_<?php echo addslashes($customerName); ?>.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
        }

        async function generatePDF(returnFile = false) {
            const { jsPDF } = window.jspdf;
            const element = document.getElementById('capture-area');
            const canvas = await html2canvas(element, { scale: 2, useCORS: true, backgroundColor: '#ffffff' });
            const imgData = canvas.toDataURL('image/jpeg', 0.9);
            const pdf = new jsPDF('p', 'mm', 'a4');
            const pdfWidth = pdf.internal.pageSize.getWidth();
            const pdfHeight = (canvas.height * pdfWidth) / canvas.width;
            pdf.addImage(imgData, 'JPEG', 0, 0, pdfWidth, pdfHeight);
            
            if (returnFile) {
                return pdf.output('blob');
            } else {
                pdf.save('كشف_حساب_<?php echo addslashes($customerName); ?>.pdf');
            }
        }

        async function shareViaWhatsApp() {
            try {
                const pdfBlob = await generatePDF(true);
                const file = new File([pdfBlob], "كشف_حساب_<?php echo addslashes($customerName); ?>.pdf", { type: "application/pdf" });

                if (navigator.canShare && navigator.canShare({ files: [file] })) {
                    await navigator.share({
                        files: [file],
                        title: 'كشف حساب',
                        text: 'مرفق كشف حساب من مؤسسة أبو ضياء للتوزيع. العميل: <?php echo addslashes($customerName); ?>'
                    });
                } else {
                    alert('متصفحك لا يدعم المشاركة المباشرة للملفات. سيتم تنزيل الكشف الآن لتقوم بإرساله يدوياً عبر الواتساب.');
                    generatePDF(false); 
                }
            } catch (error) {
                console.log('تم إلغاء المشاركة أو حدث خطأ:', error);
            }
        }
    </script>
</body>
</html>
