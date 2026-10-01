<?php
// قالب التصميم الجذري الخيالي - V-MAX PRO
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><meta name='mobile-web-app-capable' content='yes'>
<meta name='apple-mobile-web-app-capable' content='yes'>

    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title><?php echo $page_title ?? 'V-MAX PRO - النظام الفاخر'; ?></title>
    <style>
        :root {
            --bg-deep: #030712;
            --glass-bg: rgba(15, 23, 42, 0.75);
            --glass-border: rgba(56, 189, 248, 0.25);
            --neon-glow: 0 0 20px rgba(56, 189, 248, 0.15);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --accent-cyan: #38bdf8;
            --accent-green: #10b981;
            --accent-gold: #f59e0b;
        }
        body {
            font-family: Tahoma, sans-serif;
            background-color: var(--bg-deep);
            background-image: 
                radial-gradient(at 0% 0%, rgba(30, 58, 138, 0.3) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(15, 23, 42, 0.8) 0px, transparent 50%);
            color: var(--text-main);
            margin: 0;
            padding: 14px;
            padding-bottom: 150px;
            min-height: 100vh;
            box-sizing: border-box;
        }
        .main-container {
            max-width: 600px;
            margin: 0 auto;
        }
        .app-header {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.9), rgba(15, 23, 42, 0.95));
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 16px;
            text-align: center;
            font-size: 17px;
            font-weight: 900;
            color: var(--accent-cyan);
            margin-bottom: 16px;
            box-shadow: var(--neon-glow);
            letter-spacing: 0.5px;
        }
        .card {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 18px;
            margin-bottom: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }
        .form-control {
            width: 100%;
            padding: 13px 15px;
            background: rgba(3, 7, 18, 0.85);
            color: #fff;
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            box-sizing: border-box;
            margin-bottom: 12px;
            font-size: 13px;
            outline: none;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: var(--accent-cyan);
            box-shadow: 0 0 15px rgba(56, 189, 248, 0.3);
        }
        .btn-main {
            background: linear-gradient(135deg, #0284c7, #0369a1);
            color: #fff;
            border: none;
            padding: 14px;
            border-radius: 12px;
            font-weight: bold;
            width: 100%;
            font-size: 14px;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(2, 132, 199, 0.4);
            transition: all 0.2s ease;
        }
        .btn-main:active { transform: scale(0.98); }
        
        /* شريط التنقل السفلي الفاخر والمتباعد بانتظام تام */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(3, 7, 18, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-top: 1px solid var(--glass-border);
            display: flex;
            justify-content: space-around;
            align-items: center;
            padding: 8px 5px;
            z-index: 9999;
            box-shadow: 0 -10px 30px rgba(0, 0, 0, 0.8);
        }
        .nav-item {
            text-decoration: none;
            color: var(--text-muted);
            font-size: 10px;
            text-align: center;
            flex: 1;
            padding: 6px 2px;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-weight: bold;
        }
        .nav-item span {
            font-size: 18px;
            display: block;
            margin-bottom: 3px;
        }
        .nav-item:hover, .nav-item.active {
            color: var(--accent-cyan);
            background: rgba(56, 189, 248, 0.1);
            text-shadow: 0 0 10px rgba(56, 189, 248, 0.6);
        }
        
        .table-container {
            width: 100%;
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid var(--glass-border);
            margin-top: 10px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            white-space: nowrap;
        }
        .table th, .table td {
            padding: 11px;
            text-align: center;
        }
        .table th {
            background: rgba(15, 23, 42, 0.95);
            color: var(--accent-cyan);
            font-weight: bold;
            border-bottom: 1px solid var(--glass-border);
        }
        .table td {
            border-bottom: 1px solid rgba(30, 41, 59, 0.6);
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
<div class="main-container">


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