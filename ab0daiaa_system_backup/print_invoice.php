<?php include 'nav_bar.php'; ?>
<?php include "nav_menu.php"; ?>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
<!-- auto_fill_client_universal -->

<!-- استقبال وتعبئة اسم العميل تلقائياً (مضاف برمجياً وبأمان تام) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        const urlParams = new URLSearchParams(window.location.search);
        const clientParam = urlParams.get('client');
        
        if(clientParam) {
            // البحث عن أي حقل مخصص للعميل في صفحة الفاتورة وتعبئته فوراً
            var custInputs = document.querySelectorAll('input[name*="customer"], select[name*="customer"], #customer, .customer-input, input[name*="client"], select[name*="client"]');
            custInputs.forEach(function(inp) {
                if(inp.tagName === 'SELECT') {
                    for(var i = 0; i < inp.options.length; i++) {
                        if(inp.options[i].text.includes(clientParam)) {
                            inp.selectedIndex = i;
                            inp.dispatchEvent(new Event('change'));
                            break;
                        }
                    }
                } else {
                    inp.value = decodeURIComponent(clientParam);
                    inp.dispatchEvent(new Event('input'));
                }
            });
        }
    }, 600);
});
</script>
</body>
</html>

