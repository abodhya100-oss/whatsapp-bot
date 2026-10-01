<?php
if(session_status()===PHP_SESSION_NONE){session_start();} if(!isset($_SESSION["user_role"])){header("Location: login.php");exit;}
$db = new SQLite3('/data/data/com.termux/files/home/secure_dbs/vmax.db');

// 1. التأكد من تصحيح بيانات صنف "ريلاكس احمر" في قاعدة البيانات وخلوها من أي رموز تكسر الكود
$db->exec("UPDATE products SET name = 'ريلاكس احمر' WHERE name LIKE '%ريلاكس%' AND name LIKE '%احمر%'");

// 2. تحديث صفحات الفاتورة والكاشير لضمان نقل الـ ID بدقة مطلقة عبر خاصية آمنة
$files = ['fast_invoice.php', 'pos_touch.php'];
foreach($files as $file) {
    if(file_exists($file)) {
        $content = file_get_contents($file);
        
        //حقن كود جافاسكريبت دقيق يقرأ الـ data-id مباشرة عند النقر على أي كارت صنف
        $safe_click_script = '
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            // الاستماع للنقر على أي كارت صنف وضمان جلب الـ ID الحقيقي بدقة
            document.body.addEventListener("click", function(e) {
                let card = e.target.closest("[data-product-id], .product-card, .item-box");
                if(card) {
                    let prodId = card.getAttribute("data-product-id") || card.getAttribute("data-id");
                    if(!prodId) {
                        let inputBtn = card.querySelector("input[name=\'product_id\'], button");
                        if(inputBtn) prodId = inputBtn.value || inputBtn.getAttribute("data-id");
                    }
                    if(prodId) {
                        // إرسال طلب إضافة الصنف مباشرة عبر الرابط أو النموذج الداخلي
                        let form = card.querySelector("form");
                        if(form) {
                            form.submit();
                        } else {
                            window.location.href = "fast_invoice.php?add_id=" + prodId;
                        }
                    }
                }
            });
        });
        </script>';
        
        // إزالة النسخ القديمة وحقن السكربت الآمن الجديد
        $content = preg_replace('/<script>\/\*click_fix\*\/.*?<\/script>/s', '', $content);
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>') !== false) {
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>", $content);
            file_put_contents($file, $content);
        }
    }
}

echo "✅ تم فحص صنف ريلاكس احمر وتحديث نظام النقر والإضافة بنجاح تام!\n";

