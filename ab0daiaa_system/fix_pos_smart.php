<?php
$files = ['pos_touch.php', 'fast_invoice.php'];
foreach($files as $file) {
    if(file_exists($file)) {
        $content = file_get_contents($file);
        
        $handler = '<?php
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if(session_status()===PHP_SESSION_NONE){session_start();} if(!isset($_SESSION["user_role"])){header("Location: login.php");exit;}
$db = new SQLite3(\'vmax.db\');
        if (isset($_GET[\'add_id\']) || isset($_GET[\'add_name\'])) {
            $aid = intval($_GET[\'add_id\'] ?? 0);
            $pName = trim($_GET[\'add_name\'] ?? \'\');
            
            if ($aid > 0) {
                $st = $db->prepare("SELECT * FROM products WHERE id = :id");
                $st->bindValue(\':id\', $aid, SQLITE3_INTEGER);
                $res = $st->execute()->fetchArray(SQLITE3_ASSOC);
            } elseif (!empty($pName)) {
                $st = $db->prepare("SELECT * FROM products WHERE name LIKE :name");
                $st->bindValue(\':name\', "%" . $pName . "%", SQLITE3_TEXT);
                $res = $st->execute()->fetchArray(SQLITE3_ASSOC);
            }
            
            if (isset($res) && $res) {
                $id = $res[\'id\'];
                $pr = isset($res[\'sell_price\']) ? $res[\'sell_price\'] : 0;
                if (!isset($_SESSION[\'cart\'])) $_SESSION[\'cart\'] = [];
                if (isset($_SESSION[\'cart\'][$id])) {
                    $_SESSION[\'cart\'][$id][\'qty\'] += 1;
                } else {
                    $_SESSION[\'cart\'][$id] = [\'id\' => $id, \'name\' => $res[\'name\'], \'price\' => $pr, \'qty\' => 1];
                }
            }
            header("Location: " . basename(__FILE__));
            exit;
        }
        ?>';
        
        // تنظيف أي معالج قديم وحقن المعالج الذكي الجديد في الأعلى
        $content = preg_replace('/<\?php\s*if \(session_status\(\).*?exit;\s*\}\s*\}\s*\?>/s', '', $content);
        if(stripos($content, '<?php') !== false) {
            $content = preg_replace('/<\?php/', $handler, $content, 1);
        } else {
            $content = $handler . $content;
        }
        
        // حقن كود تفاعلي يجعل أي كارت منتج قابل للضغط وينقل اسم المنتج للمعالج
        $js_click = '
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll(".product-card, .item-box, div[style*=\'cursor: pointer\'], div[class*=\'product\']").forEach(card => {
                if(!card.getAttribute("data-fixed-click")) {
                    card.setAttribute("data-fixed-click", "1");
                    card.style.cursor = "pointer";
                    card.addEventListener("click", function() {
                        let nameEl = card.querySelector(".product-name, h3, h4, span");
                        let text = nameEl ? nameEl.innerText.trim() : card.innerText.split("\n")[0].trim();
                        if(text) {
                            window.location.href = "?add_name=" + encodeURIComponent(text);
                        }
                    });
                }
            });
        });
        </script>';
        
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>') !== false) {
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>", $content);
        }
        
        file_put_contents($file, $content);
        echo "✅ تم تحديث ومعالجة الملف: $file بنجاح تام!\n";
    }
}

