<?php
// customers.php - صفحة العملاء مع حساب الأرصدة تلقائياً من الفواتير - V-MAX PRO
require_once 'auth.php';
$db_file = '/data/data/com.termux/files/home/secure_dbs/vmax.db';
$db = new SQLite3($db_file);

// جلب العملاء وحساب الرصيد الحقيقي تلقائياً من مجموع الفواتير غير المسددة لكل عميل
$customers = [];
$total_balance = 0;
$res = $db->query("SELECT * FROM customers ORDER BY id DESC");

while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $cust_id = $row['id'];

    // استعلام لحساب إجمالي المتبقي الفعلي من فواتير هذا العميل
    $inv_res = $db->query("SELECT SUM(grand_total - paid_amount) as total_rem FROM invoices WHERE customer_id = $cust_id");
    $inv_row = $inv_res->fetchArray(SQLITE3_ASSOC);
    $real_balance = floatval($inv_row['total_rem'] ?? 0);

    // تحديث قيمة الرصيد لتعرض القيمة الحقيقية المطابقة للفواتير
    $row['balance'] = $real_balance;

    $customers[] = $row;
    $total_balance += $real_balance;
}

$total_count = count($customers);
$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>العملاء - V-MAX PRO</title>
    <style>
        :root {
            --bg-deep: #F1F5F9;
            --card-border: #E2E8F0;
            --primary: #0284C7;
            --text-main: #0F172A;
            --text-muted: #64748B;
        }
        * { box-sizing: border-box; }
        body { font-family: 'Tahoma', sans-serif; background: var(--bg-deep); margin: 0; padding: 12px; padding-bottom: 90px; color: var(--text-main); }

        /* بطاقة الإحصائيات العلوية */
        .top-card { background: #0F172A; color: white; border-radius: 12px; padding: 20px; text-align: center; margin-bottom: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .top-card h3 { margin: 0 0 5px 0; font-size: 13px; color: #94A3B8; font-weight: normal; display: flex; align-items: center; justify-content: center; gap: 5px; }
        .top-card .total-amt { font-size: 26px; font-weight: bold; color: #EF4444; margin-bottom: 5px; }
        .top-card .cust-count { font-size: 11px; color: #64748B; }

        /* بطاقة العميل */
        .customer-card { display: flex; justify-content: space-between; align-items: center; background: #fff; border-radius: 12px; padding: 12px; margin-bottom: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.03); border: 1px solid var(--card-border); }
        .card-right { text-align: right; }
        .cust-name { font-size: 13px; font-weight: bold; color: #0F172A; margin-bottom: 6px; display: flex; align-items: center; gap: 5px; }
        .cust-details { font-size: 10px; color: #64748B; display: flex; align-items: center; gap: 6px; }

        .card-left { display: flex; align-items: center; gap: 10px; }
        .balance-badge { background: #FEE2E2; color: #EF4444; padding: 5px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; box-shadow: inset 0 0 0 1px #FCA5A5; }
        .balance-badge.zero { background: #ECFDF5; color: #10B981; box-shadow: inset 0 0 0 1px #6EE7B7; }

        /* القائمة المنسدلة */
        .dropdown { position: relative; }
        .btn-dots { width: 34px; height: 34px; border-radius: 50%; background: var(--primary); color: white; border: none; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: bold; cursor: pointer; box-shadow: 0 2px 6px rgba(2, 132, 199, 0.3); padding-bottom: 6px; transition: transform 0.1s; }
        .btn-dots:active { transform: scale(0.9); }

        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            margin-top: 8px;
            min-width: 175px;
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            border: 1px solid #CBD5E1;
            z-index: 9999;
            overflow: hidden;
            text-align: right;
        }
        .dropdown-menu.show { display: block; animation: dropDownFade 0.2s ease; }
        @keyframes dropDownFade { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

        .dropdown-item { display: flex; align-items: center; gap: 8px; padding: 10px 12px; color: #1E293B; text-decoration: none; font-size: 11.5px; font-weight: bold; border-bottom: 1px solid #F1F5F9; transition: background 0.1s; }
        .dropdown-item:last-child { border-bottom: none; }
        .dropdown-item:active { background: #F8FAFC; }
        .text-danger { color: #EF4444 !important; }
        .text-warning { color: #D97706 !important; }

        /* زر إضافة عميل */
        .btn-add { position: fixed; bottom: 85px; left: 15px; background: var(--primary); color: white; border: none; padding: 12px 20px; border-radius: 25px; font-size: 12.5px; font-weight: bold; box-shadow: 0 4px 15px rgba(2, 132, 199, 0.4); display: flex; align-items: center; gap: 6px; cursor: pointer; text-decoration: none; z-index: 100; transition: transform 0.1s; }
        .btn-add:active { transform: scale(0.95); }
    </style>
</head>
<body>

    <div class="top-card">
        <h3>📈 إجمالي أرصدة العملاء</h3>
        <div class="total-amt"><?php echo number_format($total_balance, 2); ?> ر.ي</div>
        <div class="cust-count">العدد: <?php echo $total_count; ?> عميل | حسابات العملاء المعتمدة</div>
    </div>

    <?php if(count($customers) > 0): ?>
        <?php foreach($customers as $c): ?>
        <div class="customer-card">
            <div class="card-right" onclick="window.location.href='customer_invoices.php?customer_id=<?php echo $c['id']; ?>'" style="cursor: pointer; flex: 1;">
                <div class="cust-name">👑 🛒 <?php echo htmlspecialchars($c['name']); ?></div>
                <div class="cust-details">
                    📍 <?php echo htmlspecialchars($c['address'] ?? 'غير محدد'); ?> | 📞 <?php echo htmlspecialchars($c['phone'] ?? '000000000'); ?>
                </div>
            </div>
            <div class="card-left">
                <div class="balance-badge <?php echo ($c['balance'] == 0) ? 'zero' : ''; ?>">
                    <?php echo number_format($c['balance'], 2); ?> ر.ي
                </div>
                <div class="dropdown">
                    <button class="btn-dots" onclick="toggleDropdown(<?php echo $c['id']; ?>)">...</button>
                    <div class="dropdown-menu" id="drop-<?php echo $c['id']; ?>">
                        <a href="formal_invoice.php?customer_id=<?php echo $c['id']; ?>" class="dropdown-item">🧾 فاتورة جديدة</a>
                        <a href="customer_invoices.php?customer_id=<?php echo $c['id']; ?>" class="dropdown-item">💳 سداد الفواتير</a>
                        <a href="receipts.php?customer_id=<?php echo $c['id']; ?>" class="dropdown-item">💵 سند قبض وصرف</a>
                        <a href="customer_statement.php?customer_id=<?php echo $c['id']; ?>" class="dropdown-item">📊 كشف الحساب</a>
                        <a href="edit_customer.php?id=<?php echo $c['id']; ?>" class="dropdown-item">📝 تعديل بيانات</a>
                        <a href="zero_account.php?id=<?php echo $c['id']; ?>" class="dropdown-item text-warning" onclick="return confirm('هل أنت متأكد من تصفير حساب هذا العميل؟')">🔄 تصفير الحساب</a>
                        <a href="delete_customer.php?id=<?php echo $c['id']; ?>" class="dropdown-item text-danger" onclick="return confirm('تنبيه هام: هل أنت متأكد من حذف العميل بشكل نهائي؟')">🗑️ حذف العميل</a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div style="text-align: center; color: #64748B; margin-top: 40px; font-size: 13px;">لا يوجد عملاء مسجلين حالياً.</div>
    <?php endif; ?>

    <a href="add_customer.php" class="btn-add">عميل جديد ➕</a>

    <?php if(file_exists('nav_menu.php')) { include 'nav_menu.php'; } ?>

    <script>
    function toggleDropdown(id) {
        let menu = document.getElementById('drop-' + id);
        let isShown = menu.classList.contains('show');

        document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.remove('show'));

        if (!isShown) {
            menu.classList.add('show');
        }
    }

    window.onclick = function(event) {
        if (!event.target.matches('.btn-dots')) {
            document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.remove('show'));
        }
    }
    </script>
</body>
</html>
