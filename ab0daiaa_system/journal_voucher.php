<?php
// journal_voucher.php - نظام قيود اليومية العامة ودليل الحسابات لـ نظام ابوضياء العالمي
require_once 'auth.php';

$db_file = '/data/data/com.termux/files/home/secure_dbs/vmax.db';
if(session_status()===PHP_SESSION_NONE){session_start();} if(!isset($_SESSION["user_role"])){header("Location: login.php");exit;}
$db = new SQLite3($db_file);

// 1. إنشاء جداول القيود المحاسبية العالمية ودليل الحسابات
$db->exec("CREATE TABLE IF NOT EXISTS accounts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT UNIQUE,
    name TEXT NOT NULL,
    type TEXT NOT NULL
)");

$db->exec("CREATE TABLE IF NOT EXISTS journal_entries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    entry_date DATE,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$db->exec("CREATE TABLE IF NOT EXISTS journal_details (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    entry_id INTEGER,
    account_id INTEGER,
    debit REAL DEFAULT 0,
    credit REAL DEFAULT 0
)");

// إضافة حسابات افتراضية أساسية إذا كان الدليل فارغاً
$check_acc = $db->querySingle("SELECT COUNT(*) FROM accounts");
if ($check_acc == 0) {
    $db->exec("INSERT INTO accounts (code, name, type) VALUES ('101', 'الصندوق الرئيسي (الدرج)', 'أصول')");
    $db->exec("INSERT INTO accounts (code, name, type) VALUES ('102', 'حساب البنك (الكريمي / موبايل موني)', 'أصول')");
    $db->exec("INSERT INTO accounts (code, name, type) VALUES ('103', 'حسابات العملاء (المدينون)', 'أصول')");
    $db->exec("INSERT INTO accounts (code, name, type) VALUES ('201', 'حسابات الموردين (الدائنون)', 'خصوم')");
    $db->exec("INSERT INTO accounts (code, name, type) VALUES ('301', 'رأس المال', 'حقوق ملكية')");
    $db->exec("INSERT INTO accounts (code, name, type) VALUES ('401', 'إيرادات المبيعات والصيانة', 'إيرادات')");
    $db->exec("INSERT INTO accounts (code, name, type) VALUES ('501', 'المصروفات النثرية والتشغيلية', 'مصروفات')");
}

$msg = "";

// معالجة حفظ القيد المحاسبي المزدوج
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_journal'])) {
    $entry_date = trim($_POST['entry_date'] ?? date('Y-m-d'));
    $description = trim($_POST['description'] ?? '');
    
    $account_ids = $_POST['account_id'] ?? [];
    $debits = $_POST['debit'] ?? [];
    $credits = $_POST['credit'] ?? [];

    $total_debit = 0;
    $total_credit = 0;

    for ($i = 0; $i < count($account_ids); $i++) {
        $total_debit += floatval($debits[$i] ?? 0);
        $total_credit += floatval($credits[$i] ?? 0);
    }

    // شرط القيد المحاسبي العالمي: يجب أن يتساوى المدين مع الدائن تماماً
    if (abs($total_debit - $total_credit) > 0.001 && $total_debit > 0) {
        $msg = "<div style='background:rgba(220,53,69,0.3); color:#ff4d4d; padding:10px; border-radius:10px; text-align:center; font-weight:bold; margin-bottom:10px;'>❌ خطأ محاسبي: إجمالي المدين (" . number_format($total_debit, 2) . ") لا يساوي إجمالي الدائن (" . number_format($total_credit, 2) . ")! القيد غير متوازن.</div>";
    } elseif ($total_debit <= 0) {
        $msg = "<div style='background:rgba(255,193,7,0.3); color:#ffc107; padding:10px; border-radius:10px; text-align:center; font-weight:bold; margin-bottom:10px;'>⚠️ يرجى إدخال مبالغ صحيحة للقيود.</div>";
    } else {
        // حفظ رأس القيد
        $stmt = $db->prepare("INSERT INTO journal_entries (entry_date, description) VALUES (:ed, :desc)");
        $stmt->bindValue(':ed', $entry_date, SQLITE3_TEXT);
        $stmt->bindValue(':desc', $description, SQLITE3_TEXT);
        $stmt->execute();
        $entry_id = $db->lastInsertRowID();
        $stmt->close();

        // حفظ تفاصيل القيد (الأسطر)
        for ($i = 0; $i < count($account_ids); $i++) {
            $acc_id = intval($account_ids[$i]);
            $deb = floatval($debits[$i] ?? 0);
            $cre = floatval($credits[$i] ?? 0);

            if ($acc_id > 0 && ($deb > 0 || $cre > 0)) {
                $dt_stmt = $db->prepare("INSERT INTO journal_details (entry_id, account_id, debit, credit) VALUES (:eid, :aid, :deb, :cre)");
                $dt_stmt->bindValue(':eid', $entry_id, SQLITE3_INTEGER);
                $dt_stmt->bindValue(':aid', $acc_id, SQLITE3_INTEGER);
                $dt_stmt->bindValue(':deb', $deb, SQLITE3_FLOAT);
                $dt_stmt->bindValue(':cre', $cre, SQLITE3_FLOAT);
                $dt_stmt->execute();
                $dt_stmt->close();
            }
        }
        $msg = "<div style='background:rgba(40,167,69,0.3); color:#28a745; padding:10px; border-radius:10px; text-align:center; font-weight:bold; margin-bottom:10px;'>✅ تم ترحيل وحفظ قيد اليومية العامة بنجاح وتوازن تام!</div>";
    }
}

// جلب دليل الحسابات للاختيار
$accounts_res = $db->query("SELECT * FROM accounts ORDER BY code ASC");
$accounts_list = [];
while($acc = $accounts_res->fetchArray(SQLITE3_ASSOC)) {
    $accounts_list[] = $acc;
}

// جلب القيود المسجلة السابقة
$entries_res = $db->query("SELECT * FROM journal_entries ORDER BY id DESC LIMIT 20");
$entries_list = [];
while($en = $entries_res->fetchArray(SQLITE3_ASSOC)) {
    $entries_list[] = $en;
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
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>نظام ابوضياء العالمي - قيود اليومية العامة والمحاسبة المزدوجة</title>
    <style>
        body { font-family: 'Tahoma', sans-serif; background: linear-gradient(135deg, #0f2027, #203a43, #2c5364); color: #fff; margin: 0; padding: 0; padding-bottom: 110px; }
        .container { max-width: 720px; margin: 15px auto; padding: 15px; }
        h1 { text-align: center; font-size: 20px; margin-bottom: 4px; color: #ffd200; }
        p.sub-title { text-align: center; color: #bbb; font-size: 12px; margin-bottom: 12px; }
        .card { background: rgba(255, 255, 255, 0.08); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.15); border-radius: 14px; padding: 12px; margin-bottom: 12px; box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37); }
        label { display: block; font-size: 11px; color: #ffd200; margin-bottom: 3px; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 9px; margin-bottom: 8px; border: 1px solid rgba(255, 255, 255, 0.25); background: rgba(0, 0, 0, 0.5); color: #fff; border-radius: 8px; box-sizing: border-box; font-size: 13px; }
        select option { background: #FFFFFF; color: white; }
        
        .row-entry { display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 6px; margin-bottom: 6px; background: rgba(0,0,0,0.2); padding: 8px; border-radius: 8px; }
        .btn-action { width: 100%; background: linear-gradient(135deg, #ffd200, #f39c12); color: #000; border: none; padding: 12px; border-radius: 10px; font-size: 15px; font-weight: bold; cursor: pointer; box-shadow: 0 4px 15px rgba(255, 210, 0, 0.4); margin-top: 5px; }
        
        .entry-card { background: rgba(0, 0, 0, 0.3); padding: 10px; border-radius: 8px; margin-bottom: 8px; border-right: 4px solid #ffd200; font-size: 12px; }
        
        /* شريط البصمة الثابت */
        .nav-bar { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(15, 32, 39, 0.98); backdrop-filter: blur(10px); border-top: 1px solid rgba(255, 255, 255, 0.15); z-index: 1000; }
        .system-signature { text-align: center; font-size: 11px; color: #ffd200; background: rgba(0, 0, 0, 0.7); padding: 5px 10px; border-bottom: 1px solid rgba(255, 210, 0, 0.2); font-weight: bold; display: flex; justify-content: center; align-items: center; gap: 8px; }
        .footer-wa-btn { background: #FFFFFF; color: #fff; padding: 2px 8px; border-radius: 4px; text-decoration: none; font-size: 10px; font-weight: bold; display: inline-flex; align-items: center; gap: 2px; }
        .nav-links { display: flex; justify-content: space-around; padding: 8px 0; }
        .nav-item { text-align: center; color: #aaa; text-decoration: none; font-size: 12px; }
        .nav-item.active, .nav-item:hover { color: #ffd200; }
        .nav-item div { font-size: 18px; margin-bottom: 1px; }
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
    <h1>🏛️ قيود اليومية العامة (المحاسبة المزدوجة)</h1>
    <p class="sub-title">إدارة وترحيل قيود البنوك والشركات الكبرى (مدين ودائن)</p>

    <?php echo $msg; ?>

    <div class="card">
        <h3 style="margin-top:0; font-size:13px; color:#ffd200; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:5px;">➕ تسجيل قيد يومية عام جديد</h3>
        <form method="POST">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                <div>
                    <label>تاريخ القيد:</label>
                    <input type="date" name="entry_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div>
                    <label>وصف القيد (البيان العام):</label>
                    <input type="text" name="description" placeholder="مثلاً: قيد إثبات رأس المال أو مصاريف..." required>
                </div>
            </div>

            <label style="margin-top: 8px;">بنود القيد المحاسبي (يجب أن يتساوى إجمالي المدين مع الدائن):</label>
            
            <div id="entry-rows">
                <div class="row-entry">
                    <select name="account_id[]" required>
                        <option value="">اختر الحساب...</option>
                        <?php foreach($accounts_list as $acc): ?>
                            <option value="<?php echo $acc['id']; ?>"><?php echo $acc['code'] . ' - ' . $acc['name'] . ' (' . $acc['type'] . ')'; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" step="0.01" name="debit[]" placeholder="المدين (ر.ي)" value="0">
                    <input type="number" step="0.01" name="credit[]" placeholder="الدائن (ر.ي)" value="0">
                </div>
                <div class="row-entry">
                    <select name="account_id[]" required>
                        <option value="">اختر الحساب...</option>
                        <?php foreach($accounts_list as $acc): ?>
                            <option value="<?php echo $acc['id']; ?>"><?php echo $acc['code'] . ' - ' . $acc['name'] . ' (' . $acc['type'] . ')'; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" step="0.01" name="debit[]" placeholder="المدين (ر.ي)" value="0">
                    <input type="number" step="0.01" name="credit[]" placeholder="الدائن (ر.ي)" value="0">
                </div>
            </div>

            <button type="submit" name="save_journal" class="btn-action">💾 ترحيل وحفظ القيد المحاسبي</button>
        </form>
    </div>

    <div class="card">
        <h3 style="margin-top:0; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:6px; font-size: 13px; color: #ffd200;">📜 سجل القيود المحاسبية المرحّلة</h3>
        <?php if(count($entries_list) > 0): ?>
            <?php foreach($entries_list as $en): ?>
                <div class="entry-card">
                    <div style="display:flex; justify-content:space-between; font-weight:bold; color:#ffd200;">
                        <span>قيد رقم #<?php echo $en['id']; ?></span>
                        <span><?php echo $en['entry_date']; ?></span>
                    </div>
                    <div style="margin-top:3px; color:#fff;"><?php echo htmlspecialchars($en['description']); ?></div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align: center; color: #aaa; font-size: 11px;">لا توجد قيود مسجلة حتى الآن.</p>
        <?php endif; ?>
    </div>
</div>


<?php include 'nav_bar.php'; ?>

<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
