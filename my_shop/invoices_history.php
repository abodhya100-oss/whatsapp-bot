<?php
// invoices_history.php - سجل وبحث فواتير المبيعات المتقدم لـ نظام ابوضياء العالمي
require_once 'auth.php';

$db_file = __DIR__ . '/vmax.db';
if(session_status()===PHP_SESSION_NONE){session_start();} if(!isset($_SESSION["user_role"])){header("Location: login.php");exit;}
$db = new SQLite3($db_file);

$search = trim($_GET['search'] ?? '');
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$where = "1=1";
if (!empty($search)) {
    $where .= " AND (i.id LIKE '%$search%' OR c.name LIKE '%$search%')";
}
if (!empty($start_date) && !empty($end_date)) {
    $where .= " AND DATE(i.created_date) BETWEEN '$start_date' AND '$end_date'";
}

$invoices_res = $db->query("
    SELECT i.*, c.name as customer_name, c.phone, c.region 
    FROM invoices i 
    LEFT JOIN customers c ON i.customer_id = c.id 
    WHERE $where 
    ORDER BY i.id DESC LIMIT 50
");

$invoices_list = [];
while($inv = $invoices_res->fetchArray(SQLITE3_ASSOC)) {
    $invoices_list[] = $inv;
}
$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <link rel="manifest" href="manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js");}</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام ابوضياء العالمي - سجل فواتير المبيعات</title>
    <style>
        body { font-family: 'Tahoma', sans-serif; background: linear-gradient(135deg, #0f2027, #203a43, #2c5364); color: #fff; margin: 0; padding: 0; padding-bottom: 90px; }
        .container { max-width: 680px; margin: 15px auto; padding: 15px; }
        h1 { text-align: center; font-size: 20px; margin-bottom: 4px; color: #00f2fe; }
        p.sub-title { text-align: center; color: #bbb; font-size: 12px; margin-bottom: 12px; }
        .card { background: rgba(255, 255, 255, 0.08); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.15); border-radius: 14px; padding: 12px; margin-bottom: 12px; box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37); }
        .filter-form { display: grid; grid-template-columns: 1fr auto; gap: 8px; margin-bottom: 10px; }
        input, select { width: 100%; padding: 9px; border: 1px solid rgba(255, 255, 255, 0.25); background: rgba(0, 0, 0, 0.5); color: #fff; border-radius: 8px; box-sizing: border-box; font-size: 13px; }
        .btn-search { background: linear-gradient(135deg, #00f2fe, #4facfe); color: #000; border: none; padding: 10px 15px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 13px; }
        .inv-card { background: rgba(0, 0, 0, 0.3); padding: 11px; border-radius: 8px; margin-bottom: 8px; border-right: 4px solid #00f2fe; display: flex; justify-content: space-between; align-items: center; }
        .btn-view { background: #00f2fe; color: #000; padding: 6px 12px; border-radius: 6px; font-size: 11px; text-decoration: none; font-weight: bold; }
        .nav-bar { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(15, 32, 39, 0.95); backdrop-filter: blur(10px); display: flex; justify-content: space-around; padding: 10px 0; border-top: 1px solid rgba(255, 255, 255, 0.1); z-index: 1000; }
        .nav-item { text-align: center; color: #aaa; text-decoration: none; font-size: 12px; }
        .nav-item.active, .nav-item:hover { color: #00f2fe; }
        .nav-item div { font-size: 20px; margin-bottom: 2px; }
    </style>
</head>
<body>

<div class="container">
    <h1>📜 سجل وبحث فواتير المبيعات</h1>
    <p class="sub-title">استعراض والبحث في كافة الفواتير السابقة بدقة</p>

    <!-- نموذج البحث والفلترة -->
    <div class="card">
        <form method="GET" class="filter-form">
            <input type="text" name="search" placeholder="ابحث برقم الفاتورة أو اسم العميل..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn-search">🔍 بحث</button>
        </form>
    </div>

    <!-- قائمة الفواتير -->
    <div class="card">
        <h3 style="margin-top:0; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:6px; font-size: 13px; color: #00f2fe;">📋 الفواتير المسجلة (آخر 50 فاتورة)</h3>
        
        <?php if (count($invoices_list) > 0): ?>
            <?php foreach ($invoices_list as $inv): ?>
                <div class="inv-card">
                    <div>
                        <strong style="font-size: 13px; color: #fff;">فاتورة #<?php echo $inv['id']; ?></strong>
                        <div style="font-size: 11px; color: #bbb; margin-top: 2px;">العميل: <b><?php echo htmlspecialchars($inv['customer_name']); ?></b></div>
                        <div style="font-size: 10px; color: #888; margin-top: 1px;"><?php echo $inv['created_date']; ?> | النوع: <?php echo ($inv['payment_type'] == 'cash' ? 'نقدي' : 'أجل'); ?></div>
                    </div>
                    <div style="text-align: left;">
                        <div style="color: #38ef7d; font-weight: bold; font-size: 14px; margin-bottom: 5px;"><?php echo number_format($inv['total'], 2); ?> ر.ي</div>
                        <a href="view_invoice.php?id=<?php echo $inv['id']; ?>" class="btn-view">📄 استعراض</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align: center; color: #aaa; font-size: 12px;">لا توجد فواتير مطابقة للبحث.</p>
        <?php endif; ?>
    </div>
</div>


<?php include 'nav_bar.php'; ?>

<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
