<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
if(session_status()===PHP_SESSION_NONE){session_start();} if(!isset($_SESSION["user_role"])){header("Location: login.php");exit;}
$db = new SQLite3(__DIR__ . '/vmax.db');

// إنشاء الجداول المتخصصة إذا لم تكن موجودة
$db->exec("CREATE TABLE IF NOT EXISTS warranties (id INTEGER PRIMARY KEY AUTOINCREMENT, customer_id INTEGER, device_name TEXT, warranty_code TEXT, start_date TEXT, end_date TEXT, status TEXT DEFAULT 'ساري');");
$db->exec("CREATE TABLE IF NOT EXISTS installments (id INTEGER PRIMARY KEY AUTOINCREMENT, customer_id INTEGER, amount REAL, due_date TEXT, status TEXT DEFAULT 'معلق');");
$db->exec("CREATE TABLE IF NOT EXISTS ratings (id INTEGER PRIMARY KEY AUTOINCREMENT, customer_id INTEGER, rating INTEGER, comment TEXT, created_date DATETIME DEFAULT CURRENT_TIMESTAMP);");
@$db->exec("ALTER TABLE customers ADD COLUMN points INTEGER DEFAULT 0");

$phone = trim($_GET['phone'] ?? '');
$customer = null;
$invoices = [];
$maintenance_items = [];
$warranties = [];
$installments = [];
$products = [];
$msg = "";

// استقبال تقييم العميل
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_rating'])) {
    $cid = intval($_POST['customer_id'] ?? 0);
    $rate = intval($_POST['rating'] ?? 5);
    $comm = trim($_POST['comment'] ?? '');
    if ($cid > 0) {
        $stmt = $db->prepare("INSERT INTO ratings (customer_id, rating, comment) VALUES (:cid, :rate, :comm)");
        $stmt->bindValue(':cid', $cid, SQLITE3_INTEGER);
        $stmt->bindValue(':rate', $rate, SQLITE3_INTEGER);
        $stmt->bindValue(':comm', $comm, SQLITE3_TEXT);
        $stmt->execute();
        $stmt->close();
        $msg = "⭐ شكراً لتقييمك الرائع! تمت إضافة 10 نقاط ولاء لحسابك.";
        $db->exec("UPDATE customers SET points = points + 10 WHERE id = $cid");
    }
}

// استقبال طلب صيانة جديد أرسله العميل
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_client_request'])) {
    $cname = trim($_POST['client_name'] ?? '');
    $dev = trim($_POST['device_name'] ?? '');
    $iss = trim($_POST['issue_desc'] ?? '');
    $cphone = trim($_POST['client_phone'] ?? '');

    if (!empty($cname) && !empty($dev)) {
        $stmt = $db->prepare("INSERT INTO maintenance (customer_name, device_name, issue, cost, status) VALUES (:cname, :dev, :iss, 0, 'طلب جديد قيد المراجعة')");
        $stmt->bindValue(':cname', $cname, SQLITE3_TEXT);
        $stmt->bindValue(':dev', $dev, SQLITE3_TEXT);
        $stmt->bindValue(':iss', $iss, SQLITE3_TEXT);
        $stmt->execute();
        $stmt->close();
        $msg = "✅ تم إرسال طلب الصيانة بنجاح إلى ورشة نظام ابوضياء العالمي!";
        $phone = $cphone;
    }
}

if (!empty($phone)) {
    $stmt = $db->prepare("SELECT * FROM customers WHERE phone = :phone LIMIT 1");
    $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
    $customer = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    $stmt->close();

    if ($customer) {
        $cid = $customer['id'];
        $inv_res = $db->query("SELECT * FROM invoices WHERE customer_id = $cid ORDER BY id DESC");
        while($inv = $inv_res->fetchArray(SQLITE3_ASSOC)) { $invoices[] = $inv; }

        $cname = $customer['name'];
        $m_res = $db->query("SELECT * FROM maintenance WHERE customer_name LIKE '%$cname%' ORDER BY id DESC");
        while($m = $m_res->fetchArray(SQLITE3_ASSOC)) { $maintenance_items[] = $m; }

        $w_res = $db->query("SELECT * FROM warranties WHERE customer_id = $cid ORDER BY id DESC");
        while($w = $w_res->fetchArray(SQLITE3_ASSOC)) { $warranties[] = $w; }

        $i_res = $db->query("SELECT * FROM installments WHERE customer_id = $cid ORDER BY due_date ASC");
        while($i = $i_res->fetchArray(SQLITE3_ASSOC)) { $installments[] = $i; }
    }
}

// جلب كتالوج المنتجات والعروض
$p_res = $db->query("SELECT * FROM products ORDER BY id DESC LIMIT 8");
while($p = $p_res->fetchArray(SQLITE3_ASSOC)) { $products[] = $p; }

$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <link rel="manifest" href="manifest.json">
    <meta name='mobile-web-app-capable' content='yes'>
    <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js");}</script>
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>بوابة العميل الرقمية الفاخرة - نظام ابوضياء العالمي</title>
    <style>
        body { font-family: 'Tahoma', sans-serif; background: linear-gradient(135deg, #0f2027, #203a43, #2c5364); color: #fff; margin: 0; padding: 12px; padding-bottom: 80px; min-height: 100vh; }
        .container { max-width: 620px; margin: auto; }
        
        .portal-header { text-align: center; margin-bottom: 20px; padding-top: 10px; }
        .portal-header h1 { color: #ffd200; font-size: 22px; margin: 0 0 5px 0; text-shadow: 0 2px 10px rgba(255,210,0,0.3); }
        .portal-header p { color: #b2bec3; font-size: 11px; margin: 0; }

        .glass-card { background: rgba(255, 255, 255, 0.06); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.12); padding: 16px; border-radius: 16px; margin-bottom: 14px; box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37); transition: transform 0.2s; }
        .glass-card:hover { border-color: rgba(255,210,0,0.4); }

        label { display: block; font-size: 11px; color: #ffd200; margin-bottom: 6px; font-weight: bold; }
        input, textarea, select { width: 100%; padding: 11px; margin-bottom: 10px; background: rgba(0, 0, 0, 0.5); color: #fff; border: 1px solid rgba(255,255,255,0.2); border-radius: 10px; box-sizing: border-box; font-size: 13px; outline: none; transition: border-color 0.2s; }
        input:focus, textarea:focus, select:focus { border-color: #ffd200; }
        input { text-align: center; font-weight: bold; }
        textarea { resize: vertical; height: 80px; }
        select option { background: #1a2f38; color: #fff; }

        .btn-gold { width: 100%; background: linear-gradient(135deg, #f6d365, #fda085); color: #000; padding: 12px; border: none; border-radius: 10px; font-weight: bold; cursor: pointer; font-size: 14px; box-shadow: 0 4px 15px rgba(246,211,101,0.4); transition: opacity 0.2s; }
        .btn-gold:active { opacity: 0.8; }

        .btn-wa { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; background: linear-gradient(135deg, #25d366, #128c7e); color: #fff; text-align: center; padding: 12px; border-radius: 10px; text-decoration: none; font-weight: bold; font-size: 13px; margin-top: 12px; box-shadow: 0 4px 15px rgba(37,211,102,0.4); }

        .table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 11px; background: rgba(0,0,0,0.3); border-radius: 10px; overflow: hidden; }
        .table th, .table td { padding: 9px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.08); }
        .table th { background: rgba(255,210,0,0.15); color: #ffd200; font-weight: bold; }

        .badge { background: rgba(56,239,125,0.2); color: #38ef7d; border: 1px solid #38ef7d; padding: 3px 8px; border-radius: 12px; font-size: 10px; font-weight: bold; display: inline-block; }
        .badge-red { background: rgba(255,77,77,0.2); color: #ff4d4d; border: 1px solid #ff4d4d; }
        
        .alert-box { background: rgba(56,239,125,0.15); border: 1px solid #38ef7d; color: #38ef7d; padding: 12px; border-radius: 10px; text-align: center; font-size: 12px; margin-bottom: 12px; font-weight: bold; box-shadow: 0 0 15px rgba(56,239,125,0.2); }

        .catalog-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-top: 10px; }
        .catalog-item { background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); padding: 12px; border-radius: 10px; text-align: center; transition: transform 0.2s; }
        .catalog-item:hover { transform: translateY(-3px); border-color: #ffd200; }
        
        .section-title { color: #ffd200; font-size: 13px; font-weight: bold; display: flex; align-items: center; gap: 6px; margin-bottom: 10px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 6px; }
    </style>
</head>
<body>

<div class="container">
    <div class="portal-header">
        <h1>👑 بوابة العميل الرقمية الفاخرة</h1>
        <p>نظام ابوضياء العالمي - سيئون السوق العام | نظام ابوضياء العالمي</p>
    </div>

    <?php if(!empty($msg)): ?>
        <div class="alert-box"><?php echo $msg; ?></div>
    <?php endif; ?>

    <div class="glass-card">
        <form method="GET">
            <label>📞 أدخل رقم هاتفك لاستعراض حسابك الشامل:</label>
            <button type="submit" class="btn-gold">🔍 عرض تفاصيل حسابي الذكي</button>
        </form>
    </div>

    <?php if(!empty($phone) && $customer): ?>
        <div class="glass-card" style="border-color: #38ef7d; background: rgba(56,239,125,0.04);">
            <h3 style="color:#ffd200; margin-top:0; font-size:16px; display:flex; justify-content:space-between; align-items:center;">
                <span class="badge">عميل معتمد</span>
            </h3>
            <div style="font-size:12px; margin-bottom:6px; color:#dfe6e9;">رقم الهاتف: <b><?php echo htmlspecialchars($customer['phone']); ?></b></div>
            <div style="font-size:12px; margin-bottom:6px; color:#dfe6e9;">⭐ رصيد نقاط الولاء: <b style="color:#ffd200; font-size:13px;"><?php echo intval($customer['points'] ?? 0); ?> نقطة</b></div>
            <div style="font-size:14px; font-weight:bold; margin-top:10px; background:rgba(0,0,0,0.3); padding:8px; border-radius:8px; display:flex; justify-content:space-between;">
                <span>إجمالي الديون المستحقة:</span>
                <span style="color:#ff4d4d;"><?php echo number_format($customer['balance'], 2); ?> ر.ي</span>
            </div>
            <?php if(floatval($customer['latitude']) != 0): ?>
                <div style="margin-top:10px; text-align:center;"><a href="https://maps.google.com/?q=<?php echo $customer['latitude']; ?>,<?php echo $customer['longitude']; ?>" target="_blank" style="color:#38ef7d; font-size:11px; text-decoration:none; font-weight:bold; background:rgba(56,239,125,0.1); padding:6px 12px; border-radius:20px; display:inline-block;">🗺️ انقر لعرض موقعك الجغرافي المسجل على الخريطة</a></div>
            <?php endif; ?>
        </div>

        <!-- 🧾 سجل الفواتير -->
        <div class="glass-card">
            <div class="section-title">🧾 سجل فواتيرك ومشترياتك السابقة</div>
            <?php if(!empty($invoices)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>رقم</th>
                            <th>الإجمالي</th>
                            <th>المتبقي</th>
                            <th>الإجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($invoices as $inv): ?>
                            <tr>
                                <td><b>#<?php echo $inv['id']; ?></b></td>
                                <td><?php echo number_format($inv['total'], 2); ?></td>
                                <td style="color:#ff4d4d; font-weight:bold;"><?php echo number_format($inv['remaining_amount'], 2); ?></td>
                                <td><a href="view_invoice.php?id=<?php echo $inv['id']; ?>" target="_blank" style="background:#ffd200; color:#000; padding:4px 8px; border-radius:6px; font-size:10px; text-decoration:none; font-weight:bold;">طباعة / PDF</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align:center; color:#b2bec3; font-size:11px;">لا توجد فواتير مسجلة حالياً.</p>
            <?php endif; ?>
        </div>

        <!-- 🏷️ بطاقات الضمان الرقمية -->
        <div class="glass-card">
            <div class="section-title">🏷️ بطاقات الضمان الرقمية للأجهزة</div>
            <?php if(!empty($warranties)): ?>
                <?php foreach($warranties as $w): ?>
                    <div style="background:rgba(0,0,0,0.3); padding:10px; border-radius:8px; margin-bottom:8px; font-size:11px; border-right:3px solid #38ef7d;">
                        <b style="color:#fff; font-size:12px;"><?php echo htmlspecialchars($w['device_name']); ?></b><br>
                        رقم الضمان: <span style="color:#ffd200; font-weight:bold;"><?php echo htmlspecialchars($w['warranty_code']); ?></span><br>
                        <span style="color:#b2bec3;">ساري من: <?php echo $w['start_date']; ?> إلى: <?php echo $w['end_date']; ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align:center; color:#b2bec3; font-size:11px;">لا توجد بطاقات ضمان نشطة مسجلة.</p>
            <?php endif; ?>
        </div>

        <!-- 📅 جدول الأقساط والديون -->
        <div class="glass-card">
            <div class="section-title">📅 جدول استحقاق الدفعات والأقساط</div>
            <?php if(!empty($installments)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>المبلغ</th>
                            <th>تاريخ الاستحقاق</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($installments as $ins): ?>
                            <tr>
                                <td style="color:#ff4d4d; font-weight:bold;"><?php echo number_format($ins['amount'], 2); ?> ر.ي</td>
                                <td><?php echo $ins['due_date']; ?></td>
                                <td><span class="badge"><?php echo $ins['status']; ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align:center; color:#b2bec3; font-size:11px;">لا توجد أقساط معلقة مسجلة.</p>
            <?php endif; ?>
        </div>

        <!-- 📱 حالة أجهزة الصيانة -->
        <div class="glass-card">
            <div class="section-title">📱 متابعة أجهزتك في ورشة الصيانة</div>
            <?php if(!empty($maintenance_items)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>الجهاز</th>
                            <th>العطل</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($maintenance_items as $m): ?>
                            <tr>
                                <td><b><?php echo htmlspecialchars($m['device_name']); ?></b></td>
                                <td><?php echo htmlspecialchars($m['issue']); ?></td>
                                <td><span class="badge"><?php echo htmlspecialchars($m['status']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align:center; color:#b2bec3; font-size:11px;">لا توجد أجهزة صيانة حالية في الورشة.</p>
            <?php endif; ?>
        </div>

        <!-- ⭐ تقييم رضا العملاء -->
        <div class="glass-card">
            <div class="section-title">⭐ تقييم تجربتك معنا (واكسب 10 نقاط ولاء)</div>
            <form method="POST">
                <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                <label>التقييم العام:</label>
                <select name="rating">
                    <option value="5">⭐⭐⭐⭐⭐ (ممتاز جداً - تجربة استثنائية)</option>
                    <option value="4">⭐⭐⭐⭐ (جيد جداً)</option>
                    <option value="3">⭐⭐⭐ (مقبول)</option>
                    <option value="2">⭐⭐ (يحتاج تحسين)</option>
                    <option value="1">⭐ (سيء)</option>
                </select>
                <label>ملاحظاتك واقراحاتك:</label>
                <textarea name="comment" placeholder="اكتب رأيك بصراحة لنستمر في التطوير..."></textarea>
                <button type="submit" name="submit_rating" class="btn-gold" style="background: linear-gradient(135deg, #38ef7d, #11998e); color:#000;">⭐ إرسال التقييم وكسب النقاط</button>
            </form>
        </div>


    <?php elseif(!empty($phone) && !$customer): ?>
        <div class="glass-card" style="border-color: #ff4d4d; background: rgba(255,77,77,0.06); text-align:center;">
            <b style="color:#ff4d4d; font-size:14px;">⚠️ الرقم غير مسجل كعميل رسمي في النظام!</b>
            <p style="font-size:11px; color:#b2bec3; margin-top:6px;">يمكنك إرسال طلب صيانة أو استفسار فوري عبر النموذج أدناه وسيتم التواصل معك:</p>
        </div>
    <?php endif; ?>

    <!-- 🛍️ كتالوج المنتجات والعروض -->
    <div class="glass-card">
        <div class="section-title">🛍️ أحدث الهواتف والعروض المتوفرة في المحل</div>
        <div class="catalog-grid">
            <?php if(!empty($products)): ?>
                <?php foreach($products as $prod): ?>
                    <div class="catalog-item">
                        <div style="font-size:12px; font-weight:bold; color:#fff; margin-bottom:5px;"><?php echo htmlspecialchars($prod['name']); ?></div>
                        <div style="font-size:13px; color:#38ef7d; font-weight:bold;"><?php echo number_format(floatval($prod['sell_price']), 2); ?> ر.ي</div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align:center; color:#b2bec3; font-size:11px; grid-column:span 2;">لا توجد منتجات معروضة حالياً.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- 🛠️ نموذج طلب صيانة أو استفسار جديد -->
    <div class="glass-card">
        <div class="section-title">🛠️ نموذج طلب صيانة أو استفسار سريع</div>
        <form method="POST">
            <input type="hidden" name="client_phone" value="<?php echo htmlspecialchars($phone); ?>">
            <label>👤 اسمك الكريم:</label>
            <input type="text" name="client_name" placeholder="ادخل اسمك الكامل..." value="<?php echo $customer ? htmlspecialchars($customer['name']) : ''; ?>" required>
            
            <label>📱 نوع الجهاز أو الهاتف:</label>
            <input type="text" name="device_name" placeholder="مثال: Samsung Galaxy S24 Ultra" required>
            
            <label>💬 تفاصيل العطل أو الطلب:</label>
            <textarea name="issue_desc" placeholder="اكتب وصفاً مختصراً للمشكلة أو الاستفسار..." required></textarea>

            <button type="submit" name="submit_client_request" class="btn-gold">🚀 إرسال الطلب إلى الورشة فوراً</button>
        </form>
    </div>

</div>


<?php include 'nav_bar.php'; ?>
<?php include "nav_menu.php"; ?>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>
</html>

