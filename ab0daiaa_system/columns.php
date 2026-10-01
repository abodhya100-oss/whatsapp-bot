<?php ini_set("display_errors", 1); error_reporting(E_ALL); ?>
<?php
require_once 'db.php';
$result = $conn->query("SHOW COLUMNS FROM products");
echo "<h3>أعمدة جدول المنتجات (products):</h3><ul dir='ltr' style='text-align:left; font-size:18px;'>";
while ($row = $result->fetch_assoc()) {
    echo "<li><b>" . $row['Field'] . "</b> (النوع: " . $row['Type'] . ")</li>";
}
echo "</ul>";
?>

<?php include 'nav_bar.php'; ?>

<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
