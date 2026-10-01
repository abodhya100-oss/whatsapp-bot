<?php                                         
// products.php - إدارة المخزون والأصناف (هرم الوحدات الثلاثي الكامل + العملات + الجرد) - نظام أبوضياء
require_once 'auth.php';
require_once 'db.php';

$msg = "";                                    
$upload_dir = 'uploads/products/';
if (!is_dir($upload_dir)) {                       
    mkdir($upload_dir, 0755, true);
}

@$db->exec("ALTER TABLE products ADD COLUMN currency TEXT DEFAULT 'YER'");
@$db->exec("ALTER TABLE products ADD COLUMN unit_major TEXT");        
@$db->exec("ALTER TABLE products ADD COLUMN price_major REAL DEFAULT 0"); 
@$db->exec("ALTER TABLE products ADD COLUMN unit_mid TEXT");          
@$db->exec("ALTER TABLE products ADD COLUMN price_mid REAL DEFAULT 0");   
@$db->exec("ALTER TABLE products ADD COLUMN unit_minor TEXT");        
@$db->exec("ALTER TABLE products ADD COLUMN price_minor REAL DEFAULT 0"); 
@$db->exec("ALTER TABLE products ADD COLUMN rate_mid REAL DEFAULT 1");    
@$db->exec("ALTER TABLE products ADD COLUMN rate_major REAL DEFAULT 1");  

if (isset($_POST['add_product'])) {           
    $name = trim($_POST['p_name']);           
    $cost = floatval($_POST['p_cost'] ?? 0);  
    $qty = floatval($_POST['p_qty'] ?? 0);    
    $barcode = trim($_POST['p_barcode'] ?? '');
    $currency = trim($_POST['currency'] ?? 'YER');

    $unit_minor = trim($_POST['unit_minor'] ?? 'حبة');
    $price_minor = floatval($_POST['price_minor'] ?? 0);
    
    $unit_mid = trim($_POST['unit_mid'] ?? '');
    $price_mid = floatval($_POST['price_mid'] ?? 0);
    $rate_mid = floatval($_POST['rate_mid'] ?? 1);
    
    $unit_major = trim($_POST['unit_major'] ?? 'كرتون');
    $price_major = floatval($_POST['p_price'] ?? 0);
    $rate_major = floatval($_POST['rate_major'] ?? 1);
    
    $price = $price_major > 0 ? $price_major : $price_minor;

    $image_path = '';
    if (isset($_FILES['p_image']) && $_FILES['p_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['p_image']['tmp_name'];
        $file_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['p_image']['name']);
        if (move_uploaded_file($file_tmp, $upload_dir . $file_name)) {
            $image_path = $upload_dir . $file_name;
        }
    }                                         

    if (!empty($name)) {                      
        $stmt = $db->prepare("INSERT INTO products (name, price, cost, qty, barcode, image, currency, unit_minor, price_minor, unit_mid, price_mid, rate_mid, unit_major, price_major, rate_major) VALUES (:name, :price, :cost, :qty, :barcode, :image, :currency, :unit_minor, :price_minor, :unit_mid, :price_mid, :rate_mid, :unit_major, :price_major, :rate_major)");
        
        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
        $stmt->bindValue(':price', $price, SQLITE3_FLOAT);
        $stmt->bindValue(':cost', $cost, SQLITE3_FLOAT);
        $stmt->bindValue(':qty', $qty, SQLITE3_FLOAT);
        $stmt->bindValue(':barcode', $barcode, SQLITE3_TEXT);
        $stmt->bindValue(':image', $image_path, SQLITE3_TEXT);
        $stmt->bindValue(':currency', $currency, SQLITE3_TEXT);
        
        $stmt->bindValue(':unit_minor', $unit_minor, SQLITE3_TEXT);
        $stmt->bindValue(':price_minor', $price_minor, SQLITE3_FLOAT);
        $stmt->bindValue(':unit_mid', $unit_mid, SQLITE3_TEXT);
        $stmt->bindValue(':price_mid', $price_mid, SQLITE3_FLOAT);
        $stmt->bindValue(':rate_mid', $rate_mid, SQLITE3_FLOAT);
        $stmt->bindValue(':unit_major', $unit_major, SQLITE3_TEXT);
        $stmt->bindValue(':price_major', $price_major, SQLITE3_FLOAT);
        $stmt->bindValue(':rate_major', $rate_major, SQLITE3_FLOAT);
        
        $stmt->execute();
        $msg = "✅ تم إضافة الصنف بهرم الوحدات الثلاثي بنجاح!";                           
    }                                         
}

if (isset($_POST['update_product'])) {
    $p_id = intval($_POST['p_id']);
    $p_name = trim($_POST['p_name']);
    $p_cost = floatval($_POST['p_cost'] ?? 0);
    $p_qty = floatval($_POST['p_qty'] ?? 0);
    $currency = trim($_POST['currency'] ?? 'YER');

    $unit_minor = trim($_POST['unit_minor'] ?? 'حبة');
    $price_minor = floatval($_POST['price_minor'] ?? 0);
    $unit_mid = trim($_POST['unit_mid'] ?? '');
    $price_mid = floatval($_POST['price_mid'] ?? 0);
    $rate_mid = floatval($_POST['rate_mid'] ?? 1);
    $unit_major = trim($_POST['unit_major'] ?? 'كرتون');
    $price_major = floatval($_POST['p_price'] ?? 0);
    $rate_major = floatval($_POST['rate_major'] ?? 1);
    
    $price = $price_major > 0 ? $price_major : $price_minor;

    $old_img = $db->querySingle("SELECT image FROM products WHERE id = $p_id");
    $image_path = $old_img;

    if (isset($_FILES['p_image']) && $_FILES['p_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['p_image']['tmp_name'];
        $file_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['p_image']['name']);
        if (move_uploaded_file($file_tmp, $upload_dir . $file_name)) {
            $image_path = $upload_dir . $file_name;
        }
    }

    if ($p_id > 0 && !empty($p_name)) {
        $stmt = $db->prepare("UPDATE products SET name=:name, price=:price, cost=:cost, qty=:qty, image=:image, currency=:currency, unit_minor=:unit_minor, price_minor=:price_minor, unit_mid=:unit_mid, price_mid=:price_mid, rate_mid=:rate_mid, unit_major=:unit_major, price_major=:price_major, rate_major=:rate_major WHERE id=:id");
        
        $stmt->bindValue(':name', $p_name, SQLITE3_TEXT);
        $stmt->bindValue(':price', $price, SQLITE3_FLOAT);
        $stmt->bindValue(':cost', $p_cost, SQLITE3_FLOAT);
        $stmt->bindValue(':qty', $p_qty, SQLITE3_FLOAT);
        $stmt->bindValue(':image', $image_path, SQLITE3_TEXT);
        $stmt->bindValue(':currency', $currency, SQLITE3_TEXT);
        
        $stmt->bindValue(':unit_minor', $unit_minor, SQLITE3_TEXT);
        $stmt->bindValue(':price_minor', $price_minor, SQLITE3_FLOAT);
        $stmt->bindValue(':unit_mid', $unit_mid, SQLITE3_TEXT);
        $stmt->bindValue(':price_mid', $price_mid, SQLITE3_FLOAT);
        $stmt->bindValue(':rate_mid', $rate_mid, SQLITE3_FLOAT);
        $stmt->bindValue(':unit_major', $unit_major, SQLITE3_TEXT);
        $stmt->bindValue(':price_major', $price_major, SQLITE3_FLOAT);
        $stmt->bindValue(':rate_major', $rate_major, SQLITE3_FLOAT);
        $stmt->bindValue(':id', $p_id, SQLITE3_INTEGER);
        
        $stmt->execute();
        $msg = "✅ تم التحديث بنجاح!";
    }
}

if (isset($_POST['bulk_pricing'])) {
    $target_curr = trim($_POST['bulk_currency'] ?? 'ALL');
    $action_type = trim($_POST['bulk_action'] ?? 'increase_pct');
    $val = floatval($_POST['bulk_val'] ?? 0);

    if ($val > 0) {
        $where_clause = ($target_curr === 'ALL') ? "" : "WHERE currency = '$target_curr'";
        $res_bulk = $db->query("SELECT id, price_major, price_mid, price_minor FROM products $where_clause");
        while ($row = $res_bulk->fetchArray(SQLITE3_ASSOC)) {
            $id = $row['id'];
            $new_major = $row['price_major'];
            $new_mid = $row['price_mid'];
            $new_minor = $row['price_minor'];

            if ($action_type === 'increase_pct') {
                if($new_major > 0) $new_major += ($new_major * ($val / 100));
                if($new_mid > 0) $new_mid += ($new_mid * ($val / 100));
                if($new_minor > 0) $new_minor += ($new_minor * ($val / 100));
            } elseif ($action_type === 'decrease_pct') {
                if($new_major > 0) $new_major -= ($new_major * ($val / 100));
                if($new_mid > 0) $new_mid -= ($new_mid * ($val / 100));
                if($new_minor > 0) $new_minor -= ($new_minor * ($val / 100));
            } elseif ($action_type === 'increase_fixed') {
                if($new_major > 0) $new_major += $val;
                if($new_mid > 0) $new_mid += $val;
                if($new_minor > 0) $new_minor += $val;
            }

            $base_price = $new_major > 0 ? $new_major : $new_minor;
            $db->exec("UPDATE products SET price=$base_price, price_major=$new_major, price_mid=$new_mid, price_minor=$new_minor WHERE id=$id");
        }
        $msg = "⚡ تم تطبيق التسعير الجماعي بنجاح!";
    }
}

if (isset($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);
    if ($del_id > 0) {
        $db->exec("DELETE FROM products WHERE id = $del_id");
        $msg = "🗑️ تم الحذف!";
    }
}

$products = [];                               
$res = $db->query("SELECT * FROM products ORDER BY name ASC");                              
while($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $products[] = $row;
}

$total_count = count($products);
$out_of_stock_count = 0;
foreach($products as $p) {
    if(floatval($p['qty']) <= 0) $out_of_stock_count++;
}

$db->close();                                 
?>                                            
<!DOCTYPE html>                               
<html lang="ar" dir="rtl" translate="no">
<head>
<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'>
    <meta charset="UTF-8">
    <title>إدارة المخزون - نظام أبوضياء</title>                           
    <?php include 'global_style.php'; ?>      
    <style>                                   
        .lux-wrapper { padding: 6px; max-width: 650px; margin: 0 auto 75px auto; }
        .lux-title { font-size: 13px !important; padding: 8px !important; margin-bottom: 6px !important; }
        
        .stats-bar { display: flex; gap: 6px; margin-bottom: 6px; }
        .stat-card { flex: 1; background: var(--card-white); border: 1px solid var(--border-gray); border-radius: 6px; padding: 6px; text-align: center; }
        .stat-card .num { font-size: 13px; font-weight: bold; color: var(--btn-blue); }
        .stat-card .lbl { font-size: 9px; color: #64748B; font-weight: bold; }

        .prod-card { background: var(--card-white); border: 1px solid var(--border-gray); border-radius: 8px; padding: 6px 8px; margin-bottom: 5px; display: flex; align-items: center; gap: 8px; }
        .form-box { background: #F8FAFC; border: 1px solid var(--border-gray); border-radius: 8px; padding: 8px 10px; margin-bottom: 8px; }
        .bulk-box { background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 8px; padding: 8px 10px; margin-bottom: 8px; }
        .edit-form { display: none; background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 6px; padding: 6px; margin-top: 5px; }
        .lux-input { padding: 5px 8px !important; font-size: 11px !important; margin-bottom: 5px !important; }
        .lux-btn { padding: 6px !important; font-size: 11px !important; }
        .btn-edit-toggle { background: #0284C7; color: #FFF; border: none; padding: 3px 6px; border-radius: 4px; font-size: 9px; font-weight: bold; cursor: pointer; }
        .btn-delete { background: #DC2626; color: #FFF; border: none; padding: 3px 6px; border-radius: 4px; font-size: 9px; font-weight: bold; cursor: pointer; text-decoration: none; display: inline-block; margin-top: 2px; }
        .btn-update { background: #16A34A; color: #FFF; border: none; padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; cursor: pointer; width: 100%; margin-top: 4px; }
        .btn-bulk { background: #D97706; color: #FFF; border: none; padding: 5px; border-radius: 4px; font-size: 10px; font-weight: bold; cursor: pointer; width: 100px; }

        .filter-bar { display: flex; gap: 4px; margin-bottom: 6px; overflow-x: auto; padding-bottom: 2px; }
        .filter-btn { background: #E2E8F0; color: #475569; border: none; padding: 4px 10px; border-radius: 15px; font-size: 10px; font-weight: bold; cursor: pointer; white-space: nowrap; }
        .filter-btn.active { background: #2563EB; color: #FFF; }

        .prod-thumb { width: 35px; height: 35px; border-radius: 6px; object-fit: cover; border: 1px solid #CBD5E1; flex-shrink: 0; }
        .prod-thumb-placeholder { width: 35px; height: 35px; border-radius: 6px; background: #E2E8F0; display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0; color: #64748B; }
        .unit-badge { background: #E0F2FE; color: #0369A1; padding: 1px 4px; border-radius: 3px; font-size: 8.5px; font-weight: bold; display: inline-block; margin: 1px 1px 0 0; }
        .curr-badge { background: #FEF3C7; color: #D97706; padding: 1px 4px; border-radius: 3px; font-size: 8.5px; font-weight: bold; display: inline-block; margin-right: 3px; }
    </style>                                  
</head>
<body>                                        
<div class="lux-wrapper">                     
    <h2 class="lux-title">📦 إدارة المخزون والأصناف</h2>                                     

    <?php if($msg): ?>
        <div style="background:var(--btn-green); color:#FFF; padding:6px; border-radius:6px; margin-bottom:8px; text-align:center; font-weight:bold; font-size:11px;"><?php echo $msg; ?></div>
    <?php endif; ?>                           

    <div class="stats-bar">
        <div class="stat-card">
            <div class="num"><?php echo $total_count; ?></div>
            <div class="lbl">إجمالي الأصناف</div>
        </div>
        <div class="stat-card">
            <div class="num" style="color: <?php echo $out_of_stock_count > 0 ? '#DC2626' : '#16A34A'; ?>;"><?php echo $out_of_stock_count; ?></div>
            <div class="lbl">أصناف نفدت</div>
        </div>
    </div>

    <div class="bulk-box">
        <h3 style="margin:0 0 5px 0; font-size:11px; color:#1D4ED8;">⚡ التسعير الجماعي السريع</h3>
        <form method="POST" onsubmit="return confirm('تطبيق التعديل على الأسعار؟');">
            <div style="display:flex; gap:4px; margin-bottom:4px;">
                <select name="bulk_currency" class="lux-input" style="margin:0; font-size:10px; flex:1;">
                    <option value="ALL">كافة العملات</option>
                    <option value="YER">يمني (YER)</option>
                    <option value="SAR">سعودي (SAR)</option>
                    <option value="USD">دولار (USD)</option>
                </select>
                <select name="bulk_action" class="lux-input" style="margin:0; font-size:10px; flex:1;">
                    <option value="increase_pct">زيادة (%)</option>
                    <option value="decrease_pct">خصم (%)</option>
                    <option value="increase_fixed">إضافة قيمة</option>
                </select>
            </div>
            <div style="display:flex; gap:4px;">
                <input type="number" step="any" name="bulk_val" class="lux-input" style="margin:0; font-size:10px; flex:1;" placeholder="القيمة..." required>
                <button type="submit" name="bulk_pricing" class="btn-bulk" style="margin:0;">⚡ تطبيق</button>
            </div>
        </form>
    </div>

    <!-- نموذج الإضافة بالهرم الثلاثي الكامل والقوائم المنسدلة -->  
    <div class="form-box">                    
        <h3 style="margin:0 0 6px 0; font-size:12px; color:var(--btn-blue);">➕ إضافة صنف جديد (3 وحدات)</h3>                                                       
        <form method="POST" enctype="multipart/form-data">                                  
            <div style="margin-bottom:4px;"><input type="text" name="p_name" class="lux-input" placeholder="اسم الصنف..." required></div>           

            <div style="margin-bottom:4px;">
                <select name="currency" class="lux-input">
                    <option value="YER">ريال يمني (YER)</option>
                    <option value="SAR">ريال سعودي (SAR)</option>
                    <option value="USD">دولار أمريكي (USD)</option>
                </select>
            </div>

            <!-- 1. الوحدة الصغرى -->
            <div style="display:flex; gap:4px; margin-bottom:4px;">
                <select name="unit_minor" class="lux-input" style="margin:0; flex:1;">
                    <option value="حبة" selected>حبة</option>
                    <option value="قطعة">قطعة</option>
                    <option value="ظرف">ظرف</option>
                    <option value="عبوة">عبوة</option>
                </select>
                <input type="number" step="0.01" name="price_minor" class="lux-input" style="margin:0; flex:1;" placeholder="سعر الصغرى">
            </div>

            <!-- 2. الوحدة المتوسطة -->
            <div style="display:flex; gap:4px; margin-bottom:4px;">
                <select name="unit_mid" class="lux-input" style="margin:0; flex:1;">
                    <option value="" selected>-- بدون متوسطة --</option>
                    <option value="باكت">باكت</option>
                    <option value="شدة">شدة</option>
                    <option value="طبق">طبق</option>
                    <option value="صندوق">صندوق</option>
                </select>
                <input type="number" step="0.01" name="price_mid" class="lux-input" style="margin:0; flex:1;" placeholder="سعر المتوسطة">
                <input type="number" step="0.01" name="rate_mid" class="lux-input" value="1" style="margin:0; flex:0.6;" placeholder="عددها">
            </div>

            <!-- 3. الوحدة الكبرى -->
            <div style="display:flex; gap:4px; margin-bottom:4px;">
                <select name="unit_major" class="lux-input" style="margin:0; flex:1;" required>
                    <option value="كرتون" selected>كرتون</option>
                    <option value="باكت">باكت</option>
                    <option value="شدة">شدة</option>
                    <option value="طبق">طبق</option>
                    <option value="كيس">كيس</option>
                    <option value="صندوق">صندوق</option>
                </select>
                <input type="number" step="0.01" name="p_price" class="lux-input" style="margin:0; flex:1;" placeholder="سعر الكبرى" required>
                <input type="number" step="0.01" name="rate_major" class="lux-input" value="1" style="margin:0; flex:0.6;" placeholder="عددها">
            </div>

            <div style="display:flex; gap:4px; margin-bottom:4px;">                         
                <input type="number" step="0.01" name="p_cost" class="lux-input" style="margin:0; flex:1;" placeholder="التكلفة">
                <input type="number" step="0.01" name="p_qty" class="lux-input" value="0" style="margin:0; flex:1;" placeholder="المخزون">
                <input type="file" name="p_image" accept="image/*" class="lux-input" style="margin:0; flex:1.2; padding:3px; background:#FFF; font-size:9px;">
            </div>                            
            <button type="submit" name="add_product" class="lux-btn" style="width:100%; padding:6px;">💾 حفظ الصنف</button>    
        </form>                               
    </div>                                                                                  

    <div style="margin-bottom: 6px;">        
        <input type="text" id="prodSearch" class="lux-input" placeholder="🔍 بحث سريع..." onkeyup="filterProd()" style="margin:0;">                  
    </div>

    <div class="filter-bar">
        <button type="button" class="filter-btn active" onclick="filterCurrency('ALL', this)">📋 الكل</button>
        <button type="button" class="filter-btn" onclick="filterCurrency('YER', this)">🇾🇪 يمني</button>
        <button type="button" class="filter-btn" onclick="filterCurrency('SAR', this)">🇸🇦 سعودي</button>
        <button type="button" class="filter-btn" onclick="filterCurrency('USD', this)">💵 دولار</button>
    </div>

    <div id="productsList">                   
        <?php foreach($products as $p): 
            $curr = $p['currency'] ?? 'YER';
            $u_maj = $p['unit_major'] ?? 'كرتون';
            $u_mid = $p['unit_mid'] ?? '';
            $u_min = $p['unit_minor'] ?? 'حبة';
        ?>    
        <div class="prod-card p-item" data-name="<?php echo strtolower(htmlspecialchars($p['name'])); ?>" data-curr="<?php echo $curr; ?>">

            <div>
                <?php if(!empty($p['image']) && file_exists($p['image'])): ?>
                    <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="صورة" class="prod-thumb">
                <?php else: ?>
                    <div class="prod-thumb-placeholder">📦</div>
                <?php endif; ?>
            </div>

            <div style="flex: 1; overflow:hidden;">            
                <div style="font-weight:bold; color:var(--text-dark); font-size:12px;">
                    <?php echo htmlspecialchars($p['name']); ?>
                    <span class="curr-badge"><?php echo $curr; ?></span>
                </div>  
                <div style="font-size:10px; color:#64748B;">
                    م: <strong><?php echo $p['qty']; ?></strong> | ت: <strong><?php echo $p['cost']; ?></strong>
                </div>
                
                <div>
                    <?php if(!empty($u_maj)): ?>
                        <span class="unit-badge">📦 <?php echo $u_maj; ?>: <?php echo number_format($p['price_major'] > 0 ? $p['price_major'] : $p['price'], 2); ?></span>
                    <?php endif; ?>
                    <?php if(!empty($u_mid) && floatval($p['price_mid']) > 0): ?>
                        <span class="unit-badge" style="background:#FEF3C7; color:#B45309;">📁 <?php echo $u_mid; ?>: <?php echo number_format($p['price_mid'], 2); ?></span>
                    <?php endif; ?>
                    <?php if(!empty($u_min) && floatval($p['price_minor']) > 0): ?>
                        <span class="unit-badge" style="background:#DCFCE7; color:#15803D;">🍬 <?php echo $u_min; ?>: <?php echo number_format($p['price_minor'], 2); ?></span>
                    <?php endif; ?>
                </div>

                <!-- نموذج التعديل السريع بالهرم الثلاثي -->
                <div class="edit-form" id="edit_box_<?php echo $p['id']; ?>">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="p_id" value="<?php echo $p['id']; ?>">
                        <div style="margin-bottom:3px;"><input type="text" name="p_name" class="lux-input" value="<?php echo htmlspecialchars($p['name']); ?>" style="margin:0;" required></div>

                        <div style="margin-bottom:3px;">
                            <select name="currency" class="lux-input" style="margin:0;">
                                <option value="YER" <?php if($curr=='YER') echo 'selected'; ?>>يمني (YER)</option>
                                <option value="SAR" <?php if($curr=='SAR') echo 'selected'; ?>>سعودي (SAR)</option>
                                <option value="USD" <?php if($curr=='USD') echo 'selected'; ?>>دولار (USD)</option>
                            </select>
                        </div>

                        <!-- تعديل الصغرى -->
                        <div style="display:flex; gap:3px; margin-bottom:3px;">
                            <select name="unit_minor" class="lux-input" style="margin:0; flex:1;">
                                <option value="حبة" <?php if($u_min=='حبة') echo 'selected'; ?>>حبة</option>
                                <option value="قطعة" <?php if($u_min=='قطعة') echo 'selected'; ?>>قطعة</option>
                                <option value="ظرف" <?php if($u_min=='ظرف') echo 'selected'; ?>>ظرف</option>
                                <option value="عبوة" <?php if($u_min=='عبوة') echo 'selected'; ?>>عبوة</option>
                            </select>
                            <input type="number" step="0.01" name="price_minor" class="lux-input" value="<?php echo $p['price_minor']; ?>" style="margin:0; flex:1;" placeholder="سعر الصغرى">
                        </div>

                        <!-- تعديل المتوسطة -->
                        <div style="display:flex; gap:3px; margin-bottom:3px;">
                            <select name="unit_mid" class="lux-input" style="margin:0; flex:1;">
                                <option value="" <?php if(empty($u_mid)) echo 'selected'; ?>>-- بدون --</option>
                                <option value="باكت" <?php if($u_mid=='باكت') echo 'selected'; ?>>باكت</option>
                                <option value="شدة" <?php if($u_mid=='شدة') echo 'selected'; ?>>شدة</option>
                                <option value="طبق" <?php if($u_mid=='طبق') echo 'selected'; ?>>طبق</option>
                                <option value="صندوق" <?php if($u_mid=='صندوق') echo 'selected'; ?>>صندوق</option>
                            </select>
                            <input type="number" step="0.01" name="price_mid" class="lux-input" value="<?php echo $p['price_mid']; ?>" style="margin:0; flex:1;" placeholder="سعرها">
                            <input type="number" step="0.01" name="rate_mid" class="lux-input" value="<?php echo $p['rate_mid'] ?? 1; ?>" style="margin:0; flex:0.6;" placeholder="العدد">
                        </div>

                        <!-- تعديل الكبرى -->
                        <div style="display:flex; gap:3px; margin-bottom:3px;">
                            <select name="unit_major" class="lux-input" style="margin:0; flex:1;" required>
                                <option value="كرتون" <?php if($u_maj=='كرتون') echo 'selected'; ?>>كرتون</option>
                                <option value="باكت" <?php if($u_maj=='باكت') echo 'selected'; ?>>باكت</option>
                                <option value="شدة" <?php if($u_maj=='شدة') echo 'selected'; ?>>شدة</option>
                                <option value="طبق" <?php if($u_maj=='طبق') echo 'selected'; ?>>طبق</option>
                                <option value="كيس" <?php if($u_maj=='كيس') echo 'selected'; ?>>كيس</option>
                                <option value="صندوق" <?php if($u_maj=='صندوق') echo 'selected'; ?>>صندوق</option>
                            </select>
                            <input type="number" step="0.01" name="p_price" class="lux-input" value="<?php echo $p['price_major'] > 0 ? $p['price_major'] : $p['price']; ?>" style="margin:0; flex:1;" placeholder="سعرها" required>
                            <input type="number" step="0.01" name="rate_major" class="lux-input" value="<?php echo $p['rate_major'] ?? 1; ?>" style="margin:0; flex:0.6;" placeholder="العدد">
                        </div>

                        <div style="display:flex; gap:3px; margin-bottom:3px;">
                            <input type="number" step="0.01" name="p_cost" class="lux-input" value="<?php echo $p['cost']; ?>" style="margin:0; flex:1;" placeholder="التكلفة">
                            <input type="number" step="0.01" name="p_qty" class="lux-input" value="<?php echo $p['qty']; ?>" style="margin:0; flex:1;" placeholder="المخزون">
                        </div>
                        <div style="margin-bottom:3px;">
                            <input type="file" name="p_image" accept="image/*" class="lux-input" style="padding:2px; font-size:9px; background:#FFF; margin:0;">
                        </div>
                        <button type="submit" name="update_product" class="btn-update">💾 حفظ</button>
                    </form>
                </div>
            </div>

            <div style="text-align: left; flex-shrink:0;">
                <div style="font-weight:bold; font-size:11px; color:var(--btn-blue); margin-bottom: 3px; text-align: left;" dir="ltr">
                    <?php echo number_format($p['price_major'] > 0 ? $p['price_major'] : $p['price'], 2); ?>
                </div>
                <button type="button" class="btn-edit-toggle" onclick="toggleEdit(<?php echo $p['id']; ?>)">✏️ تعديل</button><br>
                <a href="products.php?delete_id=<?php echo $p['id']; ?>" class="btn-delete" onclick="return confirm('حذف نهائي؟');">🗑️ حذف</a>
            </div>
        </div>                                
        <?php endforeach; ?>                  
    </div>                                    
</div>                                                                                      

<script>
let currentCurrencyFilter = 'ALL';

function filterProd() {                       
    let input = document.getElementById('prodSearch').value.toLowerCase();                  
    let cards = document.getElementsByClassName('p-item');                                  
    for (let i = 0; i < cards.length; i++) {  
        let name = cards[i].getAttribute('data-name');
        let curr = cards[i].getAttribute('data-curr');
        
        let matchSearch = name.includes(input);
        let matchCurr = (currentCurrencyFilter === 'ALL' || curr === currentCurrencyFilter);
        
        cards[i].style.display = (matchSearch && matchCurr) ? "" : "none";                        
    }                                         
}

function filterCurrency(curr, btn) {
    currentCurrencyFilter = curr;
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    filterProd();
}

function toggleEdit(id) {
    let box = document.getElementById('edit_box_' + id);
    if(box.style.display === 'block') {
        box.style.display = 'none';
    } else {
        box.style.display = 'block';
    }
}
</script>                                     
<?php include 'nav_menu.php'; ?>              
</body>
</html>
