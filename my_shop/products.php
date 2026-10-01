<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_role'])) { header("Location: login.php"); exit; }
$db = new SQLite3('vmax.db');

$db->exec("CREATE TABLE IF NOT EXISTS inventory (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    item_name TEXT,
    buy_price REAL,
    sell_price REAL,
    stock INTEGER,
    image TEXT
);");
@$db->exec("ALTER TABLE inventory ADD COLUMN image TEXT;");

$msg = "";
$err = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    $item_name = trim($_POST['item_name']);
    $buy_price = floatval($_POST['buy_price']);
    $sell_price = floatval($_POST['sell_price']);
    $stock = intval($_POST['stock']);
    
    // معالجة رفع الصورة بوضوح تام
    $image_val = '📦';
    if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        $file_ext = strtolower(pathinfo($_FILES['item_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (in_array($file_ext, $allowed)) {
            $new_filename = 'img_' . time() . '_' . mt_rand(1000, 9999) . '.' . $file_ext;
            $destination = $upload_dir . $new_filename;
            if (move_uploaded_file($_FILES['item_image']['tmp_name'], $destination)) {
                $image_val = $destination;
            }
        }
    } elseif (!empty($_POST['emoji_icon'])) {
        $image_val = trim($_POST['emoji_icon']);
    }

    if (!empty($item_name) && $sell_price > 0) {
        $stmt = $db->prepare("INSERT INTO inventory (item_name, buy_price, sell_price, stock, image) VALUES (:name, :buy, :sell, :stock, :img)");
        $stmt->bindValue(':name', $item_name, SQLITE3_TEXT);
        $stmt->bindValue(':buy', $buy_price, SQLITE3_FLOAT);
        $stmt->bindValue(':sell', $sell_price, SQLITE3_FLOAT);
        $stmt->bindValue(':stock', $stock, SQLITE3_INTEGER);
        $stmt->bindValue(':img', $image_val, SQLITE3_TEXT);
        $stmt->execute();
        $msg = "✅ تم إضافة الصنف مع الصورة بنجاح تام!";
    } else {
        $err = "⚠️ يرجى إدخال اسم الصنف وسعر البيع بشكل صحيح!";
    }
}

if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $db->exec("DELETE FROM inventory WHERE id = $del_id");
    header("Location: products.php");
    exit;
}

$items = [];
$res = $db->query("SELECT * FROM inventory ORDER BY id DESC");
while ($row = $res->fetchArray(SQLITE3_ASSOC)) { $items[] = $row; }
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
    <link rel="manifest" href="manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js");}</script>
    <meta charset="UTF-8">
    <meta name="google" content="notranslate">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المخزون والأصناف - نظام الإمبراطورية</title>
    <style>
        body { font-family: Tahoma, sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 10px; padding-bottom: 90px; direction: rtl; }
        .header { text-align: center; font-size: 15px; font-weight: 900; color: #38bdf8; margin-bottom: 12px; text-shadow: 0 0 10px rgba(56,189,248,0.5); }
        .glass-panel { background: linear-gradient(135deg, rgba(30, 41, 59, 0.95), rgba(15, 23, 42, 0.98)); backdrop-filter: blur(20px); border: 1.5px solid rgba(56, 189, 248, 0.4); border-radius: 18px; padding: 12px; margin-bottom: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.6); }
        .form-control { width: 100%; padding: 9px; background: #0f172a; color: #fff; border: 1px solid rgba(56, 189, 248, 0.4); border-radius: 10px; font-size: 11px; box-sizing: border-box; margin-bottom: 8px; }
        .row-inputs { display: flex; gap: 6px; }
        .row-inputs > div { flex: 1; }
        .btn-submit { background: linear-gradient(135deg, #22c55e, #16a34a); color: #fff; border: none; padding: 11px; border-radius: 12px; font-weight: bold; width: 100%; cursor: pointer; font-size: 12px; box-shadow: 0 4px 15px rgba(34,197,94,0.4); }
        .item-row { background: rgba(15, 23, 42, 0.85); border: 1px solid rgba(56, 189, 248, 0.3); border-radius: 12px; padding: 10px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center; }
        .item-img-preview { width: 45px; height: 45px; object-fit: cover; border-radius: 10px; border: 1px solid rgba(56,189,248,0.5); background: #1e293b; display: flex; align-items: center; justify-content: center; font-size: 20px; }
    </style>
</head>
<body>
    <div class="header">📦 إدارة المخزون والأصناف</div>

    <?php if(!empty($msg)): ?><div style="background: rgba(34,197,94,0.2); color: #4ade80; padding: 10px; border-radius: 10px; font-size: 11px; margin-bottom: 10px; text-align:center; font-weight:bold;"><?php echo $msg; ?></div><?php endif; ?>
    <?php if(!empty($err)): ?><div style="background: rgba(239,68,68,0.2); color: #ef4444; padding: 10px; border-radius: 10px; font-size: 11px; margin-bottom: 10px; text-align:center; font-weight:bold;"><?php echo $err; ?></div><?php endif; ?>

    <div class="glass-panel">
        <div style="font-size: 12px; font-weight: bold; color: #38bdf8; margin-bottom: 8px;">➕ إضافة صنف جديد مع صورة واضحة:</div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="add_item" value="1">
            
            <label style="font-size: 9px; color: #94a3b8;">اسم الصنف / المنتج:</label>
            <input type="text" name="item_name" class="form-control" placeholder="مثل: آيفون 15 برو ماكس..." required>

            <div class="row-inputs">
                <div>
                    <label style="font-size: 9px; color: #94a3b8;">سعر الشراء:</label>
                    <input type="number" step="any" name="buy_price" class="form-control" value="0" placeholder="0">
                </div>
                <div>
                    <label style="font-size: 9px; color: #94a3b8;">سعر البيع:</label>
                    <input type="number" step="any" name="sell_price" class="form-control" placeholder="0" required>
                </div>
            </div>

            <div class="row-inputs">
                <div>
                    <label style="font-size: 9px; color: #94a3b8;">الكمية بالمستودع:</label>
                    <input type="number" name="stock" class="form-control" value="1" placeholder="1">
                </div>
                <div>
                    <label style="font-size: 9px; color: #94a3b8;">أيقونة إيموجي (اختياري):</label>
                    <input type="text" name="emoji_icon" class="form-control" placeholder="📱">
                </div>
            </div>

            <label style="font-size: 9px; color: #94a3b8;">📸 صورة الصنف الحقيقية (واضحة من الكاميرا أو المعرض):</label>
            <input type="file" name="item_image" accept="image/*" class="form-control" style="padding: 6px; background: rgba(56,189,248,0.08);">

            <button type="submit" class="btn-submit">💾 حفظ الصنف والصورة في المخزون</button>
        </form>
    </div>

    <div class="glass-panel">
        <div style="font-size: 12px; font-weight: bold; color: #38bdf8; margin-bottom: 8px; display: flex; justify-content: space-between;">
            <span>📋 قائمة أصناف المستودع:</span>
            <span style="color: #4ade80;"><?php echo count($items); ?> صنف</span>
        </div>

        <?php if(empty($items)): ?>
            <div style="text-align: center; color: #94a3b8; padding: 15px; font-size: 11px;">⚠️ لا توجد أصناف مسجلة حالياً.</div>
        <?php else: foreach($items as $it): ?>
            <div class="item-row">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div>
                        <?php if(!empty($it['image']) && strpos($it['image'], 'uploads/') === 0 && file_exists($it['image'])): ?>
                            <img src="<?php echo htmlspecialchars($it['image']); ?>" class="item-img-preview">
                        <?php else: ?>
                            <div class="item-img-preview"><?php echo !empty($it['image']) ? $it['image'] : '📦'; ?></div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div style="font-size: 12px; font-weight: bold; color: #f8fafc;"><?php echo htmlspecialchars($it['item_name']); ?></div>
                        <div style="font-size: 10px; color: #94a3b8; margin-top: 2px;">
                            السعر: <span style="color: #4ade80; font-weight: bold;"><?php echo number_format($it['sell_price'], 0); ?> ر.ي</span> | 
                            الكمية: <span style="color: #38bdf8; font-weight: bold;"><?php echo $it['stock']; ?></span>
                        </div>
                    </div>
                </div>
                <div>
                    <a href="products.php?delete=<?php echo $it['id']; ?>" onclick="return confirm('⚠️ هل أنت متأكد من حذف هذا الصنف؟');" style="background: rgba(239,68,68,0.2); color: #ef4444; border: 1px solid #ef4444; padding: 6px 10px; border-radius: 8px; text-decoration: none; font-size: 10px; font-weight: bold;">🗑️ حذف</a>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>

    <?php include "nav_menu.php"; ?>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>
</html>

