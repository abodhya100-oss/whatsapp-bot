<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// دالة التحقق من صلاحيات المدير
function require_admin() {
    if (isset($_SESSION['role']) && $_SESSION['role'] !== 'admin') {
        die("<div style='text-align:center; margin-top:50px; font-family:tahoma;'><h3>عذراً، هذه الصفحة مخصصة للمسؤولين فقط.</h3><a href='index.php'>العودة للرئيسية</a></div>");
    }
}
?>
