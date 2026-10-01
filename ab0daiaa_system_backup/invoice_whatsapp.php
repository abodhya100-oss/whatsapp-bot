<?php
// invoice_whatsapp.php - أقصى درجة غمقان وعرض ممكنة (Max Black & Bold) - V-MAX PRO
require_once 'auth.php';
$db_file = '/data/data/com.termux/files/home/secure_dbs/vmax.db';
$db = new SQLite3($db_file);

$invoice_id = intval($_GET['id'] ?? 0);
$auto_wa = trim($_GET['auto_wa'] ?? '');

if ($invoice_id <= 0) { die("رقم الفاتورة غير صالح"); }

$inv_stmt =$db->prepare("SELECT i.*, c.name as customer_name, c.phone as customer_phone FROM invoices i LEFT JOIN customers c ON i.customer_id = c.id WHERE i.id = :id");
$inv_stmt->bindValue(':id',$invoice_id, SQLITE3_INTEGER);
$inv_res =$inv_stmt->execute();
$invoice =$inv_res->fetchArray(SQLITE3_ASSOC);
if (!$invoice) { die("الفاتورة غير موجودة"); }

$invoice_date = $invoice['created_at'] ?? ($invoice['date'] ?? date('Y-m-d'));
$customer_name = !empty($invoice['customer_name']) ?$invoice['customer_name'] : 'عميل نقدي عام';
$customer_phone = !empty($auto_wa) ?$auto_wa : (!empty($invoice['customer_phone']) ? preg_replace('/[^0-9]/', '', $invoice['customer_phone']) : '');

$items_stmt =$db->prepare("SELECT ii.*, p.name as product_name FROM invoice_items ii LEFT JOIN products p ON ii.product_id = p.id WHERE ii.invoice_id = :id");
$items_stmt->bindValue(':id',$invoice_id, SQLITE3_INTEGER);
$items_res =$items_stmt->execute();
$items = [];$subtotal_sum = 0;
while ($row =$items_res->fetchArray(SQLITE3_ASSOC)) {
    $items[] =$row;
    $subtotal_sum += ($row['qty'] *$row['price']);
}
$db->close();

$grand_total = floatval($invoice['grand_total']);$paid_amount = floatval($invoice['paid_amount'] ?? 0);$remaining = max(0, $grand_total -$paid_amount);

$wa_lines = "🧾 *مؤسسة أبو ضياء للتوزيع*\n" .
            "التوزيع والتوكيل التجاري العام - سيئون\n" .
            "━━━━━━━━━━━━━━━\n" .
            "📄 *فاتورة مبيعات رقم:* #" . str_pad($invoice['id'], 4, '0', STR_PAD_LEFT) . "\n" .
            "👤 *العميل:* " . $customer_name . "\n" .
            "📅 *التاريخ:* " . $invoice_date . "\n" .
            "━━━━━━━━━━━━━━━\n" .
            "*الأصناف المباعة:*";
foreach ($items as $index =>$item) {
    $num =$index + 1;
    $p_total =$item['qty'] * $item['price'];$wa_lines .= "\n{$num}. " . $item['product_name'] . " (" . ($item['unit'] ?? 'شدة') . ") \vert{} الكمية: {$item['qty']} | السعر: {$item['price']} \vert{} الإجمالي: {$p_total}";
}
$wa_lines .= "\n━━━━━━━━━━━━━━━\n" .
             "💎 *إجمالي الأصناف:* " . number_format($subtotal_sum, 2) . " ر.ي\n" .
             "💰 *صافي الفاتورة:* *" . number_format($grand_total, 2) . " ر.ي*\n" .
             "💵 *المدفوع الآن:* " . number_format($paid_amount, 2) . " ر.ي\n" .
             "📌 *المتبقي (أجل):* " . number_format($remaining, 2) . " ر.ي\n" .
             "━━━━━━━━━━━━━━━\n" .
             "📌 *ملاحظة:* البضاعة المباعة لا ترد ولا تستبدل إلا بوجود عيب مصنعي.\n" .
             "🙏 شكراً لتعاملكم معنا في مؤسسة أبو ضياء 🌿";
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>                                        
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>فاتورة #<?php echo $invoice['id']; ?> - V-MAX PRO</title>
    <style>
        * { box-sizing: border-box; font-family: 'Tahoma', sans-serif; }
        body { background: #E2E8F0; margin: 0; padding: 10px; color: #000; }

        .scroll-wrapper { width: 100%; overflow-x: auto; padding-bottom: 15px; }
        .invoice-card { width: 576px; min-width: 576px; background: #FFFFFF; padding: 10px 15px; margin: 0 auto; }

        .invoice-header { border-bottom: 3px solid #000; padding-bottom: 12px; text-align: center; margin-bottom: 15px; }
        .invoice-header h2 { margin: 0 0 5px 0; font-size: 30px; font-weight: 900; color: #000; }
        .invoice-header p { margin: 4px 0; font-size: 18px; font-weight: bold; color: #000; }

        .customer-box { border: 3px solid #000; padding: 10px; margin-bottom: 15px; text-align: center; font-size: 24px; font-weight: bold; color: #000; }

        .invoice-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 22px; font-weight: bold; color: #000; }  
        .invoice-table th { background: #000; padding: 10px; text-align: center; color: #FFF; border: 2px solid #000; }                   
        .invoice-table td { padding: 10px; border: 2px solid #000; text-align: center; color: #000; font-weight: 900; }

        .summary-box { border: 3px solid #000; margin-bottom: 15px; color: #000; }          
        .summary-row { display: flex; justify-content: space-between; padding: 10px 12px; font-size: 22px; font-weight: bold; border-bottom: 2px solid #000; }                          
        .summary-row:last-child { border-bottom: none; }                                    
        .row-grand { font-size: 26px; font-weight: 900; border-top: 4px solid #000; padding-top: 12px; }                                  

        .notes-box { border: 2px solid #000; padding: 10px; margin-bottom: 15px; font-size: 18px; font-weight: bold; text-align: center; color: #000; }                                 

        .footer-brand { color: #000; text-align: center; padding: 10px; font-size: 16px; font-weight: bold; border-top: 2px solid #000; margin-top: 10px; }

        .actions-panel { background: #1E293B; border-radius: 10px; padding: 12px; margin: 15px auto; max-width: 576px; }                  
        .actions-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 10px; }
        .btn { padding: 12px 6px; border-radius: 8px; font-weight: bold; font-size: 14px; border: none; cursor: pointer; text-align: center; text-decoration: none; color: #FFFFFF; }
        .btn-edit { background: #475569; }
        .btn-new-sale { background: #7C3AED; }
        .btn-whatsapp { background: #10B981; grid-column: span 3; font-size: 16px; padding: 14px; }
        .btn-thermer { grid-column: span 3; background: #0284C7; padding: 18px; font-size: 18px; font-weight: bold; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.4); border: 2px solid #38BDF8; }
    </style>
</head>
<body>                                        

<div class="scroll-wrapper">
    <div class="invoice-card" id="invoiceCard">
        <div class="invoice-header">
            <h2>مؤسسة أبو ضياء للتوزيع</h2>
            <p>التوزيع والتوكيل التجاري العام</p>
            <p>سيئون - السوق العام | جوال: 771458609</p>
            <p>التاريخ: <?php echo $invoice_date; ?> | فاتورة: #<?php echo str_pad($invoice['id'], 4, '0', STR_PAD_LEFT); ?></p>
        </div>
        <div class="customer-box">            
            العميل: <?php echo htmlspecialchars($customer_name); ?> <?php echo !empty($invoice['customer_phone']) ? '('.$invoice['customer_phone'].')' : ''; ?>
        </div>                                                                              
        <table class="invoice-table">
            <thead><tr><th style="width: 35%;">الصنف</th><th style="width: 15%;">الوحدة</th><th style="width: 15%;">الكمية</th><th style="width: 15%;">السعر</th><th style="width: 20%;">الإجمالي</th></tr></thead>
            <tbody>                           
                <?php foreach($items as$item): ?>
                <tr>
                    <td style="text-align: right;"><?php echo htmlspecialchars($item['product_name']); ?></td>
                    <td><?php echo htmlspecialchars($item['unit'] ?? 'شدة'); ?></td>        
                    <td><?php echo $item['qty']; ?></td>
                    <td><?php echo number_format($item['price'], 2); ?></td>                
                    <td><?php echo number_format($item['qty'] *$item['price'], 2); ?></td>  
                </tr>                         
                <?php endforeach; ?>
            </tbody>
        </table>                              
        <div class="summary-box">             
            <div class="summary-row"><span>إجمالي الأصناف:</span><span><?php echo number_format($subtotal_sum, 2); ?> ر.ي</span></div>    
            <div class="summary-row row-grand"><span>صافي الفاتورة:</span><span><?php echo number_format($grand_total, 2); ?> ر.ي</span></div>
            <div class="summary-row"><span>المدفوع الآن:</span><span><?php echo number_format($paid_amount, 2); ?> ر.ي</span></div>       
            <div class="summary-row"><span>المتبقي (أجل):</span><span><?php echo number_format($remaining, 2); ?> ر.ي</span></div>
        </div>
        <div class="notes-box">البضاعة المباعة لا ترد ولا تستبدل إلا بوجود عيب مصنعي.<br>يرجى الاحتفاظ بالفاتورة.</div>                                                                 
        <div class="footer-brand">V-MAX PRO | تصميم وتطوير أبو ضياء</div>                   
    </div>
</div>

<div class="actions-panel" id="actionsPanel"> 
    <div class="actions-row">
        <!-- الزر السحري الجديد للغمقان الأقصى -->
        <button id="thermerBtn" onclick="generateHDInvoice()" class="btn btn-thermer">🖨️ طباعة الفاتورة (أغمق درجة - Ultra Black)</button>
    </div>
    <div class="actions-row">
        <a href="formal_invoice.php?edit_id=<?php echo $invoice['id']; ?>" class="btn btn-edit">✏️ تعديل الفاتورة</a>                      
        <button onclick="window.location.href='formal_invoice.php'" class="btn btn-new-sale">⚡ بيع جديد</button>
        <a href="invoices_list.php" class="btn" style="background: #059669; text-decoration: none;">📊 سجل الفواتير</a>
    </div>
    <div class="actions-row">
        <button onclick="sendWhatsApp()" class="btn btn-whatsapp">💬 إرسال عبر واتساب</button>
    </div>
</div>

<?php if(file_exists('nav_menu.php')) { include 'nav_menu.php'; } ?>

<script>
function generateHDInvoice() {
    let btn = document.getElementById('thermerBtn');
    let originalText = btn.innerText;
    btn.innerText = "⏳ جاري التشبع بالحبر (Ultra Black)...";

    const items = <?php echo json_encode($items, JSON_UNESCAPED_UNICODE); ?>;
    const invDate = "<?php echo addslashes($invoice_date); ?>";
    const invId = "<?php echo str_pad($invoice['id'], 4, '0', STR_PAD_LEFT); ?>";
    const cName = "<?php echo addslashes($customer_name . (!empty($invoice['customer_phone']) ? ' ('.$invoice['customer_phone'].')' : '')); ?>";
    const subTotal = "<?php echo number_format($subtotal_sum, 2); ?>";
    const gTotal = "<?php echo number_format($grand_total, 2); ?>";
    const pAmount = "<?php echo number_format($paid_amount, 2); ?>";
    const rAmount = "<?php echo number_format($remaining, 2); ?>";

    const rowHeight = 70;
    const headerHeight = 70;
    const tableStartY = 350;
    const tableHeight = headerHeight + (items.length * rowHeight);
    const summaryStartY = tableStartY + tableHeight + 30;
    const canvasHeight = summaryStartY + 320;

    const scale = 2;
    const canvas = document.createElement('canvas');
    canvas.width = 576 * scale;
    canvas.height = canvasHeight * scale;
    const ctx = canvas.getContext('2d', { willReadFrequently: true });

    ctx.scale(scale, scale);

    // خلفية بيضاء نقية
    ctx.fillStyle = "#ffffff";
    ctx.fillRect(0, 0, 576, canvasHeight);

    ctx.fillStyle = "#000000";
    ctx.direction = "rtl";
    ctx.textBaseline = "middle";
    const setFont = (size, weight = '900') => { ctx.font = `${weight} ${size}px Tahoma, Arial`; };

    // تقنية طباعة النص 3 مرات متداخلة بمسافة محسوبة لزيادة السماكة والغمقان بدون تداخل مزعج
    const drawText = (txt, x, y, maxW = undefined) => {
        if (maxW) {
            ctx.fillText(txt, x, y, maxW);
            ctx.fillText(txt, x + 1, y, maxW);     // تظليل يمين
            ctx.fillText(txt, x, y + 1, maxW);     // تظليل أسفل
        } else {
            ctx.fillText(txt, x, y);
            ctx.fillText(txt, x + 1, y);
            ctx.fillText(txt, x, y + 1);
        }
    };

    // 1. رسم الترويسة
    ctx.textAlign = "center";
    setFont(38, '900'); drawText("مؤسسة أبو ضياء للتوزيع", 288, 55);
    setFont(26, '900'); drawText("التوزيع والتوكيل التجاري العام", 288, 105);
    setFont(24, '900'); drawText("سيئون - السوق العام | جوال: 771458609", 288, 145);
    setFont(24, '900'); drawText(`التاريخ: ${invDate} | فاتورة: #${invId}`, 288, 185);

    ctx.lineWidth = 4;
    ctx.beginPath(); ctx.moveTo(20, 215); ctx.lineTo(556, 215); ctx.stroke();

    // 2. مربع العميل
    ctx.lineWidth = 4;
    ctx.strokeRect(20, 235, 536, 75);
    setFont(28, '900'); drawText(`العميل: ${cName}`, 288, 272);

    // 3. رأس الجدول
    ctx.fillRect(20, tableStartY, 536, headerHeight);
    ctx.fillStyle = "#ffffff";
    setFont(26, '900');

    const colX = [462, 329, 249, 169, 74];
    const colLines = [369, 289, 209, 129];

    ctx.textAlign = "right";  ctx.fillText("الصنف", 546, tableStartY + 35);
    ctx.textAlign = "center"; ctx.fillText("الوحدة", colX[1], tableStartY + 35);
                              ctx.fillText("الكمية", colX[2], tableStartY + 35);
                              ctx.fillText("السعر", colX[3], tableStartY + 35);
                              ctx.fillText("الإجمالي", colX[4], tableStartY + 35);

    // 4. صفوف الأصناف
    ctx.fillStyle = "#000000";
    setFont(26, '900');
    let currentY = tableStartY + headerHeight;

    for (let i = 0; i < items.length; i++) {
        let item = items[i];
        let y = currentY + (rowHeight / 2);

        ctx.textAlign = "right";  drawText(item.product_name, 546, y, 175);
        ctx.textAlign = "center"; drawText(item.unit || 'شدة', colX[1], y, 75);
                                  drawText(item.qty, colX[2], y, 75);
                                  drawText(parseFloat(item.price).toFixed(2), colX[3], y, 75);
                                  drawText((item.qty * item.price).toFixed(2), colX[4], y, 110);

        currentY += rowHeight;
        ctx.beginPath(); ctx.moveTo(20, currentY); ctx.lineTo(556, currentY); ctx.stroke();
    }

    ctx.strokeRect(20, tableStartY, 536, tableHeight);
    for (let lx of colLines) {
        ctx.beginPath(); ctx.moveTo(lx, tableStartY); ctx.lineTo(lx, tableStartY + tableHeight); ctx.stroke();
    }

    // 5. مربع الحسابات
    ctx.strokeRect(20, summaryStartY, 536, 200);
    let r1 = summaryStartY + 40, r2 = summaryStartY + 95, r3 = summaryStartY + 145, r4 = summaryStartY + 185;

    ctx.textAlign = "right";
    setFont(26, '900'); drawText("إجمالي الأصناف:", 540, r1);
    setFont(32, '900'); drawText("صافي الفاتورة:", 540, r2);
    setFont(26, '900'); drawText("المدفوع الآن:", 540, r3);
                        drawText("المتبقي (أجل):", 540, r4);

    ctx.textAlign = "left";
    setFont(26, '900'); drawText(`${subTotal} ر.ي`, 30, r1);
    setFont(32, '900'); drawText(`${gTotal} ر.ي`, 30, r2);
    setFont(26, '900'); drawText(`${pAmount} ر.ي`, 30, r3);
                        drawText(`${rAmount} ر.ي`, 30, r4);

    ctx.lineWidth = 2; ctx.beginPath(); ctx.moveTo(20, summaryStartY + 65); ctx.lineTo(556, summaryStartY + 65); ctx.stroke();
    ctx.lineWidth = 5; ctx.beginPath(); ctx.moveTo(20, summaryStartY + 115); ctx.lineTo(556, summaryStartY + 115); ctx.stroke();

    // 6. الملاحظات والتذييل
    let ny = summaryStartY + 240;
    ctx.textAlign = "center";
    ctx.lineWidth = 3;
    setFont(22, '900'); drawText("البضاعة المباعة لا ترد ولا تستبدل إلا بوجود عيب مصنعي.", 288, ny);
                        drawText("يرجى الاحتفاظ بالفاتورة للمراجعة والضمان.", 288, ny + 30);

    ctx.beginPath(); ctx.moveTo(100, ny + 60); ctx.lineTo(476, ny + 60); ctx.stroke();
    setFont(18, '900'); drawText("V-MAX PRO | تصميم وتطوير أبو ضياء", 288, ny + 90);

    // فلتر قاتل الرمادي (تحويل أي بكسل غير ناصع البياض إلى أسود فاحم 100%)
    const imgData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    const data = imgData.data;
    for (let i = 0; i < data.length; i += 4) {
        let avg = (data[i] + data[i+1] + data[i+2]) / 3;
        let color = avg < 220 ? 0 : 255; // عتبة عالية جداً لضمان عدم بقاء أي لون رمادي باهت
        data[i] = color;
        data[i+1] = color;
        data[i+2] = color;
    }
    ctx.putImageData(imgData, 0, 0);

    // التحويل والمشاركة المباشرة
    canvas.toBlob(function(blob) {
        btn.innerText = originalText;
        let file = new File([blob], "invoice.png", { type: "image/png" });
        if (navigator.canShare && navigator.canShare({ files: [file] })) {
            navigator.share({
                title: 'طباعة الفاتورة Ultra Black',
                files: [file]
            }).catch((error) => console.log('تم الإلغاء أو خطأ:', error));
        } else {
            let link = document.createElement('a');
            link.download = 'Invoice_#<?php echo $invoice['id']; ?>.png';
            link.href = URL.createObjectURL(blob);
            link.click();
        }
    }, 'image/png', 1.0);
}

function sendWhatsApp() {                     
    let phone = "<?php echo $customer_phone; ?>";
    let text = `<?php echo addslashes($wa_lines); ?>`;
    let url = "";
    if (phone) {
        url = "https://api.whatsapp.com/send?phone=" + phone + "&text=" + encodeURIComponent(text);                                       
    } else {
        url = "https://api.whatsapp.com/send?text=" + encodeURIComponent(text);             
    }
    window.open(url, '_blank');
}
</script>
</body>
</html>
