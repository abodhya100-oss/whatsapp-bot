<?php
// edit_customer.php - تعديل بيانات العميل والمديونية - V-MAX PRO
include_once 'config_loader.php';
$dbFile = '/data/data/com.termux/files/home/secure_dbs/vmax.db';
if(!file_exists($dbFile)) { $dbFile = 'database.db'; }
$db = new SQLite3($dbFile);

// التأكد من وجود أعمدة جدول العملاء كاملة
@$db->exec("ALTER TABLE customers ADD COLUMN location TEXT");
@$db->exec("ALTER TABLE customers ADD COLUMN street TEXT");
@$db->exec("ALTER TABLE customers ADD COLUMN debt REAL DEFAULT 0");

$c_id = intval($_GET['c_id'] ?? 0);
if ($c_id <= 0) {
    echo "<script>window.location.href='customers.php';</script>";
    exit;
}

// معالجة حفظ التعديلات عند الضغط على زر الحفظ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['c_name'] ?? '');
    $phone = trim($_POST['c_phone'] ?? '');
    $location = trim($_POST['c_location'] ?? '');
    $street = trim($_POST['c_street'] ?? '');
    $new_debt = floatval($_POST['c_debt'] ?? 0);

    if (!empty($name)) {
        // تحديث البيانات الأساسية والمديونية المباشرة في جدول العملاء
        $stmt = $db->prepare("UPDATE customers SET name = ?, phone = ?, location = ?, street = ?, debt = ? WHERE id = ?");
        $stmt->bindValue(1, $name, SQLITE3_TEXT);
        $stmt->bindValue(2, $phone, SQLITE3_TEXT);
        $stmt->bindValue(3, $location, SQLITE3_TEXT);
        $stmt->bindValue(4, $street, SQLITE3_TEXT);
        $stmt->bindValue(5, $new_debt, SQLITE3_FLOAT);
        $stmt->bindValue(6, $c_id, SQLITE3_INTEGER);
        $stmt->execute();

        echo "<script>alert('✅ تم تحديث بيانات العميل والمديونية بنجاح'); window.location.href='customers.php';</script>";
        exit;
    }
}

// جلب بيانات العميل الحالية
$stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->bindValue(1, $c_id, SQLITE3_INTEGER);
$res = $stmt->execute();
$customer = $res->fetchArray(SQLITE3_ASSOC);

if (!$customer) {
    echo "<script>window.location.href='customers.php';</script>";
    exit;
}

$current_debt = floatval($customer['debt'] ?? 0);
if ($current_debt <= 0 && isset($customer['balance'])) {
    $current_debt = floatval($customer['balance']);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل بيانات العميل</title>
    <style>
        body { font-family: 'Tahoma', Arial, sans-serif; background-color: #f1f5f9; margin: 0; padding: 20px; padding-bottom: 90px; }
        .form-card {
            background: white; border-radius: 14px; padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); max-width: 500px; margin: 20px auto;
            border: 1px solid #e2e8f0;
        }
        .title { font-size: 16px; font-weight: bold; color: #0f172a; margin-bottom: 20px; text-align: center; }
        .input-group { margin-bottom: 15px; }
        .input-group label { display: block; margin-bottom: 6px; color: #334155; font-size: 13px; font-weight: bold; }
        .input-group input {
            width: 100%; padding: 11px; border: 1px solid #cbd5e1;
            border-radius: 8px; box-sizing: border-box; font-size: 14px; background-color: #f8fafc;
        }
        .input-group input:focus { outline: none; border-color: #0284c7; background-color: #fff; }
        .btn-submit {
            width: 100%; background: linear-gradient(135deg, #0284c7, #0369a1); color: white; padding: 13px;
            border: none; border-radius: 8px; font-size: 15px; font-weight: bold;
            cursor: pointer; margin-top: 10px; box-shadow: 0 4px 10px rgba(2, 132, 199, 0.3);
        }
        .btn-back {
            display: block; text-align: center; margin-top: 15px; color: #64748b;
            text-decoration: none; font-size: 13px; font-weight: bold;
        }
    </style>
</head>
<body>
<div class="form-card">
    <div class="title">✏️ تعديل بيانات العميل: <?php echo htmlspecialchars($customer['name']); ?></div>
    <form method="POST">
        <div class="input-group">
            <label>اسم العميل أو المحل</label>
            <input type="text" name="c_name" required value="<?php echo htmlspecialchars($customer['name']); ?>">
        </div>
        <div class="input-group">
            <label>رقم الهاتف</label>
            <input type="tel" name="c_phone" value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>">
        </div>
        <div class="input-group">
            <label>الموقع (المنطقة / المدينه)</label>
            <input type="text" name="c_location" value="<?php echo htmlspecialchars($customer['location'] ?? ''); ?>">
        </div>
        <div class="input-group">
            <label>الشارع</label>
            <input type="text" name="c_street" value="<?php echo htmlspecialchars($customer['street'] ?? ''); ?>">
        </div>
        <div class="input-group">
            <label>المديونية (ر.ي)</label>
            <input type="number" step="0.01" name="c_debt" value="<?php echo htmlspecialchars($current_debt); ?>">
        </div>
        <button type="submit" class="btn-submit">💾 حفظ التعديلات والمديونية</button>
    </form>
    <a href="customers.php" class="btn-back">🔙 إلغاء والعودة لقائمة العملاء</a>
</div>
</body>
</html>
