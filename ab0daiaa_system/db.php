<?php
$db = new SQLite3('/data/data/com.termux/files/home/secure_dbs/vmax.db');
@$db->exec("CREATE TABLE IF NOT EXISTS customers (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, phone TEXT, debt REAL DEFAULT 0)");
@$db->exec("CREATE TABLE IF NOT EXISTS products (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, price REAL, qty INTEGER, cost REAL DEFAULT 0)");
@$db->exec("CREATE TABLE IF NOT EXISTS invoices (id INTEGER PRIMARY KEY AUTOINCREMENT, customer_id INTEGER, total REAL, discount REAL, grand_total REAL, paid_amount REAL DEFAULT 0, payment_status TEXT DEFAULT 'unpaid', date TEXT)");
@$db->exec("CREATE TABLE IF NOT EXISTS invoice_items (id INTEGER PRIMARY KEY AUTOINCREMENT, invoice_id INTEGER, product_id INTEGER, product_name TEXT, qty INTEGER, price REAL, total REAL, unit TEXT DEFAULT '', free_qty INTEGER DEFAULT 0)");
@$db->exec("CREATE TABLE IF NOT EXISTS account_payments (id INTEGER PRIMARY KEY AUTOINCREMENT, customer_id INTEGER, amount REAL, type TEXT, note TEXT, date TEXT)");
@$db->exec("CREATE TABLE IF NOT EXISTS vouchers (id INTEGER PRIMARY KEY AUTOINCREMENT, type TEXT, amount REAL, date TEXT)");
?>
