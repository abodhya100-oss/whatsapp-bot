<?php
$files = ['fast_invoice.php', 'pos_touch.php'];
foreach($files as $file) {
    if(file_exists($file)) {
        $content = file_get_contents($file);
        
        // إزالة أي زوائد قديمة
        $content = str_replace('<script>/*click_fix*/</script>', '', $content);
        
        // كود الجافاسكريبت الآمن لجعل كل كروت الأصناف قابلة للضغط وإضافة الصنف مباشرة
        $js_code = '
        <script>/*click_fix*/
        document.addEventListener("DOMContentLoaded", function() {
            document.body.addEventListener("click", function(e) {
                let card = e.target.closest(".product-card, .item-box, [data-id], .card, .product-item");
                if(card) {
                    let id = card.getAttribute("data-id") || card.getAttribute("data-product-id");
                    if(!id) {
                        let btn = card.querySelector("button, input, a");
                        if(btn) {
                            id = btn.getAttribute("data-id") || btn.getAttribute("value");
                        }
                    }
                    if(id) {
                        window.location.href = "fast_invoice.php?add_id=" + id;
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
echo "✅ تم اصلاح تفاعلية الأصناف بنجاح تام!\n";

