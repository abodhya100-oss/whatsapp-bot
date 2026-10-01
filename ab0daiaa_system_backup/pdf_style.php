<?php
// pdf_style.php - التصميم الموحد والراقي لجميع التقارير والكشوفات - نظام أبوضياء العالمي
function get_pdf_header_html($title, $subtitle = "سيئون - السوق العام | ج: 771458609") {
    return '
    <div style="background: #1e3a8a; color: #fff; border-radius: 12px; overflow: hidden; margin-bottom: 12px; display: flex; box-shadow: 0 4px 15px rgba(30,58,138,0.3);">
        <div style="background: #172554; padding: 15px; width: 40%; text-align: center; border-left: 1px solid rgba(255,255,255,0.15);">
            <div style="font-size: 13px; font-weight: bold; margin-bottom: 2px;">نظام أبوضياء العالمي</div>
            <div style="font-size: 15px; font-weight: bold; color: #38bdf8;">' . $title . '</div>
        </div>
        <div style="padding: 15px; width: 60%; text-align: center;">
            <div style="font-size: 17px; font-weight: bold; margin-bottom: 2px;">محلات أبو ضياء</div>
            <div style="font-size: 10px; opacity: 0.95; margin-bottom: 2px;">جوالات إكسسوارات وصيانة شاملة</div>
            <div style="font-size: 10px; opacity: 0.9;">' . $subtitle . '</div>
        </div>
    </div>
    <div style="height: 4px; background: linear-gradient(90deg, #eab308, #f59e0b); width: 100%; border-radius: 2px; margin-bottom: 15px;"></div>
    ';
}

function get_pdf_footer_html() {
    return '
    <div style="text-align: center; font-size: 11px; color: #475569; border-top: 1px dashed #cbd5e1; padding-top: 10px; margin-top: 20px; font-weight: bold;">
        تصميم وتطوير نظام <b>أبو ضياء العالمي</b> ♕ أبو ضياء | واتساب: <span style="color: #16a34a;">771458609</span>
    </div>
    ';
}
?>
