<?php
$id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);
if($id > 0) {
    header("Location: statement_pro.php?customer_id=" . $id);
    exit;
} else {
    // إذا لم يجد رقم عميل، يفتح صفحة الكشف مباشرة
    header("Location: statement_pro.php");
    exit;
}
?>