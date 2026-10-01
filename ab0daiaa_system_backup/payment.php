<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = null;
if (file_exists('db.php')) { include_once 'db.php'; }
if (!isset($conn) || !$conn) { $conn = @new SQLite3("vmax.db"); }

$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $beneficiary = $_POST['beneficiary'] ?? '';
    $amount = floatval($_POST['amount'] ?? 0);
    $expense_type = $_POST['expense_type'] ?? '';
    $notes = $_POST['notes'] ?? '';

    if ($conn) {
        @$conn->exec("CREATE TABLE IF NOT EXISTS payments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            beneficiary TEXT,
            amount REAL,
            expense_type TEXT,
            notes TEXT,
            created_at TEXT
        );");
        
        $stmt = $conn->prepare("INSERT INTO payments (beneficiary, amount, expense_type, notes, created_at) VALUES (?, ?, ?, ?, datetime('now'))");
        if ($stmt) {
            $stmt->bindValue(1, $beneficiary, SQLITE3_TEXT);
            $stmt->bindValue(2, $amount, SQLITE3_FLOAT);
            $stmt->bindValue(3, $expense_type, SQLITE3_TEXT);
            $stmt->bindValue(4, $notes, SQLITE3_TEXT);
            $stmt->execute();
        }
    }
    $success = true;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <link rel="manifest" href="manifest.json">
    <meta name='mobile-web-app-capable' content='yes'>
    <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js");}</script>
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>سند صرف رسمي - أبو ضياء</title>
    <style>
        * { box-sizing: border-box; font-family: 'Tahoma', Arial, sans-serif; }
        body { background: #e2e8f0; margin: 0; padding: 10px; display: flex; justify-content: center; flex-direction: column; align-items: center; }
        .top-nav { width: 100%; max-width: 650px; background: #000; padding: 8px 12px; border-radius: 6px; display: flex; justify-content: space-between; gap: 5px; margin-bottom: 10px; flex-wrap: wrap; }
        .nav-link { color: #fff; text-decoration: none; font-size: 11px; font-weight: bold; padding: 6px 10px; border-radius: 4px; background: #333; }
        .nav-link.active { background: #2563eb; }
        .voucher-card { background: #fff; width: 100%; max-width: 650px; padding: 20px; border: 2px solid #000; border-radius: 6px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #000; border-bottom: 2px solid #000; padding-bottom: 8px; margin-top: 0; font-size: 16px; }
        .form-group { margin-bottom: 12px; }
        label { display: block; font-weight: bold; margin-bottom: 3px; color: #000; font-size: 12px; }
        select, input, textarea { width: 100%; padding: 8px; border: 2px solid #000; border-radius: 4px; font-size: 13px; font-weight: bold; background: #fff; }
        textarea { resize: vertical; height: 60px; }
        .btn-submit { background: #000; color: #fff; border: none; width: 100%; padding: 12px; font-size: 15px; font-weight: bold; border-radius: 4px; cursor: pointer; margin-top: 5px; }
        .alert { background: #dcfce7; color: #166534; padding: 10px; border: 2px solid #16a34a; border-radius: 4px; text-align: center; font-weight: bold; margin-bottom: 10px; font-size: 13px; }
    </style>
</head>
<body>

    <div class="top-nav">
        <a href="index.php" class="nav-link">📄 فاتورة مبيعات</a>
        <a href="receipt.php" class="nav-link">📥 سند قبض</a>
        <a href="payment.php" class="nav-link active">📤 سند صرف</a>
        <a href="journal.php" class="nav-link">⚖️ قيد يومية</a>
        <a href="settings.php" class="nav-link">⚙️ الإعدادات</a>
    </div>

    <div class="voucher-card">
        <h2>📤 سند صرف نقدي (مصروفات ومدفوعات)</h2>

        <?php if ($success): ?>
            <div class="alert">✅ تم حفظ سند الصرف وتوثيقه في النظام بنجاح!</div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>المستفيد (اسم الشخص أو جهة الصرف):</label>
                <input type="text" name="beneficiary" placeholder="أجور عمال / محطة وقود..." required>
            </div>

            <div class="form-group">
                <label>المبلغ المصروف (ريال يمني):</label>
                <input type="number" step="0.01" name="amount" placeholder="0.00" required>
            </div>

            <div class="form-group">
                <label>نوع المصروف:</label>
                <select name="expense_type">
                    <option value="مصروفات تشغيلية">مصروفات تشغيلية / سيارة</option>
                    <option value="أجور ومرتبات">أجور ومرتبات</option>
                    <option value="نثريات وضيافة">نثريات وضيافة</option>
                </select>
            </div>

            <div class="form-group">
                <label>البيان / تفاصيل الصرف:</label>
                <textarea name="notes">قيمة شراء وقود لسيارة التوزيع</textarea>
            </div>

            <button type="submit" class="btn-submit">💾 حفظ واعتماد سند الصرف</button>
        </form>
    </div>

    <?php if (file_exists('nav_bar.php')) include 'nav_bar.php'; ?>
    <?php if (file_exists('nav_menu.php')) include 'nav_menu.php'; ?>
    <?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); if(file_exists("global_footer.php")) include "global_footer.php"; } ?>
</body>
</html>
