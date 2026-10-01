import http.server
import socketserver
import json
import sqlite3

PORT = 8080

# 🌸 إنشاء قاعدة البيانات
def init_db():
    conn = sqlite3.connect('global_invoices.db')
    c = conn.cursor()
    c.execute('''CREATE TABLE IF NOT EXISTS invoices (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        customer_name TEXT,
        invoice_type TEXT,
        total_amount REAL,
        sweet_note TEXT,
        date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )''')
    c.execute('''CREATE TABLE IF NOT EXISTS journal_entries (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        amount REAL,
        debit_account TEXT,
        credit_account TEXT,
        description TEXT,
        date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )''')
    conn.commit()
    conn.close()

HTML_UI = """
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>شغف الأرقام - تطبيق محاسبي يفتح النفس</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&family=Amiri:ital@0;1&display=swap" rel="stylesheet">
    <style>
        :root { --primary-color: #8fa0e0; --secondary-color: #ffb7b2; --background-gradient: linear-gradient(135deg, #e0c3fc 0%, #8ec5fc 100%); --glass-bg: rgba(255, 255, 255, 0.4); --text-dark: #4a4a4a; --text-light: #7a7a7a; }
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body { font-family: 'Tajawal', sans-serif; background-image: var(--background-gradient); margin: 0; padding: 0; height: 100vh; overflow: hidden; display: flex; justify-content: center; background-size: cover; color: var(--text-dark); }
        
        /* حاوية التطبيق (تشبه شاشة الجوال) */
        .app-container { width: 100%; max-width: 480px; height: 100%; position: relative; display: flex; flex-direction: column; overflow: hidden; background: rgba(255,255,255,0.1); backdrop-filter: blur(5px); }
        
        /* الشاشة الرئيسية (Dashboard) */
        .dashboard { flex: 1; display: flex; flex-direction: column; padding: 30px 20px; overflow-y: auto; }
        .dash-header { text-align: center; margin-bottom: 40px; margin-top: 20px; }
        .dash-header h1 { font-family: 'Amiri', serif; font-size: 2.5rem; color: #5c6bc0; margin: 0; text-shadow: 1px 1px 2px rgba(255,255,255,0.6); }
        .dash-header p { color: var(--text-dark); font-size: 1.1rem; margin-top: 5px; opacity: 0.8; }
        
        .action-cards { display: grid; grid-template-columns: 1fr; gap: 20px; }
        .card { background: rgba(255, 255, 255, 0.5); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.8); border-radius: 25px; padding: 30px 20px; text-align: center; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 8px 32px rgba(31, 38, 135, 0.1); display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .card:active { transform: scale(0.96); }
        .card-icon { font-size: 3.5rem; margin-bottom: 10px; }
        .card-title { font-size: 1.3rem; font-weight: 700; color: #5c6bc0; margin: 0; }
        .card-subtitle { font-size: 0.9rem; color: var(--text-light); margin-top: 5px; }

        /* النوافذ المنزلقة (Modals) */
        .modal { position: absolute; top: 100%; left: 0; width: 100%; height: 100%; background: var(--background-gradient); z-index: 10; display: flex; flex-direction: column; transition: top 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .modal.active { top: 0; }
        
        .modal-header { padding: 20px; display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.4); backdrop-filter: blur(15px); border-bottom: 1px solid rgba(255,255,255,0.5); z-index: 11; }
        .modal-header h2 { margin: 0; font-size: 1.5rem; color: #5c6bc0; }
        .close-btn { background: rgba(255,255,255,0.6); border: none; width: 40px; height: 40px; border-radius: 50%; font-size: 1.2rem; color: #555; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        
        .modal-body { flex: 1; overflow-y: auto; padding: 25px; padding-bottom: 100px; }

        /* تصميم النماذج من الداخل */
        .invoice-type-selector { display: flex; justify-content: center; gap: 10px; margin-bottom: 25px; background: rgba(255,255,255,0.3); padding: 5px; border-radius: 30px; }
        .type-btn { flex: 1; background: transparent; border: none; padding: 10px; border-radius: 25px; cursor: pointer; font-family: 'Tajawal', sans-serif; font-weight: 700; color: var(--text-dark); transition: all 0.3s ease; }
        .type-btn.active { background: white; color: var(--primary-color); box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #5c6bc0; font-size: 0.95rem; }
        .form-control { width: 100%; padding: 15px; background: rgba(255, 255, 255, 0.7); border: 1px solid rgba(255, 255, 255, 0.9); border-radius: 15px; font-family: 'Tajawal', sans-serif; font-size: 1rem; transition: all 0.3s ease; }
        .form-control:focus { outline: none; border-color: var(--primary-color); background: white; box-shadow: 0 0 0 3px rgba(143, 160, 224, 0.2); }
        
        .items-table { width: 100%; border-collapse: separate; border-spacing: 0 10px; margin-bottom: 15px; }
        .item-row td { padding: 0 3px; }
        .add-item-btn { background: transparent; border: none; color: var(--primary-color); font-family: 'Tajawal', sans-serif; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 5px; margin-bottom: 20px; font-size: 1rem; padding: 10px 0; }
        .total-section { background: rgba(255, 255, 255, 0.8); padding: 20px; border-radius: 20px; margin-bottom: 25px; box-shadow: 0 5px 15px rgba(0,0,0,0.02); }
        .total-row.grand-total { font-size: 1.2rem; font-weight: 700; color: #5c6bc0; display: flex; justify-content: space-between; align-items: center; }
        
        .submit-btn { width: 100%; background: linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%); color: white; border: none; padding: 18px; border-radius: 20px; font-family: 'Tajawal', sans-serif; font-size: 1.2rem; font-weight: 700; cursor: pointer; box-shadow: 0 8px 25px rgba(251, 194, 235, 0.5); }
        .submit-btn.entry-btn { background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%); box-shadow: 0 8px 25px rgba(132, 250, 176, 0.5); color: #2c3e50; }
        
        .toast { visibility: hidden; min-width: 250px; background-color: rgba(255, 255, 255, 0.95); color: #5c6bc0; text-align: center; border-radius: 30px; padding: 16px 20px; position: fixed; z-index: 1000; bottom: 30px; left: 50%; transform: translateX(-50%); font-family: 'Tajawal', sans-serif; font-weight: bold; border: 1px solid rgba(255, 255, 255, 0.8); box-shadow: 0 10px 40px rgba(0,0,0,0.15); backdrop-filter: blur(10px); }
        .toast.show { visibility: visible; animation: fadein 0.5s, fadeout 0.5s 2.5s; }
        @keyframes fadein { from { bottom: 0; opacity: 0; } to { bottom: 30px; opacity: 1; } }
        @keyframes fadeout { from { bottom: 30px; opacity: 1; } to { bottom: 0; opacity: 0; } }
    </style>
</head>
<body>
    <div class="app-container">
        
        <div class="dashboard">
            <div class="dash-header">
                <h1>شغف الأرقام ✨</h1>
                <p>مرحباً بك في مساحتك المنظمة</p>
            </div>
            
            <div class="action-cards">
                <div class="card" onclick="openModal('invoiceModal')">
                    <div class="card-icon">🧾</div>
                    <h3 class="card-title">إصدار فاتورة</h3>
                    <p class="card-subtitle">بيع، آجل، أو مرتجع</p>
                </div>
                
                <div class="card" onclick="openModal('entryModal')">
                    <div class="card-icon">⚖️</div>
                    <h3 class="card-title">تسجيل قيد يومي</h3>
                    <p class="card-subtitle">مصروفات وإيرادات</p>
                </div>
            </div>
        </div>

        <div id="invoiceModal" class="modal">
            <div class="modal-header">
                <h2>فاتورة جديدة ✨</h2>
                <button class="close-btn" onclick="closeModal('invoiceModal')">✖</button>
            </div>
            <div class="modal-body">
                <div class="invoice-type-selector">
                    <button type="button" class="type-btn active" onclick="changeType('cash', 'شكراً لتعاملكم الراقي.. أسعدتنا خدمتكم! 🌸')">نقداً</button>
                    <button type="button" class="type-btn" onclick="changeType('credit', 'ميثاق ثقة.. بيننا وبينكم كل الود 🤝')">آجل</button>
                    <button type="button" class="type-btn" onclick="changeType('return', 'رضاكم غايتنا.. نتشوق لخدمتكم مجدداً 🔄')">مرتجع</button>
                </div>
                <form id="invoiceForm">
                    <div class="form-group">
                        <label>اسم العميل العزيز</label>
                        <input type="text" id="customerName" class="form-control" placeholder="أدخل اسم العميل..." required>
                    </div>
                    <table class="items-table" id="itemsTable">
                        <tbody id="itemsBody">
                            <tr class="item-row">
                                <td style="width:55%"><input type="text" class="form-control item-name" placeholder="المنتج..." required></td>
                                <td style="width:15%"><input type="number" class="form-control item-qty" value="1" min="1" oninput="calc()"></td>
                                <td style="width:30%"><input type="number" step="0.01" class="form-control item-price" placeholder="السعر" oninput="calc()" required></td>
                            </tr>
                        </tbody>
                    </table>
                    <button type="button" class="add-item-btn" onclick="addItem()">+ إضافة منتج آخر بحب</button>
                    <div class="total-section">
                        <div class="total-row grand-total">
                            <span>الإجمالي:</span>
                            <span id="grandTotalText">0 💰</span>
                        </div>
                    </div>
                    <button type="submit" class="submit-btn">حفظ الفاتورة وإغلاق ✨</button>
                </form>
            </div>
        </div>

        <div id="entryModal" class="modal">
            <div class="modal-header">
                <h2>قيد يومي 🌿</h2>
                <button class="close-btn" onclick="closeModal('entryModal')">✖</button>
            </div>
            <div class="modal-body">
                <form id="entryForm">
                    <div class="form-group">
                        <label>المبلغ (بكل دقة) 💰</label>
                        <input type="number" step="0.01" id="entryAmount" class="form-control" placeholder="أدخل مبلغ القيد..." required>
                    </div>
                    <div class="form-group">
                        <label>من حساب (المدين) 📤</label>
                        <input type="text" id="entryDebit" class="form-control" placeholder="مثال: المصروفات..." required>
                    </div>
                    <div class="form-group">
                        <label>إلى حساب (الدائن) 📥</label>
                        <input type="text" id="entryCredit" class="form-control" placeholder="مثال: الصندوق..." required>
                    </div>
                    <div class="form-group">
                        <label>البيان (شرح القيد) 📝</label>
                        <input type="text" id="entryDesc" class="form-control" placeholder="اشرح تفاصيل الحركة..." required>
                    </div>
                    <br>
                    <button type="submit" class="submit-btn entry-btn">حفظ القيد وإغلاق ⚖️✨</button>
                </form>
            </div>
        </div>

        <div id="toastMessage" class="toast">تم الحفظ بنجاح ✨</div>
    </div>

    <script>
        // فتح وإغلاق النوافذ بنعومة
        function openModal(id) { document.getElementById(id).classList.add('active'); }
        function closeModal(id) { document.getElementById(id).classList.remove('active'); }

        let currentType = 'cash';
        let currentNote = 'شكراً لتعاملكم الراقي.. أسعدتنا خدمتكم! 🌸';

        function changeType(type, noteText) {
            currentType = type;
            currentNote = noteText;
            document.querySelectorAll('.type-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
        }

        function addItem() {
            const tbody = document.getElementById('itemsBody');
            const tr = document.createElement('tr');
            tr.className = 'item-row';
            tr.innerHTML = `
                <td><input type="text" class="form-control item-name" placeholder="المنتج..." required></td>
                <td><input type="number" class="form-control item-qty" value="1" min="1" oninput="calc()"></td>
                <td><input type="number" step="0.01" class="form-control item-price" placeholder="السعر" oninput="calc()" required></td>
            `;
            tbody.appendChild(tr);
        }

        function calc() {
            let total = 0;
            document.querySelectorAll('.item-row').forEach(row => {
                const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
                const price = parseFloat(row.querySelector('.item-price').value) || 0;
                total += (qty * price);
            });
            document.getElementById('grandTotalText').innerText = total.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 2}) + ' 💰';
            return total;
        }

        function showToast(msg) {
            const toast = document.getElementById("toastMessage");
            toast.innerText = msg;
            toast.className = "toast show";
            setTimeout(() => { toast.className = toast.className.replace("show", ""); }, 3000);
        }

        // إرسال الفاتورة
        document.getElementById('invoiceForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const payload = {
                customer_name: document.getElementById('customerName').value,
                type: currentType,
                total: currentType === 'return' ? -calc() : calc(),
                note: currentNote
            };
            try {
                const res = await fetch('/api/save_invoice', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
                const result = await res.json();
                showToast(result.message);
                document.getElementById('invoiceForm').reset();
                calc();
                closeModal('invoiceModal'); // إغلاق النافذة والعودة للرئيسية
            } catch (err) { alert("حدث خطأ 💔"); }
        });

        // إرسال القيد
        document.getElementById('entryForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const payload = {
                amount: parseFloat(document.getElementById('entryAmount').value),
                debit_account: document.getElementById('entryDebit').value,
                credit_account: document.getElementById('entryCredit').value,
                description: document.getElementById('entryDesc').value
            };
            try {
                const res = await fetch('/api/save_entry', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
                const result = await res.json();
                showToast(result.message);
                document.getElementById('entryForm').reset();
                closeModal('entryModal'); // إغلاق النافذة والعودة للرئيسية
            } catch (err) { alert("حدث خطأ 💔"); }
        });
    </script>
</body>
</html>
"""

class GlobalRomanticHandler(http.server.SimpleHTTPRequestHandler):
    def do_GET(self):
        if self.path == '/':
            self.send_response(200)
            self.send_header('Content-type', 'text/html; charset=utf-8')
            self.end_headers()
            self.wfile.write(HTML_UI.encode('utf-8'))
        else:
            super().do_GET()
            
    def do_POST(self):
        content_length = int(self.headers['Content-Length'])
        data = json.loads(self.rfile.read(content_length).decode('utf-8'))
        conn = sqlite3.connect('global_invoices.db')
        c = conn.cursor()

        if self.path == '/api/save_invoice':
            c.execute("INSERT INTO invoices (customer_name, invoice_type, total_amount, sweet_note) VALUES (?, ?, ?, ?)",
                      (data.get('customer_name'), data.get('type'), data.get('total'), data.get('note')))
            msg = 'تم حفظ الفاتورة بنجاح ✨'
            
        elif self.path == '/api/save_entry':
            c.execute("INSERT INTO journal_entries (amount, debit_account, credit_account, description) VALUES (?, ?, ?, ?)",
                      (data.get('amount'), data.get('debit_account'), data.get('credit_account'), data.get('description')))
            msg = 'تم حفظ القيد بنجاح ⚖️✨'

        conn.commit()
        conn.close()
        self.send_response(200)
        self.send_header('Content-type', 'application/json; charset=utf-8')
        self.end_headers()
        self.wfile.write(json.dumps({'status': 'success', 'message': msg}).encode('utf-8'))

init_db()
with socketserver.TCPServer(("", PORT), GlobalRomanticHandler) as httpd:
    print("="*40)
    print("🌸 التطبيق المحدث (شاشات مخفية داخل أزرار) يعمل الآن! 🌸")
    print(f"👉 الرابط: http://localhost:{PORT}")
    print("="*40)
    httpd.serve_forever()
import http.server
import socketserver
import json
import sqlite3

PORT = 8080

# 🌸 إنشاء قاعدة البيانات (فواتير + قيود)
def init_db():
    conn = sqlite3.connect('global_invoices.db')
    c = conn.cursor()
    c.execute('''CREATE TABLE IF NOT EXISTS invoices (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        customer_name TEXT,
        invoice_type TEXT,
        total_amount REAL,
        sweet_note TEXT,
        date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )''')
    c.execute('''CREATE TABLE IF NOT EXISTS journal_entries (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        amount REAL,
        debit_account TEXT,
        credit_account TEXT,
        description TEXT,
        date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )''')
    conn.commit()
    conn.close()

HTML_UI = """
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>شغف الأرقام - تطبيق محاسبي يفتح النفس</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&family=Amiri:ital@0;1&display=swap" rel="stylesheet">
    <style>
        :root { --primary-color: #8fa0e0; --secondary-color: #ffb7b2; --background-gradient: linear-gradient(135deg, #e0c3fc 0%, #8ec5fc 100%); --glass-bg: rgba(255, 255, 255, 0.4); --text-dark: #4a4a4a; --text-light: #7a7a7a; }
        body { font-family: 'Tajawal', sans-serif; background-image: var(--background-gradient); min-height: 100vh; margin: 0; display: flex; justify-content: center; align-items: center; padding: 20px; color: var(--text-dark); }
        .glass-container { background: var(--glass-bg); backdrop-filter: blur(15px); border-radius: 20px; border: 1px solid rgba(255, 255, 255, 0.6); box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.2); width: 100%; max-width: 600px; padding: 30px; position: relative; overflow: hidden; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { font-family: 'Amiri', serif; font-size: 2.2rem; color: #5c6bc0; margin: 0; }
        .header p { color: var(--text-light); font-size: 0.9rem; margin-top: 5px; }
        
        .tabs { display: flex; justify-content: center; gap: 10px; margin-bottom: 25px; background: rgba(255,255,255,0.3); padding: 5px; border-radius: 30px; }
        .tab-btn { flex: 1; background: transparent; border: none; padding: 12px; border-radius: 25px; cursor: pointer; font-family: 'Tajawal', sans-serif; font-weight: 700; color: var(--text-dark); transition: all 0.3s ease; }
        .tab-btn.active { background: white; color: var(--primary-color); box-shadow: 0 4px 10px rgba(0,0,0,0.05); }

        .invoice-type-selector { display: flex; justify-content: center; gap: 10px; margin-bottom: 25px; }
        .type-btn { background: rgba(255, 255, 255, 0.5); border: 1px solid rgba(255,255,255,0.8); padding: 8px 15px; border-radius: 30px; cursor: pointer; font-family: 'Tajawal', sans-serif; font-weight: 500; font-size: 0.9rem; color: var(--text-dark); transition: all 0.3s ease; }
        .type-btn.active { background: var(--primary-color); color: white; border-color: var(--primary-color); }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; color: #5c6bc0; font-size: 0.95rem; }
        .form-control { width: 100%; padding: 12px 15px; background: rgba(255, 255, 255, 0.6); border: 1px solid rgba(255, 255, 255, 0.8); border-radius: 12px; font-family: 'Tajawal', sans-serif; box-sizing: border-box; transition: all 0.3s ease;}
        .form-control:focus { outline: none; border-color: var(--primary-color); background: white; }
        
        .items-table { width: 100%; border-collapse: separate; border-spacing: 0 8px; margin-bottom: 15px; }
        .item-row td { padding: 3px; }
        .add-item-btn { background: transparent; border: none; color: var(--primary-color); font-family: 'Tajawal', sans-serif; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 5px; margin-bottom: 20px; font-size: 0.9rem; }
        .total-section { background: rgba(255, 255, 255, 0.6); padding: 15px; border-radius: 15px; margin-bottom: 20px; }
        .total-row.grand-total { font-size: 1.1rem; font-weight: 700; color: #5c6bc0; display: flex; justify-content: space-between; }
        
        .submit-btn { width: 100%; background: linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%); color: white; border: none; padding: 15px; border-radius: 15px; font-family: 'Tajawal', sans-serif; font-size: 1.1rem; font-weight: 700; cursor: pointer; box-shadow: 0 4px 15px rgba(251, 194, 235, 0.4); transition: transform 0.2s; }
        .submit-btn:hover { transform: translateY(-2px); }
        .submit-btn.entry-btn { background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%); box-shadow: 0 4px 15px rgba(132, 250, 176, 0.4); color: #2c3e50; }
        
        .romantic-note { text-align: center; margin-top: 15px; font-family: 'Amiri', serif; font-style: italic; color: var(--text-light); }
        .toast { visibility: hidden; min-width: 250px; background-color: rgba(255, 255, 255, 0.95); color: #5c6bc0; text-align: center; border-radius: 15px; padding: 16px; position: fixed; z-index: 1000; bottom: 30px; left: 50%; transform: translateX(-50%); font-family: 'Tajawal', sans-serif; font-weight: bold; border: 1px solid rgba(255, 255, 255, 0.6); box-shadow: 0 10px 30px rgba(0,0,0,0.1); backdrop-filter: blur(10px); }
        .toast.show { visibility: visible; animation: fadein 0.5s, fadeout 0.5s 2.5s; }
        @keyframes fadein { from { bottom: 0; opacity: 0; } to { bottom: 30px; opacity: 1; } }
        @keyframes fadeout { from { bottom: 30px; opacity: 1; } to { bottom: 0; opacity: 0; } }
        
        .section { display: none; animation: fadeIn 0.4s ease; }
        .section.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>
    <div class="glass-container">
        <div class="header">
            <h1>شغف الأرقام ✨</h1>
            <p>نظام محاسبي يجمع الفواتير والقيود بحب</p>
        </div>

        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('invoicesTab')">الفواتير 🧾</button>
            <button class="tab-btn" onclick="switchTab('entriesTab')">القيود اليومية ⚖️</button>
        </div>

        <div id="invoicesTab" class="section active">
            <div class="invoice-type-selector">
                <button type="button" class="type-btn active" onclick="changeType('cash', 'نقداً', 'شكراً لتعاملكم الراقي.. أسعدتنا خدمتكم! 🌸')">نقداً</button>
                <button type="button" class="type-btn" onclick="changeType('credit', 'آجل', 'ميثاق ثقة.. بيننا وبينكم كل الود 🤝')">آجل</button>
                <button type="button" class="type-btn" onclick="changeType('return', 'مرتجع', 'رضاكم غايتنا.. نتشوق لخدمتكم مجدداً 🔄')">مرتجع</button>
            </div>
            <form id="invoiceForm">
                <div class="form-group">
                    <label>اسم العميل العزيز</label>
                    <input type="text" id="customerName" class="form-control" placeholder="أدخل اسم العميل..." required>
                </div>
                <table class="items-table" id="itemsTable">
                    <tbody id="itemsBody">
                        <tr class="item-row">
                            <td style="width:50%"><input type="text" class="form-control item-name" placeholder="المنتج..." required></td>
                            <td style="width:20%"><input type="number" class="form-control item-qty" value="1" min="1" oninput="calc()"></td>
                            <td style="width:30%"><input type="number" step="0.01" class="form-control item-price" placeholder="السعر" oninput="calc()" required></td>
                        </tr>
                    </tbody>
                </table>
                <button type="button" class="add-item-btn" onclick="addItem()">+ إضافة بند آخر بحب</button>
                <div class="total-section">
                    <div class="total-row grand-total">
                        <span>الإجمالي بابتسامة:</span>
                        <span id="grandTotalText">0 💰</span>
                    </div>
                </div>
                <button type="submit" class="submit-btn" id="submitBtn">حفظ الفاتورة ✨</button>
                <div class="romantic-note" id="romanticNote">"شكراً لتعاملكم الراقي.. أسعدتنا خدمتكم! 🌸"</div>
            </form>
        </div>

        <div id="entriesTab" class="section">
            <form id="entryForm">
                <div class="form-group">
                    <label>المبلغ (بكل دقة) 💰</label>
                    <input type="number" step="0.01" id="entryAmount" class="form-control" placeholder="أدخل مبلغ القيد..." required>
                </div>
                <div class="form-group">
                    <label>من حساب (المدين / أين ذهب العطاء؟) 📤</label>
                    <input type="text" id="entryDebit" class="form-control" placeholder="مثال: المصروفات، المشتريات..." required>
                </div>
                <div class="form-group">
                    <label>إلى حساب (الدائن / من أين جاء النماء؟) 📥</label>
                    <input type="text" id="entryCredit" class="form-control" placeholder="مثال: الصندوق، المبيعات..." required>
                </div>
                <div class="form-group">
                    <label>قصة القيد (البيان) 📝</label>
                    <input type="text" id="entryDesc" class="form-control" placeholder="اشرح تفاصيل هذه الحركة بلطف..." required>
                </div>
                <button type="submit" class="submit-btn entry-btn">حفظ القيد ليوازن الشغف ⚖️✨</button>
                <div class="romantic-note">"كل قيد تسجله هو خطوة نحو نجاحك المنظم 🌿"</div>
            </form>
        </div>

    </div>
    <div id="toastMessage" class="toast">تم الحفظ بنجاح ✨</div>

    <script>
        function switchTab(tabId) {
            document.querySelectorAll('.section').forEach(sec => sec.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            event.target.classList.add('active');
        }

        let currentType = 'cash';
        let currentNote = 'شكراً لتعاملكم الراقي.. أسعدتنا خدمتكم! 🌸';

        function changeType(type, btnText, noteText) {
            currentType = type;
            currentNote = noteText;
            document.querySelectorAll('.type-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            document.getElementById('romanticNote').innerText = `"${noteText}"`;
        }

        function addItem() {
            const tbody = document.getElementById('itemsBody');
            const tr = document.createElement('tr');
            tr.className = 'item-row';
            tr.innerHTML = `
                <td><input type="text" class="form-control item-name" placeholder="المنتج..." required></td>
                <td><input type="number" class="form-control item-qty" value="1" min="1" oninput="calc()"></td>
                <td><input type="number" step="0.01" class="form-control item-price" placeholder="السعر" oninput="calc()" required></td>
            `;
            tbody.appendChild(tr);
        }

        function calc() {
            let total = 0;
            document.querySelectorAll('.item-row').forEach(row => {
                const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
                const price = parseFloat(row.querySelector('.item-price').value) || 0;
                total += (qty * price);
            });
            document.getElementById('grandTotalText').innerText = total.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 2}) + ' 💰';
            return total;
        }

        function showToast(msg) {
            const toast = document.getElementById("toastMessage");
            toast.innerText = msg;
            toast.className = "toast show";
            setTimeout(() => { toast.className = toast.className.replace("show", ""); }, 3000);
        }

        document.getElementById('invoiceForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const payload = {
                customer_name: document.getElementById('customerName').value,
                type: currentType,
                total: currentType === 'return' ? -calc() : calc(),
                note: currentNote
            };
            try {
                const res = await fetch('/api/save_invoice', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
                const result = await res.json();
                showToast(result.message);
                document.getElementById('invoiceForm').reset();
                calc();
            } catch (err) { alert("حدث خطأ 💔"); }
        });

        document.getElementById('entryForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const payload = {
                amount: parseFloat(document.getElementById('entryAmount').value),
                debit_account: document.getElementById('entryDebit').value,
                credit_account: document.getElementById('entryCredit').value,
                description: document.getElementById('entryDesc').value
            };
            try {
                const res = await fetch('/api/save_entry', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
                const result = await res.json();
                showToast(result.message);
                document.getElementById('entryForm').reset();
            } catch (err) { alert("حدث خطأ 💔"); }
        });
    </script>
</body>
</html>
"""

class GlobalRomanticHandler(http.server.SimpleHTTPRequestHandler):
    def do_GET(self):
        if self.path == '/':
            self.send_response(200)
            self.send_header('Content-type', 'text/html; charset=utf-8')
            self.end_headers()
            self.wfile.write(HTML_UI.encode('utf-8'))
        else:
            super().do_GET()
            
    def do_POST(self):
        content_length = int(self.headers['Content-Length'])
        data = json.loads(self.rfile.read(content_length).decode('utf-8'))
        conn = sqlite3.connect('global_invoices.db')
        c = conn.cursor()

        if self.path == '/api/save_invoice':
            c.execute("INSERT INTO invoices (customer_name, invoice_type, total_amount, sweet_note) VALUES (?, ?, ?, ?)",
                      (data.get('customer_name'), data.get('type'), data.get('total'), data.get('note')))
            msg = 'تم حفظ الفاتورة بنجاح ✨'
            
        elif self.path == '/api/save_entry':
            c.execute("INSERT INTO journal_entries (amount, debit_account, credit_account, description) VALUES (?, ?, ?, ?)",
                      (data.get('amount'), data.get('debit_account'), data.get('credit_account'), data.get('description')))
            msg = 'تم حفظ القيد وتوازن الشغف بنجاح ⚖️✨'

        conn.commit()
        conn.close()
        self.send_response(200)
        self.send_header('Content-type', 'application/json; charset=utf-8')
        self.end_headers()
        self.wfile.write(json.dumps({'status': 'success', 'message': msg}).encode('utf-8'))

init_db()
with socketserver.TCPServer(("", PORT), GlobalRomanticHandler) as httpd:
    print("="*40)
    print("🌸 تطبيق 'شغف الأرقام' (فواتير + قيود) جاهز! 🌸")
    print(f"👉 الرابط: http://localhost:{PORT}")
    print("="*40)
    httpd.serve_forever()
import http.server
import socketserver
import json
import sqlite3
import urllib.parse

PORT = 8080

# 🌸 إنشاء قاعدة البيانات للتطبيق العالمي
def init_db():
    conn = sqlite3.connect('global_invoices.db')
    c = conn.cursor()
    c.execute('''CREATE TABLE IF NOT EXISTS invoices (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        customer_name TEXT,
        invoice_type TEXT,
        total_amount REAL,
        sweet_note TEXT,
        date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )''')
    conn.commit()
    conn.close()

# 🌸 الواجهة الزجاجية الرومانسية (عالمية تناسب أي مشروع)
HTML_UI = """
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رصيد الود - تطبيق محاسبي يفتح النفس</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&family=Amiri:ital@0;1&display=swap" rel="stylesheet">
    <style>
        :root { --primary-color: #8fa0e0; --secondary-color: #ffb7b2; --background-gradient: linear-gradient(135deg, #e0c3fc 0%, #8ec5fc 100%); --glass-bg: rgba(255, 255, 255, 0.4); --text-dark: #4a4a4a; --text-light: #7a7a7a; }
        body { font-family: 'Tajawal', sans-serif; background-image: var(--background-gradient); min-height: 100vh; margin: 0; display: flex; justify-content: center; align-items: center; padding: 20px; color: var(--text-dark); }
        .glass-container { background: var(--glass-bg); backdrop-filter: blur(15px); border-radius: 20px; border: 1px solid rgba(255, 255, 255, 0.6); box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.2); width: 100%; max-width: 600px; padding: 40px; position: relative; overflow: hidden; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { font-family: 'Amiri', serif; font-size: 2.2rem; color: #5c6bc0; margin: 0; }
        .header p { color: var(--text-light); font-size: 0.9rem; margin-top: 5px; }
        .invoice-type-selector { display: flex; justify-content: center; gap: 15px; margin-bottom: 30px; }
        .type-btn { background: rgba(255, 255, 255, 0.5); border: 1px solid rgba(255,255,255,0.8); padding: 10px 20px; border-radius: 30px; cursor: pointer; font-family: 'Tajawal', sans-serif; font-weight: 500; color: var(--text-dark); transition: all 0.3s ease; }
        .type-btn.active { background: var(--primary-color); color: white; border-color: var(--primary-color); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #5c6bc0; }
        .form-control { width: 100%; padding: 12px 15px; background: rgba(255, 255, 255, 0.5); border: 1px solid rgba(255, 255, 255, 0.8); border-radius: 12px; font-family: 'Tajawal', sans-serif; box-sizing: border-box; }
        .items-table { width: 100%; border-collapse: separate; border-spacing: 0 10px; margin-bottom: 20px; }
        .item-row td { padding: 5px; }
        .add-item-btn { background: transparent; border: none; color: var(--primary-color); font-family: 'Tajawal', sans-serif; font-weight: 500; cursor: pointer; display: flex; align-items: center; gap: 5px; margin-bottom: 20px; }
        .total-section { background: rgba(255, 255, 255, 0.6); padding: 20px; border-radius: 15px; margin-bottom: 30px; }
        .total-row.grand-total { font-size: 1.2rem; font-weight: 700; color: #5c6bc0; display: flex; justify-content: space-between; }
        .submit-btn { width: 100%; background: linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%); color: white; border: none; padding: 15px; border-radius: 15px; font-family: 'Tajawal', sans-serif; font-size: 1.1rem; font-weight: 700; cursor: pointer; box-shadow: 0 4px 15px rgba(251, 194, 235, 0.4); }
        .romantic-note { text-align: center; margin-top: 20px; font-family: 'Amiri', serif; font-style: italic; color: var(--text-light); }
        .toast { visibility: hidden; min-width: 250px; background-color: rgba(255, 255, 255, 0.9); color: #5c6bc0; text-align: center; border-radius: 15px; padding: 16px; position: fixed; z-index: 1000; bottom: 30px; left: 50%; transform: translateX(-50%); font-family: 'Tajawal', sans-serif; border: 1px solid rgba(255, 255, 255, 0.6); backdrop-filter: blur(10px); }
        .toast.show { visibility: visible; animation: fadein 0.5s, fadeout 0.5s 2.5s; }
        @keyframes fadein { from { bottom: 0; opacity: 0; } to { bottom: 30px; opacity: 1; } }
        @keyframes fadeout { from { bottom: 30px; opacity: 1; } to { bottom: 0; opacity: 0; } }
    </style>
</head>
<body>
    <div class="glass-container">
        <div class="header">
            <h1>شغف الأرقام ✨</h1>
            <p>نظام فواتير عالمي.. سجل إنجازاتك بكل حب</p>
        </div>
        <div class="invoice-type-selector">
            <button type="button" class="type-btn active" onclick="changeType('cash', 'نقداً', 'شكراً لتعاملكم الراقي.. أسعدتنا خدمتكم! 🌸')">نقداً</button>
            <button type="button" class="type-btn" onclick="changeType('credit', 'آجل', 'ميثاق ثقة.. بيننا وبينكم كل الود 🤝')">آجل</button>
            <button type="button" class="type-btn" onclick="changeType('return', 'مرتجع', 'رضاكم غايتنا.. نتشوق لخدمتكم مجدداً 🔄')">مرتجع</button>
        </div>
        <form id="invoiceForm">
            <div class="form-group">
                <label>اسم العميل العزيز</label>
                <input type="text" id="customerName" class="form-control" placeholder="أدخل اسم العميل أو الجهة..." required>
            </div>
            <table class="items-table" id="itemsTable">
                <tbody id="itemsBody">
                    <tr class="item-row">
                        <td style="width:50%"><input type="text" class="form-control item-name" placeholder="المنتج أو الخدمة..." required></td>
                        <td style="width:20%"><input type="number" class="form-control item-qty" value="1" min="1" oninput="calc()"></td>
                        <td style="width:30%"><input type="number" step="0.01" class="form-control item-price" placeholder="السعر" oninput="calc()" required></td>
                    </tr>
                </tbody>
            </table>
            <button type="button" class="add-item-btn" onclick="addItem()">+ إضافة بند آخر بحب</button>
            <div class="total-section">
                <div class="total-row grand-total">
                    <span>الإجمالي بابتسامة:</span>
                    <span id="grandTotalText">0 💰</span>
                </div>
            </div>
            <button type="submit" class="submit-btn" id="submitBtn">حفظ الفاتورة ✨</button>
            <div class="romantic-note" id="romanticNote">"شكراً لتعاملكم الراقي.. أسعدتنا خدمتكم! 🌸"</div>
        </form>
    </div>
    <div id="toastMessage" class="toast">تم حفظ الفاتورة بنجاح ✨</div>

    <script>
        let currentType = 'cash';
        let currentNote = 'شكراً لتعاملكم الراقي.. أسعدتنا خدمتكم! 🌸';

        function changeType(type, btnText, noteText) {
            currentType = type;
            currentNote = noteText;
            document.querySelectorAll('.type-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            document.getElementById('romanticNote').innerText = `"${noteText}"`;
        }

        function addItem() {
            const tbody = document.getElementById('itemsBody');
            const tr = document.createElement('tr');
            tr.className = 'item-row';
            tr.innerHTML = `
                <td><input type="text" class="form-control item-name" placeholder="المنتج أو الخدمة..." required></td>
                <td><input type="number" class="form-control item-qty" value="1" min="1" oninput="calc()"></td>
                <td><input type="number" step="0.01" class="form-control item-price" placeholder="السعر" oninput="calc()" required></td>
            `;
            tbody.appendChild(tr);
        }

        function calc() {
            let total = 0;
            document.querySelectorAll('.item-row').forEach(row => {
                const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
                const price = parseFloat(row.querySelector('.item-price').value) || 0;
                total += (qty * price);
            });
            // تنسيق الرقم ليكون عالمياً (بدون ذكر عملة محددة)
            document.getElementById('grandTotalText').innerText = total.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 2}) + ' 💰';
            return total;
        }

        document.getElementById('invoiceForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const total = calc();
            const payload = {
                customer_name: document.getElementById('customerName').value,
                type: currentType,
                total: currentType === 'return' ? -total : total,
                note: currentNote
            };

            try {
                const response = await fetch('/api/save', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await response.json();
                
                const toast = document.getElementById("toastMessage");
                toast.innerText = result.message;
                toast.className = "toast show";
                setTimeout(() => { toast.className = toast.className.replace("show", ""); }, 3000);
                
                document.getElementById('invoiceForm').reset();
                calc();
            } catch (err) { alert("حدث خطأ 💔 يرجى المحاولة مرة أخرى."); }
        });
    </script>
</body>
</html>
"""

# 🌸 معالج الطلبات (القلب النابض)
class GlobalRomanticHandler(http.server.SimpleHTTPRequestHandler):
    def do_GET(self):
        if self.path == '/':
            self.send_response(200)
            self.send_header('Content-type', 'text/html; charset=utf-8')
            self.end_headers()
            self.wfile.write(HTML_UI.encode('utf-8'))
        else:
            super().do_GET()
            
    def do_POST(self):
        if self.path == '/api/save':
            content_length = int(self.headers['Content-Length'])
            post_data = self.rfile.read(content_length)
            data = json.loads(post_data.decode('utf-8'))
            
            # حفظ في قاعدة البيانات بحب
            conn = sqlite3.connect('global_invoices.db')
            c = conn.cursor()
            c.execute("INSERT INTO invoices (customer_name, invoice_type, total_amount, sweet_note) VALUES (?, ?, ?, ?)",
                      (data.get('customer_name'), data.get('type'), data.get('total'), data.get('note')))
            conn.commit()
            conn.close()
            
            # الرد بنجاح
            self.send_response(200)
            self.send_header('Content-type', 'application/json; charset=utf-8')
            self.end_headers()
            response = {'status': 'success', 'message': 'تم حفظ الفاتورة بنجاح ✨'}
            self.wfile.write(json.dumps(response).encode('utf-8'))

# تشغيل السيرفر
init_db()
with socketserver.TCPServer(("", PORT), GlobalRomanticHandler) as httpd:
    print("="*40)
    print("🌸 تطبيق 'شغف الأرقام' العالمي يعمل الآن! 🌸")
    print("👉 افتح متصفحك (جوجل كروم) على هذا الرابط:")
    print(f"    http://localhost:{PORT}")
    print("="*40)
    print("(لإيقاف السيرفر، اضغط على زر خفض الصوت + حرف C في Termux)")
    httpd.serve_forever()
import http.server
import socketserver
import json
import sqlite3
import urllib.parse

PORT = 8080

# 🌸 إنشاء وتجهيز قاعدة البيانات تلقائياً
def init_db():
    conn = sqlite3.connect('love_invoices.db')
    c = conn.cursor()
    c.execute('''CREATE TABLE IF NOT EXISTS invoices (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        customer_name TEXT,
        invoice_type TEXT,
        total_amount REAL,
        sweet_note TEXT,
        date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )''')
    conn.commit()
    conn.close()

# 🌸 الواجهة الزجاجية الرومانسية (HTML & CSS & JS)
HTML_UI = """
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فاتورة تفتح النفس - هوت فون سيئون</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&family=Amiri:ital@0;1&display=swap" rel="stylesheet">
    <style>
        :root { --primary-color: #8fa0e0; --secondary-color: #ffb7b2; --background-gradient: linear-gradient(135deg, #e0c3fc 0%, #8ec5fc 100%); --glass-bg: rgba(255, 255, 255, 0.4); --text-dark: #4a4a4a; --text-light: #7a7a7a; }
        body { font-family: 'Tajawal', sans-serif; background-image: var(--background-gradient); min-height: 100vh; margin: 0; display: flex; justify-content: center; align-items: center; padding: 20px; color: var(--text-dark); }
        .glass-container { background: var(--glass-bg); backdrop-filter: blur(15px); border-radius: 20px; border: 1px solid rgba(255, 255, 255, 0.6); box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.2); width: 100%; max-width: 600px; padding: 40px; position: relative; overflow: hidden; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { font-family: 'Amiri', serif; font-size: 2.2rem; color: #5c6bc0; margin: 0; }
        .header p { color: var(--text-light); font-size: 0.9rem; margin-top: 5px; }
        .invoice-type-selector { display: flex; justify-content: center; gap: 15px; margin-bottom: 30px; }
        .type-btn { background: rgba(255, 255, 255, 0.5); border: 1px solid rgba(255,255,255,0.8); padding: 10px 20px; border-radius: 30px; cursor: pointer; font-family: 'Tajawal', sans-serif; font-weight: 500; color: var(--text-dark); transition: all 0.3s ease; }
        .type-btn.active { background: var(--primary-color); color: white; border-color: var(--primary-color); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #5c6bc0; }
        .form-control { width: 100%; padding: 12px 15px; background: rgba(255, 255, 255, 0.5); border: 1px solid rgba(255, 255, 255, 0.8); border-radius: 12px; font-family: 'Tajawal', sans-serif; box-sizing: border-box; }
        .items-table { width: 100%; border-collapse: separate; border-spacing: 0 10px; margin-bottom: 20px; }
        .item-row td { padding: 5px; }
        .add-item-btn { background: transparent; border: none; color: var(--primary-color); font-family: 'Tajawal', sans-serif; font-weight: 500; cursor: pointer; display: flex; align-items: center; gap: 5px; margin-bottom: 20px; }
        .total-section { background: rgba(255, 255, 255, 0.6); padding: 20px; border-radius: 15px; margin-bottom: 30px; }
        .total-row.grand-total { font-size: 1.2rem; font-weight: 700; color: #5c6bc0; display: flex; justify-content: space-between; }
        .submit-btn { width: 100%; background: linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%); color: white; border: none; padding: 15px; border-radius: 15px; font-family: 'Tajawal', sans-serif; font-size: 1.1rem; font-weight: 700; cursor: pointer; box-shadow: 0 4px 15px rgba(251, 194, 235, 0.4); }
        .romantic-note { text-align: center; margin-top: 20px; font-family: 'Amiri', serif; font-style: italic; color: var(--text-light); }
        .toast { visibility: hidden; min-width: 250px; background-color: rgba(255, 255, 255, 0.9); color: #5c6bc0; text-align: center; border-radius: 15px; padding: 16px; position: fixed; z-index: 1000; bottom: 30px; left: 50%; transform: translateX(-50%); font-family: 'Tajawal', sans-serif; border: 1px solid rgba(255, 255, 255, 0.6); backdrop-filter: blur(10px); }
        .toast.show { visibility: visible; animation: fadein 0.5s, fadeout 0.5s 2.5s; }
        @keyframes fadein { from { bottom: 0; opacity: 0; } to { bottom: 30px; opacity: 1; } }
        @keyframes fadeout { from { bottom: 30px; opacity: 1; } to { bottom: 0; opacity: 0; } }
    </style>
</head>
<body>
    <div class="glass-container">
        <div class="header">
            <h1>هوت فون سيئون 📱✨</h1>
            <p>سجل لحظات مبيعاتك بكل حب</p>
        </div>
        <div class="invoice-type-selector">
            <button type="button" class="type-btn active" onclick="changeType('cash', 'نقداً', 'شكراً لزيارتك.. أسعدتنا رؤيتك! 🌸')">نقداً</button>
            <button type="button" class="type-btn" onclick="changeType('credit', 'آجل', 'ميثاق ثقة.. بيننا وبينكم كل الود 🤝')">آجل</button>
            <button type="button" class="type-btn" onclick="changeType('return', 'مرتجع', 'رضاكم غايتنا.. نتشوق لخدمتكم مجدداً 🔄')">مرتجع</button>
        </div>
        <form id="invoiceForm">
            <div class="form-group">
                <label>اسم العميل العزيز</label>
                <input type="text" id="customerName" class="form-control" placeholder="أدخل اسم العميل..." required>
            </div>
            <table class="items-table" id="itemsTable">
                <tbody id="itemsBody">
                    <tr class="item-row">
                        <td style="width:50%"><input type="text" class="form-control item-name" placeholder="المنتج..." required></td>
                        <td style="width:20%"><input type="number" class="form-control item-qty" value="1" min="1" oninput="calc()"></td>
                        <td style="width:30%"><input type="number" class="form-control item-price" placeholder="السعر" oninput="calc()" required></td>
                    </tr>
                </tbody>
            </table>
            <button type="button" class="add-item-btn" onclick="addItem()">+ إضافة منتج آخر بحب</button>
            <div class="total-section">
                <div class="total-row grand-total">
                    <span>الإجمالي بابتسامة:</span>
                    <span id="grandTotalText">0 ريال يمني</span>
                </div>
            </div>
            <button type="submit" class="submit-btn" id="submitBtn">حفظ الفاتورة ✨</button>
            <div class="romantic-note" id="romanticNote">"شكراً لزيارتك.. أسعدتنا رؤيتك! 🌸"</div>
        </form>
    </div>
    <div id="toastMessage" class="toast">تم حفظ عربون المحبة بنجاح ✨</div>

    <script>
        let currentType = 'cash';
        let currentNote = 'شكراً لزيارتك.. أسعدتنا رؤيتك! 🌸';

        function changeType(type, btnText, noteText) {
            currentType = type;
            currentNote = noteText;
            document.querySelectorAll('.type-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            document.getElementById('romanticNote').innerText = `"${noteText}"`;
        }

        function addItem() {
            const tbody = document.getElementById('itemsBody');
            const tr = document.createElement('tr');
            tr.className = 'item-row';
            tr.innerHTML = `
                <td><input type="text" class="form-control item-name" placeholder="المنتج..." required></td>
                <td><input type="number" class="form-control item-qty" value="1" min="1" oninput="calc()"></td>
                <td><input type="number" class="form-control item-price" placeholder="السعر" oninput="calc()" required></td>
            `;
            tbody.appendChild(tr);
        }

        function calc() {
            let total = 0;
            document.querySelectorAll('.item-row').forEach(row => {
                const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
                const price = parseFloat(row.querySelector('.item-price').value) || 0;
                total += (qty * price);
            });
            document.getElementById('grandTotalText').innerText = total.toLocaleString('ar-YE') + ' ريال يمني';
            return total;
        }

        document.getElementById('invoiceForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const total = calc();
            const payload = {
                customer_name: document.getElementById('customerName').value,
                type: currentType,
                total: currentType === 'return' ? -total : total,
                note: currentNote
            };

            try {
                const response = await fetch('/api/save', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await response.json();
                
                const toast = document.getElementById("toastMessage");
                toast.innerText = result.message;
                toast.className = "toast show";
                setTimeout(() => { toast.className = toast.className.replace("show", ""); }, 3000);
                
                document.getElementById('invoiceForm').reset();
                calc();
            } catch (err) { alert("حدث خطأ 💔"); }
        });
    </script>
</body>
</html>
"""

# 🌸 معالج الطلبات (القلب النابض)
class RomanticHandler(http.server.SimpleHTTPRequestHandler):
    def do_GET(self):
        if self.path == '/':
            self.send_response(200)
            self.send_header('Content-type', 'text/html; charset=utf-8')
            self.end_headers()
            self.wfile.write(HTML_UI.encode('utf-8'))
        else:
            super().do_GET()
            
    def do_POST(self):
        if self.path == '/api/save':
            content_length = int(self.headers['Content-Length'])
            post_data = self.rfile.read(content_length)
            data = json.loads(post_data.decode('utf-8'))
            
            # حفظ في قاعدة البيانات بحب
            conn = sqlite3.connect('love_invoices.db')
            c = conn.cursor()
            c.execute("INSERT INTO invoices (customer_name, invoice_type, total_amount, sweet_note) VALUES (?, ?, ?, ?)",
                      (data.get('customer_name'), data.get('type'), data.get('total'), data.get('note')))
            conn.commit()
            conn.close()
            
            # الرد بنجاح
            self.send_response(200)
            self.send_header('Content-type', 'application/json; charset=utf-8')
            self.end_headers()
            response = {'status': 'success', 'message': 'تم حفظ عربون المحبة بنجاح ✨'}
            self.wfile.write(json.dumps(response).encode('utf-8'))

# تشغيل السيرفر
init_db()
with socketserver.TCPServer(("", PORT), RomanticHandler) as httpd:
    print("="*40)
    print("🌸 سيرفر الحب يعمل الآن بكل طاقة! 🌸")
    print("👉 افتح متصفحك (جوجل كروم) على هذا الرابط:")
    print(f"    http://localhost:{PORT}")
    print("="*40)
    print("(لإيقاف السيرفر، اضغط على زر خفض الصوت + حرف C في Termux)")
    httpd.serve_forever()
from flask import Flask, redirect, render_template_string, request
from flask_sqlalchemy import SQLAlchemy
import os

app = Flask(__name__)
# تحديد مسار قاعدة البيانات
db_path = os.path.join(os.path.dirname(__file__), 'accounting.db')
app.config["SQLALCHEMY_DATABASE_URI"] = f"sqlite:///{db_path}"
app.config["SQLALCHEMY_TRACK_MODIFICATIONS"] = False
db = SQLAlchemy(app)


# تعريف جدول الحسابات
class Account(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(100), nullable=False)
    amount = db.Column(db.Float, nullable=False)

# إنشاء قاعدة البيانات إذا لم تكن موجودة
with app.app_context():
    db.create_all()

# --- كود الواجهة الرومانسية والفنية الجديدة (CSS & HTML) ---
html_template = """
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>دفتر العمر 💖</title>
    <!-- استخدام خطوط عربية جميلة من Google Fonts (Tajawal) -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        /* --- المتغيرات اللونية (Palette) --- */
        :root {
            --primary-color: #ff758c; /* الوردي الرومانسي */
            --secondary-color: #867ae9; /* الموف الملكي */
            --accent-color: #ffeb3b;  /* الأصفر الذهبي للأزرار */
            --bg-color: #fff5f8;      /* خلفية وردية فاتحة جداً */
            --text-color: #5d4037;    /* بني دافئ للنص */
            --card-bg: rgba(255, 255, 255, 0.95); /* خلفية بيضاء شفافة للبطاقات */
            --shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
        }

        body {
            font-family: 'Tajawal', sans-serif;
            background-image: linear-gradient(135deg, var(--bg-color) 0%, #e0c3fc 100%);
            background-attachment: fixed;
            color: var(--text-color);
            margin: 0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
        }

        /* عنوان الصفحة الفني */
        h1.art-title {
            font-size: 3em;
            color: #fff;
            text-shadow: 2px 4px 6px rgba(0,0,0,0.3);
            margin-bottom: 10px;
            font-weight: 700;
            position: relative;
        }
        h1.art-title::after {
            content: '🌸✨';
            font-size: 0.5em;
            position: absolute;
            top: -10px;
            right: -30px;
        }

        /* حاوية النموذج والجدول */
        .container {
            width: 100%;
            max-width: 600px;
        }

        /* تصميم النموذج (Form) */
        form {
            background: var(--card-bg);
            padding: 25px;
            margin-bottom: 30px;
            border-radius: 20px;
            box-shadow: var(--shadow);
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            position: relative;
            overflow: hidden;
        }
        form::before {
            content: '';
            position: absolute;
            top: 0; right: 0; width: 100%; height: 5px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }

        h2.form-title {
            color: var(--primary-color);
            margin-top: 0;
            text-align: center;
            font-weight: 600;
        }

        label {
            font-weight: 500;
            margin-bottom: 5px;
            display: block;
            color: var(--secondary-color);
        }

        input {
            padding: 12px;
            margin-bottom: 15px;
            border: 2px solid #f0d9e7;
            border-radius: 10px;
            font-size: 16px;
            width: 100%;
            box-sizing: border-box;
            transition: all 0.3s;
            font-family: 'Tajawal', sans-serif;
        }
        input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 10px rgba(255, 117, 140, 0.2);
        }

        button.art-btn {
            background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
            color: var(--text-color);
            border: none;
            border-radius: 50px; /* زر دائري ولطيف */
            padding: 12px 30px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(255, 117, 140, 0.4);
            transition: all 0.3s;
            width: auto;
            display: block;
            margin: 0 auto; /* توسيط الزر */
            font-family: 'Tajawal', sans-serif;
        }
        button.art-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(255, 117, 140, 0.6);
        }

        /* تصميم الجدول (Table) */
        h3.table-title {
            color: var(--secondary-color);
            text-align: center;
            margin-bottom: 15px;
            font-weight: 600;
        }

        table {
            width: 100%;
            background: var(--card-bg);
            border-collapse: separate;
            border-spacing: 0 10px; /* مسافة بين الصفوف */
            border-radius: 20px;
            /* لا نستخدم shadow العادي هنا، بل نستخدم shadow للصفوف */
        }

        th, td {
            padding: 15px;
            text-align: right;
            border: none;
        }
        th {
            background-color: var(--secondary-color);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        th:first-child { border-radius: 0 15px 15px 0; }
        th:last-child { border-radius: 15px 0 0 15px; }

        tr.table-row {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
            border-radius: 15px;
        }
        tr.table-row:hover {
            transform: scale(1.02); /* تكبير لطيف عند المرور بالماوس */
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        td:first-child { border-radius: 0 15px 15px 0; color: var(--secondary-color); font-weight: bold; }
        td:last-child { border-radius: 15px 0 0 15px; color: var(--primary-color); font-weight: bold;}

        /* تنسيق القيم السالبة (الدائن) والموجبة (المدين) */
        td.amount-cell {
            font-weight: bold;
        }

    </style>
</head>
<body>
    
    <h1 class="art-title">دفتر المحاسبة الرومانسي</h1>

    <div class="container">
        <!-- النموذج (Form) -->
        <form action="/add" method="POST">
            <h2 class="form-title">أضف قيداً جديداً</h2>
            
            <label for="name">اسم العميل / البيان:</label>
            <input type="text" id="name" name="name" required placeholder="اكتب اسم الحب أو العميل...">
            
            <label for="amount">المبلغ (موجب للمدين، سالب للدائن):</label>
            <input type="number" step="any" id="amount" name="amount" required placeholder="مثال: 500 أو -200">
            
            <button type="submit" class="art-btn">قيد العملية 💖</button>
        </form>

        <!-- الجدول (Table) -->
        <h3 class="table-title">سجل الحسابات</h3>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>البيان</th>
                    <th>المبلغ</th>
                </tr>
            </thead>
            <tbody>
                {% for acc in accounts %}
                <tr class="table-row">
                    <td>{{ acc.id }}</td>
                    <td>{{ acc.name }}</td>
                    <td class="amount-cell">{{ acc.amount }}</td>
                </tr>
                {% endfor %}
            </tbody>
        </table>
    </div>
</body>
</html>
"""

# --- مسارات التطبيق (Routes) ---

@app.route("/")
def index():
    accounts = Account.query.all()
    return render_template_string(html_template, accounts=accounts)

@app.route("/add", methods=["POST"])
def add_account():
    name = request.form.get("name")
    try:
        amount = float(request.form.get("amount"))
        if name:
            new_acc = Account(name=name, amount=amount)
            db.session.add(new_acc)
            db.session.commit()
    except ValueError:
        pass # تجاهل الإدخال إذا لم يكن المبلغ رقماً صحيحاً
    return redirect("/")

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000)
from flask import Flask, redirect, render_template_string, request
from flask_sqlalchemy import SQLAlchemy

app = Flask(__name__)
app.config["SQLALCHEMY_DATABASE_URI"] = "sqlite:///accounting.db"
db = SQLAlchemy(app)


# تعريف جدول الحسابات أو العملاء في قاعدة البيانات
class Account(db.Model):
  id = db.Column(db.Integer, primary_key=True)
  name = db.Column(db.String(100), nullable=False)
  amount = db.Column(db.Float, nullable=False)


with app.app_context():
  db.create_all()

# تصميم الصفحة الرئيسية مع جدول العرض ونموذج الإضافة
html_template = """
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>نظام المحاسبة المصغر</title>
    <style>
        body { font-family: Tahoma, sans-serif; background: #f4f4f9; padding: 20px; text-align: right; }
        h2 { color: #333; }
        form { background: #fff; padding: 15px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        input, button { padding: 10px; margin: 5px 0; font-size: 16px; width: 100%; box-sizing: border-box; }
        button { background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #218838; }
        table { width: 100%; background: #fff; border-collapse: collapse; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        th, td { padding: 12px; border-bottom: 1px solid #ddd; text-align: right; }
        th { background: #007bff; color: white; }
    </style>
</head>
<body>
    <h2>نظام المحاسبة وإدارة الحسابات 📊</h2>
    
    <form action="/add" method="POST">
        <label>اسم الحساب / العميل:</label>
        <input type="text" name="name" required placeholder="أدخل الاسم">
        
        <label>المبلغ (مدين/دائن):</label>
        <input type="number" step="any" name="amount" required placeholder="أدخل المبلغ">
        
        <button type="submit">إضافة الحساب</button>
    </form>

    <h3>سجل الحسابات:</h3>
    <table>
        <tr>
            <th>الرقم</th>
            <th>اسم الحساب</th>
            <th>المبلغ</th>
        </tr>
        {% for acc in accounts %}
        <tr>
            <td>{{ acc.id }}</td>
            <td>{{ acc.name }}</td>
            <td>{{ acc.amount }}</td>
        </tr>
        {% endfor %}
    </table>
</body>
</html>
"""


@app.route("/")
def index():
  accounts = Account.query.all()
  return render_template_string(html_template, accounts=accounts)


@app.route("/add", methods=["POST"])
def add_account():
  name = request.form.get("name")
  amount = float(request.form.get("amount"))
  new_acc = Account(name=name, amount=amount)
  db.session.add(new_acc)
  db.session.commit()
  return redirect("/")


if __name__ == "__main__":
  app.run(host="0.0.0.0", port=5000)
from flask import Flask

app = Flask(__name__)


@app.route("/")
def home():
    return "مرحباً بك! نظام المحاسبة يعمل بنجاح على هاتفك 🚀"


if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000)

