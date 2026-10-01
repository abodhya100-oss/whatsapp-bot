<?php
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;                                         
$dbFile = '/data/data/com.termux/files/home/secure_dbs/vmax.db';
if(!file_exists($dbFile)) { $dbFile = 'database.db'; }                                      
$db = new SQLite3($dbFile);                   
$voucher = $db->querySingle("SELECT v.*, c.name, c.phone, c.debt FROM vouchers v LEFT JOIN customers c ON v.customer_id = c.id WHERE v.id = $id", true);                                
if(!$voucher) {                                   
    die("<h3 style='text-align:center; margin-top:50px; font-family:Tahoma;'>السند غير موجود</h3>");                                      
}                                                                                           

$is_receipt = $voucher['type'] == 'receipt';
$type_text = $is_receipt ? 'سند قبض' : 'سند صرف';                                           
$type_color = $is_receipt ? '#10b981' : '#ef4444';                                                                                        
$customer_name = $voucher['name'] ? $voucher['name'] : 'عميل نقدي عام';
$amount = number_format($voucher['amount'], 2);                                             
$notes = $voucher['notes'] ? $voucher['notes'] : 'دفعة من الحساب';
$date = $voucher['created_date'] ?? date('Y-m-d H:i:s');                                    
$remaining_debt = isset($voucher['debt']) ? number_format($voucher['debt'], 2) : '0.00';
?>                                            
<!DOCTYPE html>                               
<html lang="ar">
<head>                                            
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">    
    <title><?php echo $type_text; ?> - #<?php echo sprintf('%04d', $id); ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html-to-image/1.11.11/html-to-image.min.js"></script>                                 
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>                                           
        body { 
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif; 
            background-color: #f1f5f9; 
            margin: 0; 
            padding: 5px; 
            padding-bottom: 20px; 
            color: #1e293b; 
            display: flex;
            flex-direction: column;
            align-items: center;
            overflow-x: hidden;
        }                                                                               
        .center-wrapper {                                 
            display: flex;                                
            justify-content: center;
            width: 100%;
            box-sizing: border-box;
        }                                     
        #capture-area {                                   
            background: #FFFFFF;
            width: 100%;
            max-width: 680px; 
            border-radius: 6px;
            overflow: hidden;                             
            box-shadow: 0 3px 12px rgba(0,0,0,0.06);                                                    
            direction: rtl;                               
            text-align: right;                            
            box-sizing: border-box;
        }                                                                                           
        .action-btns {                                    
            display: flex; 
            justify-content: center; 
            gap: 8px; 
            width: 100%;
            max-width: 680px; 
            margin: 10px auto 0 auto; 
            direction: rtl; 
            flex-wrap: wrap;
            box-sizing: border-box;
        }
        .btn-action {                                     
            padding: 9px 12px; 
            border: none; 
            border-radius: 6px; 
            color: white; 
            font-weight: bold; 
            font-size: 12px; 
            cursor: pointer; 
            text-align: center; 
            text-decoration: none; 
            flex: 1; 
            min-width: 110px;
            display: flex; 
            justify-content: center; 
            align-items: center; 
            gap: 5px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);                                   
        }                                             
        .btn-pdf { background-color: #0284c7; }
        .btn-img { background-color: #d97706; }
        .btn-print { background-color: #475569; }                                                   
        .btn-whatsapp { background-color: #25D366; }                                                
        .btn-back { background-color: #e2e8f0; color: #334155; }                            
        
        @media print {                                    
            body { background: white; padding: 0; }
            .action-btns { display: none !important; }
            #capture-area { box-shadow: none; border-radius: 0; width: 680px; max-width: none; }
        }
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
    <div class="center-wrapper">                      
        <div id="capture-area">
            <table width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: #1a2b56; color: #ffffff;">
                <tr>                                              
                    <td style="padding: 12px 20px; text-align: right; vertical-align: middle; width: 60%;">
                        <h1 style="color: #fbbf24; font-size: 20px; margin: 0 0 3px 0; font-weight: 800;">مؤسسة أبو ضياء للتوزيع</h1>
                        <p style="margin: 0; font-size: 11px; color: #cbd5e1; line-height: 1.3;">سيئون - السوق العام | جوال: 771458609<br>للتواصل والطلبات المباشرة</p>
                    </td>
                    <td style="padding: 12px 20px; text-align: left; vertical-align: middle; width: 40%;" dir="rtl">                                              
                        <table border="0" cellpadding="5" cellspacing="0" style="background-color: #121e3d; border-radius: 6px; border: 1px solid #2e3b5e; text-align: right; color: white; font-size: 11px; width: 200px; float: left;">
                            <tr>
                                <td style="color: #94a3b8; border-bottom: 1px solid rgba(255,255,255,0.05); padding: 4px 8px;">نوع المستند:</td>
                                <td style="text-align: left; border-bottom: 1px solid rgba(255,255,255,0.05); padding: 4px 8px;"><span style="background: <?php echo $type_color; ?>; color: #fff; padding: 2px 8px; border-radius: 12px; font-size: 10px; font-weight: bold;">مُعتمد</span></td>
                            </tr>                                         
                            <tr>                                              
                                <td style="color: #94a3b8; border-bottom: 1px solid rgba(255,255,255,0.05); padding: 4px 8px;">رقم السند:</td>
                                <td dir="ltr" style="font-weight: bold; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.05); padding: 4px 8px;">#<?php echo sprintf('%04d', $id); ?></td>
                            </tr>
                            <tr>
                                <td style="color: #94a3b8; padding: 4px 8px;">التاريخ:</td>                                                   
                                <td dir="ltr" style="font-weight: bold; text-align: left; padding: 4px 8px;"><?php echo substr($date, 0, 10); ?></td>                                   
                            </tr>
                        </table>
                    </td>                                     
                </tr>
            </table>                          
            
            <table width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-bottom: 1px solid #e2e8f0;">
                <tr>
                    <td style="padding: 8px 20px; text-align: right; vertical-align: middle; width: 50%;">
                        <div style="background-color: <?php echo $type_color; ?>; color: white; padding: 4px 16px; border-radius: 16px; font-weight: bold; font-size: 13px; display: inline-block;"><?php echo $type_text; ?></div>
                    </td>                                         
                    <td style="padding: 8px 20px; text-align: left; vertical-align: middle; font-size: 12px; color: #64748b; width: 50%;" dir="rtl">
                        <div style="display: inline-block; text-align: right;">
                            لحساب العميل<br>                              
                            <strong style="color: #0f172a; font-size: 15px;"><?php echo htmlspecialchars($customer_name); ?></strong>                             
                        </div>
                    </td>                                     
                </tr>
            </table>

            <table width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: #ffffff;">
                <thead>                                           
                    <tr>                                              
                        <th style="background-color: #1e3a8a; color: #ffffff; padding: 8px 20px; text-align: right; font-size: 12px; width: 70%;">وصف البيان / التفاصيل</th>                                   
                        <th style="background-color: #1e3a8a; color: #ffffff; padding: 8px 20px; text-align: left; font-size: 12px; width: 30%;">المبلغ (الإجمالي)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>                                              
                        <td style="padding: 12px 20px; border-bottom: 1px solid #f1f5f9; color: #1e293b; font-size: 13px; font-weight: 600; text-align: right; vertical-align: top;"><?php echo htmlspecialchars($notes); ?></td>
                        <td style="padding: 12px 20px; border-bottom: 1px solid #f1f5f9; color: #0f172a; font-size: 15px; font-weight: 800; text-align: left; vertical-align: top;" dir="ltr"><?php echo $amount; ?></td>                                 
                    </tr>
                </tbody>
            </table>                          
            
            <table width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: #ffffff;">
                <tr>                                              
                    <td style="padding: 12px 20px 15px; text-align: right; vertical-align: top; width: 50%;">
                        <div style="font-size: 11px; color: #64748b; line-height: 1.5;">                                  
                            <h4 style="color: #0f172a; font-size: 12px; margin: 0 0 3px 0; font-weight: bold;">📌 ملاحظات هامة</h4>                                  
                            - تم تسجيل هذه الحركة المالية في حساب العميل المذكور أعلاه.<br>                             
                            - يرجى الاحتفاظ بالسند للمطابقة والمراجعة والضمان.<br>
                            - نسعد دائماً بخدمتكم في مؤسسة أبو ضياء.<br>
                            <br>                                          
                            <span style="color:#94a3b8; font-size: 10px;">إدارة وتطوير: أبو ضياء | جوال: 771458609</span>
                        </div>                                    
                    </td>
                    <td style="padding: 12px 20px 15px; text-align: left; vertical-align: top; width: 50%;" dir="rtl">
                        <table width="100%" border="0" cellpadding="6" cellspacing="0" style="text-align: right; font-size: 12px;">                                  
                            <tr>
                                <td style="background-color: #f8fafc; color: #475569; font-weight: bold; border-bottom: 1px solid #e2e8f0; border-radius: 0 4px 4px 0; width: 50%; padding: 6px 10px;">إجمالي السند:</td>
                                <td style="background-color: #f8fafc; color: #475569; font-weight: bold; border-bottom: 1px solid #e2e8f0; text-align: left; border-radius: 4px 0 0 0; width: 50%; padding: 6px 10px;" dir="ltr"><?php echo $amount; ?></td>
                            </tr>
                            <tr><td colspan="2" style="height: 4px; padding: 0;"></td></tr>
                            <tr>
                                <td style="background-color: #eff6ff; color: #1e3a8a; font-weight: bold; font-size: 14px; border-bottom: 2px solid #bfdbfe; border-radius: 0 4px 4px 0; padding: 6px 10px;">صافي <?php echo $type_text; ?>:</td>                                         
                                <td style="background-color: #eff6ff; color: #1e3a8a; font-weight: bold; font-size: 14px; border-bottom: 2px solid #bfdbfe; text-align: left; border-radius: 4px 0 0 0; padding: 6px 10px;" dir="ltr"><?php echo $amount; ?> ر.ي</td>                                                              
                            </tr>
                            <?php if($voucher['customer_id'] > 1): ?>
                            <tr><td colspan="2" style="height: 4px; padding: 0;"></td></tr>
                            <tr>                                              
                                <td style="background-color: #f0fdf4; color: #166534; font-weight: bold; font-size: 13px; border-bottom: 2px solid #bbf7d0; border-radius: 0 4px 4px 0; padding: 6px 10px;">الرصيد المتبقي:</td>
                                <td style="background-color: #f0fdf4; color: #166534; font-weight: bold; font-size: 13px; border-bottom: 2px solid #bbf7d0; text-align: left; border-radius: 4px 0 0 0; padding: 6px 10px;" dir="ltr"><?php echo $remaining_debt; ?> ر.ي</td>
                            </tr>                                         
                            <?php endif; ?>
                        </table>                                  
                    </td>
                </tr>
            </table>
        </div>
    </div>
                                                  
    <!-- الأزرار التفاعلية -->
    <div class="action-btns">                         
        <button class="btn-action btn-pdf" onclick="generatePDF(false)">📄 تنزيل PDF</button>
        <button class="btn-action btn-img" onclick="saveAsImage()">🖼️ حفظ كصورة</button>
        <button class="btn-action btn-print" onclick="window.print()">🖨️ طباعة</button>
        <button class="btn-action btn-whatsapp" onclick="shareViaWhatsApp()">                           
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/></svg>                                 
            مشاركة واتساب
        </button>
        <a href="payments.php" class="btn-action btn-back">🔙 عودة</a>
    </div>                                    
    <script>                                          
        const captureOptions = {
            backgroundColor: '#ffffff',                   
            pixelRatio: 2,
            style: {
                transform: 'none',                            
                margin: '0',
                boxShadow: 'none',                            
                borderRadius: '0'
            }                                         };

        async function saveAsImage() {
            const element = document.getElementById('capture-area');
            try {
                const dataUrl = await htmlToImage.toPng(element, captureOptions);
                const link = document.createElement('a');                                                   
                link.download = 'سند_<?php echo addslashes($customer_name); ?>_رقم_<?php echo $id; ?>.png';
                link.href = dataUrl;
                link.click();
            } catch (error) {
                console.error('Error:', error);
                alert('حدث خطأ أثناء حفظ الصورة');
            }
        }

        async function generatePDF(returnFile = false) {
            const element = document.getElementById('capture-area');
            try {
                const dataUrl = await htmlToImage.toPng(element, captureOptions);
                return new Promise((resolve) => {
                    const img = new Image();
                    img.onload = () => {
                        const { jsPDF } = window.jspdf;
                        
                        const imgWidth = img.width; 
                        const imgHeight = img.height;
                        
                        const pdf = new jsPDF({
                            orientation: imgHeight > imgWidth ? 'p' : 'l',
                            unit: 'px',
                            format: [imgWidth, imgHeight]
                        });
                        
                        pdf.addImage(dataUrl, 'PNG', 0, 0, imgWidth, imgHeight);

                        if (returnFile) {                                 
                            resolve(pdf.output('blob'));                                                            
                        } else {
                            pdf.save('سند_<?php echo addslashes($customer_name); ?>_رقم_<?php echo $id; ?>.pdf');
                            resolve();
                        }                                         
                    };
                    img.src = dataUrl;
                });
            } catch (error) {
                console.error('Error:', error);                                                             
                alert('حدث خطأ أثناء إنشاء ملف PDF');                                                   
            }
        }                                     

        async function shareViaWhatsApp() {
            try {                                             
                const pdfBlob = await generatePDF(true);                                                    
                const file = new File([pdfBlob], "سند_<?php echo addslashes($customer_name); ?>.pdf", { type: "application/pdf" });
                const shareText = `مرفق <?php echo $type_text; ?> من مؤسسة أبو ضياء للتوزيع.\nالعميل: <?php echo addslashes($customer_name); ?>\nالمبلغ: <?php echo $amount; ?> ريال`;  
                if (navigator.canShare && navigator.canShare({ files: [file] })) {
                    await navigator.share({
                        files: [file],
                        title: 'سند من مؤسسة أبو ضياء',
                        text: shareText
                    });
                } else {
                    alert('متصفحك لا يدعم المشاركة المباشرة. سيتم تنزيل السند لإرساله يدوياً.');
                    generatePDF(false);
                }
            } catch (error) {
                console.log('تم إلغاء المشاركة أو حدث خطأ:', error);
            }
        }
    </script>
</body>
</html>
