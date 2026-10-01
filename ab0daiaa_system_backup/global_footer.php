<div style="margin: 40px auto 20px auto; padding: 15px; text-align: center; font-family: Tahoma, sans-serif; border-top: 2px dashed #38bdf8; background: #1e293b; border-radius: 8px; width: 92%; max-width: 450px; direction: ltr; box-sizing: border-box;">
    <div style="color: #f8fafc; font-size: 11px; font-weight: bold; letter-spacing: 1px;">© 2026 Abu Dhia. All Rights Reserved.</div>
    <div style="color: #38bdf8; font-family: monospace; font-size: 10px; margin-top: 5px; font-weight: bold;">SECURE SYSTEM • ID: 771458609</div>
</div>


<!-- النظام الشامل لوضع النهار/الليل وحفظ حالة العملاء والخيارات (مضاف برمجياً وبأمان) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. تطبيق وضع النهار أو الليل المحفوظ مسبقاً في كل الصفحات
    const savedTheme = localStorage.getItem('system_theme_mode');
    if(savedTheme === 'light') {
        document.body.style.background = '#f8fafc';
        document.body.style.color = '#0f172a';
    }

    // 2. التقاط وتمرير اسم العميل في أي رابط أو زر انتقال للفواتير في جميع الصفحات
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('a, button');
        if(btn) {
            var text = btn.innerText || btn.value || '';
            if(text.includes('فاتورة') || text.includes('فاتوره') || (btn.href && (btn.href.includes('index') || btn.href.includes('pos') || btn.href.includes('invoice')))) {
                var row = btn.closest('tr, .card, .customer-item, div');
                if(row) {
                    var nameEl = row.querySelector('strong, h3, h4, .name, td, span');
                    if(nameEl) {
                        var cName = nameEl.innerText.trim();
                        localStorage.setItem('active_invoice_customer', cName);
                        if(btn.tagName === 'A' && btn.href && !btn.href.includes('client=')) {
                            btn.href += (btn.href.includes('?') ? '&' : '?') + 'client=' + encodeURIComponent(cName);
                        }
                    }
                }
            }
        }
    });

    // 3. التعبئة التلقائية للعميل في صفحات الفواتير أو البيع السريع
    setTimeout(function() {
        let clientName = localStorage.getItem('active_invoice_customer');
        if(clientName) {
            var custSelect = document.getElementById('customerId') || document.querySelector('select[name="customer_id"]');
            if(custSelect) {
                for(var i = 0; i < custSelect.options.length; i++) {
                    if(custSelect.options[i].text.includes(clientName)) {
                        custSelect.selectedIndex = i;
                        custSelect.dispatchEvent(new Event('change'));
                        if(typeof showCustomerDetails === 'function') {
                            showCustomerDetails();
                        }
                        break;
                    }
                }
            }
            // نحتفظ بالاسم أو نمرره حسب الحاجة
        }
    }, 400);
});
</script>

<!-- محرك الوضع الشامل (نهار / ليل) لجميع صفحات النظام (مضاف برمجياً وبأمان) -->
<style>
/* إعدادات وضع النهار الديناميكي الشامل */
body.light-theme {
    background-color: #f8fafc !important;
    color: #0f172a !important;
}
body.light-theme .card-box, 
body.light-theme table th, 
body.light-theme table td, 
body.light-theme .nav-bar,
body.light-theme input, 
body.light-theme select, 
body.light-theme textarea {
    background-color: #ffffff !important;
    color: #0f172a !important;
    border-color: #cbd5e1 !important;
}
body.light-theme .header, 
body.light-theme span, 
body.light-theme div {
    color: inherit;
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // تطبيق الثيم المحفوظ مسبقاً فور تحميل أي صفحة
    const currentTheme = localStorage.getItem('app_theme_mode');
    if(currentTheme === 'light') {
        document.body.classList.add('light-theme');
    } else {
        document.body.classList.remove('light-theme');
    }

    // دالة عامة لتبديل الثيم وحفظه لكل النظام
    window.toggleAppTheme = function() {
        if(document.body.classList.contains('light-theme')) {
            document.body.classList.remove('light-theme');
            localStorage.setItem('app_theme_mode', 'dark');
        } else {
            document.body.classList.add('light-theme');
            localStorage.setItem('app_theme_mode', 'light');
        }
    };
});
</script>

<!-- شريط التنقل القابل للإخفاء والإظهار عبر سهم (مضاف برمجياً وبأمان) -->
<style>
/* تصميم زر السهم العائم فوق الشريط السفلي */
#navToggleBtn {
    position: fixed;
    bottom: 68px;
    left: 50%;
    transform: translateX(-50%);
    background: #1e293b;
    color: #38bdf8;
    border: 1px solid #334155;
    border-radius: 50% 50% 0 0;
    width: 36px;
    height: 22px;
    font-size: 14px;
    cursor: pointer;
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 -3px 10px rgba(0,0,0,0.3);
    transition: 0.3s ease;
}
/* حالة إخفاء الشريط السفلي */
.nav-bar.hidden-nav {
    transform: translateY(100px);
    transition: transform 0.3s ease;
}
.nav-bar {
    transition: transform 0.3s ease;
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // التأكد من عدم تكرار الزر إذا كان موجوداً مسبقاً
    if(!document.getElementById('navToggleBtn')) {
        var navBar = document.querySelector('.nav-bar');
        if(navBar) {
            var toggleBtn = document.createElement('button');
            toggleBtn.id = 'navToggleBtn';
            toggleBtn.innerHTML = '▼';
            toggleBtn.title = 'إخفاء / إظهار الشريط';
            
            // إدراج السهم فوق الشريط السفلي مباشرة
            navBar.parentNode.insertBefore(toggleBtn, navBar);
            
            toggleBtn.addEventListener('click', function() {
                navBar.classList.toggle('hidden-nav');
                if(navBar.classList.contains('hidden-nav')) {
                    toggleBtn.innerHTML = '▲';
                    toggleBtn.style.bottom = '5px';
                } else {
                    toggleBtn.innerHTML = '▼';
                    toggleBtn.style.bottom = '68px';
                }
            });
        }
    }
});
</script>