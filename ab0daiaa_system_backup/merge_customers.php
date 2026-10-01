<?php
// merge_customers.php - أداة دمج حسابات العملاء والديون لـ نظام ابوضياء العالمي
error_reporting(E_ALL);
ini_set('display_errors', 1);

$db_file = __DIR__ . '/vmax.db';
if(session_status()===PHP_SESSION_NONE){session_start();} if(!isset($_SESSION["user_role"])){header("Location: login.php");exit;}
$db = new SQLite3($db_file);

$msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['merge_now'])) {
    $source_id = intval($_POST['source_id'] ?? 0);
    $target_id = intval($_POST['target_id'] ?? 0);

    if ($source_id > 0 && $target_id > 0 && $source_id !== $target_id) {
        // بدء معاملة آمنة (Transaction) لضمان عدم ضياع أي بيانات
        $db->exec("BEGIN TRANSACTION;");

        try {
            // 1. نقل جميع الفواتير إلى العميل الرئيسي
            $db->exec("UPDATE invoices SET customer_id = $target_id WHERE customer_id = $source_id");

            // 2. نقل جميع سندات القبض والصرف إلى العميل الرئيسي
            $db->exec("UPDATE receipts SET customer_id = $target_id WHERE customer_id = $source_id");

            // 3. نقل المرتجعات إن وجدت
            @$db->exec("UPDATE returns SET customer_id = $target_id WHERE customer_id = $source_id");

            // 4. جلب رصيد واختيار اسم العميل الفرعي لدمجه
            $src_q = $db->query("SELECT balance, name FROM customers WHERE id = $source_id");
            $src_data = $src_q ? $src_q->fetchArray(SQLITE3_ASSOC) : false;

            if ($src_data) {
                $src_balance = floatval($src_data['balance']);
                $src_name = $src_data['name'];
                
                // تحديث رصيد العميل الرئيسي بإضافة رصيد العميل الفرعي
                $db->exec("UPDATE customers SET balance = balance + $src_balance WHERE id = $target_id");

                // 5. حذف العميل الفرعي نهائياً بعد نجاح نقل كافة بياناته ومديونيته
                $db->exec("DELETE FROM customers WHERE id = $source_id");

                $db->exec("COMMIT;");
                $msg = "<div style='background:rgba(40,167,69,0.3); color:#28a745; padding:12px; border-radius:12px; text-align:center; font-weight:bold; margin-bottom:15px;'>✅ تم دمج العميل ($src_name) وكافة مديونيته وفواتيره وسنداته بنجاح تام إلى العميل الرئيسي!</div>";
            } else {
                throw new Exception("العميل الفرعي المراد دمج غير موجود.");
            }

        } catch (Exception $e) {
            $db->exec("ROLLBACK;");
            $msg = "<div style='background:rgba(255,77,77,0.3); color:#ff4d4d; padding:12px; border-radius:12px; text-align:center; font-weight:bold; margin-bottom:15px;'>❌ حدث خطأ أثناء الدمج: " . $e->getMessage() . "</div>";
        }
    } else {
        $msg = "<div style='background:rgba(255,193,7,0.3); color:#ffc107; padding:12px; border-radius:12px; text-align:center; font-weight:bold; margin-bottom:15px;'>⚠️ يرجى اختيار عميلين مختلفين بشكل صحيح.</div>";
    }
}

// جلب قائمة العملاء
$results = $db->query("SELECT id, name, region, customer_type FROM customers ORDER BY name ASC");
$customers_list = [];
while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
    $customers_list[] = $row;
}
$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <link rel="manifest" href="manifest.json">
    <meta name='mobile-web-app-capable' content='yes'>
    <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js");}</script>
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>نظام ابوضياء العالمي - دمج حسابات العملاء</title>
    <style>
        body { font-family: 'Tahoma', sans-serif; background: linear-gradient(135deg, #0f2027, #203a43, #2c5364); color: #fff; margin: 0; padding: 0; padding-bottom: 90px; }
        .container { max-width: 650px; margin: 15px auto; padding: 15px; }
        h1 { text-align: center; font-size: 22px; margin-bottom: 5px; color: #ff9966; }
        p.sub-title { text-align: center; color: #bbb; font-size: 13px; margin-bottom: 15px; }
        .card { background: rgba(255, 255, 255, 0.08); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.15); border-radius: 16px; padding: 15px; margin-bottom: 15px; box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37); }
        label { display: block; font-size: 13px; color: #ff9966; margin-bottom: 5px; font-weight: bold; }
        select { width: 100%; padding: 12px; margin-bottom: 12px; border: 1px solid rgba(255, 255, 255, 0.25); background: rgba(0, 0, 0, 0.6); color: #fff; border-radius: 10px; box-sizing: border-box; font-size: 15px; }
        select option { background: #203a43; color: white; }
        .btn-merge { width: 100%; background: linear-gradient(135deg, #ff416c, #ff4b2b); color: white; border: none; padding: 15px; border-radius: 12px; font-size: 16px; font-weight: bold; cursor: pointer; box-shadow: 0 4px 15px rgba(255, 65, 108, 0.4); transition: 0.2s; }
        .btn-merge:hover { opacity: 0.9; }
        .warning-box { background: rgba(255, 193, 7, 0.15); border: 1px dashed #ffc107; padding: 12px; border-radius: 10px; margin-bottom: 15px; font-size: 13px; color: #ffc107; line-height: 1.6; }
        .nav-bar { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(15, 32, 39, 0.95); backdrop-filter: blur(10px); display: flex; justify-content: space-around; padding: 10px 0; border-top: 1px solid rgba(255, 255, 255, 0.1); z-index: 1000; }
        .nav-item { text-align: center; color: #aaa; text-decoration: none; font-size: 12px; }
        .nav-item.active, .nav-item:hover { color: #00f2fe; }
        .nav-item div { font-size: 20px; margin-bottom: 2px; }
        .btn-back { display: block; text-align: center; background: rgba(255,255,255,0.15); color: #fff; padding: 12px; border-radius: 10px; text-decoration: none; font-weight: bold; margin-top: 10px; }
    </style>
</head>
<body>

<div class="container">
    <h1>🔗 أداة دمج حسابات العملاء والديون</h1>
    <p class="sub-title">نقل كافة الفواتير والسندات والديون من عميل إلى آخر بأمان تام</p>

    <?php echo $msg; ?>

    <div class="card">
        <div class="warning-box">
            ⚠️ <b>تنبيه هام جداً:</b> عند إتمام عملية الدمج، سيتم نقل كافة الفواتير، وسندات القبض والصرف، والأرصدة للعميل الفرعي إلى العميل الرئيسي، <b>ثم سيتم حذف العميل الفرعي نهائياً</b> لمنع التكرار وضبط الحسابات.
        </div>

        <form method="POST" onsubmit="return confirm('هل أنت متأكد تماماً من رغبتك في دمج هذين الحسابين؟ هذه العملية لا يمكن التراجع عنها.');">
            
            <label>📤 العميل الفرعي (المراد نقله وحذفه):</label>
            <select name="source_id" required>
                <option value="">-- اختر العميل المراد دمجه وإلغاؤه --</option>
                <?php foreach ($customers_list as $cust): ?>
                    <option value="<?php object_id = $cust['id']; echo $cust['id']; ?>">
                        <?php echo htmlspecialchars($cust['name']); ?> (<?php echo htmlspecialchars($cust['region'] ?? 'بدون منطقة'); ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <label style="color: #38ef7d; margin-top: 10px;">📥 العميل الرئيسي (الذي ستنتقل إليه كافة الديون والفواتير):</label>
            <select name="target_id" required>
                <option value="">-- اختر العميل الرئيسي الأساسي --</option>
                <?php foreach ($customers_list as $cust): ?>
                    <option value="<?php echo $cust['id']; ?>">
                        <?php echo htmlspecialchars($cust['name']); ?> (<?php echo htmlspecialchars($cust['region'] ?? 'بدون منطقة'); ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" name="merge_now" class="btn-merge" style="margin-top: 15px;">🔗 تنفيذ دمج الحسابات والمديونيات فوراً</button>
        </form>

        <a href="customers.php" class="btn-back">⬅️ العودة إلى إدارة العملاء</a>
    </div>
</div>


<?php include 'nav_bar.php'; ?>

<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
