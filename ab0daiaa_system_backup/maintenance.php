<?php
// maintenance.php - نظام إدارة الصيانة وورشة الجوالات - نظام أبوضياء العالمي
require_once 'auth.php';
$db_file = __DIR__ . '/vmax.db';
$db = new SQLite3($db_file);                  
// التأكد من جدول الصيانة
$db->exec("CREATE TABLE IF NOT EXISTS maintenance (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_id INTEGER,
    device_model TEXT,
    issue_desc TEXT,
    repair_cost REAL,
    status TEXT DEFAULT 'pending',
    notes TEXT,
    created_date TEXT
)");

// معالجة إضافة جهاز للصيانة                  
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_maintenance'])) {
    $customer_id = intval($_POST['customer_id'] ?? 1);
    $device_model = $db->escapeString($_POST['device_model'] ?? '');
    $issue_desc = $db->escapeString($_POST['issue_desc'] ?? '');
    $repair_cost = floatval($_POST['repair_cost'] ?? 0);
    $status = $db->escapeString($_POST['status'] ?? 'pending');
    $notes = $db->escapeString($_POST['notes'] ?? '');

    if (!empty($device_model)) {
        $db->exec("INSERT INTO maintenance (customer_id, device_model, issue_desc, repair_cost, status, notes, created_date) VALUES ($customer_id, '$device_model', '$issue_desc', $repair_cost, '$status', '$notes', datetime('now', 'localtime'))");
        echo "<script>window.location.href='maintenance.php';</script>";                            
        exit;
    }
}

// معالجة تغيير حالة الصيانة
if (isset($_GET['status']) && isset($_GET['id'])) {
    $m_id = intval($_GET['id']);
    $new_status = $db->escapeString($_GET['status']);
    $db->exec("UPDATE maintenance SET status = '$new_status' WHERE id = $m_id");
    echo "<script>window.location.href='maintenance.php';</script>";
    exit;                                     
}

// جلب العملاء
$cust_res = $db->query("SELECT id, name FROM customers");
$customers = [];
while($c = $cust_res->fetchArray(SQLITE3_ASSOC)) { $customers[] = $c; }

// جلب سجلات الصيانة
$m_res = $db->query("SELECT m.*, c.name as customer_name, c.phone FROM maintenance m LEFT JOIN customers c ON m.customer_id = c.id ORDER BY m.id DESC LIMIT 50");
$maintenances = [];                           
while($row = $m_res->fetchArray(SQLITE3_ASSOC)) {                                               
    $maintenances[] = $row;
}                                             
$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'>
    <meta name='mobile-web-app-capable' content='yes'>
    <meta name='apple-mobile-web-app-capable' content='yes'>
    <title>إدارة الصيانة وورشة الجوالات - V-MAX PRO</title>
    <style>
        :root {
            --bg-deep: #F1F5F9;
            --card-bg: #FFFFFF;
            --text-main: #0F172A;
            --text-muted: #64748B;
            --primary-blue: #2563EB;
            --danger: #E11D48;
            --success: #10B981;
            --warning: #F59E0B;
            --border-color: #CBD5E1;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Tahoma', sans-serif;
            background: var(--bg-deep);
            color: var(--text-main);
            margin: 0;
            padding: 15px;
            direction: rtl;
            padding-bottom: 95px;
        }
        .container { max-width: 700px; margin: auto; }
        
        .header-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(241, 245, 249, 0.9));
            border: 1.5px solid var(--primary-blue);
            border-radius: 20px;
            padding: 18px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.06);
            margin-bottom: 15px;
        }
        .header-card h1 { margin: 0 0 5px 0; font-size: 18px; color: var(--primary-blue); }
        .header-card p { margin: 0; font-size: 11px; color: var(--text-muted); }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
        }
        .card h3 {
            color: var(--primary-blue);
            font-size: 14px;
            margin-top: 0;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 8px;
        }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px; }
        @media(max-width: 500px) { .form-grid { grid-template-columns: 1fr; } }

        input, select, textarea {
            width: 100%;
            padding: 10px;
            background: #F8FAFC;
            border: 1px solid var(--border-color);
            color: var(--text-main);
            border-radius: 10px;
            font-size: 12px;
            box-sizing: border-box;
            outline: none;
        }
        input:focus, select:focus, textarea:focus {
            border-color: var(--primary-blue);
            background: #FFFFFF;
        }
        textarea { resize: vertical; height: 55px; }

        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, var(--primary-blue), #1D4ED8);
            color: #fff;
            border: none;
            padding: 12px;
            border-radius: 10px;
            font-weight: bold;
            font-size: 13px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(37,99,235,0.25);
            transition: 0.2s;
        }
        .btn-submit:active { transform: scale(0.98); }

        table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        th, td { padding: 9px 6px; text-align: center; font-size: 11.5px; border-bottom: 1px solid #E2E8F0; }
        th { background: #EFF6FF; color: var(--primary-blue); font-weight: bold; }

        .badge-pending { background: #FEF3C7; color: #D97706; padding: 4px 8px; border-radius: 6px; font-weight: bold; font-size: 10px; border: 1px solid #FDE68A; display: inline-block; }
        .badge-done { background: #DCFCE7; color: #15803D; padding: 4px 8px; border-radius: 6px; font-weight: bold; font-size: 10px; border: 1px solid #BBF7D0; display: inline-block; }
        .badge-delivered { background: #E0F2FE; color: #0369A1; padding: 4px 8px; border-radius: 6px; font-weight: bold; font-size: 10px; border: 1px solid #BAE6FD; display: inline-block; }

        .btn-action { padding: 4px 8px; border-radius: 6px; font-size: 10px; text-decoration: none; font-weight: bold; display: inline-block; margin: 2px; }
        .btn-wa { background: #22c55e; color: #fff; box-shadow: 0 2px 5px rgba(34,197,94,0.2); }
        .btn-status { background: #F1F5F9; color: var(--text-main); border: 1px solid var(--border-color); }
        .btn-status:hover { background: #E2E8F0; }
    </style>
</head>
<body>

<div class="container">
    <div class="header-card">
        <h1>🔧 إدارة الصيانة وورشة الجوالات</h1>
        <p>تسجيل أجهزة الصيانة، الأعطال، تكلفة الإصلاح، ومتابعة حالة التسليم</p>
    </div>

    <div class="card">
        <h3>➕ استلام جهاز جديد للصيانة</h3>
        <form method="POST">
            <input type="hidden" name="add_maintenance" value="1">
            <div class="form-grid">
                <div>
                    <label style="font-size: 11px; color: var(--text-muted); display: block; margin-bottom: 4px; font-weight: bold;">العميل صاحب الجهاز:</label>
                    <select name="customer_id">
                        <option value="1">عميل نقدي عام</option>
                        <?php foreach($customers as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="font-size: 11px; color: var(--text-muted); display: block; margin-bottom: 4px; font-weight: bold;">نوع ورقم الجهاز:</label>
                    <input type="text" name="device_model" placeholder="مثال: Samsung S24 Ultra" required>
                </div>
            </div>

            <div class="form-grid" style="margin-bottom: 10px;">
                <div>
                    <label style="font-size: 11px; color: var(--text-muted); display: block; margin-bottom: 4px; font-weight: bold;">التكلفة المقدرة (ر.ي):</label>
                    <input type="number" step="0.01" name="repair_cost" value="0" required>
                </div>
                <div>
                    <label style="font-size: 11px; color: var(--text-muted); display: block; margin-bottom: 4px; font-weight: bold;">حالة الجهاز الأولية:</label>
                    <select name="status">
                        <option value="pending">⏳ قيد الصيانة</option>
                        <option value="done">✅ تم الإصلاح</option>
                        <option value="delivered">🚀 تم التسليم للعميل</option>
                    </select>
                </div>
            </div>

            <div style="margin-bottom: 12px;">
                <label style="font-size: 11px; color: var(--text-muted); display: block; margin-bottom: 4px; font-weight: bold;">وصف العطل / الإصلاحات المطلوبة:</label>
                <textarea name="issue_desc" placeholder="شاشة مكسورة، تغير بطارية، سوفتوير..."></textarea>
            </div>

            <button type="submit" class="btn-submit">💾 حفظ وتسجيل كارت الصيانة</button>
        </form>
    </div>

    <div class="card">
        <h3>📋 سجل أجهزة الصيانة الحالية</h3>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>العميل والجهاز</th>
                    <th>العطل</th>
                    <th>التكلفة</th>
                    <th>الحالة</th>
                    <th>إجراءات / واتساب</th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($maintenances)): foreach($maintenances as $m):
                    $m_id = $m['id'];
                    $c_name = $m['customer_name'] ?? 'عميل';
                    $c_phone = $m['phone'] ?? '';
                    $model = $m['device_model'] ?? '';
                    $issue = $m['issue_desc'] ?? '';
                    $cost = $m['amount'] ?? $m['repair_cost'] ?? 0;
                    $status = $m['status'] ?? 'pending';

                    $status_text = 'قيد الصيانة';
                    $badge_class = 'badge-pending';
                    if($status == 'done') { $status_text = 'تم الإصلاح'; $badge_class = 'badge-done'; }
                    elseif($status == 'delivered') { $status_text = 'تم التسليم'; $badge_class = 'badge-delivered'; }

                    $wa_msg = "🏢 *محلات أبوضياء للصيانة*\nعزيزنا العميل / $c_name\nجهازكم ($model)\nحالة الصيانة: *$status_text*\nتكلفة الإصلاح: " . number_format($cost, 2) . " ر.ي\n✨ شكراً لثقتكم بنا ✨";
                ?>
                <tr>
                    <td><b>#<?php echo $m_id; ?></b></td>
                    <td style="text-align: right;">
                        <b style="color: var(--primary-blue);"><?php echo htmlspecialchars($model); ?></b><br>
                        <span style="font-size: 10px; color: var(--text-muted);"><?php echo htmlspecialchars($c_name); ?></span>
                    </td>
                    <td><?php echo htmlspecialchars($issue); ?></td>
                    <td style="color: var(--success); font-weight: bold;"><?php echo number_format($cost, 2); ?> ر.ي</td>
                    <td>
                        <span class="<?php echo $badge_class; ?>"><?php echo $status_text; ?></span>
                        <div style="margin-top: 5px;">
                            <?php if($status != 'pending'): ?><a href="maintenance.php?id=<?php echo $m_id; ?>&status=pending" class="btn-action btn-status">قيد</a><?php endif; ?>
                            <?php if($status != 'done'): ?><a href="maintenance.php?id=<?php echo $m_id; ?>&status=done" class="btn-action btn-status">أصلح</a><?php endif; ?>
                            <?php if($status != 'delivered'): ?><a href="maintenance.php?id=<?php echo $m_id; ?>&status=delivered" class="btn-action btn-status">سلم</a><?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <?php if(!empty($c_phone)): ?>
                        <a href="https://api.whatsapp.com/send?phone=<?php echo urlencode($c_phone); ?>&text=<?php echo urlencode($wa_msg); ?>" target="_blank" class="btn-action btn-wa">💬 واتس</a>
                        <?php else: ?>
                        <span style="color: var(--text-muted); font-size: 10px;">بدون رقم</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 25px; font-weight: bold;">
                        لا توجد أجهزة مسجلة في ورشة الصيانة حالياً.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if(file_exists('nav_menu.php')) { include 'nav_menu.php'; } ?>
</body>
</html>
