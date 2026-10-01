
// دالة تعديل كمية الصنف المباشرة المرتبطة بنظام الكاشير لديك
function openEditPrompt(id, currentQty) {
    let qty = prompt("أدخل الكمية الجديدة للصنف:", currentQty);
    if (qty !== null && !isNaN(qty) && qty.trim() !== "") {
        cartAction('update', id, parseFloat(qty));
    }
}
