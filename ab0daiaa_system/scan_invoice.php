<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$db = new SQLite3('/data/data/com.termux/files/home/secure_dbs/vmax.db', SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
$db->busyTimeout(5000);

// إعادة إنشاء الجدول نظيفاً ليقبل عمود الصورة والبيانات
$db->exec("DROP TABLE IF EXISTS scanned_invoices;");
$db->exec("CREATE TABLE scanned_invoices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT,
    amount REAL,
    image_data TEXT,
    date TEXT,
    user TEXT
)");

$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_scanned'])) {
    $title = trim($_POST['invoice_title'] ?? '');
    $amount = floatval($_POST['invoice_amount'] ?? 0);
    $image_data = $_POST['image_data'] ?? '';
    $date = date('Y-m-d H:i:s');
    $user_name = $_SESSION['username'] ?? 'مدير النظام';

    if (!empty($title) && $amount > 0) {
        $stmt = $db->prepare("INSERT INTO scanned_invoices (title, amount, image_data, date, user) VALUES (:title, :amount, :img, :date, :user)");
        $stmt->bindValue(':title', $title, SQLITE3_TEXT);
        $stmt->bindValue(':amount', $amount, SQLITE3_FLOAT);
        $stmt->bindValue(':img', $image_data, SQLITE3_TEXT);
        $stmt->bindValue(':date', $date, SQLITE3_TEXT);
        $stmt->bindValue(':user', $user_name, SQLITE3_TEXT);
        $stmt->execute();
        
        $msg = "✅ تم حفظ الفاتورة مع صورتها الأصلية في الأرشيف بنجاح!";
    } else {
        $msg = "❌ يرجى إدخال اسم المورد والمبلغ صحيحاً.";
    }
}

if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $db->exec("DELETE FROM scanned_invoices WHERE id = $del_id");
    header("Location: scan_invoice.php");
    exit;
}

$scanned_list = [];
$res = $db->query("SELECT * FROM scanned_invoices ORDER BY id DESC");
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $scanned_list[] = $row;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><meta name='mobile-web-app-capable' content='yes'>
<meta name='apple-mobile-web-app-capable' content='yes'>

    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>أرشيف الفواتير المصورة - V-MAX PRO</title>
    <style>
        body { font-family: Tahoma, sans-serif; background: #FFFFFF; color: #f8fafc; margin: 0; padding: 10px; padding-bottom: 110px; }
        .header { text-align: center; font-size: 16px; font-weight: 900; color: #38bdf8; margin-bottom: 12px; }
        .card { background: #FFFFFF; border: 1px solid #38bdf8; border-radius: 10px; padding: 14px; margin-bottom: 12px; box-shadow: 0 0 15px rgba(56,189,248,0.2); }
        .form-control { width: 100%; padding: 10px; background: #FFFFFF; color: #fff; border: 1px solid #38bdf8; border-radius: 8px; box-sizing: border-box; margin-bottom: 8px; font-size: 12px; }
        .btn-scan { background: #FFFFFF; color: #fff; border: none; padding: 12px; border-radius: 8px; font-weight: bold; width: 100%; font-size: 13px; cursor: pointer; margin-top: 6px; }
        .btn-submit { background: #FFFFFF; color: #fff; border: none; padding: 12px; border-radius: 8px; font-weight: bold; width: 100%; font-size: 13px; cursor: pointer; margin-top: 6px; }
        .alert { padding: 10px; border-radius: 6px; text-align: center; font-size: 11px; font-weight: bold; margin-bottom: 10px; }
        .table { width: 100%; border-collapse: collapse; font-size: 11px; margin-top: 8px; }
        .table th, .table td { border: 1px solid #334155; padding: 6px; text-align: center; }
        .table th { background: #FFFFFF; color: #fff; }
        
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.9); z-index: 99999; justify-content: center; align-items: center; padding: 15px; }
        .modal img { max-width: 100%; max-height: 85vh; border-radius: 8px; border: 2px solid #38bdf8; }
        .close-btn { position: absolute; top: 15px; right: 20px; background: #FFFFFF; color: #fff; border: none; padding: 8px 14px; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 14px; }
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
    <div class="header">📸 أرشيف الفواتير والصور المرفقة</div>

    <?php if($msg): ?>
    <div class="alert" style="background: <?php echo strpos($msg, '✅') !== false ? '#16a34a' : '#dc2626'; ?>; color: #fff;"><?php echo $msg; ?></div>
    <?php endif; ?>

    <div class="card">
        <h3 style="margin-top: 0; color: #38bdf8; font-size: 13px;">📑 التقاط وتخزين الفاتورة</h3>
        <form method="POST">
            <input type="file" accept="image/*" capture="environment" id="cameraInput" style="display:none;" onchange="previewImage(event)">
            <input type="hidden" name="image_data" id="imageDataInput">
            
            <button type="button" onclick="document.getElementById('cameraInput').click();" class="btn-scan">
                📷 فتح الكاميرا لالتقاط الفاتورة الورقية
            </button>

            <div id="previewContainer" style="margin-top: 10px; text-align: center; display: none;">
                <img id="imgPreview" style="max-width: 100%; max-height: 140px; border-radius: 6px; border: 1px solid #38bdf8;">
                <div style="font-size: 10px; color: #4ade80; margin-top: 4px;">تم إرفاق صورة الفاتورة بنجاح ✅</div>
            </div>

            <div style="margin-top: 10px;">
                <label style="font-size: 11px; color: #38bdf8; font-weight: bold;">اسم المورد أو بيان الفاتورة:</label>
                <input type="text" name="invoice_title" class="form-control" required placeholder="اكتب اسم المورد أو تفاصيل الفاتورة...">

                <label style="font-size: 11px; color: #38bdf8; font-weight: bold;">المبلغ الصحيح (ر.ي):</label>
                <input type="number" step="any" name="invoice_amount" class="form-control" required placeholder="اكتب المبلغ المكتوب في الفاتورة...">

                <button type="submit" name="save_scanned" value="1" class="btn-submit">💾 حفظ الفاتورة وصورتها في النظام</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h3 style="margin-top: 0; color: #38bdf8; font-size: 13px;">📂 السجلات والفواتير المحفوظة</h3>
        <?php if(empty($scanned_list)): ?>
            <p style="font-size: 11px; color: #94a3b8; text-align: center; margin: 5px 0;">لا توجد فواتير محفوظة حتى الآن.</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>البيان</th>
                        <th>المبلغ</th>
                        <th>الصورة</th>
                        <th>حذف</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($scanned_list as $inv): ?>
                    <tr>
                        <td style="text-align: right;">
                            <b><?php echo htmlspecialchars($inv['title']); ?></b><br>
                            <span style="font-size: 9px; color: #94a3b8;"><?php echo $inv['date']; ?></span>
                        </td>
                        <td style="direction: ltr; font-weight: bold; color: #4ade80;"><?php echo number_format($inv['amount'], 0); ?></td>
                        <td>
                            <?php if(!empty($inv['image_data'])): ?>
                                <button type="button" onclick="openModal('<?php echo $inv['image_data']; ?>')" style="background: #FFFFFF; color: #fff; border: none; padding: 4px 8px; border-radius: 4px; font-size: 10px; cursor: pointer;">👁️ عرض الورقة</button>
                            <?php else: ?>
                                <span style="font-size: 9px; color: #94a3b8;">بدون صورة</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="scan_invoice.php?delete=<?php echo $inv['id']; ?>" onclick="return confirm('هل تريد حذف الفاتورة؟')" style="background: #FFFFFF; color: #fff; padding: 3px 6px; border-radius: 4px; text-decoration: none; font-size: 10px;">حذف</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div id="imageModal" class="modal">
        <img id="modalImg" src="">
    </div>

    <script>
    function previewImage(event) {
        let file = event.target.files[0];
        if (!file) return;
        let reader = new FileReader();
        reader.onload = function(e) {
            let base64String = e.target.result;
            document.getElementById('imgPreview').src = base64String;
            document.getElementById('imageDataInput').value = base64String;
            document.getElementById('previewContainer').style.display = 'block';
        }
        reader.readAsDataURL(file);
    }

    function openModal(imgSrc) {
        document.getElementById('modalImg').src = imgSrc;
        document.getElementById('imageModal').style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('imageModal').style.display = 'none';
    }
    </script>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>
</html>

