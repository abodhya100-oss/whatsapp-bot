document.addEventListener("DOMContentLoaded", function() {
    // البحث عن جميع الصفوف أو الروابط في جداول العملاء وإلحاق معرف العميل الصحيح برابط كشف الحساب
    const rows = document.querySelectorAll('tr, .customer-row, li');
    rows.forEach(row => {
        const statementLink = row.querySelector('a[href="statement_new.php"]');
        if (statementLink) {
            // محاولة العثور على أي رابط آخر في نفس السطر يحمل معرف العميل (مثل سند القبض أو الفاتورة)
            const anyLinkWithId = row.querySelector('a[href*="id="], a[href*="customer_id="]');
            if (anyLinkWithId) {
                const href = anyLinkWithId.getAttribute('href');
                const matchId = href.match(/(?:customer_id|id)=(\d+)/);
                if (matchId && matchId[1]) {
                    statementLink.setAttribute('href', 'statement_new.php?customer_id=' + matchId[1]);
                }
            }
        }
    });
});