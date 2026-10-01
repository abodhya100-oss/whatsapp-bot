from flask import Flask, render_template_string, request, redirect, send_file
import sqlite3
import os

app = Flask(__name__)

def init_db():
    conn = sqlite3.connect('accounting_exact.db')
    cursor = conn.cursor()
    # جدول الحركات المالية
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS entries (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            category TEXT,
            amount REAL,
            details TEXT,
            date TEXT DEFAULT CURRENT_TIMESTAMP
        )
    ''')
    # جدول الإعدادات
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            app_name TEXT,
            address TEXT,
            mobile TEXT,
            tax_number TEXT,
            sync_type TEXT
        )
    ''')
    # جدول الأصناف
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT,
            price REAL,
            quantity INTEGER
        )
    ''')
    # جدول المخازن
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS warehouses (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT,
            location TEXT
        )
    ''')
    
    cursor.execute("SELECT COUNT(*) FROM settings")
    if cursor.fetchone()[0] == 0:
        cursor.execute("INSERT INTO settings (app_name, address, mobile, tax_number, sync_type) VALUES (?, ?, ?, ?, ?)",
                       ("نظام محاسبي متكامل", "الجمهورية اليمنية - صنعاء", "000000000 967", "", "المحلى"))
    conn.commit()
    conn.close()

init_db()

UI_TEMPLATE = '''
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>{{ settings[1] }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Tahoma, sans-serif; background: #f1f5f9; margin: 0; padding: 0; color: #1e293b; }
        .header { background: #1d4ed8; color: white; padding: 12px 15px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header h2 { margin: 0; font-size: 18px; font-weight: bold; }
        .top-icons { display: flex; gap: 15px; font-size: 18px; }
        
        .sidebar { height: 100%; width: 0; position: fixed; z-index: 1000; top: 0; right: 0; background-color: #ffffff; overflow-x: hidden; transition: 0.3s; box-shadow: -2px 0 10px rgba(0,0,0,0.2); text-align: right; }
        .sidebar-header { background: #1d4ed8; color: white; padding: 15px; font-size: 16px; font-weight: bold; display: flex; justify-content: space-between; align-items: center; }
        .sidebar a { padding: 12px 15px; text-decoration: none; font-size: 14px; color: #1e293b; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; }
        .sidebar a:hover { background-color: #f8fafc; color: #2563eb; }
        .close-btn { font-size: 22px; cursor: pointer; color: white; }

        .actions-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; padding: 10px; background: #1e40af; }
        .action-btn { background: #2563eb; color: white; border: 1px solid #60a5fa; border-radius: 6px; padding: 10px 4px; text-align: center; font-size: 13px; font-weight: bold; cursor: pointer; text-decoration: none; box-sizing: border-box; }
        .action-btn.purple { background: #7c3aed; border-color: #a78bfa; }
        
        .cards-container { padding: 10px; max-width: 500px; margin: auto; }
        .main-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 60px; }
        .card { background: white; border: 2px solid #93c5fd; border-radius: 12px; padding: 12px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.05); cursor: pointer; }
        .card-icon { font-size: 24px; margin-bottom: 5px; }
        .badge { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; border-radius: 50%; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; margin: 0 auto 5px auto; font-size: 12px; font-weight: bold; }
        .card-title { font-size: 14px; font-weight: bold; color: #0f172a; margin-bottom: 5px; }
        .card-sub { font-size: 13px; color: #dc2626; font-weight: bold; }

        .footer-bar { position: fixed; bottom: 0; left: 0; right: 0; background: #1d4ed8; color: white; display: grid; grid-template-columns: repeat(3, 1fr); padding: 10px 5px; text-align: center; border-top: 2px solid #60a5fa; font-size: 12px; font-weight: bold; z-index: 50; }
        .footer-bar span { font-size: 14px; color: #f8fafc; }

        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1100; }
        .modal-content { background: white; padding: 20px; border-radius: 10px; width: 90%; max-width: 350px; text-align: right; }
        .modal-content input { width: 100%; padding: 8px; margin: 8px 0; border: 1px solid #cbd5e1; border-radius: 5px; box-sizing: border-box; }
        .modal-btn { background: #2563eb; color: white; border: none; padding: 10px; width: 100%; border-radius: 5px; font-weight: bold; cursor: pointer; }
    </style>
</head>
<body>

    <div class="header">
        <div style="cursor:pointer;" onclick="openSidebar()">☰</div>
        <h2>{{ settings[1] }}</h2>
        <div class="top-icons">🌙 🔍</div>
    </div>

    <div id="mySidebar" class="sidebar">
        <div class="sidebar-header">
            <span>{{ settings[1] }}</span>
            <span class="close-btn" onclick="closeSidebar()">&times;</span>
        </div>
        <a href="/settings">خيارات التطبيق (الإعدادات) <span>⚙️</span></a>
        <a href="/items_page">ملف الأصناف <span>📖</span></a>
        <a href="/warehouses_page">ملف المخازن <span>📦</span></a>
        <a href="/income_statement">قائمة الدخل - الأرباح <span>📑</span></a>
        <a href="/backup">حفظ نسخة احتياطية <span>📥</span></a>
        <a href="#" onclick="alert('النظام يعمل بكامل الصلاحيات المحلية')">دليل الحسابات <span>📋</span></a>
        <a href="#" onclick="alert('الدعم الفني: ' + '{{ settings[3] }}')">التواصل والدعم <span>📞</span></a>
    </div>

    <div class="actions-grid">
        <a href="#" class="action-btn" onclick="openModal('سند صرف/قبض')">سند صرف/قبض</a>
        <a href="#" class="action-btn" onclick="openModal('سند جديد')">سند جديد</a>
        <a href="#" class="action-btn" onclick="openModal('قيد متعدد')">قيد متعدد</a>
        <a href="#" class="action-btn purple" onclick="openModal('فاتورة جديده')">فاتورة جديده</a>
        <a href="#" class="action-btn purple" onclick="openModal('حواله جديده')">حواله جديده</a>
        <a href="#" class="action-btn purple" onclick="openModal('عروض/طلبيات')">عروض/طلبيات</a>
        <a href="#" class="action-btn" onclick="openModal('كشف حساب')">كشف حساب</a>
        <a href="#" class="action-btn" onclick="openModal('صرف عملات')">صرف عملات</a>
        <a href="#" class="action-btn" onclick="openModal('مراجعة الحركات')">مراجعة الحركات</a>
    </div>

    <div class="cards-container">
        <div class="main-grid">
            <div class="card" onclick="openModal('الموردين')">
                <div class="card-icon">🚚</div>
                <div class="badge">{{ counts.get('الموردين', 0) }}</div>
                <div class="card-title">الموردين</div>
                <div class="card-sub">{{ totals.get('الموردين', 0.0) }}</div>
            </div>
            <div class="card" onclick="openModal('العملاء')">
                <div class="card-icon">👥</div>
                <div class="badge">{{ counts.get('العملاء', 0) }}</div>
                <div class="card-title">العملاء</div>
                <div class="card-sub">{{ totals.get('العملاء', 0.0) }}</div>
            </div>
            <div class="card" onclick="openModal('الموظفين')">
                <div class="card-icon">🏢</div>
                <div class="badge">{{ counts.get('الموظفين', 0) }}</div>
                <div class="card-title">الموظفين</div>
                <div class="card-sub">{{ totals.get('الموظفين', 0.0) }}</div>
            </div>
            <div class="card" onclick="openModal('المصاريف')">
                <div class="card-icon">✂️</div>
                <div class="badge">{{ counts.get('المصاريف', 0) }}</div>
                <div class="card-title">المصاريف</div>
                <div class="card-sub">{{ totals.get('المصاريف', 0.0) }}</div>
            </div>
            <div class="card" onclick="openModal('المشتريات')">
                <div class="card-icon">🛒</div>
                <div class="badge">{{ counts.get('المشتريات', 0) }}</div>
                <div class="card-title">المشتريات</div>
                <div class="card-sub">{{ totals.get('المشتريات', 0.0) }}</div>
            </div>
            <div class="card" onclick="openModal('المبيعات')">
                <div class="card-icon">📈</div>
                <div class="badge">{{ counts.get('المبيعات', 0) }}</div>
                <div class="card-title">المبيعات</div>
                <div class="card-sub">{{ totals.get('المبيعات', 0.0) }}</div>
            </div>
            <div class="card" onclick="openModal('الصناديق والبنوك')">
                <div class="card-icon">🏦</div>
                <div class="badge">{{ counts.get('الصناديق والبنوك', 0) }}</div>
                <div class="card-title">الصناديق والبنوك</div>
                <div class="card-sub">{{ totals.get('الصناديق والبنوك', 0.0) }}</div>
            </div>
            <div class="card" onclick="openModal('الأصول والإيرادات')">
                <div class="card-icon">🏛️</div>
                <div class="badge">{{ counts.get('الأصول والإيرادات', 0) }}</div>
                <div class="card-title">الأصول والإيرادات</div>
                <div class="card-sub">{{ totals.get('الأصول والإيرادات', 0.0) }}</div>
            </div>
        </div>
    </div>

    <div class="modal" id="entryModal">
        <div class="modal-content">
            <h3 id="modalTitle" style="color: #1d4ed8; margin-top:0;">إضافة حركة</h3>
            <form action="/add" method="POST">
                <input type="hidden" name="category" id="modalCategory">
                <label>المبلغ:</label>
                <input type="number" step="0.01" name="amount" placeholder="0.00" required>
                <label>البيان / التفاصيل:</label>
                <input type="text" name="details" placeholder="التفاصيل...">
                <button type="submit" class="modal-btn">حفظ</button>
                <button type="button" onclick="closeModal()" style="background:#64748b; color:white; border:none; padding:8px; width:100%; border-radius:5px; margin-top:5px; cursor:pointer;">إلغاء</button>
            </form>
        </div>
    </div>

    <div class="footer-bar">
        <div>الإيرادات<br><span>{{ total_revenues }}</span></div>
        <div>المصروفات<br><span>{{ total_expenses }}</span></div>
        <div>صافي الأرباح<br><span>{{ net_profit }}</span></div>
    </div>

    <script>
        function openSidebar() { document.getElementById("mySidebar").style.width = "280px"; }
        function closeSidebar() { document.getElementById("mySidebar").style.width = "0"; }
        function openModal(category) {
            document.getElementById('modalCategory').value = category;
            document.getElementById('modalTitle').innerText = 'تسجيل في: ' + category;
            document.getElementById('entryModal').style.display = 'flex';
        }
        function closeModal() { document.getElementById('entryModal').style.display = 'none'; }
    </script>
</body>
</html>
'''

SETTINGS_TEMPLATE = '''
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إعدادات التطبيق</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Tahoma, sans-serif; background: #f1f5f9; margin: 0; padding: 10px; color: #1e293b; text-align: right; }
        .top-bar { background: #1d4ed8; color: white; padding: 12px 15px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .top-bar a { color: white; text-decoration: none; font-weight: bold; }
        form { background: white; padding: 15px; border-radius: 10px; max-width: 500px; margin: auto; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        label { display: block; margin-top: 10px; font-weight: bold; font-size: 13px; color: #1e3a8a; }
        input[type="text"] { width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #cbd5e1; border-radius: 5px; box-sizing: border-box; }
        .section-box { background: #f8fafc; border: 1px solid #e2e8f0; padding: 10px; border-radius: 6px; margin-top: 10px; font-size: 13px; }
        .btn-save { background: #2563eb; color: white; border: none; padding: 12px; width: 100%; border-radius: 6px; font-weight: bold; margin-top: 15px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="top-bar">
        <span>تحديث التطبيق VS.1633</span>
        <a href="/">الرئيسية 🏠</a>
    </div>

    <form action="/save_settings" method="POST">
        <div class="section-box">
            <strong>تفعيل التطبيق:</strong> نشط ومفعل كلياً
        </div>
        <label>اسم المنشأة:</label>
        <input type="text" name="app_name" value="{{ s[1] }}">
        <label>العنوان:</label>
        <input type="text" name="address" value="{{ s[2] }}">
        <label>رقم الجوال:</label>
        <input type="text" name="mobile" value="{{ s[3] }}">
        <label>الرقم الضريبي:</label>
        <input type="text" name="tax_number" value="{{ s[4] }}">
        <button type="submit" class="btn-save">حفظ الإعدادات</button>
    </form>
</body>
</html>
'''

ITEMS_TEMPLATE = '''
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>ملف الأصناف</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Tahoma, sans-serif; background: #f1f5f9; padding: 10px; text-align: right; }
        .top-bar { background: #1d4ed8; color: white; padding: 12px 15px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; }
        .top-bar a { color: white; text-decoration: none; font-weight: bold; }
        form, .list { background: white; padding: 15px; border-radius: 10px; max-width: 500px; margin: 15px auto; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        input { width: 100%; padding: 8px; margin: 5px 0 10px 0; border: 1px solid #cbd5e1; border-radius: 5px; box-sizing: border-box; }
        .btn { background: #2563eb; color: white; border: none; padding: 10px; width: 100%; border-radius: 5px; font-weight: bold; cursor: pointer; }
        .item-row { border-bottom: 1px solid #e2e8f0; padding: 8px 0; display: flex; justify-content: space-between; }
    </style>
</head>
<body>
    <div class="top-bar">
        <span>ملف الأصناف</span>
        <a href="/">الرئيسية 🏠</a>
    </div>

    <form action="/add_item" method="POST">
        <h3>إضافة صنف جديد</h3>
        <label>اسم الصنف:</label>
        <input type="text" name="name" required>
        <label>السعر:</label>
        <input type="number" step="0.01" name="price" required>
        <label>الكمية:</label>
        <input type="number" name="quantity" required>
        <button type="submit" class="btn">حفظ الصنف</button>
    </form>

    <div class="list">
        <h3>قائمة الأصناف المسجلة</h3>
        {% for item in items %}
            <div class="item-row">
                <span><b>{{ item[1] }}</b> (الكمية: {{ item[3] }})</span>
                <span style="color: #2563eb; font-weight: bold;">{{ item[2] }} ر.ي</span>
            </div>
        {% endfor %}
    </div>
</body>
</html>
'''

WAREHOUSES_TEMPLATE = '''
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>ملف المخازن</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Tahoma, sans-serif; background: #f1f5f9; padding: 10px; text-align: right; }
        .top-bar { background: #1d4ed8; color: white; padding: 12px 15px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; }
        .top-bar a { color: white; text-decoration: none; font-weight: bold; }
        form, .list { background: white; padding: 15px; border-radius: 10px; max-width: 500px; margin: 15px auto; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        input { width: 100%; padding: 8px; margin: 5px 0 10px 0; border: 1px solid #cbd5e1; border-radius: 5px; box-sizing: border-box; }
        .btn { background: #2563eb; color: white; border: none; padding: 10px; width: 100%; border-radius: 5px; font-weight: bold; cursor: pointer; }
        .item-row { border-bottom: 1px solid #e2e8f0; padding: 8px 0; }
    </style>
</head>
<body>
    <div class="top-bar">
        <span>ملف المخازن</span>
        <a href="/">الرئيسية 🏠</a>
    </div>

    <form action="/add_warehouse" method="POST">
        <h3>إضافة مخزن جديد</h3>
        <label>اسم المخزن:</label>
        <input type="text" name="name" required>
        <label>الموقع:</label>
        <input type="text" name="location" required>
        <button type="submit" class="btn">حفظ المخزن</button>
    </form>

    <div class="list">
        <h3>المخازن المسجلة</h3>
        {% for wh in warehouses %}
            <div class="item-row">
                <b>{{ wh[1] }}</b> - الموقع: {{ wh[2] }}
            </div>
        {% endfor %}
    </div>
</body>
</html>
'''

PROFIT_TEMPLATE = '''
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>قائمة الدخل والأرباح</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Tahoma, sans-serif; background: #f1f5f9; padding: 10px; text-align: right; }
        .top-bar { background: #1d4ed8; color: white; padding: 12px 15px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; }
        .top-bar a { color: white; text-decoration: none; font-weight: bold; }
        .box { background: white; padding: 20px; border-radius: 10px; max-width: 500px; margin: 15px auto; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e2e8f0; font-weight: bold; }
    </style>
</head>
<body>
    <div class="top-bar">
        <span>قائمة الدخل - الأرباح</span>
        <a href="/">الرئيسية 🏠</a>
    </div>
    <div class="box">
        <div class="row" style="color: #16a34a;">
            <span>إجمالي الإيرادات:</span>
            <span>{{ total_rev }} ر.ي</span>
        </div>
        <div class="row" style="color: #dc2626;">
            <span>إجمالي المصروفات والمشتريات:</span>
            <span>{{ total_exp }} ر.ي</span>
        </div>
        <div class="row" style="font-size: 18px; color: #1d4ed8; border-bottom: none;">
            <span>صافي الربح الصافي:</span>
            <span>{{ net }} ر.ي</span>
        </div>
    </div>
</body>
</html>
'''

@app.route('/')
def index():
    conn = sqlite3.connect('accounting_exact.db')
    cursor = conn.cursor()
    cursor.execute("SELECT category, COUNT(*) FROM entries GROUP BY category")
    counts = {row[0]: row[1] for row in cursor.fetchall()}
    cursor.execute("SELECT category, SUM(amount) FROM entries GROUP BY category")
    totals = {row[0]: row[1] for row in cursor.fetchall()}
    cursor.execute("SELECT * FROM settings WHERE id = 1")
    settings = cursor.fetchone()
    conn.close()
    
    sales = totals.get('المبيعات', 0.0) + totals.get('الأصول والإيرادات', 0.0)
    expenses = totals.get('المصاريف', 0.0) + totals.get('المشتريات', 0.0)
    net_profit = sales - expenses

    return render_template_string(UI_TEMPLATE, counts=counts, totals=totals, 
                                  total_revenues=sales, total_expenses=expenses, net_profit=net_profit, settings=settings)

@app.route('/settings')
def settings():
    conn = sqlite3.connect('accounting_exact.db')
    cursor = conn.cursor()
    cursor.execute("SELECT * FROM settings WHERE id = 1")
    s = cursor.fetchone()
    conn.close()
    return render_template_string(SETTINGS_TEMPLATE, s=s)

@app.route('/save_settings', methods=['POST'])
def save_settings():
    app_name = request.form['app_name']
    address = request.form['address']
    mobile = request.form['mobile']
    tax_number = request.form['tax_number']

    conn = sqlite3.connect('accounting_exact.db')
    cursor = conn.cursor()
    cursor.execute("UPDATE settings SET app_name=?, address=?, mobile=?, tax_number=? WHERE id=1",
                   (app_name, address, mobile, tax_number))
    conn.commit()
    conn.close()
    return redirect('/')

@app.route('/items_page')
def items_page():
    conn = sqlite3.connect('accounting_exact.db')
    cursor = conn.cursor()
    cursor.execute("SELECT * FROM items")
    items = cursor.fetchall()
    conn.close()
    return render_template_string(ITEMS_TEMPLATE, items=items)

@app.route('/add_item', methods=['POST'])
def add_item():
    name = request.form['name']
    price = float(request.form['price'])
    quantity = int(request.form['quantity'])
    conn = sqlite3.connect('accounting_exact.db')
    cursor = conn.cursor()
    cursor.execute("INSERT INTO items (name, price, quantity) VALUES (?, ?, ?)", (name, price, quantity))
    conn.commit()
    conn.close()
    return redirect('/items_page')

@app.route('/warehouses_page')
def warehouses_page():
    conn = sqlite3.connect('accounting_exact.db')
    cursor = conn.cursor()
    cursor.execute("SELECT * FROM warehouses")
    warehouses = cursor.fetchall()
    conn.close()
    return render_template_string(WAREHOUSES_TEMPLATE, warehouses=warehouses)

@app.route('/add_warehouse', methods=['POST'])
def add_warehouse():
    name = request.form['name']
    location = request.form['location']
    conn = sqlite3.connect('accounting_exact.db')
    cursor = conn.cursor()
    cursor.execute("INSERT INTO warehouses (name, location) VALUES (?, ?)", (name, location))
    conn.commit()
    conn.close()
    return redirect('/warehouses_page')

@app.route('/income_statement')
def income_statement():
    conn = sqlite3.connect('accounting_exact.db')
    cursor = conn.cursor()
    cursor.execute("SELECT category, SUM(amount) FROM entries GROUP BY category")
    totals = {row[0]: row[1] for row in cursor.fetchall()}
    conn.close()
    sales = totals.get('المبيعات', 0.0) + totals.get('الأصول والإيرادات', 0.0)
    expenses = totals.get('المصاريف', 0.0) + totals.get('المشتريات', 0.0)
    net = sales - expenses
    return render_template_string(PROFIT_TEMPLATE, total_rev=sales, total_exp=expenses, net=net)

@app.route('/backup')
def backup():
    db_path = 'accounting_exact.db'
    if os.path.exists(db_path):
        return send_file(db_path, as_attachment=True)
    return "قاعدة البيانات غير موجودة"

@app.route('/add', methods=['POST'])
def add():
    category = request.form['category']
    try:
        amount = float(request.form['amount'])
    except ValueError:
        amount = 0.0
    details = request.form['details']
    
    conn = sqlite3.connect('accounting_exact.db')
    cursor = conn.cursor()
    cursor.execute("INSERT INTO entries (category, amount, details) VALUES (?, ?, ?)", (category, amount, details))
    conn.commit()
    conn.close()
    return redirect('/')

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000)
