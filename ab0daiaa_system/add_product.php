<?php
// add_product.php - إضافة صنف جديد (دعم الوحدات والعملات) - نظام أبوضياء
include_once 'config_loader.php';
require_once 'auth.php';

$db_file = '/data/data/com.termux/files/home/secure_dbs/vmax.db';
$db = new SQLite3($db_file);

// تحديث قاعدة البيانات بالأعمدة الجديدة (منها عمود العملة) دون فقدان البيانات
@$db->exec("ALTER TABLE products ADD COLUMN wholesale_price REAL DEFAULT 0");
@$db->exec("ALTER TABLE products ADD COLUMN image TEXT");
@$db->exec("ALTER TABLE products ADD COLUMN units_per_pack INTEGER DEFAULT 1");
@$db->exec("ALTER TABLE products ADD COLUMN unit_1_name TEXT DEFAULT 'حبة'");
@$db->exec("ALTER TABLE products ADD COLUMN unit_1_price REAL DEFAULT 0");
@$db->exec("ALTER TABLE products ADD COLUMN unit_2_name TEXT DEFAULT ''");
@$db->exec("ALTER TABLE products ADD COLUMN unit_2_price REAL DEFAULT 0");
@$db->exec("ALTER TABLE products ADD COLUMN unit_3_name TEXT DEFAULT ''");
@$db->exec("ALTER TABLE products ADD COLUMN unit_3_price REAL DEFAULT 0");
// الإضافة الجديدة: عمود العملة لكل صنف
@$db->exec("ALTER TABLE products ADD COLUMN currency TEXT DEFAULT 'YER'");

$msg = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'] ?? '';
    $unit = $_POST['unit'] ?? 'كرتون';
    $currency = $_POST['currency'] ?? 'YER'; // استقبال عملة الصنف
    $price_retail = $_POST['price_retail'] ?? 0;
    $price_wholesale = $_POST['price_wholesale'] ?? 0;
    $units_per_pack = intval($_POST['units_per_pack'] ?? 1);
    if($units_per_pack <= 0) $units_per_pack = 1;

    // استقبال بيانات الوحدات الإضافية
    $unit_1_name = $_POST['unit_1_name'] ?? 'حبة';
    $unit_1_price = $_POST['unit_1_price'] ?? 0;
    $unit_2_name = $_POST['unit_2_name'] ?? '';
    $unit_2_price = $_POST['unit_2_price'] ?? 0;
    $unit_3_name = $_POST['unit_3_name'] ?? '';
    $unit_3_price = $_POST['unit_3_price'] ?? 0;

    $image_path = '';

    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        if (!is_dir('uploads')) mkdir('uploads', 0777, true);
        $img_name = time() . '_' . basename($_FILES['image']['name']);
        $target = 'uploads/' . $img_name;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $image_path = $target;
        }
    }

    if (!empty($name)) {
        $stmt = $db->prepare("INSERT INTO products (name, price, wholesale_price, unit_major, units_per_pack, image, unit_1_name, unit_1_price, unit_2_name, unit_2_price, unit_3_name, unit_3_price, currency) VALUES (:n, :pr, :pw, :u, :upp, :img, :u1n, :u1p, :u2n, :u2p, :u3n, :u3p, :curr)");
        $stmt->bindValue(':n', $name);
        $stmt->bindValue(':pr', $price_retail);
        $stmt->bindValue(':pw', $price_wholesale);
        $stmt->bindValue(':u', $unit);
        $stmt->bindValue(':upp', $units_per_pack);
        $stmt->bindValue(':img', $image_path);
        
        $stmt->bindValue(':u1n', $unit_1_name);
        $stmt->bindValue(':u1p', $unit_1_price);
        $stmt->bindValue(':u2n', $unit_2_name);
        $stmt->bindValue(':u2p', $unit_2_price);
        $stmt->bindValue(':u3n', $unit_3_name);
        $stmt->bindValue(':u3p', $unit_3_price);
        
        // ربط عملة الصنف
        $stmt->bindValue(':curr', $currency);

        if ($stmt->execute()) {
            $msg = "<div class='msg success'> ✅ تم تسجيل الصنف بنجاح!</div>";
        } else {
            $msg = "<div class='msg error'>❌ حدث خطأ أثناء الحفظ.</div>";
        }
    } else {
        $msg = "<div class='msg error'>⚠️ يرجى كتابة اسم الصنف.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'>
    <title>إضافة صنف جديد</title>
    <style>
        :root {
            --primary: #0f172a;
            --secondary: #0284c7;
            --accent: #38bdf8;
            --success: #10b981;
            --bg-light: #f4f7f9;
            --card-bg: #ffffff;
            --text-dark: #1e293b;
            --border-color: #e2e8f0;
            --text-muted: #64748b;
        }
        body {
            font-family: 'Tahoma', Arial, sans-serif;
            background-color: var(--bg-light);
            margin: 0; padding: 15px; color: var(--text-dark);
            padding-bottom: 100px;
        }
        .header-card {
            background: linear-gradient(135deg, var(--primary), #1e293b);
            border-radius: 16px; padding: 20px; text-align: center;
            color: #fff; margin-bottom: 20px; box-shadow: 0 8px 25px rgba(15, 23, 42, 0.2);
        }
        .header-card h1 { margin: 0; font-size: 18px; font-weight: 900; }
        .header-card p { margin: 5px 0 0 0; font-size: 11px; color: #cbd5e1; }
        .form-card {
            background: var(--card-bg); border-radius: 16px; padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid var(--border-color);
            margin-bottom: 15px;
        }
        .form-group { margin-bottom: 15px; display: flex; flex-direction: column; }
        .form-row { display: flex; gap: 15px; }
        .form-row .form-group { flex: 1; }
        label { font-size: 12px; font-weight: bold; margin-bottom: 6px; color: var(--primary); }
        .form-input {
            padding: 10px 12px; border-radius: 10px; border: 1px solid var(--border-color);
            background: #FFFFFF; font-size: 13px; font-weight: bold; color: var(--primary);
            outline: none; transition: 0.3s; font-family: inherit; width: 100%; box-sizing: border-box;
        }
        .form-input:focus { border-color: var(--secondary); background: #FFFFFF; box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.1); }
        select.form-input { appearance: none; cursor: pointer; }
        .price-input-wrapper { position: relative; }
        .price-input-wrapper input { width: 100%; box-sizing: border-box; padding-left: 45px; }
        .price-currency {
            position: absolute; left: 15px; top: 50%; transform: translateY(-50%);
            font-size: 11px; color: var(--text-muted); font-weight: bold; pointer-events: none;
        }
        
        /* تصميم أزرار العملات المدمجة */
        .currency-toggle {
            display: flex; background: #FFFFFF; border-radius: 10px; padding: 4px; overflow: hidden;
        }
        .curr-radio { flex: 1; text-align: center; position: relative; }
        .curr-radio input { position: absolute; opacity: 0; cursor: pointer; height: 0; width: 0; }
        .curr-radio span {
            display: block; padding: 10px 5px; font-size: 11px; font-weight: 800; color: var(--text-muted);
            cursor: pointer; border-radius: 8px; transition: 0.3s;
        }
        .curr-radio input:checked ~ span {
            background: #FFFFFF; color: var(--primary); box-shadow: 0 2px 5px rgba(0,0,0,0.05); border: 1px solid var(--secondary);
        }

        .file-upload-wrapper { position: relative; overflow: hidden; display: inline-block; width: 100%; }
        .file-upload-btn {
            background: #FFFFFF; border: 2px dashed #bae6fd; color: var(--secondary);
            padding: 15px; text-align: center; border-radius: 10px; font-weight: bold;
            display: flex; flex-direction: column; align-items: center; gap: 5px; cursor: pointer;
        }
        .file-upload-wrapper input[type=file] { font-size: 100px; position: absolute; left: 0; top: 0; opacity: 0; cursor: pointer; height: 100%; }
        .btn-submit {
            background: linear-gradient(135deg, var(--success), #059669); color: #fff; border: none; padding: 15px; border-radius: 12px;
            font-size: 15px; font-weight: 900; cursor: pointer; width: 100%; box-shadow: 0 6px 15px rgba(16, 185, 129, 0.3); transition: 0.2s; margin-top: 10px;
        }
        .btn-submit:active { transform: scale(0.97); }
        .msg { padding: 12px; border-radius: 10px; text-align: center; font-weight: bold; font-size: 13px; margin-bottom: 15px; }
        .msg.success { background: #FFFFFF; color: #166534; border: 1px solid #bbf7d0; }
        .msg.error { background: #FFFFFF; color: #991b1b; border: 1px solid #fecaca; }
        .section-title { background: #FFFFFF; padding: 8px 10px; border-radius: 8px; font-size: 12px; font-weight: bold; color: var(--secondary); margin: 15px 0 10px 0; border-right: 4px solid var(--secondary); }
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

<div class="header-card">
    <h1>📦 إضافة صنف جديد</h1>
    <p>حدد العملة والوحدات وسعاتها بدقة</p>
</div>

<?php echo $msg; ?>

<form method="POST" enctype="multipart/form-data">
    <div class="form-card">
        <div class="form-group">
            <label>اسم الصنف</label>
            <input type="text" name="name" class="form-input" placeholder="مثال: ريلاكس أحمر" required>
        </div>

        <div class="form-group">
            <label>💱 عملة تسعير الصنف (بكم اشتريته وتبيعه؟)</label>
            <div class="currency-toggle">
                <label class="curr-radio">
                    <input type="radio" name="currency" value="YER" checked onclick="updateCurrencyLabels('ر.ي')">
                    <span>يمني (YER)</span>
                </label>
                <label class="curr-radio">
                    <input type="radio" name="currency" value="SAR" onclick="updateCurrencyLabels('ر.س')">
                    <span>سعودي (SAR)</span>
                </label>
                <label class="curr-radio">
                    <input type="radio" name="currency" value="USD" onclick="updateCurrencyLabels('$')">
                    <span>دولار (USD)</span>
                </label>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>وحدة البيع الكبرى</label>
                <select name="unit" class="form-input">
                    <option value="كرتون">كرتون</option>
                    <option value="شدة">شدة</option>
                    <option value="كيس">كيس</option>
                </select>
            </div>
            <div class="form-group">
                <label>كم كيس في الوحدة؟</label>
                <input type="number" name="units_per_pack" class="form-input" value="1" min="1" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>سعر التجزئة الأساسي</label>
                <div class="price-input-wrapper">
                    <input type="number" step="any" name="price_retail" class="form-input" placeholder="0" required>
                    <span class="price-currency dynamic-curr">ر.ي</span>
                </div>
            </div>
            <div class="form-group">
                <label>سعر الجملة</label>
                <div class="price-input-wrapper">
                    <input type="number" step="any" name="price_wholesale" class="form-input" placeholder="0" required>
                    <span class="price-currency dynamic-curr">ر.ي</span>
                </div>
            </div>
        </div>
    </div>

    <div class="form-card">
        <div class="section-title">⚖️ مستويات الوحدات الإضافية للتوزيع</div>
        
        <div class="form-row">
            <div class="form-group">
                <label>اسم الوحدة الصغرى</label>
                <input type="text" name="unit_1_name" class="form-input" placeholder="مثال: حبة" value="حبة" required>
            </div>
            <div class="form-group">
                <label>السعر</label>
                <div class="price-input-wrapper">
                    <input type="number" step="any" name="unit_1_price" class="form-input" placeholder="0">
                    <span class="price-currency dynamic-curr">ر.ي</span>
                </div>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>اسم الوحدة الوسطى</label>
                <input type="text" name="unit_2_name" class="form-input" placeholder="مثال: باكت">
            </div>
            <div class="form-group">
                <label>السعر</label>
                <div class="price-input-wrapper">
                    <input type="number" step="any" name="unit_2_price" class="form-input" placeholder="0">
                    <span class="price-currency dynamic-curr">ر.ي</span>
                </div>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>اسم الوحدة الكبرى</label>
                <input type="text" name="unit_3_name" class="form-input" placeholder="مثال: كرتون">
            </div>
            <div class="form-group">
                <label>السعر</label>
                <div class="price-input-wrapper">
                    <input type="number" step="any" name="unit_3_price" class="form-input" placeholder="0">
                    <span class="price-currency dynamic-curr">ر.ي</span>
                </div>
            </div>
        </div>
    </div>

    <div class="form-card">
        <div class="form-group">
            <label>صورة الصنف (اختياري)</label>
            <div class="file-upload-wrapper">
                <div class="file-upload-btn">
                    <span style="font-size: 24px;">📸</span>
                    <span>اضغط هنا لاختيار صورة</span>
                </div>
                <input type="file" name="image" accept="image/*">
            </div>
        </div>

        <button type="submit" class="btn-submit">حفظ الصنف في المخزون ✔</button>
    </div>
</form>

<script>
// دالة لتغيير الرموز (ر.ي، ر.س، $) بجانب خانات الأسعار فور تغيير العملة
function updateCurrencyLabels(symbol) {
    let labels = document.querySelectorAll('.dynamic-curr');
    labels.forEach(lbl => {
        lbl.innerText = symbol;
    });
}
</script>

</body>
</html>
