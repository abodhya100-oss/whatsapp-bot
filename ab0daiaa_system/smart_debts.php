<?php
// smart_debts.php - الديون الذكية ومتابعة الذمم (النسخة النهائية مع الطباعة الرسمية) - V-MAX PRO
require_once 'auth.php';
$db_file = '/data/data/com.termux/files/home/secure_dbs/vmax.db';
$db = new SQLite3($db_file);

$customers = [];$total_debts = 0;

$res =$db->query("SELECT * FROM customers ORDER BY name ASC");
if ($res) {
    while($row =$res->fetchArray(SQLITE3_ASSOC)) {
        $c_id =$row['id'];

        $inv_res = @$db->query("SELECT SUM(COALESCE(grand_total, total, 0) - COALESCE(paid_amount, 0)) as rem FROM invoices WHERE customer_id = $c_id");
        $inv_row = $inv_res ? $inv_res->fetchArray(SQLITE3_ASSOC) : null;
        $debt = floatval($inv_row['rem'] ?? 0);

        if ($debt <= 0 && isset($row['balance'])) {
            $debt = floatval($row['balance']);
        }

        if ($debt > 0.01) {
            $row['debt'] =$debt;
            $total_debts +=$debt;
            $customers[] =$row;
        }
    }
}
$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>الديون الذكية ومتابعة الذمم - V-MAX PRO</title>
    <style>
        :root {
            --bg-deep: #F1F5F9;
            --card-bg: #FFFFFF;
            --text-main: #0F172A;
            --text-muted: #64748B;
            --primary-blue: #2563EB;
            --danger: #E11D48;
            --success: #10B981;
            --border-color: #CBD5E1;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Tahoma', sans-serif;
            background: var(--bg-deep);
            color: var(--text-main);
            margin: 0;
            padding: 15px;
            direction: rtl;
            padding-bottom: 95px;
        }
        .container { max-width: 680px; margin: auto; }

        .header-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(241, 245, 249, 0.9));
            border: 1.5px solid var(--primary-blue);
            border-radius: 20px;
            padding: 18px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.06);
            margin-bottom: 15px;
        }
        .header-card h1 { margin: 0 0 5px 0; font-size: 18px; color: var(--primary-blue); }
        .header-card p { margin: 0; font-size: 11px; color: var(--text-muted); }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
        }
        .card h3 {
            color: var(--primary-blue);
            font-size: 14px;
            margin-top: 0;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .total-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            font-weight: bold;
            background: #FFFFFF;
            padding: 12px 15px;
            border-radius: 12px;
            margin-bottom: 12px;
            color: var(--danger);
            border: 1px solid #FECDD3;
        }
        .controls-row {
            display: flex;
            gap: 8px;
            margin-bottom: 10px;
        }
        .search-input {
            flex: 1;
            padding: 10px 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 11.5px;
            background: #FFFFFF;
            color: var(--text-main);
            outline: none;
        }
        .action-btn-top {
            background: var(--primary-blue);
            color: #fff;
            border: none;
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: bold;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .filter-buttons {
            display: flex;
            gap: 5px;
            margin-bottom: 12px;
        }
        .filter-btn {
            flex: 1;
            background: #F8FAFC;
            border: 1px solid var(--border-color);
            padding: 8px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: bold;
            color: #334155;
            cursor: pointer;
        }
        .filter-btn.active {
            background: var(--primary-blue);
            color: #fff;
            border-color: var(--primary-blue);
        }
        .btn-bulk {
            background: #059669;
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            color: #fff;
            border: none;
            font-weight: bold;
            font-size: 11.5px;
            cursor: pointer;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .btn-copy {
            background: #D97706;
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            color: #fff;
            border: none;
            font-weight: bold;
            font-size: 11.5px;
            cursor: pointer;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .btn-print {
            background: #0284C7;
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            color: #fff;
            border: none;
            font-weight: bold;
            font-size: 11.5px;
            cursor: pointer;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        th, td { padding: 10px 8px; text-align: center; font-size: 11.5px; border-bottom: 1px solid #E2E8F0; }
        th { background: #F8FAFC; color: var(--primary-blue); font-weight: bold; }
        .btn-wa {
            background: #10B981;
            color: #fff;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 10.5px;
            text-decoration: none;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            box-shadow: 0 2px 6px rgba(16,185,129,0.2);
        }
        .btn-wa:active { transform: scale(0.95); }

        /* تخصيص الطباعة */
        @media print {
            body { background: #FFFFFF; padding: 0; color: #000; }
            .bottom-nav, .controls-row, .filter-buttons, .btn-bulk, .btn-copy, .btn-print, .btn-wa, .header-card { display: none !important; }
            .container { max-width: 100%; padding: 0; }
            .card { border: none; box-shadow: none; padding: 0; }
            table { border: 1px solid #000; }
            th, td { border: 1px solid #000; color: #000; }
            th { background: #EEE !important; }
            .print-header { display: block !important; text-align: center; margin-bottom: 20px; }
        }
        .print-header { display: none; }
    </style>
</head>
<body>

<div class="container">
    <div class="print-header">
        <h2>مؤسسة أبو ضياء للتوزيع</h2>
        <h3>تقرير مديونيات العملاء الشامل</h3>
        <p style="font-size: 11px; color: #555;">تاريخ التقرير: <?php echo date('Y-m-d H:i'); ?></p>
        <hr>
    </div>

    <div class="header-card">
        <h1>🔔 الديون الذكية ومتابعة الذمم</h1>
        <p>متابعة الذمم المتأخرة وإرسال تذكيرات آلية عبر الواتساب</p>
    </div>

    <div class="card">
        <h3>
            <span>📋 قائمة العملاء أصحاب الذمم</span>
            <span style="font-size: 11px; color: var(--text-muted);">(<?php echo count($customers); ?> عميل)</span>
        </h3>

        <div class="total-box">
            <span>إجمالي الديون المعلقة لدى العملاء:</span>
            <span style="font-size: 15px;" dir="ltr"><?php echo number_format($total_debts, 2); ?> ر.ي</span>
        </div>

        <div class="controls-row">
            <input type="text" id="searchInput" class="search-input" placeholder="🔍 ابحث عن اسم العميل..." onkeyup="filterDebtsTable()">
            <button type="button" class="action-btn-top" onclick="toggleSortOrder()">🔃 ترتيب الأكبر</button>
        </div>

        <!-- أزرار الفلترة السريعة -->
        <div class="filter-buttons">
            <button type="button" class="filter-btn active" onclick="filterByAmount('all', this)">عرض الجميع</button>
            <button type="button" class="filter-btn" onclick="filterByAmount('50000', this)">ديون > 50,000</button>
            <button type="button" class="filter-btn" onclick="filterByAmount('100000', this)">ديون > 100,000</button>
        </div>

        <?php if(!empty($customers)): ?>
        <button type="button" class="btn-bulk" onclick="sendBulkReminders()">
            📢 إرسال تذكير جماعي عبر الواتساب (للجميع)
        </button>
        <button type="button" class="btn-copy" onclick="copyDebtReport()">
            📋 نسخ تقرير الديون كـ نص جاهز لمشاركته
        </button>
        <button type="button" class="btn-print" onclick="window.print()">
            🖨️ طباعة أو حفظ تقرير الديون (PDF)
        </button>
        <?php endif; ?>

        <div style="overflow-x: auto;">
            <table id="debtsTable">
                <thead>
                    <tr>
                        <th style="text-align: right;">اسم العميل</th>
                        <th>رقم الجوال</th>
                        <th>المبلغ المتبقي</th>
                        <th class="no-print">مطالبة واتساب</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($customers)): ?>
                    <tr>
                        <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 30px; font-weight: bold;">
                            لا توجد أي ديون متأخرة. جميع العملاء مسددون لحساباتهم! 🎉
                        </td>
                    </tr>
                    <?php else: foreach($customers as $c):$msg = "🏢 *مؤسسة أبو ضياء للتوزيع*\nعزيزنا العميل / {$c['name']}\nنود تذكيركم بأن لديكم رصيد متبقي مستحق الدفع بقيمة *[ " . number_format($c['debt'], 2) . " ر.ي ]*.\nنتمنى سرعة السداد ولكم جزيل الشكر والتقدير  ✨";
                    ?>
                    <tr class="debt-row" data-name="<?php echo htmlspecialchars($c['name']); ?>" data-debt="<?php echo $c['debt']; ?>">
                        <td style="font-weight: bold; color: var(--text-main); text-align: right;"><?php echo htmlspecialchars($c['name']); ?></td>
                        <td style="color: var(--text-muted);" dir="ltr"><?php echo htmlspecialchars($c['phone'] ?? 'غير متوفر'); ?></td>
                        <td style="color: var(--danger); font-weight: bold;" dir="ltr" class="debt-val"><?php echo number_format($c['debt'], 2); ?> ر.ي</td>
                        <td class="no-print">
                            <?php if(!empty($c['phone'])): 
                                $clean_phone = preg_replace('/[^0-9]/', '',$c['phone']);
                            ?>
                            <a href="https://api.whatsapp.com/send?phone=<?php echo $clean_phone; ?>&text=<?php echo urlencode($msg); ?>" target="_blank" class="btn-wa">💬 تذكير واتس</a>
                            <?php else: ?>
                            <span style="color: var(--text-muted); font-size: 10px;">لا يوجد رقم</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// تصفية وبحث سريع عن العملاء بالاسم
function filterDebtsTable() {
    let input = document.getElementById('searchInput');
    let filter = input.value.toLowerCase();
    let rows = document.getElementsByClassName('debt-row');

    for (let i = 0; i < rows.length; i++) {
        let name = rows[i].getAttribute('data-name').toLowerCase();
        if (name.indexOf(filter) > -1) {
            rows[i].style.display = "";
        } else {
            rows[i].style.display = "none";
        }
    }
}

// فلترة حسب حجم المبلغ
let currentMinAmount = 'all';
function filterByAmount(minAmt, btn) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    currentMinAmount = minAmt;

    let rows = document.getElementsByClassName('debt-row');
    for (let i = 0; i < rows.length; i++) {
        let debt = parseFloat(rows[i].getAttribute('data-debt'));
        if (minAmt === 'all') {
            rows[i].style.display = "";
        } else {
            let limit = parseFloat(minAmt);
            if (debt >= limit) {
                rows[i].style.display = "";
            } else {
                rows[i].style.display = "none";
            }
        }
    }
}

// الترتيب التصاعدي والتنازلي للمبالغ
let sortAsc = false;
function toggleSortOrder() {
    let table = document.getElementById('debtsTable');
    let tbody = table.querySelector('tbody');
    let rows = Array.from(tbody.querySelectorAll('.debt-row'));

    rows.sort((a, b) => {
        let valA = parseFloat(a.getAttribute('data-debt'));
        let valB = parseFloat(b.getAttribute('data-debt'));
        return sortAsc ? valA - valB : valB - valA;
    });

    sortAsc = !sortAsc;
    event.target.innerText = sortAsc ? '🔃 ترتيب الأصغر' : '🔃 ترتيب الأكبر';

    rows.forEach(row => tbody.appendChild(row));
}

// نسخ تقرير الديون كـ نص جاهز
function copyDebtReport() {
    let rows = document.querySelectorAll('.debt-row');
    let report = "📋 *تقرير مديونيات العملاء - مؤسسة أبو ضياء*\n━━━━━━━━━━━━━━━\n";
    let count = 0;
    let totalFiltered = 0;

    rows.forEach(row => {
        if(row.style.display !== 'none') {
            let name = row.getAttribute('data-name');
            let debt = parseFloat(row.getAttribute('data-debt'));
            report += `👤 ${name} : *${debt.toLocaleString()} ر.ي*\n`;
            totalFiltered += debt;
            count++;
        }
    });

    report += `━━━━━━━━━━━━━━━\n📊 الإجمالي: *${totalFiltered.toLocaleString()} ر.ي* (${count} عميل)`;

    navigator.clipboard.writeText(report).then(() => {
        alert('📋 تم نسخ تقرير الديون بنجاح وجاهز للمشاركته!');
    }).catch(err => {
        alert('❌ فشل النسخ تلقائياً.');
    });
}

// إرسال تذكير جماعي تباعاً عبر الواتساب
function sendBulkReminders() {
    let rows = document.querySelectorAll('.debt-row');
    let visibleRows = Array.from(rows).filter(r => r.style.display !== 'none');

    if(visibleRows.length === 0) {
        alert('لا توجد عملاء ظاهرون للإرسال.');
        return;
    }

    if(confirm('هل تريد البدء بإرسال تذكيرات واتساب لـ ' + visibleRows.length + ' عميل؟')) {
        visibleRows.forEach((row, index) => {
            let waLink = row.querySelector('.btn-wa');
            if(waLink) {
                setTimeout(() => {
                    window.open(waLink.href, '_blank');
                }, index * 1000);
            }
        });
    }
}
</script>

</body>
</html>
