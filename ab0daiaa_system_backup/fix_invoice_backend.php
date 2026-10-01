<?php
// تعديل صفحة الفاتورة السريعة لتستقبل الـ add_id وتضيفه تلقائياً
$file = 'fast_invoice.php';
if(file_exists($file)) {
    $content = file_get_contents($file);
    
    // إضافة كود معالجة إضافة الصنف في أول الملف إن لم يكن موجوداً
    $backend_handler = '
    <?php
    // معالجة إضافة الصنف مباشرة عبر الـ URL
    if (isset($_GET[\'add_id\'])) {
        $add_id = intval($_GET[\'add_id\']);
        if ($add_id > 0) {
            // التحقق من وجود جلسة للسلة أو إنشاء واحدة
            if (!isset($_SESSION[\'cart\'])) { $_SESSION[\'cart\'] = []; }
            if (isset($_SESSION[\'cart\'][$add_id])) {
                $_SESSION[\'cart\'][$add_id][\'qty\'] += 1;
            } else {
                $db_conn = new SQLite3(\'vmax.db\');
                $stmt = $db_conn->prepare("SELECT * FROM products WHERE id = :id");
                $stmt->bindValue(\':id\', $add_id, SQLITE3_INTEGER);
                $res = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
                if ($res) {
                    $_SESSION[\'cart\'][$add_id] = [
                        \'id\' => $res[\'id\'],
                        \'name\' => $res[\'name\'],
                        \'price\' => $res[\'sell_price\'],
                        \'qty\' => 1
                    ];
                }
            }
            header("Location: fast_invoice.php");
            exit;
        }
    }
    ?>';
    
    // وضع المعالج في بداية الملف بعد الـ opening php tag إن وجد أو في أول السطر
    if (stripos($content, '<?php') !== false) {
        $content = preg_replace('/<\?php/', "<?php\n" . $backend_handler, $content, 1);
    } else {
        $content = $backend_handler . $content;
    }
    
    file_put_contents($file, $content);
}

// تحديث طريقة عرض كروت الأصناف لتكون قابلة للنقر المباشر وتتوجه للرابط
$files_to_update = ['fast_invoice.php', 'pos_touch.php'];
foreach($files_to_update as $f) {
    if(file_exists($f)) {
        $c = file_get_contents($f);
        // استبدال أي كارت صنف ليصبح رابطاً مباشراً يضيف الصنف
        $click_script = '
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            let cards = document.querySelectorAll(".product-card, .item-box, .card");
            cards.forEach(card => {
                let id = card.getAttribute("data-id") || card.getAttribute("data-product-id");
                if(!id) {
                    let btn = card.querySelector("button, input, a");
                    if(btn) id = btn.getAttribute("data-id") || btn.value;
                }
                if(id && !card.getAttribute("data-fixed")) {
                    card.setAttribute("data-fixed", "true");
                    card.style.cursor = "pointer";
                    card.addEventListener("click", function(e) {
                        if(!e.target.closest("form, button, input")) {
                            window.location.href = "fast_invoice.php?add_id=" + id;
                        }
                    });
                }
            });
        });
        </script>';
        
        if(stripos($c, '<?php include "nav_menu.php"; ?>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>') !== false) {
            $c = str_ireplace('<?php include "nav_menu.php"; ?>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>', $click_script . "\n<?php include "nav_menu.php"; ?>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>", $c);
            file_put_contents($f, $c);
        }
    }
}

echo "✅ تم ضبط النظام ومعالجة الإضافة البرمجية للأصناف بنجاح تام!\n";

