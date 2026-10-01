<?php
// معالجة إضافة العميل عند الضغط على حفظ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_file = '/data/data/com.termux/files/home/secure_dbs/vmax.db';
    $db = new SQLite3($db_file);

    // التأكد من وجود أعمدة الموقع والشارع في جدول العملاء
    @$db->exec("ALTER TABLE customers ADD COLUMN location TEXT");
    @$db->exec("ALTER TABLE customers ADD COLUMN street TEXT");

    $name = $_POST['c_name'] ?? '';
    $phone = $_POST['c_phone'] ?? '';
    $debt = floatval($_POST['c_debt'] ?? 0);
    $location = $_POST['c_location'] ?? '';
    $street = $_POST['c_street'] ?? '';

    if (!empty($name)) {
        $stmt = $db->prepare("INSERT INTO customers (name, phone, debt, location, street) VALUES (?, ?, ?, ?, ?)");
        $stmt->bindValue(1, $name, SQLITE3_TEXT);
        $stmt->bindValue(2, $phone, SQLITE3_TEXT);
        $stmt->bindValue(3, $debt, SQLITE3_FLOAT);
        $stmt->bindValue(4, $location, SQLITE3_TEXT);
        $stmt->bindValue(5, $street, SQLITE3_TEXT);
        $stmt->execute();

        // العودة لصفحة العملاء بعد الإضافة بنجاح
        header("Location: customers.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة عميل جديد</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f7fa; margin: 0; padding: 20px; }
        .form-card {
            background: white; border-radius: 12px; padding: 20px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05); margin-top: 30px;
            border: 1px solid #e2e8f0;
        }
        .title { font-size: 16px; font-weight: bold; color: #1e293b; margin-bottom: 20px; text-align: center; }
        .input-group { margin-bottom: 15px; }
        .input-group label { display: block; margin-bottom: 6px; color: #475569; font-size: 13px; font-weight: bold; }
        .input-group input {
            width: 100%; padding: 12px; border: 1px solid #cbd5e1;
            border-radius: 8px; box-sizing: border-box; font-size: 14px; background-color: #f8fafc;
        }
        .input-group input:focus { outline: none; border-color: #0284c7; background-color: #fff; }
        .btn-submit {
            width: 100%; background-color: #0284c7; color: white; padding: 14px;
            border: none; border-radius: 8px; font-size: 15px; font-weight: bold;
            cursor: pointer; margin-top: 10px; box-shadow: 0 2px 4px rgba(2, 132, 199, 0.3);
        }
        .btn-back {
            display: block; text-align: center; margin-top: 15px; color: #64748b;
            text-decoration: none; font-size: 13px; font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="form-card">
        <div class="title">➕ تسجيل عميل جديد</div>
        <form method="POST">
            <div class="input-group">
                <label>اسم العميل أو المحل (مطلوب)</label>
                <input type="text" name="c_name" required placeholder="مثال: بقالة أبوضياء للتوزيع...">
            </div>
            <div class="input-group">
                <label>رقم الهاتف</label>
                <input type="tel" name="c_phone" placeholder="مثال: 771458609">
            </div>
            <div class="input-group">
                <label>الموقع (المنطقة / المدينه)</label>
                <input type="text" name="c_location" placeholder="مثال: سيئون">
            </div>
            <div class="input-group">
                <label>الشارع</label>
                <input type="text" name="c_street" placeholder="مثال: السوق العام - شارع التجارة">
            </div>
            <div class="input-group">
                <label>رصيد افتتاحي / مديونية سابقة (إن وجد)</label>
                <input type="number" step="0.01" name="c_debt" value="0" placeholder="0.00">
            </div>
            <button type="submit" class="btn-submit">حفظ واعتماد العميل</button>
        </form>
        <a href="customers.php" class="btn-back">🔙 إلغاء والعودة للقائمة</a>
    </div>
</body>
</html>
