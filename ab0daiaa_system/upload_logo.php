<?php
// upload_logo.php - صفحة رفع شعارات الفاتورة (أبو ضياء والمشقاص) - نظام أبوضياء العالمي
require_once 'auth.php';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['logo_abudiya']) && $_FILES['logo_abudiya']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['logo_abudiya']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
            move_uploaded_file($_FILES['logo_abudiya']['tmp_name'], __DIR__ . '/logo_abudiya.png');
            $msg = "<div style='color:#4ade80; background:rgba(34,197,94,0.15); border:1px solid #22c55e; padding:12px; border-radius:8px; margin-bottom:15px; text-align:center; font-weight:bold;'>✅ تم رفع شعار أبو ضياء بنجاح!</div>";
        }
    }
    if (isset($_FILES['logo_mashqas']) && $_FILES['logo_mashqas']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['logo_mashqas']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
            move_uploaded_file($_FILES['logo_mashqas']['tmp_name'], __DIR__ . '/logo_mashqas.png');
            $msg = "<div style='color:#4ade80; background:rgba(34,197,94,0.15); border:1px solid #22c55e; padding:12px; border-radius:8px; margin-bottom:15px; text-align:center; font-weight:bold;'>✅ تم رفع شعار شركة المشقاص بنجاح!</div>";
        }
    }
}

$l1_exists = file_exists(__DIR__ . '/logo_abudiya.png');
$l2_exists = file_exists(__DIR__ . '/logo_mashqas.png');
$l1_url = $l1_exists ? 'logo_abudiya.png?' . time() : '';
$l2_url = $l2_exists ? 'logo_mashqas.png?' . time() : '';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><meta name='mobile-web-app-capable' content='yes'>
<meta name='apple-mobile-web-app-capable' content='yes'>

    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>إدارة الشعارات - أبو ضياء</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Tahoma', sans-serif; background: #FFFFFF; color: #f8fafc; margin: 0; padding: 15px; direction: rtl; }
        .container { max-width: 480px; margin: 20px auto; background: #FFFFFF; border: 1px solid rgba(56, 189, 248, 0.3); border-radius: 16px; padding: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.6); }
        h2 { color: #38bdf8; text-align: center; margin-top: 0; font-size: 19px; }
        p { color: #94a3b8; font-size: 12px; text-align: center; margin-bottom: 20px; }
        .logos-preview { display: flex; justify-content: center; gap: 20px; margin-bottom: 20px; }
        .preview-box { text-align: center; background: rgba(15,23,42,0.6); padding: 10px; border-radius: 12px; border: 1px dashed #334155; width: 48%; }
        .preview-img { width: 65px; height: 65px; border-radius: 50%; object-fit: cover; border: 2.5px solid #fbbf24; background: #FFFFFF; }
        .upload-section { background: rgba(15,23,42,0.4); padding: 12px; border-radius: 10px; margin-bottom: 15px; border: 1px solid #334155; }
        input[type="file"] { width: 100%; padding: 10px; background: #FFFFFF; border: 1px solid #334155; color: #fff; border-radius: 8px; font-size: 12px; cursor: pointer; }
        .btn-submit { width: 100%; background: linear-gradient(135deg, #22c55e, #16a34a); color: #fff; border: none; padding: 12px; border-radius: 10px; font-size: 14px; font-weight: bold; cursor: pointer; box-shadow: 0 4px 15px rgba(34,197,94,0.3); margin-bottom: 10px; }
        .btn-back { display: block; width: 100%; background: #FFFFFF; color: #cbd5e1; text-align: center; text-decoration: none; padding: 11px; border-radius: 10px; font-weight: bold; font-size: 13px; }
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
    <h2>🖼️ إدارة شعارات الفاتورة</h2>
    <p>قم بررفع الشعارات لتظهر بوضوح تام في الفاتورة</p>

    <?php echo $msg; ?>

    <div class="logos-preview">
        <div class="preview-box">
            <?php if($l1_exists): ?>
                <img src="<?php echo $l1_url; ?>" alt="شعار أبو ضياء" class="preview-img"><br>
                <span style="font-size:10px; color:#4ade80;">شعار أبو ضياء مفعل</span>
            <?php else: ?>
                <div style="font-size:25px;">🏷️</div>
                <span style="font-size:10px; color:#f87171;">لا يوجد شعار</span>
            <?php endif; ?>
        </div>
        <div class="preview-box">
            <?php if($l2_exists): ?>
                <img src="<?php echo $l2_url; ?>" alt="شعار المشقاص" class="preview-img"><br>
                <span style="font-size:10px; color:#4ade80;">شعار المشقاص مفعل</span>
            <?php else: ?>
                <div style="font-size:25px;">🏷️</div>
                <span style="font-size:10px; color:#f87171;">لا يوجد شعار</span>
            <?php endif; ?>
        </div>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <div class="upload-section">
            <label style="font-size:11px; color:#38bdf8; font-weight:bold; display:block; margin-bottom:5px;">📌 شعار أبو ضياء (العلوي):</label>
            <input type="file" name="logo_abudiya" accept="image/png, image/jpeg">
        </div>

        <div class="upload-section">
            <label style="font-size:11px; color:#38bdf8; font-weight:bold; display:block; margin-bottom:5px;">📌 شعار شركة المشقاص (السفلي):</label>
            <input type="file" name="logo_mashqas" accept="image/png, image/jpeg">
        </div>

        <button type="submit" class="btn-submit">💾 رفع وتثبيت الشعارات</button>
    </form>

    <a href="pos.php" class="btn-back">⚡ العودة لشاشة الكاشير</a>
</div>

</body>
</html>
