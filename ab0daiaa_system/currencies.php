<?php
// currencies.php - إدارة العملات وأسعار الصرف (ملف مستقل تماماً وآمن)
error_reporting(E_ALL);
ini_set('display_errors', 1);

$db = new SQLite3('/data/data/com.termux/files/home/secure_dbs/vmax.db');

// إنشاء جدول العملات في SQLite بأمان تام دون المساس بأي جداول أخرى
$db->exec("CREATE TABLE IF NOT EXISTS currencies (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    currency_name TEXT NOT NULL,
    currency_code TEXT NOT NULL,
    exchange_rate REAL NOT NULL,
    is_default INTEGER DEFAULT 0,
    created_at TEXT
);");

$msg = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_currency') {
        $name = trim($_POST['currency_name'] ?? '');
        $code = trim($_POST['currency_code'] ?? '');
        $rate = floatval($_POST['exchange_rate'] ?? 1);
        $is_default = intval($_POST['is_default'] ?? 0);
        $date = date('Y-m-d H:i:s');

        if (!empty($name) && !empty($code) && $rate > 0) {
            // إذا كانت العملة افتراضية، نلغي الافتراضي عن البقية
            if ($is_default == 1) {
                $db->exec("UPDATE currencies SET is_default = 0");
            }
            
            $stmt = $db->prepare("INSERT INTO currencies (currency_name, currency_code, exchange_rate, is_default, created_at) VALUES (:n, :c, :r, :d, :date)");
            $stmt->bindValue(':n', $name, SQLITE3_TEXT);
            $stmt->bindValue(':c', $code, SQLITE3_TEXT);
            $stmt->bindValue(':r', $rate, SQLITE3_FLOAT);
            $stmt->bindValue(':d', $is_default, SQLITE3_INTEGER);
            $stmt->bindValue(':date', $date, SQLITE3_TEXT);
            
            if ($stmt->execute()) {
                $msg = "✅ تم إضافة العملة بنجاح!";
            } else {
                $error = "❌ حدث خطأ أثناء حفظ العملة.";
            }
        } else {
            $error = "⚠️ جميع الحقول الأساسية مطلوبة ويجب أن يكون سعر الصرف صحيحاً.";
        }
    } elseif ($action === 'delete_currency') {
        $id = intval($_POST['currency_id'] ?? 0);
        if ($id > 0) {
            $db->exec("DELETE FROM currencies WHERE id = $id");
            $msg = "🗑️ تم حذف العملة بنجاح.";
        }
    }
}

// جلب العملات المسجلة
$currencies = [];
$res = $db->query("SELECT * FROM currencies ORDER BY is_default DESC, id DESC");
if ($res) {
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $currencies[] = $row;
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
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>إدارة العملات وأسعار الصرف - أبو ضياء</title>
    <style>
        * { box-sizing: border-box; font-family: 'Tahoma', Arial, sans-serif; margin: 0; padding: 0; direction: rtl; text-align: right; }
        body { background-color: #0f172a; color: #f8fafc; padding: 10px; padding-bottom: 90px; }
        .header { background: linear-gradient(135deg, #1e3a8a, #0f172a); color: white; padding: 20px 15px; text-align: center; border-radius: 16px; margin-bottom: 15px; border: 1px solid rgba(56, 189, 248, 0.3); box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
        .header h1 { font-size: 16px; margin-bottom: 4px; font-weight: bold; color: #38bdf8; }
        .header p { font-size: 11px; opacity: 0.9; color: #cbd5e1; }
        
        .container { max-width: 600px; margin: 0 auto; }
        
        .glass-card { background: rgba(30, 41, 59, 0.85); backdrop-filter: blur(15px); border: 1px solid rgba(56, 189, 248, 0.3); border-radius: 14px; padding: 15px; margin-bottom: 12px; box-shadow: 0 8px 20px rgba(0,0,0,0.4); }
        
        .form-group { margin-bottom: 10px; }
        .form-label { display: block; font-size: 11px; font-weight: bold; color: #38bdf8; margin-bottom: 4px; }
        .form-control { width: 100%; padding: 10px; background: #FFFFFF; color: #fff; border: 1px solid rgba(56, 189, 248, 0.4); border-radius: 8px; font-size: 12px; outline: none; }
        
        .btn-submit { background: linear-gradient(135deg, #22c55e, #16a34a); color: white; border: none; padding: 12px; border-radius: 8px; font-size: 13px; font-weight: bold; width: 100%; cursor: pointer; box-shadow: 0 4px 12px rgba(34,197,94,0.4); }
        .btn-delete { background: #FFFFFF; color: white; border: none; padding: 6px 10px; border-radius: 6px; font-size: 10px; font-weight: bold; cursor: pointer; }
        
        .currency-item { display: flex; justify-content: space-between; align-items: center; background: rgba(15, 23, 42, 0.9); border: 1px solid rgba(56, 189, 248, 0.2); border-radius: 10px; padding: 12px; margin-bottom: 8px; }
        .currency-info { font-size: 12px; }
        .currency-name { font-weight: bold; color: #38bdf8; font-size: 14px; margin-bottom: 2px; }
        .currency-details { color: #94a3b8; font-size: 11px; }
        
        .alert-success { background: rgba(34,197,94,0.2); border: 1px solid #22c55e; color: #4ade80; padding: 10px; border-radius: 8px; margin-bottom: 12px; font-size: 11px; text-align: center; font-weight: bold; }
        .alert-error { background: rgba(239,68,68,0.2); border: 1px solid #ef4444; color: #ef4444; padding: 10px; border-radius: 8px; margin-bottom: 12px; font-size: 11px; text-align: center; font-weight: bold; }
        
        .toggle-btn { background: #FFFFFF; color: white; border: 1px solid #38bdf8; padding: 10px; border-radius: 20px; font-size: 12px; font-weight: bold; cursor: pointer; width: 100%; margin-bottom: 12px; box-shadow: 0 4px 10px rgba(30,58,138,0.4); }
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
        <div class="header">
            <h1>💱 نظام إدارة العملات وأسعار الصرف</h1>
            <p>مؤسسة أبو ضياء - إعدادات النظام المستقلة</p>
        </div>

        <?php if(!empty($msg)): ?>
            <div class="alert-success"><?php echo $msg; ?></div>
        <?php endif; ?>
        <?php if(!empty($error)): ?>
            <div class="alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <button class="toggle-btn" onclick="toggleForm()">➕ إضافة عملة جديدة أو سعر صرف</button>

        <div id="addForm" class="glass-card" style="display: none; border: 2px solid #38bdf8;">
            <div style="font-size: 13px; font-weight: bold; color: #38bdf8; margin-bottom: 10px;">إدخال بيانات العملة الجديدة:</div>
            <form method="POST">
                <input type="hidden" name="action" value="add_currency">
                <div class="form-group">
                    <label class="form-label">اسم العملة:</label>
                    <input type="text" name="currency_name" class="form-control" placeholder="مثل: ريال سعودي، دولار أمريكي..." required>
                </div>
                <div class="form-group">
                    <label class="form-label">رمز العملة:</label>
                    <input type="text" name="currency_code" class="form-control" placeholder="مثل: SAR, USD, YER" required>
                </div>
                <div class="form-group">
                    <label class="form-label">سعر الصرف (مقارنة بالعملة الأساسية):</label>
                    <input type="number" step="0.0001" name="exchange_rate" class="form-control" placeholder="1.0000" required>
                </div>
                <div class="form-group" style="display: flex; align-items: center; gap: 8px; margin-top: 8px;">
                    <input type="checkbox" id="is_default" name="is_default" value="1" style="width: 16px; height: 16px;">
                    <label for="is_default" style="font-size: 11px; color: #cbd5e1; cursor: pointer; margin:0;">تعيين كعملة أساسية للنظام</label>
                </div>
                <button type="submit" class="btn-submit" style="margin-top: 12px;">💾 حفظ العملة واعتمادها</button>
            </form>
        </div>

        <div style="font-size: 13px; font-weight: bold; color: #38bdf8; margin: 15px 0 10px 5px;">العملات وأسعار الصرف المعتمدة:</div>

        <?php if(empty($currencies)): ?>
            <div class="glass-card"><div style="text-align: center; color: #94a3b8; padding: 20px; font-size: 12px;">لا توجد عملات مسجلة حالياً. أضف عملتك الأولى الآن!</div></div>
        <?php else: ?>
            <?php foreach($currencies as $cur): ?>
            <div class="currency-item">
                <div class="currency-info">
                    <div class="currency-name">
                        <?php echo htmlspecialchars($cur['currency_name']); ?> (<?php echo htmlspecialchars($cur['currency_code']); ?>)
                        <?php if($cur['is_default'] == 1): ?>
                            <span style="background: #FFFFFF; color: #fff; font-size: 9px; padding: 2px 6px; border-radius: 4px; margin-right: 5px;">الأساسية</span>
                        <?php endif; ?>
                    </div>
                    <div class="currency-details">
                        سعر الصرف: <b style="color: #4ade80;"><?php echo $cur['exchange_rate']; ?></b> | تاريخ الإضافة: <?php echo $cur['created_at']; ?>
                    </div>
                </div>
                <div>
                    <form method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذه العملة؟');">
                        <input type="hidden" name="action" value="delete_currency">
                        <input type="hidden" name="currency_id" value="<?php echo $cur['id']; ?>">
                        <button type="submit" class="btn-delete">حذف</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        function toggleForm() {
            const form = document.getElementById('addForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    </script>

    <!-- تضمين الشريط السفلي العائم الموجود مسبقاً -->
</body>
</html>
