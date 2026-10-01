<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$db = new SQLite3('vmax.db', SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
$db->busyTimeout(5000);

// إنشاء جدول القيود العامة المتقدمة ودليل الحسابات
$db->exec("CREATE TABLE IF NOT EXISTS accounts_chart (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    account_code TEXT,
    account_name TEXT,
    account_type TEXT
);");

$db->exec("CREATE TABLE IF NOT EXISTS general_journal (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    entry_number TEXT,
    date TEXT,
    description TEXT,
    account_name TEXT,
    debit REAL DEFAULT 0,
    credit REAL DEFAULT 0,
    user_name TEXT
);");

// إضافة الحسابات الرئيسية الافتراضية للمعايير العالمية إن كانت فارغة
$count_acc = $db->querySingle("SELECT COUNT(*) FROM accounts_chart");
if ($count_acc == 0) {
    $defaults = [
        ['101', 'النقدية بالصندوق', 'أصول'],
        ['102', 'البنك', 'أصول'],
        ['103', 'العملاء', 'أصول'],
        ['104', 'المخزون', 'أصول'],
        ['201', 'الموردين', 'خصوم'],
        ['301', 'رأس المال', 'حقوق ملكية'],
        ['401', 'إيرادات المبيعات', 'إيرادات'],
        ['501', 'مصروفات الإيجار والكهرباء', 'مصروفات']
    ];
    foreach ($defaults as $acc) {
        $stmt = $db->prepare("INSERT INTO accounts_chart (account_code, account_name, account_type) VALUES (?, ?, ?)");
        $stmt->bindValue(1, $acc[0], SQLITE3_TEXT);
        $stmt->bindValue(2, $acc[1], SQLITE3_TEXT);
        $stmt->bindValue(3, $acc[2], SQLITE3_TEXT);
        $stmt->execute();
    }
}

$settings = [];
$s_res = @$db->query("SELECT key, value FROM settings");
if ($s_res) { while ($s_row = $s_res->fetchArray(SQLITE3_ASSOC)) { $settings[$s_row['key']] = $s_row['value']; } }

$store_name = $settings['store_name'] ?? 'محل هوت فون';
$store_tagline = $settings['store_tagline'] ?? 'جوالات إكسسوارات وصيانة شاملة';
$store_address = $settings['store_address'] ?? 'سيئون - السوق العام';

$accounts = [];
$acc_res = $db->query("SELECT * FROM accounts_chart ORDER BY account_code ASC");
while ($row = $acc_res->fetchArray(SQLITE3_ASSOC)) { $accounts[] = $row; }

$success_msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_general_entry'])) {
    $entry_number = trim($_POST['entry_number'] ?? 'JE-' . rand(1000, 9999));
    $date = $_POST['date'] ?? date('Y-m-d H:i:s');
    $description = trim($_POST['description'] ?? 'قيد عام');
    $user_name = $_SESSION['username'] ?? 'مدير النظام';

    $lines = $_POST['lines'] ?? [];
    $total_debit = 0;
    $total_credit = 0;

    foreach ($lines as $line) {
        $total_debit += floatval($line['debit'] ?? 0);
        $total_credit += floatval($line['credit'] ?? 0);
    }

    // التحقق من توازن القيد (المعايير المحاسبية العالمية: إجمالي المدين يجب أن يساوي إجمالي الدائن)
    if (abs($total_debit - $total_credit) < 0.01 && $total_debit > 0) {
        $db->exec("BEGIN TRANSACTION;");
        $stmt = $db->prepare("INSERT INTO general_journal (entry_number, date, description, account_name, debit, credit, user_name) VALUES (:num, :date, :desc, :acc, :deb, :cred, :user)");
        
        foreach ($lines as $line) {
            $deb = floatval($line['debit'] ?? 0);
            $cred = floatval($line['credit'] ?? 0);
            if ($deb > 0 || $cred > 0) {
                $stmt->bindValue(':num', $entry_number, SQLITE3_TEXT);
                $stmt->bindValue(':date', $date, SQLITE3_TEXT);
                $stmt->bindValue(':desc', $description, SQLITE3_TEXT);
                $stmt->bindValue(':acc', $line['account'], SQLITE3_TEXT);
                $stmt->bindValue(':deb', $deb, SQLITE3_FLOAT);
                $stmt->bindValue(':cred', $cred, SQLITE3_FLOAT);
                $stmt->bindValue(':user', $user_name, SQLITE3_TEXT);
                $stmt->execute();
            }
        }
        $db->exec("COMMIT;");
        $success_msg = "✅ تم ترحيل وحفظ القيد المحاسبي المزدوج بنجاح وفق المعايير العالمية!";
    } else {
        $success_msg = "❌ خطأ: القيد غير متوازن! إجمالي المدين (" . $total_debit . ") لا يساوي إجمالي الدائن (" . $total_credit . ").";
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>القيود العامة المحاسبية - V-MAX PRO</title>
    <style>
        body { font-family: Tahoma, sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 10px; padding-bottom: 110px; }
        .header { text-align: center; font-size: 16px; font-weight: 900; color: #38bdf8; margin-bottom: 12px; }
        .card { background: #1e293b; border: 1px solid #38bdf8; border-radius: 10px; padding: 12px; margin-bottom: 12px; }
        .form-control { width: 100%; padding: 8px; background: #0f172a; color: #fff; border: 1px solid #38bdf8; border-radius: 8px; box-sizing: border-box; margin-bottom: 8px; font-size: 11px; }
        .btn { background: #22c55e; color: #fff; border: none; padding: 10px; border-radius: 8px; font-weight: bold; width: 100%; font-size: 12px; cursor: pointer; }
        .line-row { display: flex; gap: 4px; margin-bottom: 6px; align-items: center; }
        .line-row select, .line-row input { flex: 1; padding: 6px; background: #0f172a; color: #fff; border: 1px solid #38bdf8; border-radius: 6px; font-size: 10px; }
        .alert { padding: 8px; border-radius: 6px; text-align: center; font-size: 11px; font-weight: bold; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="header">🌐 القيود العامة المحاسبية (المعايير العالمية)</div>

    <?php if($success_msg): ?>
    <div class="alert" style="background: <?php echo strpos($success_msg, '✅') !== false ? '#16a34a' : '#dc2626'; ?>; color: #fff;"><?php echo $success_msg; ?></div>
    <?php endif; ?>

    <div class="card">
        <h3 style="margin-top: 0; font-size: 12px; color: #38bdf8;">➕ تسجيل قيد مالي مزدوج جديد</h3>
        <form method="POST">
            <input type="hidden" name="save_general_entry" value="1">
            
            <div style="display: flex; gap: 6px;">
                <div style="flex:1;">
                    <label style="font-size: 10px;">رقم القيد:</label>
                    <input type="text" name="entry_number" class="form-control" value="JE-<?php echo rand(1000,9999); ?>" required>
                </div>
                <div style="flex:1;">
                    <label style="font-size: 10px;">التاريخ:</label>
                    <input type="date" name="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div>

            <label style="font-size: 10px;">شرح القيد (البيان العام):</label>
            <input type="text" name="description" class="form-control" required placeholder="مثال: اثبات سداد مصروفات أو إيرادات متنوعة...">

            <h4 style="font-size: 11px; color: #38bdf8; margin: 8px 0 4px 0;">بنود القيد (مدين ودائن):</h4>
            
            <div id="linesContainer">
                <!-- بند أول (مدين) -->
                <div class="line-row">
                    <select name="lines[0][account]" class="form-control" style="margin:0;">
                        <?php foreach($accounts as $acc): ?>
                        <option value="<?php echo $acc['account_name']; ?>"><?php echo $acc['account_code'] . ' - ' . $acc['account_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" step="any" name="lines[0][debit]" placeholder="مدين" class="form-control" style="margin:0;" oninput="calcTotals()">
                    <input type="number" step="any" name="lines[0][credit]" placeholder="دائن" class="form-control" style="margin:0;" value="0" oninput="calcTotals()">
                </div>
                <!-- بند ثان (دائن) -->
                <div class="line-row">
                    <select name="lines[1][account]" class="form-control" style="margin:0;">
                        <?php foreach($accounts as $acc): ?>
                        <option value="<?php echo $acc['account_name']; ?>"><?php echo $acc['account_code'] . ' - ' . $acc['account_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" step="any" name="lines[1][debit]" placeholder="مدين" class="form-control" style="margin:0;" value="0" oninput="calcTotals()">
                    <input type="number" step="any" name="lines[1][credit]" placeholder="دائن" class="form-control" style="margin:0;" oninput="calcTotals()">
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; font-size: 11px; font-weight: bold; margin: 10px 0; background: #0f172a; padding: 8px; border-radius: 6px; border: 1px solid #334155;">
                <span style="color: #4ade80;">إجمالي المدين: <span id="sumDebit">0</span></span>
                <span style="color: #f87171;">إجمالي الدائن: <span id="sumCredit">0</span></span>
            </div>

            <button type="submit" class="btn">🚀 ترحيل وحفظ القيد المحاسبي</button>
        </form>
    </div>

    <script>
    function calcTotals() {
        let debits = document.querySelectorAll('input[name*="[debit]"]');
        let credits = document.querySelectorAll('input[name*="[credit]"]');
        let sumD = 0, sumC = 0;
        debits.forEach(inp => sumD += parseFloat(inp.value) || 0);
        credits.forEach(inp => sumC += parseFloat(inp.value) || 0);
        document.getElementById('sumDebit').innerText = sumD.toLocaleString();
        document.getElementById('sumCredit').innerText = sumC.toLocaleString();
    }
    </script>
    <?php if(file_exists('nav_menu.php')) { include 'nav_menu.php'; } ?>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>
</html>

