<?php require_once 'auth.php'; ?><!DOCTYPE html><html lang="ar" dir="rtl"><head>
    <link rel="manifest" href="manifest.json">
    <meta name='mobile-web-app-capable' content='yes'>
    <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js");}</script><meta charset="UTF-8"><title>مرتجع الموردين</title><style>body{font-family:Tahoma;background: #FFFFFF;color:#fff;padding:20px;text-align:center;}</style></head><body><h2>↩️ مرتجع الموردين</h2><p>قسم مرتجعات الموردين جاهز للربط.</p><a href="main.php" style="color:#ffd200;">🏠 العودة للرئيسية</a><?php include 'nav_bar.php'; ?>
    <div style="display: flex; gap: 5px;">
    </div>
    <div style="display: flex; gap: 4px; align-items: center;">
        <span style="color: #94a3b8; font-size: 10px; font-weight: bold;">تكبير الشاشة:</span>
    </div>
</div>
</div>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body></html>

