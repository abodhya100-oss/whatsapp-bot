<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_role'])) { header("Location: login.php"); exit; }
$db = new SQLite3('/data/data/com.termux/files/home/secure_dbs/vmax.db');

// إنشاء جدول الورديات
$db->exec("CREATE TABLE IF NOT EXISTS shifts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_name TEXT,
    start_time TEXT,
    end_time TEXT,
    status TEXT,
    expected_cash REAL,
    actual_cash REAL,
    difference REAL,
    cash_sales REAL,
    receipts REAL,
    expenses REAL,
    returns REAL
);");

$user = $_SESSION['username'] ?? 'مدير النظام';
$msg = "";

// بدء وردية جديدة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_shift'])) {
    $start_time = date('Y-m-d H:i:s');
    $db->exec("INSERT INTO shifts (user_name, start_time, status) VALUES ('$user', '$start_time', 'open')");
    header("Location: shift.php");
    exit;
}

// التحقق من وجود وردية مفتوحة للمستخدم الحالي
$active_shift = $db->querySingle("SELECT * FROM shifts WHERE status = 'open' AND user_name = '$user' ORDER BY id DESC LIMIT 1", true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['close_shift'])) {
    $shift_id = intval($_POST['shift_id']);
    $actual_cash = floatval($_POST['actual_cash']);
    $end_time = date('Y-m-d H:i:s');
    
    // جلب الحسابات منذ بداية الوردية
    $start_time = $active_shift['start_time'];
    $cash_sales = $db->querySingle("SELECT SUM(net_amount) FROM invoices WHERE customer_id = 0 AND date >= '$start_time'") ?? 0;
    $receipts = $db->querySingle("SELECT SUM(amount) FROM receipts WHERE date >= '$start_time' AND user = '$user'") ?? 0;
    $expenses = $db->querySingle("SELECT SUM(amount) FROM expenses WHERE date >= '$start_time' AND user = '$user'") ?? 0;
    $returns = $db->querySingle("SELECT SUM(total_refund) FROM returns WHERE date >= '$start_time' AND user_name = '$user'") ?? 0;
    
    $expected_cash = ($cash_sales + $receipts) - $expenses - $returns;
    $difference = $actual_cash - $expected_cash;

    $stmt = $db->prepare("UPDATE shifts SET end_time = :et, status = 'closed', expected_cash = :ec, actual_cash = :ac, difference = :diff, cash_sales = :cs, receipts = :rc, expenses = :ex, returns = :rt WHERE id = :id");
    $stmt->bindValue(':et', $end_time, SQLITE3_TEXT);
    $stmt->bindValue(':ec', $expected_cash, SQLITE3_FLOAT);
    $stmt->bindValue(':ac', $actual_cash, SQLITE3_FLOAT);
    $stmt->bindValue(':diff', $difference, SQLITE3_FLOAT);
    $stmt->bindValue(':cs', $cash_sales, SQLITE3_FLOAT);
    $stmt->bindValue(':rc', $receipts, SQLITE3_FLOAT);
    $stmt->bindValue(':ex', $expenses, SQLITE3_FLOAT);
    $stmt->bindValue(':rt', $returns, SQLITE3_FLOAT);
    $stmt->bindValue(':id', $shift_id, SQLITE3_INTEGER);
    $stmt->execute();
    
    $active_shift = null; // إعادة تعيين
}

// جلب آخر 5 ورديات مغلقة للتقرير
$closed_shifts = [];
$res = $db->query("SELECT * FROM shifts WHERE status = 'closed' ORDER BY id DESC LIMIT 5");
if ($res) {
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) { $closed_shifts[] = $row; }
}

// حسابات الوردية الحية (إن وجدت)
$live_expected = 0;
if ($active_shift) {
    $st = $active_shift['start_time'];
    $l_sales = $db->querySingle("SELECT SUM(net_amount) FROM invoices WHERE customer_id = 0 AND date >= '$st'") ?? 0;
    $l_receipts = $db->querySingle("SELECT SUM(amount) FROM receipts WHERE date >= '$st' AND user = '$user'") ?? 0;
    $l_expenses = $db->querySingle("SELECT SUM(amount) FROM expenses WHERE date >= '$st' AND user = '$user'") ?? 0;
    $l_returns = $db->querySingle("SELECT SUM(total_refund) FROM returns WHERE date >= '$st' AND user_name = '$user'") ?? 0;
    $live_expected = ($l_sales + $l_receipts) - $l_expenses - $l_returns;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <link rel="manifest" href="manifest.json">
    <meta name='mobile-web-app-capable' content='yes'>
    <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js");}</script>
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>إدارة الورديات - أبو ضياء</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; background: #FFFFFF; color: #f8fafc; margin: 0; padding: 10px; padding-bottom: 80px; }
        .header { text-align: center; font-size: 18px; font-weight: 900; color: #10b981; margin-bottom: 15px; }
        .glass-panel { background: linear-gradient(135deg, rgba(30, 41, 59, 0.95), rgba(15, 23, 42, 0.98)); border: 1px solid rgba(16, 185, 129, 0.4); border-radius: 12px; padding: 15px; margin-bottom: 15px; text-align: center; }
        .form-control { width: 100%; padding: 12px; background: #FFFFFF; color: #fff; border: 1px solid rgba(16, 185, 129, 0.4); border-radius: 8px; margin-bottom: 10px; box-sizing: border-box; text-align: center; font-size: 18px; font-weight: bold; }
        .btn-submit { background: linear-gradient(135deg, #10b981, #047857); color: white; padding: 12px; border: none; width: 100%; font-weight: bold; border-radius: 8px; cursor: pointer; font-size: 16px; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.4); }
        .btn-start { background: linear-gradient(135deg, #3b82f6, #1d4ed8); border-color: #3b82f6; box-shadow: 0 4px 10px rgba(59, 130, 246, 0.4); }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 15px; }
        .metric-box { background: rgba(15, 23, 42, 0.6); border: 1px solid #334155; padding: 10px; border-radius: 8px; }
        .alert-success { background: rgba(34, 197, 94, 0.2); color: #4ade80; border: 1px solid #22c55e; padding: 10px; border-radius: 8px; margin-bottom: 10px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; font-size: 10px; text-align: center; margin-top: 10px; }
        th { background: rgba(16, 185, 129, 0.2); color: #34d399; padding: 8px; border: 1px solid rgba(16, 185, 129, 0.3); }
        td { padding: 8px; border: 1px solid rgba(148, 163, 184, 0.2); background: rgba(30, 41, 59, 0.5); }
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
    <div class="header">⏳ إدارة الورديات والصندوق</div>
    <?php echo $msg; ?>

    <?php if(!$active_shift): ?>
        <div class="glass-panel" style="border-color: #3b82f6;">
            <div style="font-size: 14px; font-weight: bold; color: #93c5fd; margin-bottom: 15px;">لا توجد وردية مفتوحة حالياً للكاشير: <?php echo $user; ?></div>
            <form method="POST">
                <input type="hidden" name="start_shift" value="1">
                <button type="submit" class="btn-submit btn-start">▶️ بدء وردية جديدة (استلام الصندوق)</button>
            </form>
        </div>
    <?php else: ?>
        <div class="glass-panel">
            <div style="font-size: 12px; color: #94a3b8; margin-bottom: 5px;">وردية مفتوحة بواسطة: <b style="color: #fff;"><?php echo htmlspecialchars($active_shift['user_name']); ?></b></div>
            <div style="font-size: 10px; color: #64748b; margin-bottom: 15px;">وقت البدء: <span style="direction:ltr; display:inline-block;"><?php echo $active_shift['start_time']; ?></span></div>
            
            <div class="grid-2">
                <div class="metric-box">
                    <div style="font-size: 9px; color: #94a3b8;">مبيعات نقدية</div>
                    <div style="font-weight: bold; color: #4ade80;"><?php echo number_format($l_sales, 0); ?></div>
                </div>
                <div class="metric-box">
                    <div style="font-size: 9px; color: #94a3b8;">مقبوضات ديون</div>
                    <div style="font-weight: bold; color: #38bdf8;"><?php echo number_format($l_receipts, 0); ?></div>
                </div>
                <div class="metric-box">
                    <div style="font-size: 9px; color: #94a3b8;">مصروفات</div>
                    <div style="font-weight: bold; color: #f87171;"><?php echo number_format($l_expenses, 0); ?></div>
                </div>
                <div class="metric-box">
                    <div style="font-size: 9px; color: #94a3b8;">مرتجعات</div>
                    <div style="font-weight: bold; color: #f59e0b;"><?php echo number_format($l_returns, 0); ?></div>
                </div>
            </div>

            <div style="background: rgba(16, 185, 129, 0.1); border: 1px dashed #10b981; padding: 10px; border-radius: 8px; margin-bottom: 15px;">
                <div style="font-size: 11px; color: #a7f3d0;">الكاش المفترض وجوده بالدرج الآن:</div>
                <div style="font-size: 22px; font-weight: 900; color: #10b981; direction: ltr;"><?php echo number_format($live_expected, 0); ?> ر.ي</div>
            </div>

                <input type="hidden" name="close_shift" value="1">
                <input type="hidden" name="shift_id" value="<?php echo $active_shift['id']; ?>">
                
                <label style="font-size: 11px; color: #f8fafc;">قم بعدّ النقود بيدك، وأدخل الكاش الفعلي بالدرج:</label>
                <input type="number" name="actual_cash" class="form-control" required placeholder="0">
                
            </form>
        </div>
    <?php endif; ?>

    <div style="font-weight: bold; color: #34d399; margin-top: 20px; font-size: 12px;">📑 سجل آخر الورديات المغلقة:</div>
    <table>
        <thead>
            <tr>
                <th>الكاشير</th>
                <th>المفترض</th>
                <th>الفعلي</th>
                <th>الفارق</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($closed_shifts as $s): ?>
            <tr>
                <td style="font-size: 8px; direction: ltr;"><?php echo date('m-d h:i A', strtotime($s['end_time'])); ?></td>
                <td style="font-weight: bold;"><?php echo htmlspecialchars($s['user_name']); ?></td>
                <td style="color: #38bdf8;"><?php echo number_format($s['expected_cash'], 0); ?></td>
                <td style="color: #4ade80;"><?php echo number_format($s['actual_cash'], 0); ?></td>
                <td style="font-weight: 900; direction: ltr; <?php echo $s['difference'] < 0 ? 'color: #f87171;' : ($s['difference'] > 0 ? 'color: #fbbf24;' : 'color: #94a3b8;'); ?>">
                    <?php 
                        if($s['difference'] < 0) echo "عجز " . number_format($s['difference'], 0);
                        elseif($s['difference'] > 0) echo "زيادة +" . number_format($s['difference'], 0);
                        else echo "مطابق ✓";
                    ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($closed_shifts)): ?>
            <tr><td colspan="5" style="color: #64748b; padding: 15px;">لا توجد ورديات مغلقة بعد.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <a href="dashboard.php" style="display: block; text-align: center; color: #94a3b8; text-decoration: none; font-size: 12px; font-weight: bold; margin-top: 15px;">🔙 عودة للوحة القيادة</a>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>
</html>

