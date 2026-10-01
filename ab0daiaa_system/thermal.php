<?php
// thermal.php - فاتورة حرارية مقاس 58mm لطابعات البلوتوث
require_once 'auth.php';
$db_file = '/data/data/com.termux/files/home/secure_dbs/vmax.db';
$db = new SQLite3($db_file);
$id = intval($_GET['id'] ?? 0);
$inv = @$db->querySingle("SELECT * FROM invoices WHERE id = $id", true);
if (empty($inv)) { die("الفاتورة غير موجودة!"); }
$cust = @$db->querySingle("SELECT * FROM customers WHERE id = " . intval($inv['customer_id'] ?? 0), true);
$c_name = (is_array($cust) && !empty($cust['name'])) ? $cust['name'] : 'عميل نقدي';
$items = []; 
$res = @$db->query("SELECT * FROM invoice_items WHERE invoice_id = $id");
if ($res) { while($row = @$res->fetchArray(SQLITE3_ASSOC)) { if($row) $items[] = $row; } }
$config = ['store_name' => 'أبو ضياء للتوزيع', 'phone' => '771458609', 'address' => 'سيئون - السوق العام'];
$set_res = @$db->query("SELECT * FROM settings");
if ($set_res) { 
    while($row = @$set_res->fetchArray(SQLITE3_ASSOC)) { 
        if (is_array($row) && isset($row['setting_key']) && !empty($row['setting_key'])) {
            $config[$row['setting_key']] = $row['setting_value'] ?? ''; 
        }
    } 
}
$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><meta name='mobile-web-app-capable' content='yes'>
<meta name='apple-mobile-web-app-capable' content='yes'>

    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>فاتورة حرارية #<?php echo $id; ?></title>
    <style>
        body { font-family: 'Tahoma', sans-serif; background: #FFFFFF; color: #000; margin: 0; padding: 5px; width: 58mm; font-size: 11px; }
        .text-center { text-align: center; }
        .bold { font-weight: bold; }
        .dashed-line { border-bottom: 1px dashed #000; margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin: 5px 0; }
        th, td { border-bottom: 1px dashed #ccc; padding: 4px 0; text-align: center; }
        .t-name { text-align: right; }
        @media print { .no-print { display: none; } body { width: 100%; margin: 0; padding: 0; } }
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
    <div class="no-print text-center" style="margin-bottom:10px;">
        <button onclick="window.print()" style="padding:10px; font-weight:bold; background: #FFFFFF; color:#fff; border:none; border-radius:5px; width:100%;">🖨️ طباعة عبر البلوتوث</button>
        <a href="view_invoice.php?id=<?php echo $id; ?>" style="display:block; margin-top:5px; color:#475569;">العودة لفاتورة الـ PDF</a>
    </div>

    <div class="text-center bold" style="font-size: 14px;"><?php echo htmlspecialchars($config['store_name'] ?? ''); ?></div>
    <div class="text-center">جوال: <?php echo htmlspecialchars($config['phone'] ?? ''); ?></div>
    <div class="text-center"><?php echo htmlspecialchars($config['address'] ?? ''); ?></div>
    <div class="dashed-line"></div>
    <div>رقم الفاتورة: #<?php echo $id; ?></div>
    <div>العميل: <?php echo htmlspecialchars($c_name); ?></div>
    <div>التاريخ: <?php echo date('Y/m/d H:i', strtotime($inv['created_date'] ?? 'now')); ?></div>
    <div class="dashed-line"></div>
    
    <table>
        <tr><th class="t-name" style="width:45%">الصنف</th><th>الكمية</th><th>السعر</th><th>المجموع</th></tr>
        <?php foreach($items as $i): ?>
        <tr>
            <td class="t-name"><?php echo htmlspecialchars($i['product_name'] ?? ''); ?>
                <?php if(($i['free_qty'] ?? 0) > 0) echo "<br><small>+ مجاني: {$i['free_qty']}</small>"; ?>
            </td>
            <td><?php echo $i['qty'] ?? 0; ?></td>
            <td><?php echo number_format($i['price'] ?? 0, 0); ?></td>
            <td><?php echo number_format($i['total'] ?? 0, 0); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    
    <div class="dashed-line"></div>
    <div style="display:flex; justify-content:space-between; font-size:13px;" class="bold">
        <span>الإجمالي:</span>
        <span><?php echo number_format($inv['total'] ?? 0, 0); ?> <?php echo $inv['currency'] ?? 'YER'; ?></span>
    </div>
    <div class="dashed-line"></div>
    <div class="text-center">شكراً لتعاملكم معنا</div>
    <div class="text-center" style="font-size:9px; margin-top:3px;">نظام أبوضياء للتوزيع</div>
</body>
</html>
