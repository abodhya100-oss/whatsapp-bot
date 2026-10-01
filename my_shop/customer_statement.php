<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

$cust_id = intval($_GET['id'] ?? 0);
if(session_status()===PHP_SESSION_NONE){session_start();} if(!isset($_SESSION["user_role"])){header("Location: login.php");exit;}
$db = new SQLite3(__DIR__ . '/vmax.db');

$c_stmt = $db->prepare("SELECT * FROM customers WHERE id = :id");
$c_stmt->bindValue(':id', $cust_id, SQLITE3_INTEGER);
$customer = $c_stmt->execute()->fetchArray(SQLITE3_ASSOC);
$c_stmt->close();

$inv_stmt = $db->prepare("SELECT * FROM invoices WHERE customer_id = :id ORDER BY id DESC");
$inv_stmt->bindValue(':id', $cust_id, SQLITE3_INTEGER);
$inv_res = $inv_stmt->execute();
$invoices = [];
while($inv = $inv_res->fetchArray(SQLITE3_ASSOC)) { $invoices[] = $inv; }
$inv_stmt->close();
$db->close();

$cust_name = $customer['name'] ?? 'عميل';
$cust_phone = $customer['phone'] ?? '';
$cust_balance = floatval($customer['balance'] ?? 0);

// رسالة الواتساب الجاهزة
          "رصيدك الإجمالي الحالي (الديون): " . number_format($cust_balance, 2) . " ر.ي%0A" .
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <link rel="manifest" href="manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js");}</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>كشف حساب العميل - <?php echo htmlspecialchars($cust_name); ?></title>
    <style>
        body { font-family: 'Tahoma', sans-serif; background: #0f2027; color: #fff; margin: 0; padding: 10px; padding-bottom: 80px; }
        .container { max-width: 680px; margin: auto; }
        h1 { text-align: center; color: #ffd200; font-size: 18px; margin-bottom: 3px; }
        .card { background: rgba(255,255,255,0.08); padding: 12px; border-radius: 12px; margin-bottom: 10px; border: 1px solid rgba(255,255,255,0.15); }
        .btn-wa { display: block; width: 100%; background: #25d366; color: #fff; text-align: center; padding: 11px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 13px; margin-bottom: 10px; box-shadow: 0 4px 15px rgba(37,211,102,0.4); }
        .table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 11px; background: rgba(0,0,0,0.4); border-radius: 8px; overflow: hidden; }
        .table th, .table td { padding: 8px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .table th { background: rgba(255,210,0,0.2); color: #ffd200; font-weight: bold; }
        .btn-back { display: block; text-align: center; color: #ffd200; text-decoration: none; font-size: 11px; margin-top: 15px; }
    </style>
</head>
<body>

<div class="container">
    <h1>📄 كشف حساب تفصيلي</h1>
    <p style="text-align:center; color:#aaa; font-size:11px; margin-bottom:10px;">نظام ابوضياء العالمي</p>

    <div class="card">
        <b style="color:#ffd200; font-size:13px; display:block; margin-bottom:5px;">👤 العميل: <?php echo htmlspecialchars($cust_name); ?></b>
        <div style="font-size:11px; color:#aaa; margin-bottom:8px;">هاتف: <?php echo htmlspecialchars($cust_phone ?: 'بدون'); ?></div>
        <div style="font-size:13px; font-weight:bold;">الرصيد المستحق (الدين): <span style="color:#ff4d4d;"><?php echo number_format($cust_balance, 2); ?> ر.ي</span></div>
    </div>

    <?php if(!empty($cust_phone)): ?>
        <a href="https://wa.me/967<?php echo ltrim($cust_phone, '0'); ?>?text=<?php echo $wa_msg; ?>" target="_blank" class="btn-wa">💬 إرسال كشف الحساب والديون عبر واتساب</a>
    <?php else: ?>
        <div style="background:rgba(255,193,7,0.2); color:#ffc107; padding:8px; border-radius:8px; text-align:center; font-size:11px; margin-bottom:10px;">⚠️ أضف رقم هاتفك للعميل لتفعيل ميزة الإرسال المباشر عبر واتساب.</div>
    <?php endif; ?>

    <div class="card">
        <b style="color:#ffd200; font-size:12px; display:block; margin-bottom:6px;">📋 سجل الفواتير المسجلة:</b>
        <table class="table">
            <thead>
                <tr>
                    <th>رقم الفاتورة</th>
                    <th>الإجمالي</th>
                    <th>المدفوع</th>
                    <th>المتبقي</th>
                    <th>التاريخ</th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($invoices)): ?>
                    <?php foreach($invoices as $inv): ?>
                        <tr>
                            <td><a href="view_invoice.php?id=<?php echo $inv['id']; ?>" style="color:#ffd200; font-weight:bold;">#<?php echo $inv['id']; ?></a></td>
                            <td><?php echo number_format($inv['total'], 2); ?></td>
                            <td style="color:#38ef7d;"><?php echo number_format($inv['paid_amount'], 2); ?></td>
                            <td style="color:#ff4d4d;"><?php echo number_format($inv['remaining_amount'], 2); ?></td>
                            <td style="font-size:10px; color:#aaa;"><?php echo $inv['created_date']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="color:#aaa; padding:10px;">لا توجد فواتير مسجلة لهذا العميل.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <a href="customers.php" class="btn-back">⬅️ العودة لقائمة العملاء</a>
</div>


<?php include 'nav_bar.php'; ?>
<?php include "nav_menu.php"; ?>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>
</html>

