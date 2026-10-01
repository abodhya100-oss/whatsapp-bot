<?php
// catalog.php - كتالوج الطلبات - V-MAX PRO
error_reporting(0);
ini_set('display_errors', 0);

include_once 'config_loader.php';
require_once 'auth.php';

$db_file = __DIR__ . '/vmax.db';
if(!file_exists($db_file)) { $db_file = 'database.db'; }
$db = new SQLite3($db_file);

@$db->exec("ALTER TABLE products ADD COLUMN image TEXT");
@$db->exec("ALTER TABLE products ADD COLUMN wholesale_price REAL DEFAULT 0");
@$db->exec("ALTER TABLE products ADD COLUMN units_per_pack INTEGER DEFAULT 1");
@$db->exec("ALTER TABLE products ADD COLUMN unit_1_name TEXT DEFAULT 'حبة'");
@$db->exec("ALTER TABLE products ADD COLUMN unit_1_price REAL DEFAULT 0");
@$db->exec("ALTER TABLE products ADD COLUMN unit_2_name TEXT DEFAULT ''");
@$db->exec("ALTER TABLE products ADD COLUMN unit_2_price REAL DEFAULT 0");
@$db->exec("ALTER TABLE products ADD COLUMN unit_3_name TEXT DEFAULT ''");
@$db->exec("ALTER TABLE products ADD COLUMN unit_3_price REAL DEFAULT 0");

$store_name = "مؤسسة أبوضياء للتوزيع";
$store_phone = "771458609";
$res_settings = @$db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('store_name', 'phone')");
if ($res_settings) {                          
    while($row = $res_settings->fetchArray(SQLITE3_ASSOC)) {
        if ($row['setting_key'] === 'store_name' && !empty($row['setting_value'])) $store_name = $row['setting_value'];
        if ($row['setting_key'] === 'phone' && !empty($row['setting_value'])) $store_phone = $row['setting_value'];
    }
}

$store_phone = preg_replace('/[^0-9]/', '', $store_phone);
if (strlen($store_phone) == 9 && strpos($store_phone, '7') === 0) {
    $store_phone = "967" . $store_phone;
}

$products = [];
$res_p = $db->query("SELECT * FROM products ORDER BY id DESC");
while($row_p = $res_p->fetchArray(SQLITE3_ASSOC)) {
    $products[] = $row_p;
}
$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'>
    <meta name='theme-color' content='#ffffff'>
    <title>كتالوج الطلبات - <?php echo htmlspecialchars($store_name); ?></title>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html-to-image/1.11.11/html-to-image.min.js"></script>

    <style>
        :root {
            --primary: #0f172a;
            --secondary: #0284c7;
            --accent: #38bdf8;
            --success: #10b981;
            --danger: #ef4444;
            --bg-light: #f4f7f9;
            --card-bg: #ffffff;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
        }

        body {
            font-family: 'Tahoma', Arial, sans-serif;
            background-color: var(--bg-light);
            margin: 0; padding: 0; color: var(--text-dark);
            padding-bottom: 380px; 
        }

        .catalog-container { max-width: 800px; margin: 0 auto; padding: 4px; } /* ضغط هوامش الصفحة */

        .compact-header {
            display: flex; align-items: center; background: var(--card-bg);
            border-radius: 8px; padding: 6px 8px; margin-bottom: 6px;
            border: 1px solid var(--border-color); box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }
        .compact-logo {
            width: 28px; height: 28px; background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white; border-radius: 6px; display: flex; align-items: center; justify-content: center;
            font-size: 14px; margin-left: 6px; box-shadow: 0 2px 4px rgba(2, 132, 199, 0.2);
        }
        .compact-title { display: flex; flex-direction: column; }
        .compact-title h1 { margin: 0; font-size: 11px; font-weight: 900; color: var(--primary); }
        .compact-title p { margin: 0; font-size: 8px; color: var(--text-muted); font-weight: 600; }

        .price-toggle-container {
            display: flex; background: #e2e8f0; border-radius: 6px; padding: 2px; margin-bottom: 6px;
        }
        .pt-btn {
            flex: 1; text-align: center; padding: 4px; font-size: 10px; font-weight: 800;
            color: var(--text-muted); cursor: pointer; border-radius: 4px; transition: 0.3s;
        }
        .pt-btn.active {
            background: #ffffff; color: var(--primary); box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .price-retail { display: inline; }
        .price-wholesale { display: none; }
        body.wholesale-mode .price-retail { display: none; }
        body.wholesale-mode .price-wholesale { display: inline; }

        .search-bar-container { position: sticky; top: 4px; z-index: 100; margin-bottom: 8px; }
        .search-input {
            width: 100%; box-sizing: border-box; padding: 6px 10px 6px 26px;
            border-radius: 8px; border: 1px solid var(--border-color); background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px); font-size: 11px; font-weight: 600; color: var(--primary);
            box-shadow: 0 2px 6px rgba(0,0,0,0.05); outline: none; transition: all 0.3s ease;
        }
        .search-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15); }
        .search-icon { position: absolute; left: 8px; top: 50%; transform: translateY(-50%); font-size: 12px; color: var(--text-muted); }

        /* الضغط الأقصى للشبكة (Grid) */
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(82px, 1fr)); gap: 4px; }

        .product-card {
            background: var(--card-bg); border-radius: 8px; overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03); border: 1px solid var(--border-color);
            transition: all 0.2s ease; display: flex; flex-direction: column;
        }
        .product-image-container {
            width: 100%; padding-top: 80%; position: relative; background: #ffffff;
            border-bottom: 1px solid #f1f5f9; overflow: hidden;
        }
        
        .product-image { 
            position: absolute; top: 0; left: 0; width: 100%; height: 100%; 
            object-fit: contain; padding: 1px; box-sizing: border-box; 
            image-rendering: -webkit-optimize-contrast; 
            image-rendering: high-quality; 
            transform: translateZ(0); 
        }
        
        .product-placeholder {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; flex-direction: column;
            align-items: center; justify-content: center; background: linear-gradient(135deg, #f1f5f9, #e2e8f0); color: var(--text-muted);
        }
        .placeholder-icon { font-size: 20px; margin-bottom: 2px; opacity: 0.8; }

        .product-details { padding: 4px; display: flex; flex-direction: column; flex-grow: 1; }
        .product-title { font-size: 8.5px; font-weight: 800; color: var(--primary); margin: 0 0 2px 0; line-height: 1.1; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        
        .unit-selector {
            width: 100%; padding: 1px 2px; border-radius: 3px; border: 1px solid var(--border-color);
            background: #f8fafc; color: var(--primary); font-size: 7.5px; font-weight: bold;
            outline: none; cursor: pointer; margin-bottom: 3px; font-family: inherit;
        }
        .unit-selector:focus { border-color: var(--secondary); background: #ffffff; }

        .product-meta { display: flex; justify-content: space-between; align-items: center; margin-bottom: 3px; }
        .product-price { font-size: 10px; font-weight: 900; color: var(--success); }

        .cart-controls {
            display: flex; align-items: center; justify-content: space-between;
            background: #f8fafc; border: 1px solid var(--border-color); border-radius: 4px; padding: 1px; margin-top: auto;
        }
        .qty-btn {
            background: #ffffff; border: 1px solid var(--border-color); color: var(--text-dark);
            width: 18px; height: 18px; border-radius: 3px; font-size: 11px; font-weight: bold;
            display: flex; align-items: center; justify-content: center; cursor: pointer;
            box-shadow: 0 1px 1px rgba(0,0,0,0.02); transition: 0.2s;
        }
        .qty-btn:active { background: #e2e8f0; transform: scale(0.9); }
        .qty-btn.add { color: var(--secondary); border-color: #bae6fd; background: #f0f9ff; }

        .qty-input-wrapper {
            display: flex; align-items: center; justify-content: center; cursor: pointer;
            padding: 1px 2px; border-radius: 3px; transition: 0.2s;
        }

        .qty-display {
            font-size: 10px; font-weight: 900; color: var(--primary); width: 14px; text-align: center;
            border: none; background: transparent; outline: none; pointer-events: none; padding: 0; margin: 0;
            font-family: inherit;
        }
        .inline-unit {
            font-size: 7px; font-weight: 800; color: var(--secondary); pointer-events: none; margin-right: 1px;
        }

        .floating-cart-bar {
            position: fixed; 
            bottom: 85px !important;
            left: 8px; right: 8px;
            background: rgba(15, 23, 42, 0.95); 
            backdrop-filter: blur(12px);
            border-radius: 12px;
            padding: 8px 10px; 
            display: flex; flex-direction: column; gap: 6px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.4); 
            border: 1px solid rgba(255,255,255,0.1);
            z-index: 999; transform: translateY(150%); transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .floating-cart-bar.show { transform: translateY(0); }

        .cart-inputs-row { display: flex; gap: 6px; width: 100%; }
        .customer-name-input {
            flex: 1; box-sizing: border-box; padding: 6px 8px; border-radius: 6px;
            border: 1px solid rgba(255, 255, 255, 0.2); background: rgba(255, 255, 255, 0.1);
            color: #ffffff; font-size: 10px; font-weight: bold; outline: none; font-family: inherit;
            transition: 0.2s;
        }
        .customer-name-input::placeholder { color: #94a3b8; font-weight: normal; }
        .customer-name-input:focus { background: rgba(255, 255, 255, 0.15); border-color: var(--accent); }

        .cart-bar-top { display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 4px; }
        .cart-totals { color: #fff; display: flex; flex-direction: column; }
        .cart-totals .title { font-size: 8px; color: #cbd5e1; margin-bottom: 1px; }
        .cart-totals .amount { font-size: 12px; font-weight: 900; color: #4ade80; }

        .btn-clear-cart {
            background: rgba(239, 68, 68, 0.15); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.3);
            padding: 3px 6px; border-radius: 4px; font-size: 9px; font-weight: 800; cursor: pointer;
            transition: 0.2s; display: flex; align-items: center;
        }
        .btn-clear-cart:active { background: rgba(239, 68, 68, 0.3); transform: scale(0.95); }

        .cart-breakdown {
            font-size: 9px; font-weight: bold; color: #e0f2fe; background: rgba(2, 132, 199, 0.2);
            border: 1px solid rgba(56, 189, 248, 0.2); padding: 4px 6px; border-radius: 6px; line-height: 1.3;
        }

        .cart-buttons-row { display: flex; gap: 4px; width: 100%; margin-top: 2px; }

        .btn-send-order {
            flex: 1; background: linear-gradient(135deg, #25D366, #128C7E); color: #fff; border: none; padding: 6px 3px;
            border-radius: 6px; font-size: 9.5px; font-weight: 800; cursor: pointer; text-align: center;
            box-shadow: 0 2px 6px rgba(37, 211, 102, 0.2); transition: 0.2s;
        }
        .btn-send-order.alt { background: linear-gradient(135deg, #475569, #334155); box-shadow: 0 2px 6px rgba(71, 85, 105, 0.2); }
        .btn-send-order:active { transform: scale(0.96); }

        .image-save-section {
            background: var(--card-bg); border-radius: 8px; padding: 8px; border: 1px solid var(--border-color);
            box-shadow: 0 1px 3px rgba(0,0,0,0.03); margin-top: 8px; display: flex; flex-direction: column; gap: 4px;
        }
        .image-save-title { font-size: 10px; font-weight: 800; color: var(--primary); text-align: center; }
        .image-save-row { display: flex; gap: 4px; }

        .whatsapp-share-btn {
            flex: 1; background: var(--card-bg); color: var(--primary); border: 1px solid var(--border-color);
            padding: 6px 4px; border-radius: 6px; font-size: 9.5px; font-weight: 800; cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 2px; text-decoration: none;
        }
        .camera-btn { background: linear-gradient(135deg, var(--secondary), #0369a1); color: #fff; border: none; }
        .camera-btn.alt { background: linear-gradient(135deg, #475569, #334155); color: #fff; border: none; }

        .no-results { text-align: center; padding: 15px; color: var(--text-muted); font-weight: bold; display: none; font-size: 11px; }

        .numpad-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);
            z-index: 99998; opacity: 0; visibility: hidden; transition: 0.3s;
        }
        .numpad-overlay.show { opacity: 1; visibility: visible; }

        .numpad-sheet {
            position: fixed; bottom: 0; left: 0; width: 100%;
            background: #ffffff; border-radius: 16px 16px 0 0;
            padding: 12px 12px 85px 12px; box-shadow: 0 -8px 30px rgba(0,0,0,0.2);
            z-index: 99999; transform: translateY(100%); transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-sizing: border-box; font-family: 'Tahoma', Arial, sans-serif;
        }
        .numpad-sheet.show { transform: translateY(0); }

        .np-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; border-bottom: 1px solid var(--border-color); padding-bottom: 6px; }
        .np-title { margin: 0; font-size: 12px; font-weight: 800; color: var(--primary); display: flex; flex-direction: column; }
        .np-title span { font-size: 9px; color: var(--text-muted); margin-top: 2px; }
        .np-close { font-size: 16px; color: var(--danger); cursor: pointer; padding: 2px; background: #fff1f2; border-radius: 50%; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; }

        .np-display-box { background: var(--input-bg); border: 2px solid var(--secondary); border-radius: 10px; padding: 10px; text-align: center; font-size: 20px; font-weight: 900; color: var(--primary); margin-bottom: 10px; letter-spacing: 2px; }

        .np-quick-row { display: flex; gap: 4px; margin-bottom: 10px; overflow-x: auto; padding-bottom: 4px; }
        .np-quick-btn { flex: 1; min-width: 45px; background: #f0f9ff; color: var(--secondary); border: 1px solid #bae6fd; padding: 6px 0; border-radius: 8px; font-size: 11px; font-weight: 800; cursor: pointer; transition: 0.2s; }
        
        .np-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; }
        .np-btn { background: #f8fafc; border: 1px solid var(--border-color); color: var(--primary); font-size: 18px; font-weight: 800; padding: 10px 0; border-radius: 10px; cursor: pointer; }
        .np-btn.danger { background: #fff1f2; color: var(--danger); border-color: #fecdd3; }
        .np-btn.success { background: #10b981; color: #fff; font-size: 16px; border: none; grid-column: span 2; }

        body.hide-price-mode .product-price { display: none !important; }
        body.hide-price-mode .product-meta { justify-content: flex-end; }
        body.hide-price-mode .cart-totals .amount { display: none !important; }

        body.capture-mode #hideForPic1,
        body.capture-mode #hideForPic2,
        body.capture-mode #hideForPic3,
        body.capture-mode .image-save-section,
        body.capture-mode .floating-cart-bar,
        body.capture-mode .qty-btn { display: none !important; }
        body.capture-mode .unit-selector { appearance: none; border: none; background: transparent; padding: 0; font-size: 7px; color: var(--text-muted); }

        body.capture-mode .catalog-container {
            max-width: 600px !important; background: #ffffff !important; border-radius: 16px;
            padding: 8px !important; box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        }
        body.capture-mode .products-grid {
            grid-template-columns: repeat(4, 1fr) !important; gap: 6px !important;
        }
        body.capture-mode .product-card { border: 1px solid #cbd5e1 !important; box-shadow: none !important; }
        body.capture-mode .cart-controls {
            justify-content: center; background: #f0fdf4; border: 1px solid #bbf7d0; padding: 2px;
        }
        body.capture-mode .qty-display { font-size: 11px; color: var(--success); }
        body.capture-mode .inline-unit { font-size: 7px; color: var(--success); }
    </style>
</head>
<body>

<div class="catalog-container" id="captureArea">

    <div class="compact-header">
        <div class="compact-logo">🛍️</div>
        <div class="compact-title">
            <h1><?php echo htmlspecialchars($store_name); ?></h1>
            <p>اختر الأصناف، حدد الوحدات والكمية، وأرسل طلبك فوراً</p>
        </div>
    </div>

    <div class="price-toggle-container" id="hideForPic3">
        <div id="btn-retail" class="pt-btn active" onclick="setPriceMode('retail')">سعر تجزئة</div>
        <div id="btn-wholesale" class="pt-btn" onclick="setPriceMode('wholesale')">سعر جملة</div>
    </div>

    <div class="search-bar-container" id="hideForPic1">
        <span class="search-icon">🔍</span>
        <input type="text" id="searchInput" class="search-input" placeholder="ابحث عن الصنف..." onkeyup="filterCatalog()">
    </div>

    <div class="products-grid" id="catalogGrid">
        <?php if(count($products) > 0): ?>
            <?php foreach($products as $p):
                $id = $p['id'];
                $name = htmlspecialchars($p['name']);
                
                $unit = htmlspecialchars($p['unit_major'] ?? 'كرتون');
                $price_retail = $p['price'] ? $p['price'] : 0;
                $price_wholesale = (!empty($p['wholesale_price']) && $p['wholesale_price'] > 0) ? $p['wholesale_price'] : $price_retail;
                $units_per_pack = (!empty($p['units_per_pack']) && $p['units_per_pack'] > 0) ? intval($p['units_per_pack']) : 1;

                $u1_n = htmlspecialchars($p['unit_1_name'] ?? 'حبة');
                $u1_p = $p['unit_1_price'] ?? 0;
                $u2_n = htmlspecialchars($p['unit_2_name'] ?? '');
                $u2_p = $p['unit_2_price'] ?? 0;
                $u3_n = htmlspecialchars($p['unit_3_name'] ?? '');
                $u3_p = $p['unit_3_price'] ?? 0;

                $image = !empty($p['image']) ? $p['image'] : '';
                $icon = "📦";
                if(strpos($name, 'عصير') !== false || strpos($name, 'شراب') !== false) $icon = "🧃";
                elseif(strpos($name, 'بفك') !== false || strpos($name, 'شيبس') !== false || strpos($name, 'مقرمش') !== false) $icon = "🍟";
                elseif(strpos($name, 'ماء') !== false || strpos($name, 'مياه') !== false) $icon = "💧";
                elseif(strpos($name, 'بسكويت') !== false || strpos($name, 'كيك') !== false) $icon = "🍪";
            ?>
            <div class="product-card catalog-item" data-name="<?php echo strtolower($name); ?>">
                <div class="product-image-container">
                    <?php if($image): ?>
                        <img src="<?php echo $image; ?>" alt="<?php echo $name; ?>" class="product-image">
                    <?php else: ?>
                        <div class="product-placeholder">
                            <span class="placeholder-icon"><?php echo $icon; ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="product-details">
                    <h3 class="product-title"><?php echo $name; ?></h3>
                    
                    <select class="unit-selector" id="unitSel-<?php echo $id; ?>" onchange="updateProductDisplay(<?php echo $id; ?>)">
                        <option value="major" data-unit="<?php echo $unit; ?>" data-pr="<?php echo $price_retail; ?>" data-pw="<?php echo $price_wholesale; ?>"><?php echo $unit; ?></option>
                        <?php if(!empty($u1_n) && $u1_p > 0): ?>
                            <option value="u1" data-unit="<?php echo $u1_n; ?>" data-pr="<?php echo $u1_p; ?>" data-pw="<?php echo $u1_p; ?>"><?php echo $u1_n; ?></option>
                        <?php endif; ?>
                        <?php if(!empty($u2_n) && $u2_p > 0): ?>
                            <option value="u2" data-unit="<?php echo $u2_n; ?>" data-pr="<?php echo $u2_p; ?>" data-pw="<?php echo $u2_p; ?>"><?php echo $u2_n; ?></option>
                        <?php endif; ?>
                        <?php if(!empty($u3_n) && $u3_p > 0): ?>
                            <option value="u3" data-unit="<?php echo $u3_n; ?>" data-pr="<?php echo $u3_p; ?>" data-pw="<?php echo $u3_p; ?>"><?php echo $u3_n; ?></option>
                        <?php endif; ?>
                    </select>

                    <div class="product-meta">
                        <span class="product-price">
                            <span class="price-retail" id="pr-<?php echo $id; ?>"><?php echo number_format($price_retail, 0); ?></span>
                            <span class="price-wholesale" id="pw-<?php echo $id; ?>"><?php echo number_format($price_wholesale, 0); ?></span>
                            <small style="font-size:6px;">ر.ي</small>
                        </span>
                    </div>
                    
                    <div class="cart-controls hide-on-capture">
                        <div class="qty-btn" onclick="cartAction(<?php echo $id; ?>, '<?php echo addslashes($name); ?>', <?php echo $units_per_pack; ?>, -1)">-</div>
                        <div class="qty-input-wrapper" onclick="openNumpad(<?php echo $id; ?>, '<?php echo addslashes($name); ?>', <?php echo $units_per_pack; ?>)">
                            <input type="text" readonly class="qty-display" id="qty-<?php echo $id; ?>" value="0">
                            <span class="inline-unit" id="unitTxt-<?php echo $id; ?>"><?php echo $unit; ?></span>
                        </div>
                        <div class="qty-btn add" onclick="cartAction(<?php echo $id; ?>, '<?php echo addslashes($name); ?>', <?php echo $units_per_pack; ?>, 1)">+</div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="grid-column: 1 / -1; text-align: center; color: #64748b; padding: 20px; font-size: 11px;">
                لا توجد أصناف مسجلة في المخزون حتى الآن.
            </div>
        <?php endif; ?>
    </div>

    <div id="noResults" class="no-results">
        😕 لم يتم العثور على أي صنف مطابق لبحثك.
    </div>

    <div class="image-save-section" id="hideForPic2">
        <div class="image-save-title">📸 حفظ الطلب كصورة مع اسم الزبون</div>
        <input type="text" id="imgCustomerName" class="customer-name-input" style="background:#f8fafc; color:var(--primary); border-color:var(--border-color);" placeholder="اكتب اسم الزبون هنا (اختياري)...">
        <div class="image-save-row">
            <button onclick="downloadCatalogImage(true)" class="whatsapp-share-btn camera-btn">
                <span>📥</span> صورة بالأسعار
            </button>
            <button onclick="downloadCatalogImage(false)" class="whatsapp-share-btn camera-btn alt">
                <span>📥</span> صورة بدون أسعار
            </button>
        </div>
    </div>
    
    <div style="height: 120px; width: 100%; clear: both;"></div>

</div>

<div id="floatingCart" class="floating-cart-bar hide-on-capture">
    <div class="cart-inputs-row">
        <input type="text" id="waCustomerName" class="customer-name-input" placeholder="👤 اسم الزبون..." onfocus="this.scrollIntoView({behavior: 'smooth', block: 'center'});">
        <input type="text" id="waOrderNote" class="customer-name-input" placeholder="🕒 وقت التحميل..." onfocus="this.scrollIntoView({behavior: 'smooth', block: 'center'});">
    </div>

    <div class="cart-bar-top">
        <div class="cart-totals">
            <span class="title">إجمالي الطلب (<span id="cartItemsCount">0</span> أصناف)</span>
            <span class="amount"><span id="cartTotalSum">0</span> ر.ي</span>
        </div>
        <button onclick="clearCart()" class="btn-clear-cart">
            🗑️ تصفير
        </button>
    </div>
    
    <div id="cartBreakdown" class="cart-breakdown">التفصيل الإجمالي: 0</div>

    <div class="cart-buttons-row">
        <button onclick="sendOrderViaWhatsApp(true)" class="btn-send-order">
            إرسال بالأسعار 💬
        </button>
        <button onclick="sendOrderViaWhatsApp(false)" class="btn-send-order alt">
            إرسال بدون أسعار 💬
        </button>
    </div>
</div>

<div id="numpadOverlay" class="numpad-overlay hide-on-capture" onclick="closeNumpad()"></div>
<div id="numpadSheet" class="numpad-sheet hide-on-capture">
    <div class="np-header">
        <div class="np-title">الكمية (<span id="npItemUnit" style="color:var(--secondary);">الوحدة</span>) <span id="npItemName">الصنف</span></div>
        <div class="np-close" onclick="closeNumpad()">✖</div>
    </div>
    <div class="np-display-box" id="npDisplay">0</div>

    <div class="np-quick-row">
        <button class="np-quick-btn" onclick="quickNumpadAdd(5)">+5</button>
        <button class="np-quick-btn" onclick="quickNumpadAdd(10)">+10</button>
        <button class="np-quick-btn" onclick="quickNumpadAdd(20)">+20</button>
        <button class="np-quick-btn" onclick="quickNumpadAdd(50)">+50</button>
        <button class="np-quick-btn" onclick="quickNumpadAdd(100)">+100</button>
    </div>

    <div class="np-grid">
        <button class="np-btn" onclick="numpadPress('1')">1</button>
        <button class="np-btn" onclick="numpadPress('2')">2</button>
        <button class="np-btn" onclick="numpadPress('3')">3</button>
        <button class="np-btn" onclick="numpadPress('4')">4</button>
        <button class="np-btn" onclick="numpadPress('5')">5</button>
        <button class="np-btn" onclick="numpadPress('6')">6</button>
        <button class="np-btn" onclick="numpadPress('7')">7</button>
        <button class="np-btn" onclick="numpadPress('8')">8</button>
        <button class="np-btn" onclick="numpadPress('9')">9</button>
        <button class="np-btn danger" onclick="numpadClear()">C</button>
        <button class="np-btn" onclick="numpadPress('0')">0</button>
        <button class="np-btn success" onclick="numpadConfirm()">تأكيد ✔</button>
    </div>
</div>

<script>
    let cart = {};
    let currentPriceMode = 'retail';

    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        let clientName = urlParams.get('client') || urlParams.get('customer') || urlParams.get('name');
        if(clientName) {
            clientName = decodeURIComponent(clientName).trim();
            let waInput = document.getElementById('waCustomerName');
            let imgInput = document.getElementById('imgCustomerName');
            if(waInput) waInput.value = clientName;
            if(imgInput) imgInput.value = clientName;
        }
    });

    function setPriceMode(mode) {
        currentPriceMode = mode;
        if(mode === 'wholesale') {
            document.body.classList.add('wholesale-mode');
            document.getElementById('btn-wholesale').classList.add('active');
            document.getElementById('btn-retail').classList.remove('active');
        } else {
            document.body.classList.remove('wholesale-mode');
            document.getElementById('btn-retail').classList.add('active');
            document.getElementById('btn-wholesale').classList.remove('active');
        }
        updateCartUI();
    }

    function getSelectedUnitInfo(id) {
        let sel = document.getElementById('unitSel-' + id);
        let opt = sel.options[sel.selectedIndex];
        return {
            unitName: opt.getAttribute('data-unit'),
            priceRetail: parseFloat(opt.getAttribute('data-pr')),
            priceWholesale: parseFloat(opt.getAttribute('data-pw'))
        };
    }

    function getCartKey(id, unitName) { return id + '_' + unitName; }

    function updateProductDisplay(id) {
        let info = getSelectedUnitInfo(id);
        document.getElementById('pr-' + id).innerText = info.priceRetail.toLocaleString();
        document.getElementById('pw-' + id).innerText = info.priceWholesale.toLocaleString();
        let key = getCartKey(id, info.unitName);
        let currentQty = cart[key] ? cart[key].qty : 0;
        document.getElementById('qty-' + id).value = currentQty;
        document.getElementById('unitTxt-' + id).innerText = info.unitName;
    }

    function cartAction(id, name, upp, change) {
        let info = getSelectedUnitInfo(id);
        let key = getCartKey(id, info.unitName);

        if(!cart[key]) { 
            cart[key] = { id: id, name: name, price_retail: info.priceRetail, price_wholesale: info.priceWholesale, unit: info.unitName, units_per_pack: upp, qty: 0 }; 
        }
        
        cart[key].qty += change;
        if(cart[key].qty <= 0) { delete cart[key]; }
        
        document.getElementById('qty-' + id).value = cart[key] ? cart[key].qty : 0;
        updateCartUI();
    }

    function setCartQty(key, id, name, info, upp, val) {
        let qty = parseInt(val);
        if(isNaN(qty) || qty <= 0) {
            if(cart[key]) delete cart[key];
        } else {
            if(!cart[key]) {
                cart[key] = { id: id, name: name, price_retail: info.priceRetail, price_wholesale: info.priceWholesale, unit: info.unitName, units_per_pack: upp, qty: 0 };
            }
            cart[key].qty = qty;
        }
        document.getElementById('qty-' + id).value = cart[key] ? cart[key].qty : 0;
        updateCartUI();
    }

    function clearCart() {
        if(confirm('هل تريد حقاً تصفير وإلغاء الطلب الحالي؟')) {
            cart = {};
            let inputs = document.getElementsByClassName('qty-display');
            for(let i=0; i<inputs.length; i++) { inputs[i].value = '0'; }
            updateCartUI();
        }
    }

    function updateCartUI() {
        let totalSum = 0;
        let totalItems = 0;
        let globalSummary = {};

        for(let key in cart) {
            let item = cart[key];
            let activePrice = (currentPriceMode === 'wholesale') ? item.price_wholesale : item.price_retail;
            totalSum += activePrice * item.qty;
            totalItems += item.qty;

            let unitName = item.unit || 'وحدة';
            if(!globalSummary[unitName]) globalSummary[unitName] = 0;
            globalSummary[unitName] += item.qty;
        }

        let cartBar = document.getElementById('floatingCart');
        if(totalItems > 0) {
            cartBar.classList.add('show');
            document.getElementById('cartTotalSum').innerText = totalSum.toLocaleString();
            document.getElementById('cartItemsCount').innerText = Object.keys(cart).length;

            let breakdownEl = document.getElementById('cartBreakdown');
            let parts = [];
            for(let u in globalSummary) { parts.push(`${globalSummary[u]} ${u}`); }
            breakdownEl.style.display = 'block';
            breakdownEl.innerHTML = "📊 <b>الكميات:</b> " + parts.join(' | ');
        } else {
            cartBar.classList.remove('show');
        }
    }

    let npActiveId = null;
    let npActiveName = '';
    let npActiveUpp = 1;
    let npActiveInfo = null;
    let npCurrentStr = "0";
    let npActiveKey = '';

    function openNumpad(id, name, units_per_pack) {
        let info = getSelectedUnitInfo(id);
        let key = getCartKey(id, info.unitName);
        let currentVal = cart[key] ? cart[key].qty : 0;

        npActiveId = id; npActiveName = name; npActiveUpp = units_per_pack; npActiveInfo = info; npActiveKey = key;
        npCurrentStr = (currentVal == '0' || !currentVal) ? "" : currentVal.toString();

        document.getElementById('npItemName').innerText = name;
        document.getElementById('npItemUnit').innerText = info.unitName;
        updateNpDisplay();

        document.getElementById('numpadOverlay').classList.add('show');
        document.getElementById('numpadSheet').classList.add('show');
    }

    function closeNumpad() {
        document.getElementById('numpadOverlay').classList.remove('show');
        document.getElementById('numpadSheet').classList.remove('show');
    }

    function updateNpDisplay() { document.getElementById('npDisplay').innerText = npCurrentStr === "" ? "0" : npCurrentStr; }
    function numpadPress(numStr) { if(npCurrentStr.length < 5) { npCurrentStr += numStr; updateNpDisplay(); } }
    function numpadClear() { npCurrentStr = ""; updateNpDisplay(); }
    function quickNumpadAdd(num) {
        let current = npCurrentStr === "" ? 0 : parseInt(npCurrentStr);
        let newVal = current + num;
        if(newVal.toString().length <= 5) { npCurrentStr = newVal.toString(); updateNpDisplay(); }
    }
    function numpadConfirm() {
        let finalVal = npCurrentStr === "" ? 0 : parseInt(npCurrentStr);
        if(npActiveId !== null) { setCartQty(npActiveKey, npActiveId, npActiveName, npActiveInfo, npActiveUpp, finalVal); }
        closeNumpad();
    }

    function sendOrderViaWhatsApp(withPrice) {
        let phone = "<?php echo $store_phone; ?>";
        let customerName = document.getElementById('waCustomerName').value.trim();
        let orderNote = document.getElementById('waOrderNote').value.trim();

        let now = new Date();
        let dateStr = now.toLocaleDateString('en-GB');
        let hours = now.getHours(); let minutes = now.getMinutes();
        let ampm = hours >= 12 ? 'مساءً' : 'صباحاً';
        let displayHours = hours % 12; displayHours = displayHours ? displayHours : 12;
        let minutesStr = minutes < 10 ? '0' + minutes : minutes;
        let timeStr = displayHours + ':' + minutesStr + ' ' + ampm;
        let dateTimeFormatted = dateStr + ' | ' + timeStr;

        let text = "📝 *طلب جديد*\n📅 وقت الإرسال: *" + dateTimeFormatted + "*\n";
        if(orderNote !== "") { text += "⏱️ *ملاحظة: " + orderNote + "*\n"; }
        if(customerName !== "") { text += "👤 الزبون: *" + customerName + "*\n"; }
        text += (currentPriceMode === 'wholesale') ? "📊 التسعيرة: *سعر جملة*\n\n" : "📊 التسعيرة: *سعر تجزئة*\n\n";

        let total = 0; let index = 1; let globalSummary = {};
        for(let key in cart) {
            let item = cart[key];
            let activePrice = (currentPriceMode === 'wholesale') ? item.price_wholesale : item.price_retail;
            let unitName = item.unit || 'وحدة';
            if(!globalSummary[unitName]) globalSummary[unitName] = 0;
            globalSummary[unitName] += item.qty;

            if(!withPrice) {
                text += index + ". *" + item.qty + " " + unitName + "* ⬅️ " + item.name + "\n";
            } else {
                let itemTotal = item.qty * activePrice;
                text += index + ". *" + item.qty + " " + unitName + "* ⬅️ " + item.name + " _(" + itemTotal.toLocaleString() + " ر.ي)_\n";
                total += itemTotal;
            }
            index++;
        }

        let summaryParts = [];
        for(let u in globalSummary) { summaryParts.push(`${globalSummary[u]} ${u}`); }

        text += "\n═══════════════\n📦 *الملخص الإجمالي للطلب:*\n• إجمالي الكميات: " + summaryParts.join(' | ') + "\n";
        if(withPrice) { text += "═══════════════\n💰 *الإجمالي المطلوب: " + total.toLocaleString() + " ر.ي*\n"; }
        else { text += "═══════════════\n✅ *طلب كميات فقط*\n"; }

        let waUrl = "https://api.whatsapp.com/send?phone=" + phone + "&text=" + encodeURIComponent(text);
        window.open(waUrl, '_blank');
    }

    function filterCatalog() {
        let input = document.getElementById('searchInput').value.toLowerCase();
        let items = document.getElementsByClassName('catalog-item');
        let visibleCount = 0;
        for (let i = 0; i < items.length; i++) {
            let name = items[i].getAttribute('data-name');
            if (name.includes(input)) { items[i].style.display = ""; visibleCount++; }
            else { items[i].style.display = "none"; }
        }
        document.getElementById('noResults').style.display = (visibleCount === 0) ? "block" : "none";
    }

    function downloadCatalogImage(withPrice) {
        let customerName = document.getElementById('imgCustomerName').value.trim();
        if(!withPrice) document.body.classList.add('hide-price-mode');
        document.body.classList.add('capture-mode');

        let navMenu = document.querySelector('.floating-nav-container');
        if(navMenu) navMenu.style.display = 'none';

        var captureNode = document.getElementById("captureArea");

        htmlToImage.toPng(captureNode, { quality: 1.0, backgroundColor: '#ffffff', pixelRatio: 3 })
        .then(function (dataUrl) {
            document.body.classList.remove('capture-mode');
            if(!withPrice) document.body.classList.remove('hide-price-mode');
            if(navMenu) navMenu.style.display = 'flex';

            let link = document.createElement('a');
            let prefix = customerName !== '' ? 'طلب_' + customerName + '_' : 'طلب_أبوضياء_';
            link.download = prefix + (withPrice ? 'بالسعر.png' : 'كميات.png');
            link.href = dataUrl;
            link.click();
        })
        .catch(function (error) {
            alert('حدث خطأ أثناء حفظ الصورة.');
            document.body.classList.remove('capture-mode');
            if(!withPrice) document.body.classList.remove('hide-price-mode');
            if(navMenu) navMenu.style.display = 'flex';
        });
    }
</script>

<?php if(file_exists('nav_menu.php')) { include 'nav_menu.php'; } ?>
</body>
</html>
