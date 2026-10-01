<?php
$f = 'pos_touch.php';
$c = file_get_contents($f);

// 1. حذف حلقة PHP التي ترسم جميع الأصناف وتثقل الذاكرة
$c = preg_replace('/<\?php foreach\(\$inventory_items as \$item\): \?>.*?<\?php endforeach; \?>/s', '', $c);

// 2. حقن نظام الرسم الذكي (يعرض 60 صنف فقط لتسريع الأداء)
$js_new = "function renderGrid(query = '') {
    let html = ''; let count = 0; let q = query.toLowerCase();
    for(let i=0; i<itemsDatabase.length; i++){
        let item = itemsDatabase[i];
        if(q === '' || (item.item_name && item.item_name.toLowerCase().includes(q))){
            html += '<div class=\"item-card\" onclick=\"addToCartById(' + item.id + ')\"><div class=\"item-title\">' + item.item_name + '</div><div class=\"item-price\">' + Number(item.sell_price).toLocaleString() + '</div></div>';
            count++;
            if(count >= 60) break;
        }
    }
    document.getElementById('itemsGrid').innerHTML = html;
}
renderGrid();

function filterItems() { 
    renderGrid(document.getElementById('searchBox').value); 
}";

$c = preg_replace('/function filterItems\(\).*?function addToCartById/s', $js_new . "\n\n    function addToCartById", $c);

file_put_contents($f, $c);
echo "🚀 تم تسريع شاشة الكاشير لتصبح كالصاروخ بنجاح!\n";
?>

<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
