<?php
$id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0);
if ($id > 0) {
    header("Location: statement_new.php?customer_id=" . $id);
    exit;
} else {
    header("Location: statement_new.php");
    exit;
}
?>
