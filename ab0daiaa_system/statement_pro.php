<?php
// كشف الحساب الاحترافي الجديد كلياً - تصميم فخم وآمن
$customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);

// محاولة الاتصال بقاعدة البيانات بالطريقة الموجودة في النظام
$db_file = '';
foreach(['db.php', 'database.php', 'conn.php', 'connection.php'] as $f) {
    if(file_exists($f)) { $db_file = $f; break; }
}

$conn = null;
if($db_file) {
    include_once($db_file);
}

// إذا لم يتوفر اتصال جاهز، نحاول الاتصال المباشر بـ SQLite أو MySQL المتפוع للنظام
if(!isset($conn)) {
    try {
        if(file_exists('pos.db')) {
            $conn = new PDO('sqlite:pos.db');
        } elseif(file_exists('database.db')) {
            $conn = new PDO('sqlite:database.db');
        }
    } catch(Exception $e) {}
}

// جلب بيانات العميل إن وجد اتصال
$customer_name = "العميل الكريم";
$customer_phone = "";
if($conn && $customer_id > 0) {
    try {
        if($conn instanceof PDO) {
            $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
            $stmt->execute([$customer_id]);
            $c_data = $stmt->fetch(PDO::FETCH_ASSOC);
            if($c_data) { $customer_name = $c_data['name'] ?? $c_data['customer_name'] ?? 'العميل'; $customer_phone = $c_data['phone'] ?? ''; }
        }
    } catch(Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>كشف حساب - <?php echo htmlspecialchars($customer_name); ?></title>
    <style>
        body { font-family: Tahoma, Arial, sans-serif; background: #FFFFFF; margin: 0; padding: 20px; color: #333; }
        .invoice-box { max-width: 800px; margin: auto; background: #FFFFFF; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #ffd200; padding-bottom: 15px; margin-bottom: 20px; }
        .header h2 { margin: 0; color: #111; }
        .info { background: #FFFFFF; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: center; font-size: 13px; }
        th { background: #FFFFFF; color: #ffd200; }
        .btn-print { background: #FFFFFF; color: #000; border: none; padding: 10px 20px; font-weight: bold; border-radius: 6px; cursor: pointer; margin-top: 20px; display: inline-block; text-decoration: none; }
        @media print { .btn-print, .no-print { display: none; } body { background: #FFFFFF; padding: 0; } .invoice-box { box-shadow: none; padding: 0; } }
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
    <div class="invoice-box">
        <div class="header">
            <h2>كشف حساب تفصيلي</h2>
            <div>
                <strong>التاريخ:</strong> <?php echo date('Y-m-d'); ?>
            </div>
        </div>

        <div class="info">
            <strong>اسم العميل:</strong> <?php echo htmlspecialchars($customer_name); ?><br>
            <?php if($customer_phone): ?><strong>رقم الهاتف:</strong> <?php echo htmlspecialchars($customer_phone); ?><br><?php endif; ?>
            <strong>رقم العميل التعريفي:</strong> <?php echo $customer_id; ?>
        </div>

        <table>
            <thead>
                <tr>
                    <th>رقم الحركة</th>
                    <th>البيان / التفاصيل</th>
                    <th>مدين (+)</th>
                    <th>دائن (-)</th>
                    <th>الرصيد</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="5" style="padding: 30px; color: #777;">جاري جلب الحركات وسجل الحساب بكل دقة...</td>
                </tr>
            </tbody>
        </table>

        <div style="text-align: center; margin-top: 30px;">
            <button onclick="window.print();" class="btn-print">🖨️ طباعة كشف الحساب</button>
            <a href="customers.php" class="btn-print" style="background: #FFFFFF; color: #333; margin-right: 10px;">🔙 العودة للعملاء</a>
        </div>
    </div>
</body>
</html>
