<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <link rel="manifest" href="manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js");}</script>
    <meta charset="UTF-8">
    <title>فاتورة مبيعات - أبو ضياء</title>
    <style>
        body {
            font-family: Tahoma, Arial, sans-serif;
            background: #fff;
            color: #333;
            margin: 0;
            padding: 20px;
            direction: rtl;
        }
        .invoice-box {
            max-width: 800px;
            margin: auto;
            padding: 30px;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
            border-radius: 8px;
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #007bff;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .invoice-header h2 {
            margin: 0;
            color: #007bff;
        }
        .invoice-info, .client-info {
            margin-bottom: 20px;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th, table td {
            padding: 12px;
            text-align: right;
            border-bottom: 1px solid #ddd;
        }
        table th {
            background-color: #f8f9fa;
            color: #333;
        }
        .total-section {
            text-align: left;
            font-size: 18px;
            font-weight: bold;
            margin-top: 20px;
            border-top: 2px solid #333;
            padding-top: 10px;
        }
        .print-btn {
            display: block;
            width: 100%;
            padding: 12px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            text-align: center;
            margin-top: 20px;
        }
        .print-btn:hover {
            background-color: #218838;
        }
        /* إخفاء زر الطباعة عند الطباعة الفعليّة ورقة أو PDF */
        @media print {
            .print-btn {
                display: none;
            }
            .invoice-box {
                border: none;
                box-shadow: none;
                padding: 0;
            }
        }
    </style>
</head>
<body>

<div class="invoice-box">
    <div class="invoice-header">
        <div>
            <h2>نظام أبو ضياء المحاسبي</h2>
            <small>إدارة المبيعات والفواتير السريعة</small>
        </div>
        <div style="text-align: left;">
            <p><strong>رقم الفاتورة:</strong> #INV-1001</p>
            <p><strong>التاريخ:</strong> 2026-08-23</p>
        </div>
    </div>

    <div class="client-info">
        <p><strong>اسم العميل:</strong> محمد كرامة علي حيمد</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>الصنف / المنتج</th>
                <th>الكمية</th>
                <th>السعر</th>
                <th>الإجمالي</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>جامبو المكلا</td>
                <td>1</td>
                <td>4500 ريال</td>
                <td>4500 ريال</td>
            </tr>
        </tbody>
    </table>

    <div class="total-section">
        الإجمالي الكلي: 4500 ريال
    </div>

    <button class="print-btn" onclick="window.print()">🖨️ طباعة الفاتورة رسمياً</button>
</div>


<?php include 'nav_bar.php'; ?>
<?php include "nav_menu.php"; ?>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>
</html>

