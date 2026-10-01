<?php
$files = ['fast_invoice.php', 'pos_touch.php'];
foreach($files as $file) {
    if(file_exists($file)) {
        $content = file_get_contents($file);
        
        // تنظيف أي كود قديم
        $content = str_replace('<script>/*click_fix*/</script>', '', $content);
        
        // كود ذكي ينقر على زر الإضافة أو النموذج الداخلي في الكارت عند النقر عليه
        $js_code = '
        <script>/*click_fix*/
        document.addEventListener("DOMContentLoaded", function() {
            document.addEventListener("click", function(e) {
                let card = e.target.closest(".product-card, .item-box, .card, .product-item, div[onclick]");
                if(card) {
                    // إذا لم يضغط المستخدم على الزر مباشرة، نبحث عن زر الإضافة أو النموذج داخل الكارد ونفعله
                    let actionEl = card.querySelector("button, input[type=\'submit\'], a.btn, .add-btn, form");
                    if(actionEl && !e.target.closest("button, input, a, form")) {
                        e.preventDefault();
                        if(actionEl.tagName === "FORM") {
                            actionEl.submit();
                        } else {
                            actionEl.click();
                        }
                    }
                }
            });
        });
        </script>';
        
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>') !== false) {
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>", $content);
            file_put_contents($file, $content);
        }
    }
}
echo "✅ تم ربط النقر بالكارد بزر الإضافة الداخلي بنجاح تام!\n";

