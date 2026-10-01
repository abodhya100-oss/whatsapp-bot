<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_role'])) { header("Location: login.php"); exit; }
$db = new SQLite3('vmax.db');

@$db->exec("ALTER TABLE inventory ADD COLUMN wholesale_price REAL DEFAULT 0;");

$settings = [];
$s_res = $db->query("SELECT key, value FROM settings");
if ($s_res) {
    while ($s_row = $s_res->fetchArray(SQLITE3_ASSOC)) {
        $settings[$s_row['key']] = $s_row['value'];
    }
}
$store_name = $settings['store_name'] ?? 'أبو ضياء';
$store_tagline = $settings['store_tagline'] ?? 'جوالات إكسسوارات وصيانة شاملة';
$store_address = $settings['store_address'] ?? 'سيئون - السوق العام';

$items = [];
$res = $db->query("SELECT * FROM inventory ORDER BY item_name ASC");
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
    <title>قائمة الأسعار والمنتجات - <?php echo htmlspecialchars($store_name); ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        body { font-family: Tahoma, sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 10px; padding-bottom: 90px; direction: rtl; }
        .header { text-align: center; font-size: 15px; font-weight: 900; color: #38bdf8; margin-bottom: 12px; text-shadow: 0 0 10px rgba(56,189,248,0.5); }
        .glass-panel { background: linear-gradient(135deg, rgba(30, 41, 59, 0.95), rgba(15, 23, 42, 0.98)); backdrop-filter: blur(20px); border: 1.5px solid rgba(56, 189, 248, 0.4); border-radius: 18px; padding: 15px; margin-bottom: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.6); }
        .btn-action { background: linear-gradient(135deg, #16a34a, #15803d); color: #fff; border: none; padding: 11px; border-radius: 12px; font-weight: bold; width: 100%; cursor: pointer; font-size: 12px; box-shadow: 0 4px 15px rgba(22,163,74,0.4); margin-bottom: 8px; }
        .btn-toggle { background: linear-gradient(135deg, #0284c7, #0369a1); color: #fff; border: none; padding: 10px; border-radius: 12px; font-weight: bold; width: 100%; cursor: pointer; font-size: 11px; margin-bottom: 8px; }
        
        .catalog-container { width: 100%; max-width: 440px; margin: 0 auto; display: flex; flex-direction: column; gap: 10px; }
        .catalog-card {
            background: #ffffff; color: #0f172a; width: 100%; max-width: 100%;
            border-radius: 16px; box-shadow: 0 25px 50px rgba(0,0,0,0.9);
            font-family: Tahoma, sans-serif; direction: rtl; padding: 18px; box-sizing: border-box;
        }
        .catalog-banner {
            background: #1d4ed8; color: #ffffff; padding: 14px 18px;
            display: flex; justify-content: space-between; align-items: center;
            border-radius: 12px 12px 0 0; margin: -18px -18px 12px -18px;
        }
        .catalog-stripe { background: #eab308; height: 4px; width: calc(100% + 36px); margin: 0 -18px 12px -18px; }
        .table-img { width: 36px; height: 36px; object-fit: cover; border-radius: 6px; border: 1px solid #cbd5e1; display: inline-block; }
        .wholesale-col { display: none; }
    </style>
</head>
<body>
    <div class="header">📋 قائمة الأسعار والمنتجات</div>

    <div class="glass-panel">
        <button onclick="toggleWholesale()" id="toggleBtn" class="btn-toggle">👁️ عرض سعر الجملة (مخفي حالياً)</button>
        <button onclick="shareCatalogPDF()" class="btn-action">💬 إرسال القائمة PDF للواتساب</button>
        <button onclick="window.print()" style="background:#475569; color:#fff; border:none; padding:10px; border-radius:10px; font-weight:bold; width:100%; cursor:pointer; font-size:11px;">🖨️ طباعة القائمة مباشرة</button>
    </div>

    <!-- عرض الكتالوج -->
    <div class="catalog-container">
        <div class="catalog-card" id="catalogCard" translate="no">
            <div class="catalog-banner">
                <div>
                    <div style="font-size: 16px; font-weight: 900;"><?php echo htmlspecialchars($store_name); ?></div>
                    <div style="font-size: 10px; opacity: 0.9; margin-top: 2px;"><?php echo htmlspecialchars($store_tagline); ?></div>
                    <div style="font-size: 9px; opacity: 0.8;"><?php echo htmlspecialchars($store_address); ?> | جوال: <?php echo htmlspecialchars($store_phone); ?></div>
                </div>
                <div style="text-align: left;">
                    <div style="font-size: 14px; font-weight: bold;">قائمة الأسعار</div>
                    <div style="font-size: 10px; opacity: 0.9;"><?php echo date('Y-m-d'); ?></div>
                </div>
            </div>
            <div class="catalog-stripe"></div>

            <table style="width: 100%; border-collapse: collapse; font-size: 11px; margin-bottom: 10px; text-align: center;">
                <thead>
                    <tr style="background: #1e293b; color: #ffffff;">
                        <th style="padding: 6px; width: 40px; border: 1px solid #334155;">الصورة</th>
                        <th style="padding: 6px; text-align: right; border: 1px solid #334155;">اسم الصنف / المنتج</th>
                        <th style="padding: 6px; width: 80px; border: 1px solid #334155;">سعر البيع</th>
                        <th style="padding: 6px; width: 80px; border: 1px solid #334155; background: #0284c7;" class="wholesale-col">سعر الجملة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($items)): ?>
                    <tr><td colspan="4" style="padding: 10px; text-align: center; color: #64748b;">لا توجد أصناف متاحة حالياً.</td></tr>
                    <?php else: foreach($items as $it): 
                        $retail_price = $it['sell_price'];
                        $wholesale_price = ($it['wholesale_price'] > 0) ? $it['wholesale_price'] : round($retail_price * 0.90, 0);
                    ?>
                    <tr>
                        <td style="padding: 4px; border: 1px solid #cbd5e1; text-align: center; vertical-align: middle;">
                            <?php if(!empty($it['image']) && strpos($it['image'], 'uploads/') === 0 && file_exists($it['image'])): ?>
                                <img src="<?php echo htmlspecialchars($it['image']); ?>" class="table-img">
                            <?php else: ?>
                                <span style="font-size: 18px;"><?php echo !empty($it['image']) ? $it['image'] : '📦'; ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 6px; text-align: right; border: 1px solid #cbd5e1; vertical-align: middle;"><b style="font-size: 11px;"><?php echo htmlspecialchars($it['item_name']); ?></b></td>
                        <td style="padding: 6px; border: 1px solid #cbd5e1; direction: ltr; font-weight: bold; color: #059669; font-size: 11px; vertical-align: middle;"><?php echo number_format($retail_price, 0); ?> ر.ي</td>
                        <td style="padding: 6px; border: 1px solid #cbd5e1; direction: ltr; font-weight: bold; color: #1d4ed8; font-size: 11px; vertical-align: middle; background: #f0f9ff;" class="wholesale-col"><?php echo number_format($wholesale_price, 0); ?> ر.ي</td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>

            <div style="text-align: center; font-size: 8px; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 6px; background: #f8fafc; border-radius: 6px;">
            </div>
        </div>
    </div>

    <script>
    let showWholesale = false;
    function toggleWholesale() {
        showWholesale = !showWholesale;
        let cols = document.getElementsByClassName('wholesale-col');
        let btn = document.getElementById('toggleBtn');
        for (let c of cols) {
            c.style.display = showWholesale ? 'table-cell' : 'none';
        }
        btn.innerHTML = showWholesale ? '🙈 إخفاء سعر الجملة (مفعل حالياً)' : '👁️ عرض سعر الجملة (مخفي حالياً)';
        btn.style.background = showWholesale ? 'linear-gradient(135deg, #1d4ed8, #0284c7)' : 'linear-gradient(135deg, #0284c7, #0369a1)';
    }

    function shareCatalogPDF() {
        const element = document.getElementById('catalogCard');
        const opt = {
            margin:       [5, 5, 5, 5],
            filename:     'Price_List.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 3, useCORS: true, letterRendering: true },
            jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait', compress: true }
        };

        html2pdf().from(element).set(opt).outputPdf('blob').then(function(pdfBlob) {
            const file = new File([pdfBlob], "Price_List.pdf", { type: "application/pdf" });
            if (navigator.canShare && navigator.canShare({ files: [file] })) {
                navigator.share({
                    files: [file],
                    title: 'قائمة الأسعار - <?php echo htmlspecialchars($store_name); ?>',
                    text: 'مرفق لكم قائمة الأسعار المعتمدة'
                }).catch((error) => console.log('المشاركة أُلغيت', error));
            } else {
                html2pdf().from(element).set(opt).save();
                alert("⚠️ تم تنزيل ملف الـ PDF الواضح والجاهز على جهازك.");
            }
        });
    }
    </script>

    <?php include "nav_menu.php"; ?>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>
</html>

