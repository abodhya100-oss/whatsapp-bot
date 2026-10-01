<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
if(session_status()===PHP_SESSION_NONE){session_start();} if(!isset($_SESSION["user_role"])){header("Location: login.php");exit;}
$db = new SQLite3(__DIR__ . '/vmax.db');
$db->exec("CREATE TABLE IF NOT EXISTS installments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_id INTEGER,
    amount REAL,
    due_date TEXT,
    status TEXT DEFAULT 'معلق',
    notes TEXT,
    date TEXT
)");

$msg = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_installment'])) {
    $cust_id = intval($_POST['customer_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $due_date = trim($_POST['due_date'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $date = date('Y-m-d H:i:s');

    if ($cust_id > 0 && $amount > 0 && !empty($due_date)) {
        $stmt = $db->prepare("INSERT INTO installments (customer_id, amount, due_date, status, notes, date) VALUES (:cid, :amt, :dd, 'معلق', :notes, :date)");
        $stmt->bindValue(':cid', $cust_id, SQLITE3_INTEGER);
        $stmt->bindValue(':amt', $amount, SQLITE3_FLOAT);
        $stmt->bindValue(':dd', $due_date, SQLITE3_TEXT);
        $stmt->bindValue(':notes', $notes, SQLITE3_TEXT);
        $stmt->bindValue(':date', $date, SQLITE3_TEXT);
        $stmt->execute();
        $stmt->close();
        $msg = "<div style='background:rgba(56,239,125,0.2); color:#38ef7d; padding:8px; border-radius:8px; text-align:center; font-size:11px; margin-bottom:8px; font-weight:bold;'>✅ تم إضافة القسط بنجاح!</div>";
    }
}

if (isset($_GET['pay'])) {
    $id = intval($_GET['pay']);
    $db->exec("UPDATE installments SET status = 'مدفوع' WHERE id = $id");
    header("Location: installments.php");
    exit;
}

$customers = [];
$res_c = $db->query("SELECT * FROM customers ORDER BY name ASC");
while($c = $res_c->fetchArray(SQLITE3_ASSOC)) { $customers[] = $c; }

$installments = [];
$res_i = $db->query("SELECT ins.*, c.name as customer_name, c.phone as customer_phone FROM installments ins LEFT JOIN customers c ON ins.customer_id = c.id ORDER BY ins.due_date ASC");
while($i = $res_i->fetchArray(SQLITE3_ASSOC)) { $installments[] = $i; }
$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <link rel="manifest" href="manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js");}</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>أقساط العملاء والاستحقاقات - ابوضياء العالمي</title>
    <style>
        body { font-family: 'Tahoma', sans-serif; background: #0f2027; color: #fff; margin: 0; padding: 10px; padding-bottom: 90px; }
        .container { max-width: 680px; margin: auto; }
        h1 { text-align: center; color: #ffd200; font-size: 19px; margin-bottom: 3px; }
        p.sub-title { text-align: center; color: #aaa; font-size: 11px; margin-bottom: 10px; }
        .card { background: rgba(255,255,255,0.08); padding: 12px; border-radius: 12px; margin-bottom: 10px; border: 1px solid rgba(255,255,255,0.15); }
        label { display: block; font-size: 11px; color: #ffd200; margin-bottom: 3px; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 9px; margin-bottom: 8px; background: rgba(0,0,0,0.6); color: #fff; border: 1px solid #555; border-radius: 8px; box-sizing: border-box; font-size: 12px; }
        select option { background: #203a43; color: #fff; }
        textarea { height: 45px; resize: vertical; }
        .btn { width: 100%; background: #ffd200; color: #000; padding: 11px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 13px; }
        .table { width: 100%; border-collapse: collapse; font-size: 11px; background: rgba(0,0,0,0.4); border-radius: 8px; overflow: hidden; margin-top: 6px; }
        .table th, .table td { padding: 8px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .table th { background: rgba(255,210,0,0.2); color: #ffd200; }
        .btn-pay { background: #28a745; color: #fff; padding: 4px 8px; border-radius: 5px; text-decoration: none; font-weight: bold; font-size: 10px; }
        .btn-wa { background: #25d366; color: #fff; padding: 4px 8px; border-radius: 5px; text-decoration: none; font-weight: bold; font-size: 10px; margin-right: 3px; }
        .nav-bar { position: fixed; bottom: 0; left: 0; right: 0; background: #0f2027; border-top: 1px solid rgba(255,210,0,0.2); padding: 6px 0; text-align: center; z-index: 1000; }
        .nav-links { display: flex; justify-content: space-around; font-size: 12px; }
        .nav-links a { color: #aaa; text-decoration: none; font-weight: bold; text-align: center; }
        .nav-links a.active { color: #ffd200; }
    </style>
</head>
<body>
<div class="container">
    <h1>📅 أقساط العملاء ومواعيد الاستحقاق</h1>
    <p class="sub-title">إدارة الديون المجدولة ومتابعة التحصيل</p>
    <?php echo $msg; ?>
    <div class="card">
        <form method="POST">
            <label>👥 اختر العميل:</label>
            <select name="customer_id" required>
                <option value="">-- اختر العميل --</option>
                <?php foreach($customers as $c): ?>
                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?> (<?php echo $c['phone']; ?>)</option>
                <?php endforeach; ?>
            </select>

            <div style="display: flex; gap: 8px;">
                <div style="flex:1;">
                    <label>💰 مبلغ القسط:</label>
                    <input type="number" step="0.01" name="amount" placeholder="0.00" required>
                </div>
                <div style="flex:1;">
                    <label>📅 تاريخ الاستحقاق:</label>
                    <input type="date" name="due_date" required>
                </div>
            </div>

            <label>📝 ملاحظات:</label>
            <textarea name="notes" placeholder="تفاصيل القسط، الدفعة رقم..."></textarea>

            <button type="submit" name="add_installment" class="btn">💾 جدولة وحفظ القسط</button>
        </form>
    </div>

    <div class="card">
        <b style="color:#ffd200; font-size:12px; display:block; margin-bottom:6px;">📋 جدول الأقساط المستحقة:</b>
        <table class="table">
            <thead><tr><th>العميل</th><th>المبلغ</th><th>التاريخ</th><th>الحالة</th><th>إجراءات</th></tr></thead>
            <tbody>
                <?php if(!empty($installments)): foreach($installments as $ins): 
                    $clean_phone = preg_replace('/[^0-9]/', '', $ins['customer_phone']);
                    if(!empty($clean_phone) && !startsWith($clean_phone, '967')) { $clean_phone = '967' . preg_replace('/^0/', '', $clean_phone); }
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($ins['customer_name']); ?></td>
                        <td style="color:#ffd200; font-weight:bold;"><?php echo number_format($ins['amount'],2); ?></td>
                        <td><?php echo $ins['due_date']; ?></td>
                        <td><span style="color:<?php echo $ins['status']=='مدفوع'?'#38ef7d':'#ff4d4d'; ?>; font-weight:bold;"><?php echo $ins['status']; ?></span></td>
                        <td>
                            <?php if($ins['status'] == 'معلق'): ?>
                                <a href="installments.php?pay=<?php echo $ins['id']; ?>" class="btn-pay">سداد</a>
                                <?php if(!empty($clean_phone)): ?>
                                    <a href="https://wa.me/<?php echo $clean_phone; ?>?text=<?php echo $wa_msg; ?>" target="_blank" class="btn-wa">💬 تذكير</a>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color:#aaa;">مكتمل</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="5" style="color:#aaa;">لا توجد أقساط مجدولة.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php include 'signature.php'; ?>
    <a href="main.php" style="display:block; text-align:center; color:#ffd200; text-decoration:none; font-size:12px; margin-top:10px;">⬅️ العودة للرئيسية</a>
</div>

<?php include 'nav_bar.php'; ?>

<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
