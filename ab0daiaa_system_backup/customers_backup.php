<?php
// customers.php - صفحة إدارة العملاء الأصلية لنظام أبو ضياء
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = null;
if (file_exists('db.php')) { include_once 'db.php'; }
if (!isset($conn) || !$conn) { $conn = @new SQLite3("vmax.db"); }

// جلب العملاء بالطريقة الأصلية المعتادة
$customers = [];
if ($conn) {
    $res = @$conn->query("SELECT * FROM customers ORDER BY id DESC");
    if ($res) {
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $customers[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <link rel="manifest" href="manifest.json">
    <meta name='mobile-web-app-capable' content='yes'>
    <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js");}</script>
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>إدارة العملاء - أبو ضياء</title>
    <style>
        * { box-sizing: border-box; font-family: 'Tahoma', Arial, sans-serif; }
        body { background: #e2e8f0; margin: 0; padding: 10px; display: flex; justify-content: center; flex-direction: column; align-items: center; padding-bottom: 90px; }
        .top-nav { width: 100%; max-width: 650px; background: #000; padding: 8px 12px; border-radius: 6px; display: flex; justify-content: space-between; gap: 5px; margin-bottom: 10px; flex-wrap: wrap; }
        .nav-link { color: #fff; text-decoration: none; font-size: 11px; font-weight: bold; padding: 6px 10px; border-radius: 4px; background: #333; }
        .nav-link.active { background: #2563eb; }
        .main-card { background: #fff; width: 100%; max-width: 650px; padding: 20px; border: 2px solid #000; border-radius: 6px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #000; border-bottom: 2px solid #000; padding-bottom: 8px; margin-top: 0; font-size: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: right; font-size: 12px; }
        th { background: #f1f5f9; color: #000; }
        .btn-action { background: #2563eb; color: #fff; padding: 5px 10px; border-radius: 4px; text-decoration: none; font-size: 11px; font-weight: bold; }
    </style>
</head>
<body>

    <div class="top-nav">
        <a href="index.php" class="nav-link">📄 الفاتورة</a>
        <a href="customers.php" class="nav-link active">👥 العملاء</a>
        <a href="receipt.php" class="nav-link">📥 سند قبض</a>
        <a href="payment.php" class="nav-link">📤 سند صرف</a>
        <a href="settings.php" class="nav-link">⚙️ الإعدادات</a>
    </div>

    <div class="main-card">
        <h2>👥 قائمة العملاء والجهات</h2>
        
        <table>
            <thead>
                <tr>
                    <th>رقم</th>
                    <th>اسم العميل / المحل</th>
                    <th>الهاتف</th>
                    <th>إجراء</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                <tr>
                    <td colspan="4" style="text-align: center; color: #666;">لا توجد بيانات عملاء مسجلة حالياً.</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($customers as $c): ?>
                    <tr>
                        <td><?php echo $c['id']; ?></td>
                        <td><b><?php echo htmlspecialchars($c['name'] ?? $c['customer_name'] ?? 'عميل'); ?></b></td>
                        <td><?php echo htmlspecialchars($c['phone'] ?? ''); ?></td>
                        <td>
                            <a href="statement_new.php?customer_id= class="btn-action">كشف حساب</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (file_exists('nav_menu.php')) include 'nav_menu.php'; ?>
</body>
</html>
