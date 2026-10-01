<?php
$db_file = '/data/data/com.termux/files/home/secure_dbs/vmax.db';
if(file_exists($db_file)) {
    unlink($db_file);
}
if(session_status()===PHP_SESSION_NONE){session_start();} if(!isset($_SESSION["user_role"])){header("Location: login.php");exit;}
$db = new SQLite3($db_file);

// إنشاء الجداول الأساسية فارغة ونظيفة
$db->exec("CREATE TABLE IF NOT EXISTS customers (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, phone TEXT, balance REAL DEFAULT 0, latitude REAL DEFAULT 0, longitude REAL DEFAULT 0, status TEXT DEFAULT 'نشط', type TEXT DEFAULT 'تجزئة', region TEXT DEFAULT 'السوق العام', credit_limit REAL DEFAULT 0, notes TEXT)");
$db->exec("CREATE TABLE IF NOT EXISTS products (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, buy_price REAL DEFAULT 0, sell_price REAL DEFAULT 0, stock REAL DEFAULT 0, unit TEXT DEFAULT 'حبة', alt1_unit TEXT, alt1_factor REAL DEFAULT 1, alt1_price REAL DEFAULT 0, alt2_unit TEXT, alt2_factor REAL DEFAULT 1, alt2_price REAL DEFAULT 0, barcode TEXT, category TEXT DEFAULT 'عام', image TEXT)");
$db->exec("CREATE TABLE IF NOT EXISTS invoices (id INTEGER PRIMARY KEY AUTOINCREMENT, customer_id INTEGER, total REAL DEFAULT 0, paid REAL DEFAULT 0, type TEXT DEFAULT 'مبيعات', date TEXT)");
$db->exec("CREATE TABLE IF NOT EXISTS invoice_items (id INTEGER PRIMARY KEY AUTOINCREMENT, invoice_id INTEGER, product_id INTEGER, qty REAL, price REAL)");
$db->exec("CREATE TABLE IF NOT EXISTS returns (id INTEGER PRIMARY KEY AUTOINCREMENT, customer_id INTEGER, product_id INTEGER, qty REAL, total REAL, reason TEXT, date TEXT)");
$db->exec("CREATE TABLE IF NOT EXISTS expenses (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, amount REAL, date TEXT)");
$db->exec("CREATE TABLE IF NOT EXISTS suppliers (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, phone TEXT, balance REAL DEFAULT 0, notes TEXT)");
$db->exec("CREATE TABLE IF NOT EXISTS purchases (id INTEGER PRIMARY KEY AUTOINCREMENT, supplier_id INTEGER, product_id INTEGER, qty REAL, total REAL, date TEXT)");
$db->exec("CREATE TABLE IF NOT EXISTS receipts (id INTEGER PRIMARY KEY AUTOINCREMENT, type TEXT, target_name TEXT, amount REAL, notes TEXT, date TEXT)");
$db->exec("CREATE TABLE IF NOT EXISTS installments (id INTEGER PRIMARY KEY AUTOINCREMENT, customer_id INTEGER, amount REAL, due_date TEXT, status TEXT DEFAULT 'معلق', notes TEXT, date TEXT)");
$db->exec("CREATE TABLE IF NOT EXISTS staff (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, phone TEXT, salary REAL DEFAULT 0, notes TEXT)");
$db->exec("CREATE TABLE IF NOT EXISTS staff_advances (id INTEGER PRIMARY KEY AUTOINCREMENT, staff_id INTEGER, amount REAL, notes TEXT, date TEXT)");
$db->exec("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT UNIQUE, password TEXT, role TEXT DEFAULT 'cashier', name TEXT)");

// إضافة حساب مدير افتراضي نظيف
$db->exec("INSERT INTO users (username, password, role, name) VALUES ('admin', '123456', 'admin', 'المدير العام');");

$db->exec("CREATE TABLE IF NOT EXISTS audit_logs (id INTEGER PRIMARY KEY AUTOINCREMENT, user_name TEXT, action TEXT, details TEXT, date TEXT)");
$db->exec("CREATE TABLE IF NOT EXISTS customer_subscriptions (id INTEGER PRIMARY KEY AUTOINCREMENT, customer_id INTEGER, sub_name TEXT, price REAL DEFAULT 0, start_date TEXT, end_date TEXT, status TEXT DEFAULT 'ساري')");
$db->exec("CREATE TABLE IF NOT EXISTS settings (id INTEGER PRIMARY KEY AUTOINCREMENT, key_name TEXT UNIQUE, key_value TEXT)");

$db->close();
echo "✅ تمت عملية تصفير البيانات وإنشاء النسخة النظيفة بنجاح تام وجاهزة للتوزيع!\n";

<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
