<?php
// expenses.php - شاشة إدارة المصروفات اليومية (مستقلة تماماً)
error_reporting(E_ALL & ~E_WARNING & ~E_DEPRECATED);  
ini_set('display_errors', 0);

require_once 'auth.php';
require_once 'db.php';

$is_sqlite = isset($db);
if (!$is_sqlite) { exit("خطأ في الاتصال بقاعدة البيانات"); }

// إنشاء جدول المصروفات تلقائياً إذا لم يكن موجوداً (بدون لمس ملفات النظام الأخرى)
$db->exec("CREATE TABLE IF NOT EXISTS expenses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    amount REAL NOT NULL,
    expense_date TEXT NOT NULL,
    notes TEXT
)");

$msg = "";

// إضافة مصروف جديد
if (isset($_POST['add_expense'])) {
    $title = trim($_POST['title']);
    $amount = floatval($_POST['amount']);
    $expense_date = trim($_POST['expense_date']);
    $notes = trim($_POST['notes']);

    if (!empty($title) && $amount > 0) {
        $stmt = $db->prepare("INSERT INTO expenses (title, amount, expense_date, notes) VALUES (:title, :amount, :expense_date, :notes)");
        $stmt->bindValue(':title', $title, SQLITE3_TEXT);
        $stmt->bindValue(':amount', $amount, SQLITE3_FLOAT);
        $stmt->bindValue(':expense_date', $expense_date, SQLITE3_TEXT);
        $stmt->bindValue(':notes', $notes, SQLITE3_TEXT);
        
        if ($stmt->execute()) {
            $msg = "✅ تم تسجيل المصروف بنجاح!";
        } else {
            $msg = "❌ حدث خطأ أثناء الحفظ.";
        }
    } else {
        $msg = "⚠️ يرجى إدخال بند المصروف والمبلغ بشكل صحيح!";
    }
}

// حذف مصروف
if (isset($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);
    $db->exec("DELETE FROM expenses WHERE id = $del_id");
    header("Location: expenses.php");
    exit;
}

// فلاتر التاريخ
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : date('Y-m-d');

// جلب المصروفات
$expenses_list = [];
$total_expenses = 0;
$sql = "SELECT * FROM expenses WHERE date(expense_date) BETWEEN '$date_from' AND '$date_to' ORDER BY expense_date DESC, id DESC";
$res = $db->query($sql);
if ($res) {
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $expenses_list[] = $row;
        $total_expenses += floatval($row['amount']);
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" style="direction: rtl; unicode-bidi: embed;">
<head>
<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><meta name='mobile-web-app-capable' content='yes'>
<meta name='apple-mobile-web-app-capable' content='yes'>

    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>إدارة المصروفات - مؤسسة أبو ضياء</title>
    <?php include 'global_style.php'; ?>
    <style>
        * { direction: rtl; text-align: right; box-sizing: border-box; font-family: 'Tahoma', Arial, sans-serif; }
        body { margin: 0; padding: 1px; background: #FFFFFF; }
        .lux-wrapper { padding: 8px; max-width: 650px; margin: 0 auto 75px auto; }
        
        .card-box { background: #FFFFFF; border: 1px solid #CBD5E1; border-radius: 8px; padding: 12px; margin-bottom: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .lux-input { width: 100%; padding: 8px; border: 1px solid #CBD5E1; border-radius: 6px; font-size: 11.5px; background: #FFFFFF; margin-bottom: 8px; outline: none; }
        .lux-input:focus { border-color: #1E3A8A; background: #FFFFFF; }
        
        .btn-save { background: #FFFFFF; color: #FFF; border: none; padding: 8px 12px; border-radius: 6px; font-size: 12px; font-weight: bold; cursor: pointer; width: 100%; }
        .btn-filter { background: #FFFFFF; color: #FFF; border: none; padding: 7px; border-radius: 6px; font-size: 11px; font-weight: bold; cursor: pointer; width: 100%; margin-top: 5px; }
        
        .exp-table { width: 100%; border-collapse: collapse; font-size: 11px; margin-top: 8px; }
        .exp-table th, .exp-table td { border: 1px solid #E2E8F0; padding: 6px; text-align: center; }
        .exp-table th { background: #FFFFFF; color: #FFF; font-weight: bold; }
        .btn-del { background: #FFFFFF; color: #FFF; padding: 3px 6px; border-radius: 4px; text-decoration: none; font-size: 10px; font-weight: bold; display: inline-block; }
        
        .total-box { background: #FFFFFF; border: 1px solid #F87171; border-radius: 8px; padding: 10px; text-align: center; color: #991B1B; font-weight: bold; font-size: 13px; margin-top: 10px; }
        .msg-box { background: #FFFFFF; color: #FFF; padding: 6px; border-radius: 4px; font-size: 11px; font-weight: bold; text-align: center; margin-bottom: 10px; }
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
<div class="lux-wrapper">
    <h2 style="font-size: 15px; color: #1E3A8A; margin-bottom: 10px;">💸 إدارة مصروفات المحل</h2>

    <?php if ($msg): ?>
        <div class="msg-box"><?php echo $msg; ?></div>
    <?php endif; ?>

    <!-- نموذج تسجيل مصروف جديد -->
    <div class="card-box">
        <div style="font-size: 12px; font-weight: bold; color: #334155; margin-bottom: 8px;">➕ تسجيل مصروف جديد</div>
        <form method="POST">
            <div style="display: flex; gap: 6px;">
                <div style="flex: 2;">
                    <input type="text" name="title" class="lux-input" placeholder="بند المصروف (مثال: إيجار، غداء...)" required>
                </div>
                <div style="flex: 1;">
                    <input type="number" step="0.01" name="amount" class="lux-input" placeholder="المبلغ" required>
                </div>
            </div>
            <div style="display: flex; gap: 6px;">
                <div style="flex: 1;">
                    <input type="date" name="expense_date" class="lux-input" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div style="flex: 2;">
                    <input type="text" name="notes" class="lux-input" placeholder="ملاحظات (اختياري)">
                </div>
            </div>
            <button type="submit" name="add_expense" class="btn-save">💾 حفظ المصروف</button>
        </form>
    </div>

    <!-- فلتر عرض المصروفات -->
    <div class="card-box" style="background: #FFFFFF;">
        <form method="GET">
            <div style="display: flex; gap: 6px; align-items: center;">
                <div style="flex: 1;">
                    <label style="font-size: 9.5px; font-weight: bold; color: #475569;">من:</label>
                    <input type="date" name="date_from" class="lux-input" style="margin-bottom:0;" value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
                <div style="flex: 1;">
                    <label style="font-size: 9.5px; font-weight: bold; color: #475569;">إلى:</label>
                    <input type="date" name="date_to" class="lux-input" style="margin-bottom:0;" value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
                <div style="flex: 1;">
                    <button type="submit" class="btn-filter" style="margin-top: 13px;">🔍 عرض</button>
                </div>
            </div>
        </form>
    </div>

    <!-- جدول المصروفات -->
    <div class="card-box">
        <div style="font-size: 12px; font-weight: bold; color: #1E3A8A; margin-bottom: 5px;">📜 كشف المصروفات للفترة المحددة</div>
        
        <table class="exp-table">
            <thead>
                <tr>
                    <th style="width: 25%;">التاريخ</th>
                    <th style="width: 35%;">البند</th>
                    <th style="width: 25%;">المبلغ</th>
                    <th style="width: 15%;">حذف</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($expenses_list)): ?>
                    <tr><td colspan="4" style="padding: 10px; color: #64748B;">لا توجد مصروفات مسجلة في هذه الفترة.</td></tr>
                <?php else: foreach ($expenses_list as $exp): ?>
                    <tr>
                        <td style="font-size: 9.5px; color: #475569;"><?php echo $exp['expense_date']; ?></td>
                        <td style="font-weight: bold; color: #1E293B;"><?php echo htmlspecialchars($exp['title']); ?>
                            <?php if(!empty($exp['notes'])): ?><div style="font-size: 8px; color: #94A3B8;"><?php echo htmlspecialchars($exp['notes']); ?></div><?php endif; ?>
                        </td>
                        <td style="color: #DC2626; font-weight: bold;" dir="ltr"><?php echo number_format($exp['amount'], 2); ?></td>
                        <td>
                            <a href="expenses.php?delete_id=<?php echo $exp['id']; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>" class="btn-del" onclick="return confirm('هل أنت متأكد من حذف هذا المصروف؟');">✖</a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>

        <!-- إجمالي المصروفات -->
        <div class="total-box">
            إجمالي المصروفات للفترة: <span dir="ltr"><?php echo number_format($total_expenses, 2); ?></span> ر.ي
        </div>
    </div>

</div>
</body>
</html>
