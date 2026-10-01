<?php ini_set("display_errors", 1); error_reporting(E_ALL); ?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <link rel="manifest" href="manifest.json">
    <meta name='mobile-web-app-capable' content='yes'>
    <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js");}</script>
    <meta charset="UTF-8">
    <style>
        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            font-family: 'Segoe UI', Tahoma, sans-serif;
            margin: 0;
            padding: 20px;
            color: white;
        }
        .container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            max-width: 1200px;
            margin: auto;
        }
        .card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 25px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            text-align: center;
            transition: 0.3s;
        }
        .card:hover { transform: translateY(-10px); background: rgba(255, 255, 255, 0.2); }
        h1 { text-align: center; margin-bottom: 40px; }
        h2 { font-size: 1.2rem; margin-bottom: 10px; }
        .number { font-size: 2.5rem; font-weight: bold; margin: 10px 0; }
    </style>
</head>
<body>


    <div class="container">
        <!-- كارت إجمالي الديون -->
        <div class="card">
            <h2>إجمالي مديونية العملاء</h2>
            <div class="number" id="total_debt">0.00</div>
            <p>ريال</p>
        </div>

        <!-- كارت عدد العملاء -->
        <div class="card">
            <h2>العملاء النشطون</h2>
            <div class="number" id="total_customers">0</div>
            <p>عميل</p>
        </div>

        <!-- كارت مبيعات اليوم -->
        <div class="card">
            <h2>مبيعات اليوم</h2>
            <div class="number" id="daily_sales">0.00</div>
            <p>ريال</p>
        </div>
    </div>

    <!-- كود جلب البيانات من قاعدة البيانات (سنقوم بربطه لاحقاً) -->
    <script>
        // هنا سنضيف لاحقاً كود الاتصال لجلب البيانات من الـ API
    </script>

<?php include 'nav_bar.php'; ?>
<?php include "nav_menu.php"; ?>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>
</html>


