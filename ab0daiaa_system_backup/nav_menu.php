<?php
// nav_menu.php - الشريط الجانبي الفخم المتحرك من اليسار لليمين
?>
<style>
#vmaxToggleBtn {
    position: fixed !important;
    bottom: 20px !important;
    left: 20px !important;
    width: 50px !important;
    height: 50px !important;
    background: linear-gradient(135deg, #06b6d4, #2563eb) !important;
    color: #ffffff !important;
    border: 2px solid rgba(255, 255, 255, 0.4) !important;
    border-radius: 50% !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 24px !important;
    cursor: pointer !important;
    z-index: 2147483647 !important;
    box-shadow: 0 10px 30px rgba(6, 182, 212, 0.6), inset 0 2px 4px rgba(255, 255, 255, 0.5) !important;
    transition: all 0.4s cubic-bezier(0.68, -0.55, 0.27, 1.55) !important;
}
#vmaxToggleBtn:hover { transform: scale(1.12) rotate(90deg) !important; }

#vmaxSidebar {
    position: fixed !important;
    top: 50% !important;
    left: -290px !important;
    transform: translateY(-50%) !important;
    width: 85px !important;
    max-height: 90vh !important;
    background: rgba(15, 23, 42, 0.96) !important;
    backdrop-filter: blur(25px) !important;
    -webkit-backdrop-filter: blur(25px) !important;
    border: 1px solid rgba(255, 255, 255, 0.2) !important;
    border-radius: 0 30px 30px 0 !important;
    display: flex !important;
    flex-direction: column !important;
    justify-content: space-around !important;
    align-items: center !important;
    z-index: 2147483646 !important;
    box-shadow: 20px 0 60px rgba(0, 0, 0, 0.7), inset 2px 0 0 rgba(255, 255, 255, 0.2) !important;
    padding: 20px 6px !important;
    transition: left 0.5s cubic-bezier(0.77, 0, 0.175, 1) !important;
}
#vmaxSidebar.show-sidebar { left: 0 !important; }

.vmax-nav-item {
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    justify-content: center !important;
    text-decoration: none !important;
    color: rgba(255, 255, 255, 0.8) !important;
    font-size: 10px !important;
    font-weight: 600 !important;
    width: 100% !important;
    padding: 12px 0 !important;
    margin: 4px 0 !important;
    border-radius: 16px !important;
    transition: all 0.3s ease !important;
}
.vmax-nav-item:hover {
    color: #ffffff !important;
    background: rgba(6, 182, 212, 0.3) !important;
    transform: translateX(6px) scale(1.06) !important;
    border-right: 4px solid #06b6d4 !important;
}
.vmax-nav-icon { font-size: 22px !important; margin-bottom: 4px !important; }
.vmax-nav-label { font-family: 'Tahoma', sans-serif !important; font-size: 9.5px !important; }

#vmaxOverlay {
    position: fixed !important; top: 0 !important; left: 0 !important;
    width: 100vw !important; height: 100vh !important;
    background: rgba(3, 7, 18, 0.65) !important; backdrop-filter: blur(5px) !important;
    z-index: 2147483645 !important; opacity: 0 !important; visibility: hidden !important;
    transition: all 0.4s ease !important;
}
#vmaxOverlay.show-overlay { opacity: 1 !important; visibility: visible !important; }

body.light-mode #vmaxSidebar {
    background: rgba(255, 255, 255, 0.96) !important;
    border: 1px solid rgba(0, 0, 0, 0.12) !important;
}
body.light-mode .vmax-nav-item { color: rgba(15, 23, 42, 0.65) !important; }
body.light-mode .vmax-nav-item:hover { color: #0284c7 !important; background: rgba(2, 132, 199, 0.12) !important; }
</style>

<div id="vmaxOverlay" onclick="toggleVmaxSidebar()"></div>
<div id="vmaxToggleBtn" onclick="toggleVmaxSidebar()" title="إظهار القائمة">
    <span id="vmaxToggleIcon">🡲</span>
</div>
<div id="vmaxSidebar">
    <a href="index.php" class="vmax-nav-item"><span class="vmax-nav-icon">🏠</span><span class="vmax-nav-label">الرئيسية</span></a>
    <a href="formal_invoice.php" class="vmax-nav-item"><span class="vmax-nav-icon">🧾</span><span class="vmax-nav-label">الفاتورة</span></a>
    <a href="customers.php" class="vmax-nav-item"><span class="vmax-nav-icon">👥</span><span class="vmax-nav-label">العملاء</span></a>
    <a href="catalog.php" class="vmax-nav-item"><span class="vmax-nav-icon">📦</span><span class="vmax-nav-label">الكتالوج</span></a>
    <a href="reports.php" class="vmax-nav-item"><span class="vmax-nav-icon">📊</span><span class="vmax-nav-label">التقارير</span></a>
    <a href="settings.php" class="vmax-nav-item"><span class="vmax-nav-icon">⚙️</span><span class="vmax-nav-label">الإعدادات</span></a>
</div>

<script>
function toggleVmaxSidebar() {
    let sidebar = document.getElementById('vmaxSidebar');
    let overlay = document.getElementById('vmaxOverlay');
    let icon = document.getElementById('vmaxToggleIcon');
    let btn = document.getElementById('vmaxToggleBtn');
    sidebar.classList.toggle('show-sidebar');
    overlay.classList.toggle('show-overlay');
    if(sidebar.classList.contains('show-sidebar')) {
        icon.innerHTML = "🡰"; btn.style.left = "300px";
    } else {
        icon.innerHTML = "🡲"; btn.style.left = "20px";
    }
}
</script>
