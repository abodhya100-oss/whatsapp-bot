<?php
if(session_status()===PHP_SESSION_NONE){session_start();} if(!isset($_SESSION["user_role"])){header("Location: login.php");exit;}
$db = new SQLite3('vmax.db');

// إنشاء الجدول الأساسي للزبائن
$db->exec("CREATE TABLE IF NOT EXISTS customers (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, phone TEXT, balance REAL DEFAULT 0);");

// إنشاء الجدول الأساسي للمنتجات والأصناف
$db->exec("CREATE TABLE IF NOT EXISTS products (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, sell_price REAL, stock INTEGER, barcode TEXT);");

// إنشاء جدول الفواتير مع كافة الأعمدة المطلوبة
$db->exec("CREATE TABLE IF NOT EXISTS invoices (
    id INTEGER PRIMARY KEY AUTOINCREMENT, 
    customer_id INTEGER, 
    total REAL, 
    date TEXT, 
    payment_method TEXT, 
    discount REAL, 
    service_fee REAL, 
    amount_received REAL, 
    change_amount REAL, 
    imei TEXT
);");

// إنشاء جدول الصيانة
$db->exec("CREATE TABLE IF NOT EXISTS repairs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_name TEXT,
    phone TEXT,
    device_model TEXT,
    imei TEXT,
    issue TEXT,
    cost REAL,
    status TEXT,
    date TEXT
);");

// إنشاء جدول الموردين
$db->exec("CREATE TABLE IF NOT EXISTS suppliers (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, phone TEXT, address TEXT);");

// إنشاء جدول المشتريات
$db->exec("CREATE TABLE IF NOT EXISTS purchases (id INTEGER PRIMARY KEY AUTOINCREMENT, supplier_id INTEGER, total REAL, date TEXT);");
$db->exec("CREATE TABLE IF NOT EXISTS purchase_items (id INTEGER PRIMARY KEY AUTOINCREMENT, purchase_id INTEGER, product_name TEXT, qty INTEGER, cost REAL);");

// إضافة الأصناف الأساسية الافتراضية إذا كانت فارغة
$count = $db->querySingle("SELECT COUNT(*) FROM products");
if ($count == 0) {
    $db->exec("INSERT INTO products (name, sell_price, stock) VALUES ('ريلاكس أحمر', 500, 100);");
    $db->exec("INSERT INTO products (name, sell_price, stock) VALUES ('ريلاكس أصفر', 500, 100);");
    $db->exec("INSERT INTO products (name, sell_price, stock) VALUES ('جامبو رنجو', 500, 100);");
    $db->exec("INSERT INTO products (name, sell_price, stock) VALUES ('حار مولع', 500, 100);");
    $db->exec("INSERT INTO products (name, sell_price, stock) VALUES ('شراب غوار', 300, 100);");
}
?>

<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
