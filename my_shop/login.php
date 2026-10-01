<?php
session_start();
$db = new SQLite3('vmax.db');

// إنشاء جدول المستخدمين وإضافة مدير افتراضي إذا لم يوجد
$db->exec("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT, password TEXT, role TEXT, name TEXT)");
$check_admin = $db->querySingle("SELECT COUNT(*) FROM users");
if ($check_admin == 0) {
    $db->exec("INSERT INTO users (username, password, role, name) VALUES ('admin', '1234', 'admin', 'أبو ضياء')");
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = SQLite3::escapeString(trim($_POST['username']));
    $password = SQLite3::escapeString(trim($_POST['password']));
    
    $user = $db->querySingle("SELECT * FROM users WHERE username = '$username' AND password = '$password'", true);
    if ($user) {
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['username'] = $user['name'];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "⚠️ بيانات الدخول غير صحيحة!";
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <link rel="manifest" href="manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js");}</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دخول - أبو ضياء</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 20px; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .login-box { background: linear-gradient(135deg, rgba(30, 41, 59, 0.95), rgba(15, 23, 42, 0.98)); border: 2px solid #38bdf8; border-radius: 16px; padding: 30px; width: 100%; max-width: 350px; text-align: center; box-shadow: 0 15px 35px rgba(0,0,0,0.5); }
        .title { font-size: 24px; font-weight: 900; color: #38bdf8; margin-bottom: 5px; }
        .subtitle { font-size: 12px; color: #94a3b8; margin-bottom: 25px; }
        .form-control { width: 100%; padding: 12px; background: #1e293b; color: #fff; border: 1px solid rgba(56, 189, 248, 0.4); border-radius: 8px; margin-bottom: 15px; box-sizing: border-box; text-align: center; font-size: 16px; font-weight: bold; }
        .btn-submit { background: linear-gradient(135deg, #0ea5e9, #0284c7); color: white; padding: 12px; border: none; width: 100%; font-weight: 900; font-size: 16px; border-radius: 8px; cursor: pointer; transition: 0.3s; }
        .btn-submit:active { transform: scale(0.95); }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="title">أبو ضياء 💎</div>
        <div class="subtitle">نظام الإدارة المحاسبية | أبو ضياء</div>
        
        <?php if($error) echo "<div style='color:#ef4444; font-weight:bold; margin-bottom:15px;'>$error</div>"; ?>
        
        <form method="POST">
            <input type="text" name="username" class="form-control" placeholder="اسم المستخدم" required autocomplete="off">
            <input type="password" name="password" class="form-control" placeholder="كلمة المرور" required>
            <button type="submit" class="btn-submit">تسجيل الدخول 🚀</button>
        </form>
    </div>
<?php if(!defined("FOOTER_SHOWN")) { define("FOOTER_SHOWN", true); include "global_footer.php"; } ?>
</body>
</html>

