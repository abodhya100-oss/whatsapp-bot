<?php
// purchase.php - صفحة فواتير الشراء والتوريد لـ نظام ابوضياء العالمي
error_reporting(E_ALL);
ini_set('display_errors', 1);

$db_file = '/data/data/com.termux/files/home/secure_dbs/vmax.db';
if(session_status()===PHP_SESSION_NONE){session_start();} if(!isset($_SESSION["user_role"])){header("Location: login.php");exit;}
$db = new SQLite3($db_file);

// إنشاء جدول فواتير الشراء إن لم يكن موجوداً
$db->exec("CREATE TABLE IF NOT EXISTS purchases (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    supplier_name TEXT NOT NULL,
    total REAL NOT NULL,
    notes TEXT,
    created_date DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$msg = "";

// معالجة حفظ فاتورة الشراء الجديدة
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_purchase'])) {
    $supplier = trim($_POST['supplier_name'] ?? '');
    $total = floatval($_POST['total'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');

    if (!empty($supplier) && $total > 0) {
        $stmt = $db->prepare("INSERT INTO purchases (supplier_name, total, notes) VALUES (:supplier, :total, :notes)");
        $stmt->bindValue(':supplier', $supplier, SQLITE3_TEXT);
        $stmt->bindValue(':total', $total, SQLITE3_FLOAT);
        $stmt->bindValue(':notes', $notes, SQLITE3_TEXT);
        if ($stmt->execute()) {
            $msg = "<div style='background:rgba(40,167,69,0.3); color:#28a745; padding:12px; border-radius:12px; text-align:center; font-weight:bold; margin-bottom:15px;'>✅ تم حفظ فاتورة الشراء بنجاح!</div>";
        }
        $stmt->close();
    } else {
        $msg = "<div style='background:rgba(255,193,7,0.3); color:#ffc107; padding:12px; border-radius:12px; text-align:center; font-weight:bold; margin-bottom:15px;'>⚠️ يرجى إدخال اسم المورد وإجمالي المبلغ بشكل صحيح.</div>";
    }
}

// جلب قائمة فواتير الشراء السابقة
$purchases_list = [];
$res = $db->query("SELECT * FROM purchases ORDER BY id DESC");
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $purchases_list[] = $row;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <link rel="manifest" href="manifest.json">
    <meta name='mobile-web-app-capable' content='yes'>
    <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js");}</script>
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>نظام ابوضياء العالمي - فواتير الشراء</title>
    <style>
        body { font-family: 'Tahoma', sans-serif; background: linear-gradient(135deg, #0f2027, #203a43, #2c5364); color: #fff; margin: 0; padding: 0; padding-bottom: 90px; }
        .container { max-width: 650px; margin: 15px auto; padding: 15px; }
        h1 { text-align: center; font-size: 22px; margin-bottom: 5px; color: #ff9966; }
        p.sub-title { text-align: center; color: #bbb; font-size: 13px; margin-bottom: 15px; }
        .card { background: rgba(255, 255, 255, 0.08); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.15); border-radius: 16px; padding: 15px; margin-bottom: 15px; box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37); }
        label { display: block; font-size: 13px; color: #ffae42; margin-bottom: 5px; font-weight: bold; }
        input, textarea { width: 100%; padding: 11px; margin-bottom: 10px; border: 1px solid rgba(255, 255, 255, 0.25); background: rgba(0, 0, 0, 0.5); color: #fff; border-radius: 10px; box-sizing: border-box; font-size: 15px; }
        .btn-save { width: 100%; background: linear-gradient(135deg, #ff9966, #ff5e62); color: white; border: none; padding: 14px; border-radius: 12px; font-size: 16px; font-weight: bold; cursor: pointer; box-shadow: 0 4px 15px rgba(255, 94, 98, 0.4); transition: 0.2s; }
        .btn-save:hover { opacity: 0.9; }
        .purchase-item { background: rgba(0, 0, 0, 0.3); border: 1px solid rgba(255, 255, 255, 0.15); border-radius: 12px; padding: 12px; margin-bottom: 10px; border-right: 4px solid #ff9966; }
        .nav-bar { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(15, 32, 39, 0.95); backdrop-filter: blur(10px); display: flex; justify-content: space-around; padding: 10px 0; border-top: 1px solid rgba(255, 255, 255, 0.1); z-index: 1000; }
        .nav-item { text-align: center; color: #aaa; text-decoration: none; font-size: 12px; }
        .nav-item.active, .nav-item:hover { color: #00f2fe; }
        .nav-item div { font-size: 20px; margin-bottom: 2px; }
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

<div class="container">
    <h1>📥 تسجيل فاتورة شراء جديدة</h1>
    <p class="sub-title">إدارة فواتير الموردين والمشتريات المخزنية</p>

    <?php echo $msg; ?>

    <!-- نموذج فاتورة الشراء -->
    <div class="card">
        <form method="POST">
            <label>🏢 اسم المورد أو الشركة المزودة:</label>
            <input type="text" name="supplier_name" placeholder="مثال: شركة هائل سعيد أنعم" required>

            <label>💰 إجمالي مبلغ الفاتورة (ر.ي):</label>
            <input type="number" step="0.01" name="total" placeholder="0.00" required>

            <label>📝 ملاحظات أو تفاصيل الأصناف:</label>
            <textarea name="notes" rows="2" placeholder="أدخل تفاصيل البضاعة المشتراة..."></textarea>

            <button type="submit" name="save_purchase" class="btn-save">💾 حفظ واعتماد فاتورة الشراء</button>
        </form>
    </div>

    <!-- سجل فواتير الشراء السابقة -->
    <div class="card">
        <h3 style="margin-top:0; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:8px; font-size: 15px; color: #ff9966;">📋 أرشيف فواتير الشراء</h3>
        
        <?php if (count($purchases_list) > 0): ?>
            <?php foreach ($purchases_list as $pur): ?>
                <div class="purchase-item">
                    <div style="display:flex; justify-content:space-between; font-size:13px; margin-bottom:4px;">
                        <strong>فاتورة شراء #<?php echo $pur['id']; ?></strong>
                        <span style="color:#aaa; font-size:11px;"><?php echo $pur['created_date']; ?></span>
                    </div>
                    <div style="font-size:14px; color:#fff; margin-bottom:4px;">المورد: <b><?php echo htmlspecialchars($pur['supplier_name']); ?></b></div>
                    <div style="display:flex; justify-content:space-between; font-size:14px; font-weight:bold;">
                        <span>الإجمالي: <span style="color:#ff9966;"><?php echo number_format($pur['total'], 2); ?> ريال</span></span>
                    </div>
                    <?php if(!empty($pur['notes'])): ?>
                        <div style="font-size:12px; color:#bbb; margin-top:5px; border-top:1px dashed rgba(255,255,255,0.1); pt:4px;">ملاحظات: <?php echo htmlspecialchars($pur['notes']); ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align: center; color: #aaa; font-size: 13px;">لا توجد فواتير شراء مسجلة حتى الآن.</p>
        <?php endif; ?>
</div>

<!-- القائمة السفلية -->

<?php include 'nav_bar.php'; ?>

<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
