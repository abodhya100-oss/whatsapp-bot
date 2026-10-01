<?php
// invoices_list.php - سجل الفواتير مع الفحص التلقائي لقاعدة البيانات - V-MAX PRO
require_once 'auth.php';
$db_file = __DIR__ . '/vmax.db';
$db = new SQLite3($db_file);

// التحقق الذكي من وجود عمود التاريخ وإضافته تلقائياً لتجنب أي أخطاء
$check_col = $db->query("PRAGMA table_info(invoices)");
$has_date_col = false;
$date_column_name = 'created_at'; // الافتراضي
while ($row = $check_col->fetchArray(SQLITE3_ASSOC)) {
    if ($row['name'] === 'created_at') { $has_date_col = true; $date_column_name = 'created_at'; break; }
    if ($row['name'] === 'date') { $has_date_col = true; $date_column_name = 'date'; break; }
}

if (!$has_date_col) {
    // إضافة العمود تلقائياً إذا كان مفقوداً
    $db->exec("ALTER TABLE invoices ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
    $date_column_name = 'created_at';
}

// فلترة البحث
$search = trim($_GET['search'] ?? '');
$date_filter = trim($_GET['date_filter'] ?? date('Y-m-d'));

$query = "SELECT i.*, c.name as customer_name FROM invoices i LEFT JOIN customers c ON i.customer_id = c.id WHERE 1=1";

if (!empty($search)) {
    $query .= " AND (i.id LIKE :search OR c.name LIKE :search)";
}

if (!empty($date_filter)) {
    $query .= " AND DATE(i.{$date_column_name}) = :date_filter";
}

$query .= " ORDER BY i.id DESC";

$stmt = $db->prepare($query);
if (!empty($search)) {
    $stmt->bindValue(':search', "%$search%", SQLITE3_TEXT);
}
if (!empty($date_filter)) {
    $stmt->bindValue(':date_filter', $date_filter, SQLITE3_TEXT);
}
$result = $stmt->execute();

$invoices = [];
$total_sales_day = 0;
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $invoices[] = $row;
    $total_sales_day += floatval($row['grand_total']);
}

$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>سجل الفواتير والمبيعات - V-MAX PRO</title>
    <style>
        :root {
            --bg-deep: #FFFFFF;
            --card-bg: #F8FAFC;
            --card-border: #CBD5E1;
            --primary: #2563EB;
            --success: #10B981;
            --text-main: #0F172A;
            --text-muted: #64748B;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Tahoma', sans-serif;
            background: var(--bg-deep);
            margin: 0;
            padding: 8px;
            color: var(--text-main);
            min-height: 100vh;
            padding-bottom: 75px;
        }
        .container { max-width: 650px; margin: 0 auto; }
        .header-box {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 10px;
            padding: 10px;
            text-align: center;
            margin-bottom: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .header-box h2 { margin: 0 0 4px 0; font-size: 16px; color: #1E3A8A; font-weight: bold; }
        
        .stats-box {
            background: #ECFDF5;
            border: 1px solid #10B981;
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 8px;
            text-align: center;
        }
        .stats-title { font-size: 11px; color: #065F46; font-weight: bold; margin-bottom: 2px; }
        .stats-value { font-size: 18px; color: #047857; font-weight: bold; }

        .filter-form {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 10px;
            padding: 8px;
            margin-bottom: 8px;
            display: flex;
            gap: 6px;
        }
        .filter-form input {
            flex: 1;
            padding: 7px 10px;
            background: #FFFFFF;
            border: 1px solid var(--card-border);
            border-radius: 7px;
            color: #0F172A;
            font-size: 11px;
        }
        .btn-filter {
            padding: 7px 14px;
            background: #2563EB;
            color: #FFFFFF;
            border: none;
            border-radius: 7px;
            font-weight: bold;
            font-size: 11px;
            cursor: pointer;
        }

        .invoices-table { width: 100%; border-collapse: collapse; background: #FFFFFF; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .invoices-table th { background: #DBEAFE; font-size: 10.5px; padding: 8px 6px; text-align: center; border: 1px solid #CBD5E1; color: #1E40AF; }
        .invoices-table td { padding: 8px 6px; border: 1px solid #CBD5E1; text-align: center; font-size: 11px; color: #0F172A; }
        
        .btn-action {
            padding: 4px 8px;
            border-radius: 5px;
            font-size: 10px;
            font-weight: bold;
            text-decoration: none;
            color: #FFFFFF;
            display: inline-block;
            margin: 1px;
        }
        .btn-view { background: #2563EB; }
        .btn-wa { background: #10B981; }

        .no-data { text-align: center; padding: 20px; color: var(--text-muted); font-size: 12px; }
    </style>
</head>
<body>

<div class="container">
    <div class="header-box">
        <h2>📊 سجل الفواتير والمبيعات</h2>
    </div>

    <div class="stats-box">
        <div class="stats-title">إجمالي مبيعات التاريخ المحدد:</div>
        <div class="stats-value"><?php echo number_format($total_sales_day, 2); ?> ر.ي</div>
    </div>

    <form method="GET" class="filter-form">
        <input type="date" name="date_filter" value="<?php echo htmlspecialchars($date_filter); ?>">
        <input type="text" name="search" placeholder="بحث برقم الفاتورة أو اسم العميل..." value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="btn-filter">بحث</button>
    </form>

    <table class="invoices-table">
        <thead>
            <tr>
                <th>رقم الفاتورة</th>
                <th>العميل</th>
                <th>الإجمالي</th>
                <th>التاريخ</th>
                <th>إجراءات</th>
            </tr>
        </thead>
        <tbody>
            <?php if(count($invoices) > 0): ?>
                <?php foreach($invoices as $inv): ?>
                <tr>
                    <td style="font-weight: bold; color: #1E3A8A;">#<?php echo str_pad($inv['id'], 4, '0', STR_PAD_LEFT); ?></td>
                    <td style="text-align: right; padding-right: 8px; font-weight: bold;"><?php echo htmlspecialchars($inv['customer_name'] ?? 'عميل نقدي'); ?></td>
                    <td style="font-weight: bold; color: #059669;"><?php echo number_format($inv['grand_total'], 2); ?></td>
                    <td style="font-size: 9.5px; color: #64748B;"><?php echo $inv[$date_column_name] ?? 'بدون تاريخ'; ?></td>
                    <td>
                        <a href="invoice_whatsapp.php?id=<?php echo $inv['id']; ?>" class="btn-action btn-view">عرض</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="no-data">لا توجد فواتير مسجلة في هذا التاريخ أو مطابقة للبحث.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if(file_exists('nav_menu.php')) { include 'nav_menu.php'; } ?>

</body>
</html>
