<?php
$f = 'pos_touch.php';
$c = file_get_contents($f);

$old_js = "const opt = {
            margin: [2,0,2,0],
            filename: 'Invoice_<?php echo \$success_invoice['id']; ?>.pdf',
            jsPDF: {unit: 'mm', format: [80, 160]}
        };";

$new_js = "const opt = {
            margin: [2,0,2,0],
            filename: 'Invoice_' + '<?php echo \$success_invoice['id']; ?>' + '.pdf',
            image: { type: 'jpeg', quality: 0.95 },
            html2canvas: { scale: 1, useCORS: true, logging: false },
            jsPDF: { unit: 'mm', format: [80, 160] }
        };";

$c = str_replace($old_js, $new_js, $c);
file_put_contents($f, $c);
echo "✅ تم تطبيق وضع السرعة على ملف الـ PDF بنجاح!\n";
?>

<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
