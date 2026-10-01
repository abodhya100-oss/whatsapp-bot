<?php
session_unset();
session_destroy();
//header("Location: login.php");
exit;
?>

<?php include 'nav_bar.php'; ?>

<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
