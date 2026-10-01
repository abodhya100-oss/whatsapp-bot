<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$db = new SQLite3('vmax.db', SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
$db->busyTimeout(5000);

$settings = [];
$s_res = @$db->query("SELECT key, value FROM settings");
if ($s_res) { while ($s_row = $s_res->fetchArray(SQLITE3_ASSOC)) { $settings[$s_row['key']] = $s_row['value']; } }

$store_name = $settings['store_name'] ?? 'محل هوت فون';
$store_tagline = $settings['store_tagline'] ?? 'جوالات إكسسوارات وصيانة شاملة';
$store_address = $settings['store_address'] ?? 'سيئون - السوق العام';

// جلب المنتجات
$products = [];
$p_res = $db->query("SELECT * FROM inventory ORDER BY item_name ASC");
while ($row = $p_res->fetchArray(SQLITE3_ASSOC)) {
    $products[] = $row;
}

// جلب العملاء مع أرقام هواتفهم للواتساب
$customers = [];
$c_res = $db->query("SELECT * FROM customers ORDER BY name ASC");
while ($row = $c_res->fetchArray(SQLITE3_ASSOC)) {
    $customers[] = $row;
}

$success_invoice = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    $customer_id = intval($_POST['customer_id'] ?? 0);
    $discount = floatval($_POST['discount'] ?? 0);
    $items_json = $_POST['cart_items'] ?? '[]';
    $cart = json_decode($items_json, true);

    $customer_name = 'عميل نقدي';
    $customer_phone = '';
    if ($customer_id > 0) {
        $c_info = $db->querySingle("SELECT name, phone FROM customers WHERE id = $customer_id", true);
        if ($c_info) { 
            $customer_name = $c_info['name']; 
            $customer_phone = $c_info['phone'] ?? '';
        }
    }

    if (!empty($cart)) {
        $total_amount = 0;
        $total_cost = 0;
        foreach ($cart as $item) {
            $total_amount += ($item['price'] * $item['qty']);
            $total_cost += ($item['cost'] * $item['qty']);
        }
        $net_amount = $total_amount - $discount;
        $date = date('Y-m-d H:i:s');
        $user_name = $_SESSION['username'] ?? 'مدير النظام';

        $db->exec("BEGIN TRANSACTION;");
        
        $stmt = $db->prepare("INSERT INTO invoices (customer_id, customer_name, total_amount, total_cost, discount, net_amount, date, user_name) VALUES (:cid, :cname, :tot, :cost, :disc, :net, :date, :user)");
        $stmt->bindValue(':cid', $customer_id, SQLITE3_INTEGER);
        $stmt->bindValue(':cname', $customer_name, SQLITE3_TEXT);
        $stmt->bindValue(':tot', $total_amount, SQLITE3_FLOAT);
        $stmt->bindValue(':cost', $total_cost, SQLITE3_FLOAT);
        $stmt->bindValue(':disc', $discount, SQLITE3_FLOAT);
        $stmt->bindValue(':net', $net_amount, SQLITE3_FLOAT);
        $stmt->bindValue(':date', $date, SQLITE3_TEXT);
        $stmt->bindValue(':user', $user_name, SQLITE3_TEXT);
        $stmt->execute();
        $invoice_id = $db->lastInsertRowID();

        foreach ($cart as $item) {
            $pid = intval($item['id']);
            $qty = intval($item['qty']);
            $db->exec("UPDATE inventory SET stock = stock - $qty WHERE id = $pid");
        }

        if ($customer_id > 0) {
            $db->exec("UPDATE customers SET balance = balance + $net_amount WHERE id = $customer_id");
            $db->exec("INSERT INTO vouchers (customer_id, type, amount, note, new_balance, date) SELECT $customer_id, 'فاتورة مبيعات', $net_amount, 'فاتورة رقم $invoice_id', balance, '$date' FROM customers WHERE id = $customer_id");
        }

        $db->exec("COMMIT;");

        // تجهيز نص رسالة الواتساب

        $success_invoice = [
            'id' => $invoice_id, 'customer' => $customer_name, 'phone' => $customer_phone, 'items' => $cart,
            'total' => $total_amount, 'discount' => $discount, 'net' => $net_amount,
            'date' => date('Y-m-d H:i'), 'user_name' => $user_name, 'wa_text' => urlencode($whatsapp_text)
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>كاشير الذكاء الاصطناعي - V-MAX PRO</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        body { font-family: Tahoma, sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 8px; padding-bottom: 110px; }
        .header { display: flex; justify-content: space-between; align-items: center; background: #1e293b; padding: 8px 12px; border-radius: 8px; border: 1px solid #38bdf8; margin-bottom: 8px; }
        .grid-pos { display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 8px; }
        @media(max-width: 768px) { .grid-pos { grid-template-columns: 1fr; } }
        .panel { background: #1e293b; border: 1px solid #334155; border-radius: 8px; padding: 10px; }
        .prod-btn { background: #0f172a; border: 1px solid #38bdf8; color: #fff; padding: 10px; border-radius: 6px; text-align: right; cursor: pointer; font-size: 11px; margin-bottom: 6px; width: 100%; }
        .prod-btn:hover { background: #1e3a8a; }
        .form-control { width: 100%; padding: 8px; background: #0f172a; color: #fff; border: 1px solid #38bdf8; border-radius: 6px; box-sizing: border-box; margin-bottom: 6px; font-size: 11px; }
        .cart-table { width: 100%; border-collapse: collapse; font-size: 11px; margin-bottom: 8px; }
        .cart-table th, .cart-table td { border: 1px solid #334155; padding: 5px; text-align: center; }
        .cart-table th { background: #1e3a8a; color: #fff; }
        .btn-checkout { background: #22c55e; color: #fff; border: none; padding: 12px; border-radius: 8px; font-weight: bold; width: 100%; font-size: 13px; cursor: pointer; }
        .btn-cam { background: #8b5cf6; color: #fff; border: none; padding: 8px; border-radius: 6px; font-weight: bold; width: 100%; font-size: 11px; cursor: pointer; margin-bottom: 6px; }
        .btn-voice { background: #ec4899; color: #fff; border: none; padding: 10px; border-radius: 6px; font-weight: bold; width: 100%; font-size: 12px; cursor: pointer; margin-bottom: 6px; display: flex; align-items: center; justify-content: center; gap: 6px; }
        
        .modal-overlay { position: fixed; inset: 0; background: rgba(15,23,42,0.96); z-index: 99999; padding: 10px; overflow-y: auto; display: flex; align-items: center; justify-content: center; }
        .modal-wrapper { width: 100%; max-width: 360px; margin: auto; }
        .receipt-card { background: #ffffff !important; color: #000000 !important; width: 100%; border-radius: 14px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); direction: rtl; border: 1px solid #cbd5e1; padding: 16px; box-sizing: border-box; margin-bottom: 15px; font-family: Tahoma, sans-serif !important; }
        .actions-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; margin-top: 10px; width: 100%; }
        .btn-act { border: none; padding: 10px; border-radius: 8px; font-weight: bold; font-size: 11px; color: #fff; cursor: pointer; text-align:center; text-decoration:none; display:flex; align-items:center; justify-content:center; }
    </style>
</head>
<body>
    <div class="header">
        <div><b>كاشير الذكاء الاصطناعي</b> 🎙️💎</div>
        <div style="font-size: 10px; color: #38bdf8;"><?php echo htmlspecialchars($store_name); ?></div>
    </div>

    <?php if ($success_invoice): ?>
    <div class="modal-overlay">
        <div class="modal-wrapper">
            <div class="receipt-card" id="invoiceCard" dir="rtl" translate="no">
                <table style="width: 100%; border-collapse: collapse; background: #1d4ed8 !important; color: #ffffff !important; border-radius: 10px; overflow: hidden; margin-bottom: 12px; direction: rtl;">
                    <tr>
                        <td style="padding: 12px 14px; text-align: right; vertical-align: middle; width: 62%;">
                            <div style="font-size: 14px; font-weight: 900; color: #ffffff !important;"><?php echo htmlspecialchars((string)$store_name); ?></div>
                            <div style="font-size: 9px; opacity: 0.95; margin-top: 2px; color: #e2e8f0 !important;"><?php echo htmlspecialchars((string)$store_tagline); ?></div>
                            <div style="font-size: 8.5px; opacity: 0.90; margin-top: 2px; color: #e2e8f0 !important;"><?php echo htmlspecialchars((string)$store_address); ?> - <span style="direction:ltr; display:inline-block;"><?php echo htmlspecialchars((string)$store_phone); ?></span></div>
                        </td>
                        <td style="padding: 12px 10px; text-align: center; vertical-align: middle; width: 38%; border-right: 1.5px solid rgba(255,255,255,0.3);">
                            <div style="font-size: 11px; font-weight: bold; color: #ffffff !important;">فاتورة مبيعات</div>
                            <div style="font-size: 10px; font-weight: bold; margin-top: 3px; color: #93c5fd !important;">رقم: <span style="direction:ltr; display:inline-block;"><?php echo $success_invoice['id']; ?></span></div>
                        </td>
                    </tr>
                </table>

                <div style="background: #eab308 !important; height: 3.5px; width: 100%; border-radius: 2px; margin-bottom: 12px;"></div>

                <div style="padding: 2px; font-size: 11px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-weight: bold; color: #000;">
                        <span>العميل: <?php echo htmlspecialchars((string)$success_invoice['customer']); ?></span>
                        <span style="direction:ltr;"><?php echo $success_invoice['date']; ?></span>
                    </div>

                    <table style="width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 10px; text-align: center; background: #ffffff !important;">
                        <thead>
                            <tr style="background: #f1f5f9 !important; color: #000 !important;">
                                <th style="padding: 5px; border: 1px solid #cbd5e1;">الصنف</th>
                                <th style="padding: 5px; border: 1px solid #cbd5e1;">الكمية</th>
                                <th style="padding: 5px; border: 1px solid #cbd5e1;">السعر</th>
                                <th style="padding: 5px; border: 1px solid #cbd5e1;">الإجمالي</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($success_invoice['items'] as $it): ?>
                            <tr>
                                <td style="padding: 5px; border: 1px solid #cbd5e1; text-align: right;"><?php echo htmlspecialchars($it['name']); ?></td>
                                <td style="padding: 5px; border: 1px solid #cbd5e1;"><?php echo $it['qty']; ?></td>
                                <td style="padding: 5px; border: 1px solid #cbd5e1; direction: ltr;"><?php echo number_format($it['price'], 0); ?></td>
                                <td style="padding: 5px; border: 1px solid #cbd5e1; direction: ltr; font-weight: bold;"><?php echo number_format($it['price'] * $it['qty'], 0); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div style="background: #f8fafc !important; border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px; margin-bottom: 10px;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
                            <tr>
                                <td style="text-align: right; color: #475569;">المجموع الكلي:</td>
                                <td style="text-align: left; font-weight: bold; direction: ltr;"><?php echo number_format($success_invoice['total'], 0); ?> ر.ي</td>
                            </tr>
                            <?php if($success_invoice['discount'] > 0): ?>
                            <tr>
                                <td style="text-align: right; color: #dc2626;">الخصم:</td>
                                <td style="text-align: left; font-weight: bold; color: #dc2626; direction: ltr;"><?php echo number_format($success_invoice['discount'], 0); ?> ر.ي</td>
                            </tr>
                            <?php endif; ?>
                            <tr style="border-top: 1px dashed #cbd5e1;">
                                <td style="text-align: right; font-weight: bold; color: #1d4ed8; padding-top: 4px;">الصافي المطلوب:</td>
                                <td style="text-align: left; font-weight: bold; color: #1d4ed8; direction: ltr; padding-top: 4px; font-size: 13px;"><?php echo number_format($success_invoice['net'], 0); ?> ر.ي</td>
                            </tr>
                        </table>
                    </div>

                    <div style="margin-top: 15px; padding: 12px; border-radius: 10px; background: #f8fafc !important; border: 1px solid #e2e8f0; position: relative;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-size: 9px; color: #64748b; font-weight: bold;">تطوير وتصميم الأنظمة</div>
                                <div style="font-size: 12px; font-weight: 900; color: #0f172a;">أبو ضياء للبرمجيات 💎</div>
                            </div>
                            <div style="background: #1e293b; border-radius: 8px; padding: 4px 10px; text-align: center; border: 1px solid #334155;">
                                <div style="font-size: 7px; color: #94a3b8; font-weight: 900;">VERIFIED</div>
                                <div style="font-size: 9px; color: #38bdf8; font-weight: 900;">ABU DHIA</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="actions-grid">
                <button onclick="downloadPDF()" class="btn-act" style="background:#16a34a;">📥 تحميل PDF</button>
                <button onclick="window.print()" class="btn-act" style="background:#2563eb;">🖨️ طباعة الفاتورة</button>
                <a href="https://api.whatsapp.com/send?phone=<?php echo $success_invoice['phone']; ?>&text=<?php echo $success_invoice['wa_text']; ?>" target="_blank" class="btn-act" style="background:#16a34a;">💬 إرسال واتساب</a>
                <a href="pos_touch.php" class="btn-act" style="background:#8b5cf6;">➕ فاتورة جديدة</a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="grid-pos">
        <div class="panel">
            <h3 style="margin: 0 0 8px 0; font-size: 12px; color: #38bdf8;">🛒 سلة المبيعات الذكية</h3>
            
            <button type="button" onclick="startVoiceRecognition()" class="btn-voice" id="voiceBtn">🎙️ تحدث الآن لإضافة الصنف بالصوت</button>
            <div id="voiceStatus" style="font-size: 10px; color: #38bdf8; text-align: center; margin-bottom: 6px;">اضغط وتحدث باسم المنتج (مثال: سامسونج، شاحن...)</div>

            <button type="button" onclick="startScanner()" class="btn-cam">📷 مسح باركود المنتج بالكاميرا</button>
            <div id="reader" style="display:none; margin-bottom: 8px;"></div>

            <table class="cart-table">
                <thead>
                    <tr>
                        <th>الصنف</th>
                        <th>السعر</th>
                        <th>الكمية</th>
                        <th>إجمالي</th>
                        <th>حذف</th>
                    </tr>
                </thead>
                <tbody id="cartBody">
                    <tr><td colspan="5" style="color: #94a3b8;">السلة فارغة</td></tr>
                </tbody>
            </table>

            <form method="POST" id="checkoutForm">
                <input type="hidden" name="checkout" value="1">
                <input type="hidden" name="cart_items" id="cart_items_input">

                <label style="font-size: 10px;">العميل:</label>
                <select name="customer_id" class="form-control">
                    <option value="0">-- عميل نقدي --</option>
                    <?php foreach($customers as $c): ?>
                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                    <?php endforeach; ?>
                </select>

                <div style="display: flex; gap: 6px;">
                    <div style="flex:1;">
                        <label style="font-size: 10px;">الخصم:</label>
                        <input type="number" step="any" name="discount" id="discountInput" class="form-control" value="0" oninput="updateTotals()">
                    </div>
                    <div style="flex:1;">
                        <label style="font-size: 10px;">الصافي:</label>
                        <input type="text" id="netDisplay" class="form-control" readonly value="0" style="font-weight: bold; color: #4ade80; direction: ltr;">
                    </div>
                </div>

                <button type="button" onclick="submitCheckout()" class="btn-checkout">💾 إتمام البيع وطباعة الفاتورة</button>
            </form>
        </div>

        <div class="panel">
            <h3 style="margin: 0 0 8px 0; font-size: 12px; color: #38bdf8;">📦 المنتجات المتاحة</h3>
            <input type="text" id="searchProd" class="form-control" placeholder="ابحث عن صنف..." oninput="filterProducts()">
            
            <div id="productsList" style="max-height: 380px; overflow-y: auto;">
                <?php foreach($products as $p): ?>
                <button type="button" class="prod-btn" onclick="addToCart(<?php echo htmlspecialchars(json_encode($p)); ?>)">
                    <b><?php echo htmlspecialchars($p['item_name']); ?></b><br>
                    <span style="color: #38bdf8; direction: ltr; display:inline-block;"><?php echo number_format($p['sell_price'], 0); ?> ر.ي</span> | متوفر: <?php echo $p['stock']; ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
    let cart = [];
    let productsData = <?php echo json_encode($products); ?>;

    function addToCart(prod) {
        let found = cart.find(item => item.id == prod.id);
        if (found) {
            found.qty += 1;
        } else {
            cart.push({
                id: prod.id,
                name: prod.item_name,
                price: parseFloat(prod.sell_price),
                cost: parseFloat(prod.buy_price || 0),
                qty: 1
            });
        }
        renderCart();
    }

    function renderCart() {
        let tbody = document.getElementById('cartBody');
        tbody.innerHTML = '';
        if (cart.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" style="color: #94a3b8;">السلة فارغة</td></tr>';
            updateTotals();
            return;
        }
        cart.forEach((item, index) => {
            let tr = document.createElement('tr');
            tr.innerHTML = `
                <td style="text-align:right;"><b>${item.name}</b></td>
                <td style="direction:ltr;">${item.price}</td>
                <td><input type="number" value="${item.qty}" min="1" style="width:40px; text-align:center;" onchange="updateQty(${index}, this.value)"></td>
                <td style="direction:ltr; font-weight:bold;">${item.price * item.qty}</td>
                <td><button type="button" onclick="removeItem(${index})" style="background:#ef4444; color:#fff; border:none; padding:2px 6px; border-radius:4px;">X</button></td>
            `;
            tbody.appendChild(tr);
        });
        updateTotals();
    }

    function updateQty(index, qty) {
        cart[index].qty = parseInt(qty) || 1;
        renderCart();
    }

    function removeItem(index) {
        cart.splice(index, 1);
        renderCart();
    }

    function updateTotals() {
        let total = 0;
        cart.forEach(item => total += (item.price * item.qty));
        let discount = parseFloat(document.getElementById('discountInput').value) || 0;
        let net = total - discount;
        document.getElementById('netDisplay').value = net.toLocaleString() + ' ر.ي';
    }

    function submitCheckout() {
        if (cart.length === 0) {
            alert('السلة فارغة!');
            return;
        }
        document.getElementById('cart_items_input').value = JSON.stringify(cart);
        document.getElementById('checkoutForm').submit();
    }

    function filterProducts() {
        let val = document.getElementById('searchProd').value.toLowerCase();
        let btns = document.querySelectorAll('.prod-btn');
        btns.forEach(btn => {
            let text = btn.innerText.toLowerCase();
            btn.style.display = text.includes(val) ? 'block' : 'none';
        });
    }

    function startVoiceRecognition() {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!SpeechRecognition) {
            alert('متصفحك لا يدعم التعرف على الصوت!');
            return;
        }
        const recognition = new SpeechRecognition();
        recognition.lang = 'ar-SA';
        recognition.interimResults = false;
        recognition.maxAlternatives = 1;

        let statusDiv = document.getElementById('voiceStatus');
        statusDiv.innerText = '🎙️ جاري الاستماع... تحدث الآن';
        document.getElementById('voiceBtn').style.background = '#dc2626';

        recognition.start();

        recognition.onresult = function(event) {
            let speechResult = event.results[0][0].transcript.toLowerCase();
            statusDiv.innerText = '🗣️ سمعت: "' + speechResult + '"';
            document.getElementById('voiceBtn').style.background = '#ec4899';

            let matched = productsData.find(p => p.item_name.toLowerCase().includes(speechResult) || speechResult.includes(p.item_name.toLowerCase()));
            if (matched) {
                addToCart(matched);
            } else {
                alert('لم يتم العثور على صنف يطابق قولك: ' + speechResult);
            }
        };

        recognition.onerror = function(event) {
            statusDiv.innerText = '❌ خطأ في التعرف الصوتي';
            document.getElementById('voiceBtn').style.background = '#ec4899';
        };

        recognition.onspeechend = function() {
            recognition.stop();
        };
    }

    function startScanner() {
        let readerDiv = document.getElementById('reader');
        readerDiv.style.display = 'block';
        let scanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: 250 });
        scanner.render((decodedText) => {
            scanner.clear();
            readerDiv.style.display = 'none';
            let matched = productsData.find(p => p.barcode === decodedText);
            if (matched) {
                addToCart(matched);
            } else {
                alert('لم يتم العثور على منتج بهذا الباركود: ' + decodedText);
            }
        }, (error) => {});
    }

    function downloadPDF() {
        const element = document.getElementById('invoiceCard');
        const opt = {
            margin: [2, 0, 2, 0],
            filename: 'Invoice_<?php echo $success_invoice['id'] ?? 1; ?>.pdf',
            image: { type: 'jpeg', quality: 1.0 },
            html2canvas: { scale: 4, useCORS: true },
            jsPDF: { unit: 'mm', format: [80, 160], orientation: 'portrait' }
        };
        html2pdf().set(opt).from(element).save();
    }
    </script>
    <?php if(file_exists('nav_menu.php')) { include 'nav_menu.php'; } ?>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>
</html>

