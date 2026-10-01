<?php ini_set("display_errors", 1); error_reporting(E_ALL); ?>
<?php
// إخبار المتصفح والبوت أن الرد سيكون نصاً عادياً (جاهز للواتساب)
header('Content-Type: text/plain; charset=utf-8');
require 'db.php';

if (isset($_GET['phone'])) {
    // سحب الأرقام فقط وتجاهل أي رموز، ثم أخذ آخر 9 أرقام ليتطابق مع رقم العميل اليمني
    $raw_phone = preg_replace('/[^0-9]/', '', $_GET['phone']);
    $phone = substr($raw_phone, -9); 

    $phone = $conn->real_escape_string($phone);
    $sql = "SELECT name, balance FROM customers WHERE phone LIKE '%$phone'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $customer = $result->fetch_assoc();
        // تصميم الرسالة التي ستصل للعميل
        echo "👤 العميل: " . $customer['name'] . "\n";
        echo "💰 إجمالي المديونية: " . number_format($customer['balance'], 2) . " ريال\n\n";
        echo "شكراً لتعاملك معنا!";
    } else {
        echo "عذراً، لم يتم العثور على حساب مسجل بهذا الرقم في نظامنا. يرجى مراجعة الإدارة.";
    }
} else {
    echo "عذراً، حدث خطأ في قراءة رقم الهاتف.";
}
?>


<?php include 'nav_bar.php'; ?>

<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
