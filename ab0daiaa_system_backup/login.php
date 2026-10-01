<?php
session_start();
$db_file = __DIR__ . '/vmax.db';
$db = new SQLite3($db_file);

// إنشاء جدول المستخدمين وإضافة حساب المدير والمندوب الافتراضيين
$db->exec("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT, password TEXT, role TEXT, name TEXT)");
$check = $db->querySingle("SELECT COUNT(*) FROM users");
if ($check == 0) {
    $db->exec("INSERT INTO users (username, password, role, name) VALUES ('admin', '1234', 'admin', 'مدير النظام (أبو ضياء)')");
    $db->exec("INSERT INTO users (username, password, role, name) VALUES ('sales', '0000', 'sales', 'مندوب مبيعات')");
}

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: ' . ($_SESSION['role'] === 'admin' ? 'dashboard.php' : 'pos.php'));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    $stmt = $db->prepare("SELECT * FROM users WHERE username = :u AND password = :p");
    $stmt->bindValue(':u', $username, SQLITE3_TEXT);
    $stmt->bindValue(':p', $password, SQLITE3_TEXT);
    $user = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    
    if ($user) {
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        header('Location: ' . ($user['role'] === 'admin' ? 'dashboard.php' : 'pos.php'));
        exit;
    } else {
        $error = '❌ اسم المستخدم أو كلمة المرور غير صحيحة!';
    }
}
$db->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><meta name='mobile-web-app-capable' content='yes'>
<meta name='apple-mobile-web-app-capable' content='yes'>

    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'><title>تسجيل الدخول - أبوضياء</title>
    <style>
        body { font-family: 'Tahoma', sans-serif; background: #0f172a; margin: 0; display: flex; justify-content: center; align-items: center; height: 100vh; color: #fff; }
        .login-box { background: #1e293b; padding: 30px; border-radius: 12px; width: 90%; max-width: 350px; box-shadow: 0 15px 40px rgba(0,0,0,0.5); text-align: center; border: 1px solid #38bdf8; }
        .login-box h2 { margin-top: 0; color: #38bdf8; font-size: 20px; border-bottom: 2px solid #fbbf24; padding-bottom: 10px; }
        .form-control { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #64748b; border-radius: 8px; font-size: 15px; background: #0f172a; color: #fff; outline: none; box-sizing: border-box; text-align: center; font-weight:bold; }
        .form-control:focus { border-color: #fbbf24; }
        .btn { width: 100%; padding: 12px; background: #2563eb; color: #fff; border: none; border-radius: 8px; font-weight: bold; font-size: 16px; cursor: pointer; margin-bottom: 15px; }
        .error { color: #fca5a5; font-size: 12px; margin-bottom: 15px; font-weight: bold; }
        .hint { font-size: 10px; color: #94a3b8; text-align: right; background: #0f172a; padding: 10px; border-radius: 6px; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>💎 نظام الإمبراطورية العالمي</h2>
        <?php if($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>
        <form method="POST">
            <input type="text" name="username" class="form-control" placeholder="اسم المستخدم" required autofocus autocomplete="off">
            <input type="password" name="password" class="form-control" placeholder="كلمة المرور" required autocomplete="off">
            <button type="submit" class="btn">تسجيل الدخول 🚀</button>
        </form>
        <div class="hint">
            <strong>حساب المدير:</strong> admin | <strong>الرقم:</strong> 1234<br>
            <strong>حساب المندوب:</strong> sales | <strong>الرقم:</strong> 0000
        </div>
    </div>
<?php if(file_exists('nav_menu.php')) { include 'nav_menu.php'; } ?>
</body>
</html>
